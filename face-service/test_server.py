"""
Tests for the face scoring service.

Run:  python face-service/test_server.py

No pytest, on purpose: this has to be runnable on a deployment box that has
only what server.py itself needs, because the properties it checks are the ones
worth re-checking after a model swap or a Pillow upgrade.

The faces come from the AdminLTE template already vendored in public/ — real
photographs of real people, which matters because SCRFD does not detect drawn
avatars and a synthetic face would make every one of these tests vacuous.

WHAT IS BEING PROTECTED
The service previously embedded a plain square resize of the detection box
instead of the landmark-aligned crop ArcFace is trained on. Nothing raised, the
vectors were still 512 floats and still normalised, and every existing test
passed — the embeddings were simply in a different space from the browser's, so
enrolling through one path and matching through the other could never work.
test_alignment_* is what would have caught that.
"""

import base64
import glob
import io
import os
import sys

import numpy as np
from PIL import Image, ImageFilter

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import server as S  # noqa: E402

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..")
MATCH_DISTANCE = 1.10          # config/face.php -> face.match.distance
MIN_FOCUS = 0.02               # config/face.php -> face.scoring.enrolment.min_focus

_results = []


def check(name, passed, detail=""):
    _results.append((name, passed, detail))
    print(f'  {"PASS" if passed else "FAIL"}  {name}{"   " + detail if detail else ""}')
    return passed


def load_faces(min_side=500):
    """Real headshots, upscaled only so the attack simulations have pixels to work on."""
    paths = sorted(
        p for p in glob.glob(os.path.join(ROOT, "public/template/dist/img/*.jpg"))
        if "user" in os.path.basename(p)
    )
    out = []
    for p in paths:
        img = Image.open(p).convert("RGB")
        if max(img.size) < min_side:
            k = 700 / max(img.size)
            img = img.resize((int(img.size[0] * k), int(img.size[1] * k)), Image.BICUBIC)
        faces = S.detect(img)
        if len(faces) == 1:
            out.append((os.path.basename(p), img, faces[0]))
    return out


def simulate_screen(img, pitch=3.1, angle=2.5, depth=0.20):
    """
    A face on an LCD, re-photographed.

    The beat between the panel's pixel lattice and the sensor's is the thing
    being detected, and that beat is arithmetic — a periodic overlay at a
    slight rotation — so it simulates faithfully even though glare, backlight
    bloom and the phone's own compression do not.
    """
    a = np.asarray(img, dtype=np.float64)
    yy, xx = np.mgrid[:a.shape[0], :a.shape[1]]
    t = np.deg2rad(angle)
    u = xx * np.cos(t) - yy * np.sin(t)
    v = xx * np.sin(t) + yy * np.cos(t)
    mask = (1 - depth + depth * (0.5 + 0.5 * np.cos(2 * np.pi * u / pitch))) \
         * (1 - depth * 0.5 + depth * 0.5 * (0.5 + 0.5 * np.cos(2 * np.pi * v / pitch)))
    return Image.fromarray(np.clip(a * mask[..., None] * 1.04, 0, 255).astype(np.uint8))


def as_payload(img, quality=85):
    buf = io.BytesIO()
    img.save(buf, "JPEG", quality=quality)
    return base64.b64encode(buf.getvalue()).decode()


FACES = load_faces()


# ------------------------------------------------------------------ alignment

def test_embeddings_are_well_formed():
    print("\nembeddings")
    ok = True
    for name, img, face in FACES:
        v = S.embed(img, face)
        ok &= check(f"{name}: 512-d, unit norm", v is not None and len(v) == 512
                    and abs(np.linalg.norm(v) - 1.0) < 1e-9
                    and np.all(np.isfinite(v)))
    return ok


def test_alignment_absorbs_head_roll():
    """
    The same face at a 12 degree roll must stay the same person.

    This is the property the alignment exists for, and the one that fails
    silently without it: unaligned, the same face rotated lands 0.72 away on
    average against 0.27 aligned, which eats the entire margin to the next
    person.
    """
    print("\nalignment: same identity under roll")
    worst = 0.0
    for name, img, face in FACES:
        a = np.array(S.embed(img, face))
        rot = img.rotate(12, resample=Image.BILINEAR, expand=True, fillcolor=(0, 0, 0))
        rf = S.detect(rot)
        if len(rf) != 1:
            continue
        b = np.array(S.embed(rot, rf[0]))
        d = float(np.linalg.norm(a - b))
        worst = max(worst, d)
        check(f"{name}: d={d:.3f} < {MATCH_DISTANCE}", d < MATCH_DISTANCE)
    return check(f"worst same-person distance {worst:.3f}", worst < MATCH_DISTANCE)


