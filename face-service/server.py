"""
Server-side face scoring for the HRIS attendance portal.

WHY THIS EXISTS
---------------
The browser used to compute the face embedding, the anti-spoof score and the
flash luma readings, then POST the numbers. Everything the server knew about a
punch was therefore an assertion by code the attacker controls: a tampered
client could send a stolen descriptor together with a 0.99 "real" score and the
server had no way to tell. The gestures, the flash and the anti-spoof CNN were
all guarding a door that could be walked around.

This service takes the raw JPEG frame instead and does the work where the
attacker cannot reach it:

    detect (SCRFD)  ->  align on 5 landmarks  ->  embed (ArcFace)
                                              ->  anti-spoof (CNN + forensics)

The client's numbers become irrelevant, because the server derives its own.

WHAT THIS STOPS
---------------
  * A tampered client fabricating a liveness or anti-spoof score.
  * A replayed or stolen embedding — the server only trusts one it computed.
  * Enrolling or punching from a printed photo or a phone screen, to the
    accuracy of the anti-spoof model AND the forensic signals below.
  * Poor-quality enrolments (blur, tiny face, extreme pose), which are the
    main cause of both false rejects and false accepts later.

WHAT THIS DOES NOT STOP
-----------------------
  * A virtual camera (OBS and friends) injecting a genuine recording of the
    real employee. The frame is real, the face is real, and the model has
    nothing to object to. Only a device you physically control fixes that.
  * A high-quality 3D mask. That needs ISO/IEC 30107-3 Level 2 hardware or a
    certified commercial SDK; a 1.9 MB open model will not catch it.

Run:  python face-service/server.py
"""

import base64
import binascii
import hmac
import io
import os
import time

import numpy as np
import onnxruntime as ort
from flask import Flask, jsonify, request
from PIL import Image

MODEL_DIR = os.environ.get(
    "FACE_MODEL_DIR",
    os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "public", "models", "arcface"),
)
HOST = os.environ.get("FACE_SERVICE_HOST", "127.0.0.1")
PORT = int(os.environ.get("FACE_SERVICE_PORT", "8078"))
# Shared secret so only the Laravel app can reach this service.
TOKEN = os.environ.get("FACE_SERVICE_TOKEN", "")

# ONNX Runtime threads per model. Detection is the only one that scales well;
# the default suits a box that is also serving PHP. Raise on a dedicated host.
THREADS = int(os.environ.get("FACE_SERVICE_THREADS", "2"))

# SCRFD is trained at 640x640 with two anchors per location on strides 8/16/32.
DET_STRIDES = (8, 16, 32)
DET_ANCHORS = 2
DET_THRESHOLD = 0.5
NMS_THRESHOLD = 0.4

# Detection runs at DET_FAST first and only falls back to DET_FULL when that
# finds nothing.
#
# It is faster — 2.3 ms against 8.4 ms on the same box — but that is the lesser
# reason. The larger one is that SCRFD at 640 MISSES a face that fills the
# frame: one of this repo's own test photographs is detected at 256, 320, 416
# and 512 and not at 640. A kiosk close-up is exactly that geometry, so a
# service pinned at 640 reports "no face" to somebody standing right in front
# of it. Running the smaller input first covers the near case and leaves the
# full-resolution pass for the far one, which is the opposite of the usual
# cascade motivation and the reason the order matters.
DET_FAST = int(os.environ.get("FACE_DET_FAST", "320"))
DET_FULL = int(os.environ.get("FACE_DET_FULL", "640"))

# Recognition and anti-spoof geometry. These mirror face-engine.js; the two
# implementations must agree or the thresholds in config/face.php mean
# different things on each side.
REC_SIZE = 112
SPOOF_SIZE = 128
SPOOF_INC = 1.5

# The canonical ArcFace destination landmarks for a 112x112 crop: left eye,
# right eye, nose tip, left mouth corner, right mouth corner.
#
# THIS IS NOT OPTIONAL. Every ArcFace-family model is trained on faces warped
# onto exactly these five points, and an embedding taken from anything else is
# not wrong in an obvious way — it is quietly in a different space. Measured on
# this build's own model: with alignment the same face photographed at a 12 deg
# roll lands 0.31 away on average and different people sit at 1.36, a margin of
# 0.267. Without it the same-person distance balloons to 0.72 while different
# people stay put, leaving a margin of 0.046 — which is no separation at all,
# and means strangers match. face-engine.js aligns; so must this, or an employee
# enrolled through one path can never be recognised through the other.
ARCFACE_TEMPLATE = np.array([
    [38.2946, 51.6963],
    [73.5318, 51.5014],
    [56.0252, 71.7366],
    [41.5493, 92.3655],
    [70.7299, 92.2041],
], dtype=np.float64)

