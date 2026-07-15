/**
 * FaceEngine — SCRFD detection + ArcFace embeddings on ONNX Runtime Web.
 *
 * This is the module that replaced face-api.js. Everything the two capture UIs
 * (attendance portal, HR enrolment) need from a face stack comes through here:
 *
 *   FaceEngine.init(cfg)      — load both ONNX sessions and warm them up
 *   FaceEngine.detect(src, o) — SCRFD-500M: boxes + scores + 5 landmarks
 *   FaceEngine.embed(src, d)  — align on the landmarks, run ArcFace, get a
 *                               512-float L2-normalised embedding
 *   FaceEngine.yawOf(lm)      — signed head-turn ratio from the 5 landmarks
 *
 * Models (vendored under /models/arcface, no CDN — this HRIS must work on the
 * LGU LAN with no internet):
 *
 *   det_500m.onnx   SCRFD-500M-KPS. Fully convolutional, so the same model
 *                   serves the cheap preview loop (320px) and the quality
 *                   capture pass (640px), the way TinyFaceDetector used to be
 *                   run at two input sizes.
 *   w600k_mbf.onnx  MobileFaceNet trained with the ArcFace loss on
 *                   WebFace600K. 112×112 aligned crop in, 512 floats out.
 *
 * Execution: WebGPU when the browser has it, WASM (SIMD) otherwise. The wasm
 * binaries sit next to ort.all.min.js under /js/onnx.
 *
 * The maths (Umeyama alignment, SCRFD decode, NMS) is exposed on
 * FaceEngine._math and the file is loadable in Node, so the arithmetic is
 * testable without a camera. Keep it that way: this is the code that decides
 * whether an embedding is derived from a correctly aligned face, and a silent
 * regression here degrades every match in the building.
 */