def test_different_people_stay_apart():
    print("\nalignment: distinct identities separate")
    emb = {n: np.array(S.embed(i, f)) for n, i, f in FACES}
    keys = list(emb)
    pairs = [(float(np.linalg.norm(emb[keys[i]] - emb[keys[j]])), keys[i], keys[j])
             for i in range(len(keys)) for j in range(i + 1, len(keys))]
    closest = min(pairs)
    return check(
        f"closest of {len(pairs)} distinct pairs = {closest[0]:.3f} > {MATCH_DISTANCE}",
        closest[0] > MATCH_DISTANCE,
        f"({closest[1]} vs {closest[2]})",
    )


def _umeyama_browser(src, dst):
    """
    face-engine.js's umeyama(), transcribed line for line — closed-form 2x2 SVD
    and all.

    The browser cannot use numpy, so it solves the same problem a different way.
    Two different routes to the same transform is exactly the situation where a
    silent divergence hides, and a divergence here does not raise: it puts
    browser-enrolled and server-enrolled faces in slightly different frames, and
    the only symptom is that recognition gets quietly worse.
    """
    import math

    n = len(src)
    src_mean = [sum(p[0] for p in src) / n, sum(p[1] for p in src) / n]
    dst_mean = [sum(p[0] for p in dst) / n, sum(p[1] for p in dst) / n]

    a00 = a01 = a10 = a11 = src_var = 0.0
    for i in range(n):
        sx, sy = src[i][0] - src_mean[0], src[i][1] - src_mean[1]
        dx, dy = dst[i][0] - dst_mean[0], dst[i][1] - dst_mean[1]
        a00 += dx * sx / n
        a01 += dx * sy / n
        a10 += dy * sx / n
        a11 += dy * sy / n
        src_var += (sx * sx + sy * sy) / n

    # svd2x2: rotation-scale-rotation closed form.
    E, F = (a00 + a11) / 2, (a00 - a11) / 2
    G, H = (a10 + a01) / 2, (a10 - a01) / 2
    Q, R_ = math.hypot(E, H), math.hypot(F, G)
    s1, s2 = Q + R_, Q - R_
    a1, a2 = math.atan2(G, F), math.atan2(H, E)
    theta, phi = (a2 - a1) / 2, (a2 + a1) / 2
    U = [[math.cos(phi), -math.sin(phi)], [math.sin(phi), math.cos(phi)]]
    Vt = [[math.cos(theta), -math.sin(theta)], [math.sin(theta), math.cos(theta)]]
    if s2 < 0:
        s2 = -s2
        Vt[1][0], Vt[1][1] = -Vt[1][0], -Vt[1][1]

    det_a = a00 * a11 - a01 * a10
    det_u = U[0][0] * U[1][1] - U[0][1] * U[1][0]
    det_v = Vt[0][0] * Vt[1][1] - Vt[0][1] * Vt[1][0]
    d1 = 1.0
    d2 = -1.0 if (det_a < 0 or (det_a == 0 and det_u * det_v < 0)) else 1.0

    R = [
        [U[0][0] * d1 * Vt[0][0] + U[0][1] * d2 * Vt[1][0],
         U[0][0] * d1 * Vt[0][1] + U[0][1] * d2 * Vt[1][1]],
        [U[1][0] * d1 * Vt[0][0] + U[1][1] * d2 * Vt[1][0],
         U[1][0] * d1 * Vt[0][1] + U[1][1] * d2 * Vt[1][1]],
    ]
    scale = (s1 * d1 + s2 * d2) / max(src_var, 1e-12)
    tx = dst_mean[0] - scale * (R[0][0] * src_mean[0] + R[0][1] * src_mean[1])
    ty = dst_mean[1] - scale * (R[1][0] * src_mean[0] + R[1][1] * src_mean[1])

    return np.array([[scale * R[0][0], scale * R[0][1], tx],
                     [scale * R[1][0], scale * R[1][1], ty]])