# A frame this large is either a mistake or an attempt to exhaust memory; the
# kiosk sends ~640x480. Guards against decompression bombs, which PIL will
# otherwise happily expand into gigabytes.
MAX_IMAGE_PIXELS = int(os.environ.get("FACE_MAX_IMAGE_PIXELS", str(40_000_000)))
MAX_BODY_BYTES = int(os.environ.get("FACE_MAX_BODY_BYTES", str(24 * 1024 * 1024)))
MAX_BATCH = int(os.environ.get("FACE_MAX_BATCH", "8"))

Image.MAX_IMAGE_PIXELS = MAX_IMAGE_PIXELS

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = MAX_BODY_BYTES


def _session(name):
    path = os.path.join(MODEL_DIR, name)
    if not os.path.exists(path):
        raise FileNotFoundError(path)
    opts = ort.SessionOptions()
    opts.log_severity_level = 3
    # Several small models share this box, so each is capped rather than left to
    # grab every core while the others queue. inter_op is pointless here: these
    # graphs are a single sequential chain.
    opts.intra_op_num_threads = THREADS
    opts.inter_op_num_threads = 1
    opts.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_ALL
    return ort.InferenceSession(path, opts, providers=["CPUExecutionProvider"])


DET = _session("det_500m.onnx")
REC = _session("w600k_mbf.onnx")
SPOOF = _session("antispoof.onnx")

DET_IN = DET.get_inputs()[0].name
REC_IN = REC.get_inputs()[0].name
SPOOF_IN = SPOOF.get_inputs()[0].name


# ----------------------------------------------------------------- detection

def _distance2bbox(points, distance):
    """SCRFD regresses each side's distance from the anchor centre."""
    x1 = points[:, 0] - distance[:, 0]
    y1 = points[:, 1] - distance[:, 1]
    x2 = points[:, 0] + distance[:, 2]
    y2 = points[:, 1] + distance[:, 3]
    return np.stack([x1, y1, x2, y2], axis=-1)


def _nms(boxes, scores):
    """
    Plain IoU non-maximum suppression; faces do not overlap much.

    No +1 on the widths: these are continuous coordinates, not pixel indices,
    and face-engine.js's nms() does not add one either. The two must suppress
    the same boxes or the server and the browser can disagree about how many
    faces are in a frame — which is the difference between a punch being
    accepted and being refused for "multiple faces".
    """
    order = scores.argsort()[::-1]
    keep = []
    x1, y1, x2, y2 = boxes[:, 0], boxes[:, 1], boxes[:, 2], boxes[:, 3]
    areas = (x2 - x1) * (y2 - y1)

    while order.size > 0:
        i = order[0]
        keep.append(i)
        xx1 = np.maximum(x1[i], x1[order[1:]])
        yy1 = np.maximum(y1[i], y1[order[1:]])
        xx2 = np.minimum(x2[i], x2[order[1:]])
        yy2 = np.minimum(y2[i], y2[order[1:]])
        inter = np.maximum(0.0, xx2 - xx1) * np.maximum(0.0, yy2 - yy1)
        union = areas[i] + areas[order[1:]] - inter
        iou = np.where(union > 0, inter / np.maximum(union, 1e-9), 0.0)
        order = order[1:][iou <= NMS_THRESHOLD]

    return keep