(function (root) {
    'use strict';

    // ------------------------------------------------------------- constants

    /**
     * The canonical ArcFace destination landmarks for a 112×112 crop:
     * left eye, right eye, nose tip, left mouth corner, right mouth corner.
     * Every ArcFace-family model is trained on faces warped to these points;
     * aligning to anything else silently ruins the embedding.
     */
    var ARCFACE_TEMPLATE = [
        [38.2946, 51.6963],
        [73.5318, 51.5014],
        [56.0252, 71.7366],
        [41.5493, 92.3655],
        [70.7299, 92.2041],
    ];

    var REC_SIZE = 112;
    var STRIDES  = [8, 16, 32];  // SCRFD feature-pyramid strides, 2 anchors each
    var ANCHORS  = 2;

    // ------------------------------------------------------------- pure math

    function l2normalize(v) {
        var sum = 0;
        for (var i = 0; i < v.length; i++) sum += v[i] * v[i];
        var norm = Math.sqrt(Math.max(sum, 1e-12));
        var out = new Float32Array(v.length);
        for (var j = 0; j < v.length; j++) out[j] = v[j] / norm;
        return out;
    }

    /**
     * SVD of a 2×2 matrix [[a,b],[c,d]] via the closed rotation-scale-rotation
     * form. Returns {U, S, Vt} with S = [s1, s2], s1 >= s2 >= 0, M = U·diag(S)·Vt.
     */
    function svd2x2(a, b, c, d) {
        var E = (a + d) / 2, F = (a - d) / 2, G = (c + b) / 2, H = (c - b) / 2;
        var Q = Math.hypot(E, H), R = Math.hypot(F, G);
        var s1 = Q + R, s2 = Q - R;

        var a1 = Math.atan2(G, F), a2 = Math.atan2(H, E);
        var theta = (a2 - a1) / 2, phi = (a2 + a1) / 2;

        var U  = [[Math.cos(phi), -Math.sin(phi)], [Math.sin(phi), Math.cos(phi)]];
        var Vt = [[Math.cos(theta), -Math.sin(theta)], [Math.sin(theta), Math.cos(theta)]];

        // A negative s2 means a reflection is hiding in the rotation pair; fold
        // the sign into Vt so the singular values are what SVD promises.
        if (s2 < 0) {
            s2 = -s2;
            Vt[1][0] = -Vt[1][0];
            Vt[1][1] = -Vt[1][1];
        }

        return { U: U, S: [s1, s2], Vt: Vt };
    }

    /**
     * Umeyama similarity transform (scale + rotation + translation, no shear)
     * mapping src points onto dst points, least-squares. Returns the 2×3 affine
     * [[m00,m01,m02],[m10,m11,m12]] such that dst ≈ M·src.
     *
     * This is skimage.transform.SimilarityTransform's estimator — the one
     * insightface's norm_crop uses — reimplemented for five 2D points.
     */
    function umeyama(src, dst) {
        var n = src.length;
        var i;

        var srcMean = [0, 0], dstMean = [0, 0];
        for (i = 0; i < n; i++) {
            srcMean[0] += src[i][0] / n; srcMean[1] += src[i][1] / n;
            dstMean[0] += dst[i][0] / n; dstMean[1] += dst[i][1] / n;
        }

        // Covariance A = Σ (dst_c · src_cᵀ) / n, and the source variance.
        var a00 = 0, a01 = 0, a10 = 0, a11 = 0, srcVar = 0;
        for (i = 0; i < n; i++) {
            var sx = src[i][0] - srcMean[0], sy = src[i][1] - srcMean[1];
            var dx = dst[i][0] - dstMean[0], dy = dst[i][1] - dstMean[1];
            a00 += dx * sx / n; a01 += dx * sy / n;
            a10 += dy * sx / n; a11 += dy * sy / n;
            srcVar += (sx * sx + sy * sy) / n;
        }

        var svd = svd2x2(a00, a01, a10, a11);
        var U = svd.U, S = svd.S, Vt = svd.Vt;

        // d guards against the solution flipping the face into its mirror image.
        var detA = a00 * a11 - a01 * a10;
        var detU = U[0][0] * U[1][1] - U[0][1] * U[1][0];
        var detV = Vt[0][0] * Vt[1][1] - Vt[0][1] * Vt[1][0];

        var d1 = 1, d2 = (detA < 0 || (detA === 0 && detU * detV < 0)) ? -1 : 1;

        // R = U · diag(d) · Vt
        var R = [
            [U[0][0] * d1 * Vt[0][0] + U[0][1] * d2 * Vt[1][0], U[0][0] * d1 * Vt[0][1] + U[0][1] * d2 * Vt[1][1]],
            [U[1][0] * d1 * Vt[0][0] + U[1][1] * d2 * Vt[1][0], U[1][0] * d1 * Vt[0][1] + U[1][1] * d2 * Vt[1][1]],
        ];

        var scale = (S[0] * d1 + S[1] * d2) / Math.max(srcVar, 1e-12);

        var tx = dstMean[0] - scale * (R[0][0] * srcMean[0] + R[0][1] * srcMean[1]);
        var ty = dstMean[1] - scale * (R[1][0] * srcMean[0] + R[1][1] * srcMean[1]);

        return [
            [scale * R[0][0], scale * R[0][1], tx],
            [scale * R[1][0], scale * R[1][1], ty],
        ];
    }

    /** Standard IoU non-maximum suppression, greedy on descending score. */
    function nms(dets, iouThreshold) {
        var order = dets.map(function (_, i) { return i; })
            .sort(function (a, b) { return dets[b].score - dets[a].score; });

        var keep = [];

        while (order.length) {
            var best = order.shift();
            keep.push(dets[best]);

            order = order.filter(function (i) {
                var a = dets[best].box, b = dets[i].box;
                var x1 = Math.max(a.x, b.x), y1 = Math.max(a.y, b.y);
                var x2 = Math.min(a.x + a.width, b.x + b.width);
                var y2 = Math.min(a.y + a.height, b.y + b.height);
                var inter = Math.max(0, x2 - x1) * Math.max(0, y2 - y1);
                var union = a.width * a.height + b.width * b.height - inter;
                return union <= 0 || inter / union <= iouThreshold;
            });
        }

        return keep;
    }

    /**
     * Decode SCRFD's nine output tensors into detections, in input-square
     * pixels. Outputs are matched to their meaning structurally — column count
     * says what it is (1 score, 4 box, 10 kps), row count says which stride —
     * so the graph's output ordering and naming never matter.
     *
     * `outputs` is a list of {dims, data}; `size` is the square input size the
     * tensors were produced from.
     */
    function decodeScrfd(outputs, size, scoreThreshold) {
        var byKey = {};

        outputs.forEach(function (out) {
            var dims = out.dims;
            var rows = dims.length === 3 ? dims[1] : dims[0];
            var cols = dims.length === 3 ? dims[2] : dims[1];

            STRIDES.forEach(function (stride) {
                var cells = Math.ceil(size / stride);
                if (rows === cells * cells * ANCHORS) {
                    byKey[stride + ':' + cols] = out.data;
                }
            });
        });

        var dets = [];

        STRIDES.forEach(function (stride) {
            var scores = byKey[stride + ':1'];
            var boxes  = byKey[stride + ':4'];
            var kps    = byKey[stride + ':10'];

            if (!scores || !boxes) return;

            var w = Math.ceil(size / stride);

            for (var r = 0; r < scores.length; r++) {
                if (scores[r] < scoreThreshold) continue;

                var cell = Math.floor(r / ANCHORS);
                var cx = (cell % w) * stride;
                var cy = Math.floor(cell / w) * stride;

                var x1 = cx - boxes[r * 4]     * stride;
                var y1 = cy - boxes[r * 4 + 1] * stride;
                var x2 = cx + boxes[r * 4 + 2] * stride;
                var y2 = cy + boxes[r * 4 + 3] * stride;

                var det = {
                    score: scores[r],
                    box: { x: x1, y: y1, width: x2 - x1, height: y2 - y1 },
                    kps: [],
                };

                if (kps) {
                    for (var k = 0; k < 5; k++) {
                        det.kps.push([
                            cx + kps[r * 10 + k * 2]     * stride,
                            cy + kps[r * 10 + k * 2 + 1] * stride,
                        ]);
                    }
                }

                dets.push(det);
            }
        });

        return nms(dets, 0.4);
    }

    // --------------------------------------------------------------- browser

    var state = {
        ready: false,
        det: null,       // detection session
        rec: null,       // recognition session
        detInput: null,  // input tensor name of the detection graph
        recInput: null,
        provider: null,  // 'webgpu' | 'wasm', for the console line
    };

    var work = null, workCtx = null;   // detector input canvas
    var crop = null, cropCtx = null;   // 112×112 aligned face crop

    function canvases() {
        if (!work) {
            work = document.createElement('canvas');
            workCtx = work.getContext('2d', { willReadFrequently: true });
            crop = document.createElement('canvas');
            crop.width = crop.height = REC_SIZE;
            cropCtx = crop.getContext('2d', { willReadFrequently: true });
        }
    }

    function srcSize(source) {
        return {
            w: source.videoWidth || source.width,
            h: source.videoHeight || source.height,
        };
    }

    /** RGBA ImageData → NCHW float32 RGB, (x - mean) / std. */
    function toTensor(imageData, mean, std) {
        var px = imageData.data;
        var area = imageData.width * imageData.height;
        var data = new Float32Array(3 * area);

        for (var i = 0; i < area; i++) {
            data[i]            = (px[i * 4]     - mean) / std;  // R plane
            data[area + i]     = (px[i * 4 + 1] - mean) / std;  // G plane
            data[2 * area + i] = (px[i * 4 + 2] - mean) / std;  // B plane
        }

        return data;
    }

    async function createSession(url) {
        var ort = root.ort;

        // WebGPU first — it is dramatically faster where it exists — falling
        // back to single-threaded SIMD WASM, which every current browser runs.
        // (Multi-threaded WASM needs cross-origin isolation headers this app
        // does not serve, so it is not attempted.)
        try {
            var s = await ort.InferenceSession.create(url, {
                executionProviders: ['webgpu'],
                graphOptimizationLevel: 'all',
            });
            state.provider = state.provider || 'webgpu';
            return s;
        } catch (e) {
            var s2 = await ort.InferenceSession.create(url, {
                executionProviders: ['wasm'],
                graphOptimizationLevel: 'all',
            });
            state.provider = state.provider || 'wasm';
            return s2;
        }
    }

    /**
     * Load both models and run one inference each, so the first real frame
     * pays no compile/allocation stall while somebody stands at the camera.
     */
    async function init(cfg) {
        if (state.ready) return;

        var ort = root.ort;

        ort.env.wasm.wasmPaths = cfg.ortPath;   // where the .wasm binaries live
        ort.env.wasm.numThreads = 1;            // no COOP/COEP → no threads

        state.det = await createSession(cfg.modelsUrl + '/det_500m.onnx');
        state.rec = await createSession(cfg.modelsUrl + '/w600k_mbf.onnx');

        state.detInput = state.det.inputNames[0];
        state.recInput = state.rec.inputNames[0];

        // Warmup on zeros.
        var d = new ort.Tensor('float32', new Float32Array(3 * 320 * 320), [1, 3, 320, 320]);
        await state.det.run(inputFor(state.detInput, d));

        var r = new ort.Tensor('float32', new Float32Array(3 * REC_SIZE * REC_SIZE), [1, 3, REC_SIZE, REC_SIZE]);
        await state.rec.run(inputFor(state.recInput, r));

        state.ready = true;
    }

    function inputFor(name, tensor) {
        var feeds = {};
        feeds[name] = tensor;
        return feeds;
    }

    /**
     * Detect faces. Returns [{score, box, landmarks}] in source-frame pixels,
     * sorted by score. `landmarks` carries the five aligned-crop anchor points
     * plus named accessors the gate logic reads.
     *
     * options.size — square input fed to SCRFD. 320 is the preview loop
     * (smooth on weak hardware), 640 the capture pass, echoing the old
     * cheap/full TinyFaceDetector split.
     */
    async function detect(source, options) {
        var ort  = root.ort;
        var size = (options && options.size) || 320;
        var thr  = (options && options.scoreThreshold) || 0.5;

        canvases();

        var s = srcSize(source);
        if (!s.w || !s.h) return [];

        // Letterbox into the square, top-left anchored, like insightface does:
        // one uniform scale, so mapping back is a single division.
        var scale = size / Math.max(s.w, s.h);

        work.width = work.height = size;
        workCtx.fillStyle = '#000';
        workCtx.fillRect(0, 0, size, size);
        workCtx.drawImage(source, 0, 0, s.w * scale, s.h * scale);

        var tensorData = toTensor(workCtx.getImageData(0, 0, size, size), 127.5, 128);
        var tensor = new ort.Tensor('float32', tensorData, [1, 3, size, size]);

        var results = await state.det.run(inputFor(state.detInput, tensor));

        var outputs = Object.keys(results).map(function (k) { return results[k]; });
        var dets = decodeScrfd(outputs, size, thr);

        return dets.map(function (det) {
            var kps = det.kps.map(function (p) { return [p[0] / scale, p[1] / scale]; });

            return {
                score: det.score,
                box: {
                    x: det.box.x / scale,
                    y: det.box.y / scale,
                    width: det.box.width / scale,
                    height: det.box.height / scale,
                },
                landmarks: {
                    points:     kps,
                    leftEye:    { x: kps[0][0], y: kps[0][1] },
                    rightEye:   { x: kps[1][0], y: kps[1][1] },
                    nose:       { x: kps[2][0], y: kps[2][1] },
                    mouthLeft:  { x: kps[3][0], y: kps[3][1] },
                    mouthRight: { x: kps[4][0], y: kps[4][1] },
                },
            };
        }).sort(function (a, b) { return b.score - a.score; });
    }

    /**
     * ArcFace embedding for one detection: warp the source frame so the five
     * landmarks land on the canonical template, run the recognition net,
     * L2-normalise. Returns Float32Array(512).
     */
    async function embed(source, detection) {
        var ort = root.ort;

        canvases();

        var M = umeyama(detection.landmarks.points, ARCFACE_TEMPLATE);

        cropCtx.setTransform(M[0][0], M[1][0], M[0][1], M[1][1], M[0][2], M[1][2]);
        cropCtx.fillStyle = '#000';
        cropCtx.fillRect(-1e4, -1e4, 2e4, 2e4);
        cropCtx.drawImage(source, 0, 0);
        cropCtx.setTransform(1, 0, 0, 1, 0, 0);

        var tensorData = toTensor(cropCtx.getImageData(0, 0, REC_SIZE, REC_SIZE), 127.5, 127.5);
        var tensor = new ort.Tensor('float32', tensorData, [1, 3, REC_SIZE, REC_SIZE]);

        var results = await state.rec.run(inputFor(state.recInput, tensor));
        var out = results[state.rec.outputNames[0]].data;

        return l2normalize(out);
    }

    /**
     * Signed head-turn ratio from the five landmarks: how far the nose tip sits
     * from the eye midpoint, in units of interocular distance. ~0 facing the
     * camera; negative when the subject turns toward their own left (matching
     * the sign convention the old 68-landmark yaw used, so config/face.php's
     * yaw_invert keeps its meaning).
     */
    function yawOf(landmarks) {
        var L = landmarks.leftEye, R = landmarks.rightEye, N = landmarks.nose;

        var inter = Math.hypot(R.x - L.x, R.y - L.y);
        if (inter <= 0) return 0;

        var midX = (L.x + R.x) / 2;

        return (N.x - midX) / inter;
    }

    var api = {
        init: init,
        detect: detect,
        embed: embed,
        yawOf: yawOf,
        get ready() { return state.ready; },
        get provider() { return state.provider; },
        _math: {
            umeyama: umeyama,
            svd2x2: svd2x2,
            nms: nms,
            decodeScrfd: decodeScrfd,
            l2normalize: l2normalize,
            ARCFACE_TEMPLATE: ARCFACE_TEMPLATE,
        },
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;   // Node — used by the math tests only
    }

    root.FaceEngine = api;
})(typeof self !== 'undefined' ? self : globalThis);