def test_alignment_matches_the_browsers():
    """
    The server and public/js/face-engine/face-engine.js must warp a face onto
    the template identically.

    This is the whole compatibility claim. Registration stores what the server
    computes; the punch compares what the browser computes. If the two frames
    differ, every distance is measured between subtly different crops and the
    margin erodes without anything ever reporting an error.
    """
    print("\nalignment: server and browser agree")
    template = S.ARCFACE_TEMPLATE.tolist()
    worst = 0.0

    for name, img, face in FACES:
        kps = face["kps"]

        A, t = S.umeyama(np.asarray(kps, np.float64), S.ARCFACE_TEMPLATE)
        server = np.hstack([A, t.reshape(2, 1)])
        browser = _umeyama_browser([list(map(float, q)) for q in kps], template)

        # Expressed where it matters: how far apart the five landmarks land in
        # the 112x112 crop under the two transforms.
        src = np.hstack([np.asarray(kps, np.float64), np.ones((5, 1))])
        drift = float(np.abs(src @ server.T - src @ browser.T).max())
        worst = max(worst, drift)

        check(f"{name}: landmarks land within {drift:.2e} px", drift < 1e-6)

    return check(f"worst divergence {worst:.2e} px on a 112px crop", worst < 1e-6)


def test_landmarks_are_decoded():
    """
    Without keypoints there is no alignment, so their absence must be loud.

    They are matched out of the graph structurally, by column count, rather than
    by output position — an export that reorders its outputs would otherwise
    decode box regressions as scores and quietly produce nonsense.
    """
    print("\nlandmarks")
    ok = True
    for name, img, face in FACES:
        kps = face.get("kps")
        good = kps is not None and kps.shape == (5, 2) and np.all(np.isfinite(kps))
        if good:
            x1, y1, x2, y2 = face["bbox"]
            pad_w, pad_h = (x2 - x1) * 0.5, (y2 - y1) * 0.5
            good = bool(np.all(kps[:, 0] > x1 - pad_w) and np.all(kps[:, 0] < x2 + pad_w)
                        and np.all(kps[:, 1] > y1 - pad_h) and np.all(kps[:, 1] < y2 + pad_h))
            # Eyes above mouth corners: catches a decode that is self-consistent
            # but has the five points in the wrong order.
            good = good and kps[0][1] < kps[3][1] and kps[1][1] < kps[4][1]
        ok &= check(f"{name}: 5 landmarks inside the face, eyes above mouth", good)
    return ok


# ------------------------------------------------------------------ forensics

def test_forensics_do_not_refuse_live_faces():
    """A false reject stops somebody clocking in, so this is the harder bar."""
    print("\nforensics: live faces are not flagged")
    ok = True
    for name, img, face in FACES:
        r = S.score_image(img)
        m = r["forensics"]["moire"]
        ok &= check(f"{name}: moire {m} < {S.MAX_MOIRE}, no flags", not r["forensic_flags"])
    return ok


def test_forensics_catch_a_screen_the_cnn_does_not():
    """
    The CNN alone does not separate these; the spectrum does.

    Both halves are asserted, because the value of the forensic layer is
    entirely in the gap between them — if the CNN ever starts catching these on
    its own, this test says so rather than quietly passing.
    """
    print("\nforensics: simulated screens are flagged")
    caught = total = 0
    cnn_scores = []
    for name, img, face in FACES:
        for pitch in (2.3, 3.1, 4.7):
            r = S.score_image(simulate_screen(img, pitch))
            if r.get("faces") != 1:
                continue
            total += 1
            cnn_scores.append(r["antispoof_cnn"])
            if r["forensic_flags"]:
                caught += 1
                # A flagged frame must be reported as a spoof, not merely noted.
                check(f"{name} p={pitch}: flagged and antispoof forced to 0",
                      r["antispoof"] == 0.0)
    ok = check(f"{caught}/{total} simulated screens flagged", caught >= total * 0.8)
    ok &= check(
        f"CNN alone would have passed them (max {max(cnn_scores):.4f} >= 0.70)",
        max(cnn_scores) >= 0.70,
        "-- this is why the forensic layer exists",
    )
    return ok