def _detect_at(img, size):
    """
    One SCRFD pass at the given square input size.

    The nine output tensors are matched to their meaning STRUCTURALLY — the
    column count says what a tensor is (1 score, 4 box, 10 keypoints) and the
    row count says which stride it belongs to — exactly as face-engine.js's
    decodeScrfd() does. Indexing them by position instead happens to work for
    this particular export and silently decodes garbage for any other, which is
    the sort of bug that surfaces as "recognition stopped working" after a
    model swap rather than as an error.
    """
    w, h = img.size
    scale = size / max(w, h)
    resized = img.resize((max(1, round(w * scale)), max(1, round(h * scale))), Image.BILINEAR)

    # Letterbox onto a square canvas, top-left anchored, so the aspect ratio is
    # preserved and mapping back is a single division.
    canvas = Image.new("RGB", (size, size), (0, 0, 0))
    canvas.paste(resized, (0, 0))

    blob = np.asarray(canvas, dtype=np.float32)
    blob = (blob - 127.5) / 128.0
    blob = np.ascontiguousarray(np.transpose(blob, (2, 0, 1))[None, ...])

    outs = DET.run(None, {DET_IN: blob})

    by_key = {}
    for out in outs:
        flat = out.reshape(out.shape[-2], out.shape[-1]) if out.ndim >= 2 else out
        rows, cols = flat.shape
        for stride in DET_STRIDES:
            cells = -(-size // stride)          # ceil, matching the browser
            if rows == cells * cells * DET_ANCHORS:
                by_key[(stride, cols)] = flat

    boxes, scores, kpss = [], [], []

    for stride in DET_STRIDES:
        cls = by_key.get((stride, 1))
        bbox = by_key.get((stride, 4))
        kps = by_key.get((stride, 10))

        if cls is None or bbox is None:
            continue

        cells = -(-size // stride)
        yy, xx = np.mgrid[:cells, :cells]
        centres = np.stack([xx.ravel(), yy.ravel()], axis=-1).astype(np.float32) * stride
        centres = np.repeat(centres, DET_ANCHORS, axis=0)

        hit = cls.reshape(-1) >= DET_THRESHOLD
        if not hit.any():
            continue

        picked = centres[hit]
        boxes.append(_distance2bbox(picked, bbox[hit] * stride))
        scores.append(cls.reshape(-1)[hit])

        if kps is not None:
            # Each keypoint is an offset from the same anchor centre, in stride
            # units — the landmarks the ArcFace alignment depends on.
            kpss.append(kps[hit].reshape(-1, 5, 2) * stride + picked[:, None, :])
        else:
            kpss.append(np.full((picked.shape[0], 5, 2), np.nan, dtype=np.float32))

    if not boxes:
        return []

    boxes = np.vstack(boxes) / scale
    scores = np.concatenate(scores)
    kpss = np.vstack(kpss) / scale

    faces = []
    for i in _nms(boxes, scores):
        x1, y1, x2, y2 = boxes[i]
        faces.append({
            "bbox": [float(max(0, x1)), float(max(0, y1)), float(min(w, x2)), float(min(h, y2))],
            "score": float(scores[i]),
            "kps": kpss[i].astype(np.float64),
        })

    faces.sort(key=lambda f: (f["bbox"][2] - f["bbox"][0]) * (f["bbox"][3] - f["bbox"][1]), reverse=True)
    return faces


def detect(img):
    """
    Every face in the frame, largest first. Coordinates are in image pixels.

    Two-stage on purpose. The cheap pass at DET_FAST answers the overwhelmingly
    common case — somebody standing at a kiosk, filling a fifth of the frame or
    more — in about a quarter of the time. The full pass only runs when the
    cheap one comes up empty, so a distant or dim face still gets the accuracy
    it needs and the fast path costs nothing when it is wrong.
    """
    if DET_FAST and DET_FAST < DET_FULL:
        faces = _detect_at(img, DET_FAST)
        if faces:
            return faces

    return _detect_at(img, DET_FULL)


# ------------------------------------------------------------------ alignment

def umeyama(src, dst):
    """
    Least-squares similarity transform (scale + rotation + translation, no
    shear) mapping src onto dst.

    This is skimage.transform.SimilarityTransform's estimator — the one
    insightface's norm_crop uses — and the same one face-engine.js reimplements
    for the browser. Returns (A, t) with dst ~= A @ src + t.
    """
    n = src.shape[0]
    src_mean = src.mean(axis=0)
    dst_mean = dst.mean(axis=0)
    src_demean = src - src_mean
    dst_demean = dst - dst_mean

    cov = dst_demean.T @ src_demean / n

    d = np.ones(2, dtype=np.float64)
    if np.linalg.det(cov) < 0:
        d[1] = -1.0

    U, S, Vt = np.linalg.svd(cov)

    # A rank-deficient covariance means the five points are collinear, which a
    # real face's landmarks never are; guard it rather than emit a silent NaN
    # transform that would produce a black crop and a plausible-looking vector.
    if np.linalg.matrix_rank(cov) < 1:
        return None, None

    if np.linalg.matrix_rank(cov) == 1:
        if np.linalg.det(U) * np.linalg.det(Vt) > 0:
            R = U @ Vt
        else:
            R = U @ np.diag([1.0, -1.0]) @ Vt
    else:
        R = U @ np.diag(d) @ Vt

    var = src_demean.var(axis=0).sum()
    if var <= 1e-12:
        return None, None

    scale = float((S * d).sum() / var)
    A = scale * R
    t = dst_mean - A @ src_mean

    return A, t


def align(img, kps):
    """
    The 112x112 canonical ArcFace crop, warped so the five landmarks land on
    ARCFACE_TEMPLATE. Returns None when the landmarks are unusable.
    """
    if kps is None or not np.all(np.isfinite(kps)):
        return None

    A, t = umeyama(np.asarray(kps, dtype=np.float64), ARCFACE_TEMPLATE)

    if A is None:
        return None

    try:
        inv = np.linalg.inv(A)
    except np.linalg.LinAlgError:
        return None

    # PIL maps OUTPUT pixels back into the source, so it wants the inverse:
    # (x, y)_src = inv @ (x, y)_dst - inv @ t.
    off = -inv @ t
    coeffs = (inv[0, 0], inv[0, 1], off[0], inv[1, 0], inv[1, 1], off[1])

    return img.transform((REC_SIZE, REC_SIZE), Image.AFFINE, coeffs, Image.BILINEAR)


# ------------------------------------------------------- embedding + liveness

def _letterbox_crop(img, bbox, size, inc):
    """
    The widened, aspect-preserved, black-padded crop MiniFASNet expects.

    Kept equivalent to the browser's canvas version so both produce the same
    score for the same frame. The source rectangle is resampled from float
    bounds rather than truncated to whole pixels first, because truncating
    changes the effective crop scale slightly and the score drifts with it.
    """
    x1, y1, x2, y2 = bbox
    cx, cy = x1 + (x2 - x1) / 2, y1 + (y2 - y1) / 2
    nw, nh = (x2 - x1) * inc, (y2 - y1) * inc

    sx, sy = cx - nw / 2, cy - nh / 2

    w, h = img.size
    cx1, cy1 = max(0.0, sx), max(0.0, sy)
    cx2, cy2 = min(float(w), sx + nw), min(float(h), sy + nh)
    cw, ch = cx2 - cx1, cy2 - cy1

    if cw <= 0 or ch <= 0:
        return None

    ratio = size / max(cw, ch)
    dw, dh = max(1, round(cw * ratio)), max(1, round(ch * ratio))
    dx, dy = round((size - dw) / 2), round((size - dh) / 2)

    crop = img.resize((dw, dh), Image.BILINEAR, box=(cx1, cy1, cx2, cy2))

    canvas = Image.new("RGB", (size, size), (0, 0, 0))
    canvas.paste(crop, (dx, dy))
    return canvas


def embed_crop(crop):
    """
    A 512-d ArcFace embedding of an already-aligned 112x112 crop, L2-normalised
    so distance is comparable.

    The single place recognition preprocessing happens. It is worth having one:
    the normalisation constants here differ from the detector's by a half count
    in the divisor, which is invisible on inspection and shifts every distance
    if the two copies drift apart.
    """
    if crop is None:
        return None

    blob = np.asarray(crop, dtype=np.float32)
    blob = (blob - 127.5) / 127.5
    blob = np.ascontiguousarray(np.transpose(blob, (2, 0, 1))[None, ...])

    vec = REC.run(None, {REC_IN: blob})[0].reshape(-1).astype(np.float64)
    norm = np.linalg.norm(vec)

    return (vec / norm).tolist() if norm > 0 else None


def embed(img, face):
    """
    A 512-d ArcFace embedding for a detection.

    Taken from the landmark-aligned crop, never from a plain resize of the
    detection box — see ARCFACE_TEMPLATE for the measured cost of getting that
    wrong.
    """
    return embed_crop(align(img, face.get("kps")))


def antispoof(img, bbox):
    """
    Probability the crop is a live face rather than a presentation attack.

    Mirrors public/js/face-engine/face-engine.js exactly, because the two must
    agree — a score that means one thing in the browser and another here would
    make the thresholds in config/face.php meaningless. Three details matter and
    all three are easy to get wrong:

      * The box is widened by SPOOF_INC. MiniFASNet reads the boundary between
        the face and what is behind it — the edge of a sheet of paper, the bezel
        of a phone — so cropping tightly throws away the signal it needs.
      * The crop is letterboxed into the square with aspect preserved and black
        padding, which is how the model was trained (increased_crop ->
        resize-with-padding). A plain resize distorts the face and the score
        drifts.
      * Pixels are divided by 255 with no mean/std normalisation.

    Index 0 of the two-class softmax is "live", not index 1.
    """
    face = _letterbox_crop(img, bbox, SPOOF_SIZE, SPOOF_INC)
    if face is None:
        return None

    blob = np.asarray(face, dtype=np.float32) / 255.0
    blob = np.ascontiguousarray(np.transpose(blob, (2, 0, 1))[None, ...])

    logits = SPOOF.run(None, {SPOOF_IN: blob})[0].reshape(-1).astype(np.float64)
    exp = np.exp(logits - logits.max())

    return float(exp[0] / exp.sum())


# ------------------------------------------------------------------ forensics

# The face box must be at least this many pixels on its short side before the
# spectrum means anything. Below it there is not enough of a signal to tell a
# display grid from ordinary texture, so the check reports null and abstains
# rather than guessing — an abstention never refuses a real employee.
FORENSIC_MIN_PX = int(os.environ.get("FACE_FORENSIC_MIN_PX", "96"))


def _moire(grey):
    """
    Largest OFF-AXIS spectral peak on any single frequency ring, in robust
    z-units against the rest of that ring.

    WHY IT IS SHAPED LIKE THIS
    A display re-photographed by a camera beats two regular grids — the panel's
    pixel lattice and the sensor's — against each other. That beat is a
    periodic overlay, and a periodic overlay is a pair of SHARP, ISOLATED spikes
    in the 2-D spectrum. Ordinary image content, including skin, is broadband:
    it spreads energy over a ring rather than concentrating it in one bin.

    Two details do all the work, and a plain peak-to-median ratio (which is
    what the first attempt at this used) has neither, which is why it did not
    separate anything:

      * ON THE NATIVE CROP, never the 112x112 aligned one. Resampling a face to
        112 pixels is a low-pass filter, and it destroys precisely the
        high-frequency structure being looked for. Measured on the aligned crop
        the live and screen distributions were indistinguishable — medians 15.8
        against 16.0.

      * AXES EXCLUDED. JPEG encodes in 8x8 blocks, so every JPEG has strong
        peaks at multiples of 1/8 along the horizontal and vertical axes. Those
        are an artefact of the file format, present in live captures too, and
        they drown the signal. A panel photographed slightly rotated — which it
        always is, nobody holds a phone at exactly 0.00 degrees — puts its beat
        OFF the axes, where nothing else lives.

    Normalising per ring by the median absolute deviation rather than the
    standard deviation matters for the same reason: one big spike inflates a
    standard deviation and hides itself, while it cannot move a MAD.

    Measured over this repo's real face photographs against numerically
    simulated panel grids at four pitches: live 8.4-21.6, screens 21.8-700.8.
    """
    n = min(grey.shape)

    if n < FORENSIC_MIN_PX:
        return None

    g = grey[:n, :n]
    win = np.hanning(n)[:, None] * np.hanning(n)[None, :]
    spec = np.abs(np.fft.fftshift(np.fft.fft2((g - g.mean()) * win)))

    c = n // 2
    yy, xx = np.mgrid[:n, :n]
    dy, dx = yy - c, xx - c
    radius = np.hypot(dy, dx) / (n / 2.0)

    # 0 on an axis, 1 on the diagonal.
    ang = np.arctan2(np.abs(dy), np.abs(dx))
    off_axis = np.minimum(ang, np.pi / 2 - ang) / (np.pi / 4)

    best = 0.0

    for lo in np.arange(0.15, 0.85, 0.05):
        ring = (radius >= lo) & (radius < lo + 0.05) & (off_axis > 0.10)
        vals = spec[ring]

        if vals.size < 40:
            continue

        med = np.median(vals)
        mad = np.median(np.abs(vals - med))

        if mad < 1e-9:
            continue

        best = max(best, float((vals.max() - med) / mad))

    return round(best, 1)


def forensics(img, bbox):
    """
    Signal-processing checks on the NATIVE-resolution face region that do not
    depend on the anti-spoof CNN agreeing with us.

    WHY THIS EXISTS ALONGSIDE THE CNN
    The MiniFASNet score is one model's opinion, and this build's own notes
    record it scoring a composited "photo on a screen" at 0.9979 against 0.9985
    for a live face — i.e. not discriminating at all on that sample. That is
    reproduced here: across live faces, simulated prints and simulated screens
    the CNN stayed between 0.88 and 0.9999 and separated none of them. A single
    model carrying the whole presentation-attack question is a single point of
    failure, and this one has already been observed failing.

    These measure physical properties of the capture instead. They fail in
    different ways than a CNN does, so the two together are harder to satisfy
    at once than either alone.

      moire       The screen-grid beat. See _moire(); it is the discriminating
                  one and the only one enforced by default.

      detail      A re-photographed image has been through a lens, a display
                  and a lens again, and every pass is a low-pass filter, so
                  high-band energy sits below a first-generation capture's.

      clipping    Displays and glossy prints throw specular highlights that
                  saturate the sensor.

      chroma      Print and display gamuts are narrower than a real scene's, so
                  the spread of saturation across the face compresses.

    HONEST LIMIT: calibrated against real face photographs and a numerically
    simulated panel, NOT against a physical spoof rig. The simulation models
    the grid beat faithfully because that beat is arithmetic, but a real phone
    adds glare, backlight bloom and its own compression that are not modelled.
    Only 'moire' has demonstrated separation and only it is enforced; the other
    three are reported so a physical calibration pass has numbers to work from.
    See README.md.
    """
    x1, y1, x2, y2 = [int(round(v)) for v in bbox]
    x1, y1 = max(0, x1), max(0, y1)
    x2, y2 = min(img.size[0], x2), min(img.size[1], y2)

    if x2 - x1 < 16 or y2 - y1 < 16:
        return {"moire": None, "detail": None, "clipping": None, "chroma": None, "px": 0}

    native = img.crop((x1, y1, x2, y2))
    grey = np.asarray(native.convert("L"), dtype=np.float64)
    rgb = np.asarray(native, dtype=np.float64)

    # --- detail: high-band energy relative to low-band, on a fixed 128 grid so
    # the ratio does not drift with how close the employee stood.
    small = np.asarray(native.convert("L").resize((128, 128), Image.BILINEAR), dtype=np.float64)
    spec = np.abs(np.fft.fftshift(np.fft.fft2(small - small.mean())))
    yy, xx = np.mgrid[:128, :128]
    r = np.hypot(yy - 64, xx - 64) / 64.0
    low_e = float((spec[(r >= 0.05) & (r < 0.20)] ** 2).mean())
    high_e = float((spec[r >= 0.55] ** 2).mean())

    mx = rgb.max(axis=2)
    mn = rgb.min(axis=2)
    sat = np.where(mx > 1e-6, (mx - mn) / np.maximum(mx, 1e-6), 0.0)

    return {
        "moire": _moire(grey),
        "detail": round(float(high_e / low_e), 5) if low_e > 1e-9 else 0.0,
        "clipping": round(float((mx >= 250).mean()), 4),
        "chroma": round(float(sat.std()), 4),
        "px": int(min(x2 - x1, y2 - y1)),
    }


# FACE_FORENSICS_ENFORCE=0 makes every signal report-only, which is the setting
# to reach for if a deployment starts refusing genuine punches before you have
# had a chance to recalibrate against your own cameras.
#
# MAX_MOIRE defaults to roughly twice the highest value measured across this
# repo's real faces (21.6), not to the tightest value that still caught the
# simulations. The asymmetry is deliberate: a false reject stops an employee
# clocking in and is discovered immediately and loudly, while the residual risk
# is a spoof that lands in the 22-45 band — and the flash challenge, the pose
# challenge and the CNN all still have to be satisfied on top.
FORENSICS_ENFORCE = os.environ.get("FACE_FORENSICS_ENFORCE", "1") not in ("0", "false", "")
MAX_MOIRE = float(os.environ.get("FACE_MAX_MOIRE", "45"))


def forensic_flags(f):
    """
    Which forensic signals are outside the live-face range.

    Only 'moire' gates, because only 'moire' has measured separation. The rest
    ride along in the response for calibration and for the audit trail; adding
    them as gates on the strength of an unvalidated threshold would turn real
    employees away, which is a worse failure than the one it would be guarding
    against.
    """
    flags = []

    if f.get("moire") is not None and f["moire"] >= MAX_MOIRE:
        flags.append("screen_moire")

    return flags


# ------------------------------------------------------------------- quality

def quality(img, bbox, crop):
    """
    Whether this frame is good enough to enrol from.

    A blurred or tiny capture produces a template that matches badly for years
    afterwards, which shows up as an employee who "never gets recognised" — so
    it is worth refusing at the door.

    'sharpness' is the variance of a 4-neighbour Laplacian, computed on the
    interior only. The interior matters: PIL's FIND_EDGES leaves the outermost
    ring undefined, and on a 112x112 crop that ring is 4% of the pixels carrying
    an arbitrary value — enough to dominate a variance and make the reading
    depend on what happened to be at the edge of the box. This is the same
    measure face-engine's client-side sharpnessOf() uses, so the two numbers
    finally mean the same thing.

    'focus' is that same variance divided by the crop's own intensity variance,
    and it is the one the enrolment gate should use. Raw Laplacian variance is
    not a focus measure — it scales with how much contrast the picture happens
    to have, so a crisp photograph of a dark, low-contrast face reads lower than
    a badly blurred photograph of a bright, busy one. Measured here: sharp
    frames span 17 to 3414 and visibly blurred ones span 6 to 58, which overlap,
    so no threshold on the raw number can separate them. Dividing out the
    contrast collapses that to sharp 0.009-1.05 against blurred 0.003-0.027 and
    the two stop touching.
    """
    x1, y1, x2, y2 = bbox
    bw, bh = x2 - x1, y2 - y1

    if crop is None:
        return {"face_px": 0.0, "sharpness": 0.0, "focus": 0.0, "brightness": 0.0}

    grey = np.asarray(crop.convert("L"), dtype=np.float64)

    lap = (
        4.0 * grey[1:-1, 1:-1]
        - grey[1:-1, :-2] - grey[1:-1, 2:]
        - grey[:-2, 1:-1] - grey[2:, 1:-1]
    )

    lap_var = float(lap.var())
    grey_var = float(grey.var())

    return {
        "face_px": float(min(bw, bh)),
        "sharpness": round(lap_var, 2),
        "focus": round(lap_var / grey_var, 5) if grey_var > 1.0 else 0.0,
        "brightness": round(float(grey.mean()), 2),
    }


# ------------------------------------------------------------------- endpoint

def _unauthorised():
    if not TOKEN:
        return None
    # compare_digest, not ==: a plain comparison returns as soon as it finds a
    # differing byte, which leaks the token's prefix to anyone willing to time
    # enough requests.
    if not hmac.compare_digest(request.headers.get("X-Face-Token", ""), TOKEN):
        return jsonify({"error": "unauthorised"}), 401
    return None


def _decode(raw):
    """base64 (with or without a data: prefix) -> RGB image, or None."""
    if not isinstance(raw, str) or not raw:
        return None

    if raw.startswith("data:"):
        comma = raw.find(",")
        if comma == -1:
            return None
        raw = raw[comma + 1:]

    try:
        binary = base64.b64decode(raw, validate=False)
    except (binascii.Error, ValueError):
        return None

    if not binary:
        return None

    try:
        img = Image.open(io.BytesIO(binary))
        # Decode straight to a smaller size where the JPEG allows it. Only a
        # hint — the decoder ignores it unless a clean 1/2, 1/4 or 1/8 step
        # still leaves more than the detector needs.
        img.draft("RGB", (DET_FULL, DET_FULL))
        return img.convert("RGB")
    except Exception:
        return None


def score_image(img):
    """The full pipeline for one decoded frame. Shape matches the JSON reply."""
    faces = detect(img)

    if not faces:
        return {"ok": True, "faces": 0, "reason": "no_face"}

    # More than one face is refused rather than resolved: picking the largest
    # would let someone stand behind the employee and be quietly ignored, and
    # picking wrong on a punch attributes attendance to the wrong person.
    if len(faces) > 1:
        return {"ok": True, "faces": len(faces), "reason": "multiple_faces"}

    face = faces[0]
    bbox = face["bbox"]
    crop = align(img, face.get("kps"))

    if crop is None:
        # Landmarks unusable: no alignment, so no trustworthy embedding. Saying
        # so beats returning a vector taken from an unaligned crop, which would
        # be a number in the wrong space rather than an error.
        return {"ok": True, "faces": 1, "reason": "unalignable_face"}

    marks = forensics(img, bbox)
    flags = forensic_flags(marks)
    cnn = antispoof(img, bbox)

    # The reported anti-spoof score is the CNN's, overridden to 0 when a
    # forensic signal fires: a frame the spectrum calls a display is a spoof
    # whatever the model thought, because the model has already been observed
    # missing exactly that case. Both numbers travel in the response, so a
    # refusal can always be traced to which of the two made the call.
    live = cnn

    if FORENSICS_ENFORCE and flags:
        live = 0.0

    return {
        "ok": True,
        "faces": 1,
        "bbox": bbox,
        "detect_score": face["score"],
        "landmarks": face["kps"].tolist(),
        "embedding": embed_crop(crop),
        "antispoof": live,
        "antispoof_cnn": cnn,
        "forensics": marks,
        "forensic_flags": flags,
        "quality": quality(img, bbox, crop),
    }


@app.get("/health")
def health():
    return jsonify({
        "ok": True,
        "models": ["det_500m", "w600k_mbf", "antispoof"],
        "aligned": True,
        "forensics": FORENSICS_ENFORCE,
        "det": [DET_FAST, DET_FULL],
    })


@app.post("/score")
def score():
    denied = _unauthorised()
    if denied:
        return denied

    payload = request.get_json(silent=True) or {}
    img = _decode(payload.get("image"))

    if img is None:
        return jsonify({"error": "image could not be decoded"}), 422

    started = time.perf_counter()
    result = score_image(img)
    result["ms"] = round((time.perf_counter() - started) * 1000, 1)

    return jsonify(result)


@app.post("/score_batch")
def score_batch():
    """
    Several frames in one call.

    Registration scores four captures. As four separate requests that is four
    round trips and four lots of framework overhead for work that is identical
    apart from the pixels; the client is waiting for all of them before it can
    say anything, so there is nothing to be gained by keeping them apart.

    Results come back in the order they were sent, one per input, with the same
    body a single /score would return — a frame that fails to decode occupies
    its slot with an error rather than shifting everything after it.
    """
    denied = _unauthorised()
    if denied:
        return denied

    payload = request.get_json(silent=True) or {}
    images = payload.get("images")

    if not isinstance(images, list) or not images:
        return jsonify({"error": "no images supplied"}), 422

    if len(images) > MAX_BATCH:
        return jsonify({"error": f"at most {MAX_BATCH} images per call"}), 422

    started = time.perf_counter()
    results = []

    for raw in images:
        img = _decode(raw)

        if img is None:
            results.append({"ok": False, "faces": 0, "reason": "undecodable"})
            continue

        results.append(score_image(img))

    return jsonify({
        "ok": True,
        "results": results,
        "ms": round((time.perf_counter() - started) * 1000, 1),
    })


def warmup():
    """
    Run each graph once at startup.

    ONNX Runtime allocates its arenas and picks its kernels on the first call,
    so without this the first punch of the day pays several hundred extra
    milliseconds — which is the one punch most likely to be watched by somebody
    deciding whether the new system is any good.
    """
    for size in {DET_FAST, DET_FULL}:
        DET.run(None, {DET_IN: np.zeros((1, 3, size, size), dtype=np.float32)})
    REC.run(None, {REC_IN: np.zeros((1, 3, REC_SIZE, REC_SIZE), dtype=np.float32)})
    SPOOF.run(None, {SPOOF_IN: np.zeros((1, 3, SPOOF_SIZE, SPOOF_SIZE), dtype=np.float32)})


warmup()


if __name__ == "__main__":
    print(f"face-service listening on http://{HOST}:{PORT}  models={MODEL_DIR}")

    if not TOKEN:
        print("WARNING: FACE_SERVICE_TOKEN is unset — bind to localhost only.")

    try:
        # Flask's own server is single-process and explicitly not for
        # production; waitress is a pure-Python WSGI server that is, and needs
        # no compiler to install. Falls back so a dev box without it still runs.
        from waitress import serve

        serve(app, host=HOST, port=PORT, threads=int(os.environ.get("FACE_SERVICE_WORKERS", "4")))
    except ImportError:
        print("waitress not installed — falling back to the Flask dev server (not for production)")
        app.run(host=HOST, port=PORT, threaded=True)