def test_forensics_abstain_on_a_face_too_small_to_judge():
    print("\nforensics: abstain rather than guess")
    name, img, face = FACES[0]
    tiny = img.resize((max(1, img.size[0] // 12), max(1, img.size[1] // 12)), Image.BICUBIC)
    marks = S.forensics(tiny, [0, 0, tiny.size[0], tiny.size[1]])
    return check("a face below the resolution floor reports moire=None",
                 marks["moire"] is None, f"px={marks['px']}")


# -------------------------------------------------------------------- quality

def test_focus_separates_blur_where_raw_sharpness_cannot():
    """
    The gate must refuse every blurred capture, and refuse a sharp one only
    when that capture is genuinely poor.

    It is not asserted that every sharp frame passes. One of these photographs
    is dark and soft enough — brightness 59 and raw detail 17, against medians
    of 140 and 230 — that turning it away at enrolment is the right answer: a
    template made from it would match badly for as long as it stayed on file.
    What matters is that the gate sits clear of the blurred set.
    """
    print("\nquality: focus is the blur gate")
    sharp, blurred, raw_sharp, raw_blur = [], [], [], []
    for name, img, face in FACES:
        crop = S.align(img, face["kps"])
        q = S.quality(img, face["bbox"], crop)
        sharp.append(q["focus"])
        raw_sharp.append(q["sharpness"])
        qb = S.quality(img, face["bbox"], crop.filter(ImageFilter.GaussianBlur(1.5)))
        blurred.append(qb["focus"])
        raw_blur.append(qb["sharpness"])

    passing = sum(1 for v in sharp if v >= MIN_FOCUS)

    ok = check(f"every blurred frame is refused (max {max(blurred):.4f} < {MIN_FOCUS})",
               max(blurred) < MIN_FOCUS)
    ok &= check(f"the gate clears the blurred set by 1.4x or more "
                f"({MIN_FOCUS} vs {max(blurred):.4f})",
                MIN_FOCUS >= max(blurred) * 1.4)
    ok &= check(f"{passing}/{len(sharp)} sharp frames pass", passing >= len(sharp) - 1)
    # The reason focus exists at all: the raw number does not order these.
    ok &= check(f"raw sharpness overlaps (sharp min {min(raw_sharp):.0f} vs blurred max {max(raw_blur):.0f})",
                min(raw_sharp) < max(raw_blur),
                "-- so no raw threshold could separate them")
    return ok


# ----------------------------------------------------------------- robustness

def test_malformed_input_is_refused_not_raised():
    print("\nrobustness: bad input")
    ok = True
    for label, value in [
        ("empty string", ""), ("not base64", "!!!!"), ("base64 of junk", "aGVsbG8="),
        ("data url with no comma", "data:image/jpeg;base64"), ("None", None),
        ("an int", 5), ("a list", []),
    ]:
        try:
            ok &= check(f"{label} -> rejected", S._decode(value) is None)
        except Exception as e:
            ok &= check(f"{label} -> rejected", False, f"RAISED {type(e).__name__}: {e}")
    return ok


def test_a_frame_with_no_face_and_one_with_two():
    print("\nrobustness: face count")
    blank = S.score_image(Image.new("RGB", (640, 480), (17, 17, 17)))
    ok = check("a blank frame reports no_face", blank.get("reason") == "no_face")

    # Two people in shot is refused, never resolved to the larger: picking one
    # would let somebody stand behind the employee and be ignored.
    a, b = FACES[0][1], FACES[1][1]
    h = max(a.size[1], b.size[1])
    pair = Image.new("RGB", (a.size[0] + b.size[0], h), (0, 0, 0))
    pair.paste(a, (0, 0))
    pair.paste(b, (a.size[0], 0))
    two = S.score_image(pair)
    ok &= check(f"two faces in shot -> refused (saw {two.get('faces')})",
                two.get("faces", 0) > 1 and two.get("reason") == "multiple_faces")
    return ok


def test_detection_cascade_finds_every_face_and_agrees_on_the_box():
    """
    The cascade must never see less than the full pass, and must put the box in
    the same place when both see something.

    It is allowed to see MORE, and it does: see the next test.
    """
    print("\ndetection: the cascade loses nothing")
    ok = True
    for name, img, face in FACES:
        full = S._detect_at(img, S.DET_FULL)
        casc = S.detect(img)

        ok &= check(f"{name}: cascade found a face", bool(casc))

        if not full or not casc:
            continue

        a = np.array(full[0]["bbox"])
        b = np.array(casc[0]["bbox"])
        side = max(a[2] - a[0], a[3] - a[1])
        drift = float(np.abs(a - b).max() / side)
        ok &= check(f"{name}: box agrees within {drift*100:.1f}% of face width", drift < 0.10)
    return ok


def test_a_frame_filling_face_is_detected():
    """
    SCRFD at 640 misses a face that fills the frame — which is precisely how
    somebody standing at a kiosk appears.

    A service pinned at 640, as this one was, answers "no face" to a person
    right in front of the camera. The cascade tries the smaller input first and
    so covers the near case; this pins that down, because it is a silent
    failure and an easy one to reintroduce by "simplifying" detect().
    """
    print("\ndetection: a face that fills the frame")
    ok = True
    for name, img, face in FACES:
        x1, y1, x2, y2 = face["bbox"]
        # Crop tight to the face and blow it up, so it occupies almost all of a
        # 700px frame.
        pad_w, pad_h = (x2 - x1) * 0.12, (y2 - y1) * 0.12
        tight = img.crop((max(0, int(x1 - pad_w)), max(0, int(y1 - pad_h)),
                          min(img.size[0], int(x2 + pad_w)), min(img.size[1], int(y2 + pad_h))))
        close = tight.resize((700, 700), Image.BICUBIC)

        found = len(S.detect(close)) >= 1
        at_full = len(S._detect_at(close, S.DET_FULL)) >= 1

        ok &= check(f"{name}: close-up detected by the cascade", found,
                    "" if at_full else "(and the 640 pass alone misses it)")
    return ok


def test_the_http_surface():
    print("\nhttp")
    S.TOKEN = ""
    client = S.app.test_client()

    health = client.get("/health")
    ok = check("/health reports aligned=True", health.get_json().get("aligned") is True)

    payload = as_payload(FACES[0][1])
    one = client.post("/score", json={"image": payload}).get_json()
    ok &= check("/score returns an embedding and a bbox",
                one.get("faces") == 1 and len(one.get("embedding") or []) == 512)

    # Batch results are positional: result N belongs to image N whatever
    # happened to the others, or a caller pairs one frame's verdict with
    # another frame's vector.
    batch = client.post("/score_batch", json={
        "images": [payload, "!!!not-an-image!!!", as_payload(FACES[1][1])],
    }).get_json()
    results = batch.get("results", [])
    ok &= check("/score_batch returns one result per image, in order",
                len(results) == 3
                and results[0].get("faces") == 1
                and results[1].get("reason") == "undecodable"
                and results[2].get("faces") == 1)

    same = np.array(one["embedding"])
    also = np.array(results[0]["embedding"])
    ok &= check("batch and single scoring agree on the same frame",
                float(np.linalg.norm(same - also)) < 1e-9)

    ok &= check("/score_batch refuses an oversized batch",
                client.post("/score_batch", json={"images": [payload] * (S.MAX_BATCH + 1)}).status_code == 422)
    ok &= check("/score refuses an empty body",
                client.post("/score", json={}).status_code == 422)

    S.TOKEN = "secret-token"
    try:
        ok &= check("a wrong token is rejected",
                    client.post("/score", json={"image": payload},
                                headers={"X-Face-Token": "wrong"}).status_code == 401)
        ok &= check("the right token is accepted",
                    client.post("/score", json={"image": payload},
                                headers={"X-Face-Token": "secret-token"}).status_code == 200)
    finally:
        S.TOKEN = ""

    return ok


if __name__ == "__main__":
    if not FACES:
        print("no test faces found under public/template/dist/img — cannot run")
        sys.exit(2)

    print(f"face-service tests — {len(FACES)} real faces, det cascade {S.DET_FAST}->{S.DET_FULL}")

    for fn in [
        test_embeddings_are_well_formed,
        test_alignment_absorbs_head_roll,
        test_different_people_stay_apart,
        test_alignment_matches_the_browsers,
        test_landmarks_are_decoded,
        test_forensics_do_not_refuse_live_faces,
        test_forensics_catch_a_screen_the_cnn_does_not,
        test_forensics_abstain_on_a_face_too_small_to_judge,
        test_focus_separates_blur_where_raw_sharpness_cannot,
        test_malformed_input_is_refused_not_raised,
        test_a_frame_with_no_face_and_one_with_two,
        test_detection_cascade_finds_every_face_and_agrees_on_the_box,
        test_a_frame_filling_face_is_detected,
        test_the_http_surface,
    ]:
        try:
            fn()
        except Exception as exc:  # a raise is a failure, not a crash
            check(f"{fn.__name__} raised", False, f"{type(exc).__name__}: {exc}")

    failed = [n for n, ok, _ in _results if not ok]
    print(f"\n{len(_results) - len(failed)}/{len(_results)} checks passed")

    if failed:
        print("\nfailed:")
        for n in failed:
            print(f"  - {n}")

    sys.exit(1 if failed else 0)
