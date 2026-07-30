# face-service

Server-side face scoring for the attendance portal and face registration.

## Why

The browser used to compute the embedding, the anti-spoof score and the flash
luma readings, then POST the numbers. Everything the server knew about a punch
was an assertion by code the attacker controls — a tampered client could send a
stolen descriptor with a `0.99` "real" score and the server had no way to
disagree. The head gestures, the screen flash and the anti-spoof CNN were all
guarding a door that could be walked around.

This service takes the raw frame instead and derives its own answer:

    detect (SCRFD) -> align on 5 landmarks -> embed (ArcFace, 512-d)
                                           -> anti-spoof (MiniFASNet + forensics)

## Run

    pip install onnxruntime numpy pillow flask waitress
    FACE_SERVICE_TOKEN=$(openssl rand -hex 32) python face-service/server.py

Then in `.env`:

    FACE_SCORING_ENABLED=true
    FACE_SCORING_REQUIRED=true
    FACE_SCORING_URL=http://127.0.0.1:8078
    FACE_SERVICE_TOKEN=<the same token>

Keep it on localhost. Anything that can reach it can ask it to score arbitrary
images. In production run it under supervisor/systemd, not by hand. `waitress`
is optional but wanted: without it the process falls back to the Flask
development server, which is single-process and explicitly not for production.

## Tests

    python face-service/test_server.py

105 checks over the real photographs vendored in `public/template/dist/img`.
No pytest needed, so it runs on a deployment box that has only what
`server.py` itself needs.

## Contract

`POST /score` `{"image": "<base64 jpeg>"}` ->

    { "ok": true, "faces": 1, "bbox": [...], "detect_score": 0.88,
      "landmarks": [[x,y] x5],
      "embedding": [512 floats],
      "antispoof": 0.9954, "antispoof_cnn": 0.9954,
      "forensics": { "moire": 21.8, "detail": 0.0007, "clipping": 0.01,
                     "chroma": 0.15, "px": 395 },
      "forensic_flags": [],
      "quality": { "face_px": 395, "sharpness": 2034.8, "focus": 0.0554,
                   "brightness": 108.1 },
      "ms": 25.0 }

`POST /score_batch` `{"images": [...]}` -> `{"ok": true, "results": [...]}`,
one result per input **in the order given**, each the same body `/score`
returns. A frame that fails to decode occupies its own slot with
`reason: "undecodable"` rather than shifting everything after it — a caller
must always be able to line result N up with capture N.

`faces` other than 1 returns a `reason` of `no_face` or `multiple_faces`.
Several faces are refused rather than resolved: picking the largest would let
somebody stand behind the employee and be quietly ignored.

## Preprocessing must match the browser

`public/js/face-engine/face-engine.js` is the reference. These details are easy
to get wrong and every one of them silently corrupts the result rather than
raising:

| | value |
|---|---|
| detector input | letterboxed square, `(x-127.5)/128` |
| detector outputs | matched **structurally** (1 col = score, 4 = box, 10 = kps), never by position |
| recogniser input | 112x112 **landmark-aligned** crop (Umeyama onto the ArcFace template), `(x-127.5)/127.5`, output L2-normalised |
| anti-spoof input | box widened x1.5, letterboxed to 128x128, `x/255`, **no** mean/std |
| anti-spoof class | **index 0 is "live"** |

The class index was inverted in the first draft of this service, which scored
genuine faces at 0.000. Real portraits should score ~0.99.

## Measured

Against the real photographs in `public/template/dist/img`, `face.match.distance`
being 1.10:

| | result |
|---|---|
| same face at a 12° roll | 0.13–0.24 (worst 0.243) |
| distinct identities, 28 pairs | closest 1.314, median 1.384 |
| embeddings | 512-d, unit norm, finite |
| `/score` end to end, 1280px JPEG | median 30 ms over HTTP, 25 ms inference |
| detection alone | 9 ms (cascade 320 → 640) |

### Alignment was the bug worth finding

This service used to embed a plain square resize of the detection box instead
of the landmark-aligned crop. Nothing raised; the vectors were still 512 floats
and still unit norm. They were simply **in a different space from the
browser's**, so enrolling through one path and matching through the other could
never work — and with `FACE_SCORING_ENABLED=true` that is exactly the split,
because registration stores what this service computes while the punch compares
what the browser computes.

Even ignoring that, unaligned embeddings do not separate people:

| | unaligned | aligned |
|---|---|---|
| same person, 12° roll (mean) | 0.719 | 0.312 |
| different people (closest) | 0.901 | 1.314 |
| **margin** | **0.046** | **0.267** |

A margin of 0.046 is no separation at all. `test_alignment_absorbs_head_roll`
is what would have caught it.

### SCRFD at 640 misses a face that fills the frame

All eight test photographs, cropped tight and scaled to fill a 700px frame, are
detected at 256/320/416/512 and **not at 640**. A kiosk close-up is precisely
that geometry, so the previous fixed-640 service answered "no face" to somebody
standing right in front of the camera. Detection now runs 320 first and only
falls back to 640, which covers the near case and leaves the full-resolution
pass for the far one. It is also ~4x faster, but that is the lesser reason.

## Anti-spoofing

The CNN alone does not discriminate. Across live photographs and simulated
display captures it scored **0.9931–1.0000 on live and up to 1.0000 on
screens** — the 0.70 floor separates nothing. This reproduces the earlier note
that a composited "photo on a screen" scored 0.9979 against 0.9985 for live.

So the CNN is no longer alone. `forensics()` measures physical properties of
the capture instead, and the discriminating one is `moire`: the largest
**off-axis** spectral peak on any single frequency ring, in MAD units against
the rest of that ring. Photographing a panel beats its pixel lattice against
the sensor's, and a periodic overlay is a sharp isolated spike where ordinary
image content — skin included — is broadband.

Two details do all the work, and the first attempt at this had neither and
separated nothing:

* **Measured on the native crop, never the 112px aligned one.** Resampling a
  face to 112 pixels is a low-pass filter that destroys exactly the structure
  being looked for. On the aligned crop, live and screen medians were 15.8 and
  16.0 — indistinguishable.
* **Axes excluded.** JPEG encodes in 8x8 blocks, so every JPEG has strong peaks
  at multiples of 1/8 along the axes; they are a file-format artefact present
  in live captures too, and they drown the signal. A panel photographed even
  slightly rotated puts its beat off-axis, where nothing else lives.

Measured: **live 8.4–21.6, simulated screens 21.8–700.8**, and 24/24 simulated
captures flagged with 0/8 live false positives. `MAX_MOIRE` defaults to 45 —
roughly twice the highest live value, not the tightest value that still catches
the simulations. That asymmetry is deliberate: a false reject stops an employee
clocking in and is discovered immediately and loudly, while the residual risk is
a spoof landing in the 22–45 band that still has to satisfy the flash challenge,
the pose challenge and the CNN as well.

A flagged frame is reported with `antispoof: 0` regardless of the CNN, with
`antispoof_cnn` alongside so a refusal can always be traced to which of the two
made the call.

`detail`, `clipping` and `chroma` are reported but **not** enforced — they have
no measured separation, and gating on an unvalidated threshold would turn real
employees away. They are there so a physical calibration pass has numbers to
work from.

## Still NOT verified

The thresholds are calibrated against real photographs and a **numerically
simulated** panel. The simulation models the grid beat faithfully, because that
beat is arithmetic — but a real phone also adds glare, backlight bloom and its
own compression, none of which are modelled.

**Run the physical test before relying on it:** print a photo of an enrolled
employee, hold up a phone showing their photo, and record `antispoof_cnn` and
`forensics.moire` for both. Then set `MAX_MOIRE` from those numbers. If real
attacks land inside the live range, a certified SDK (AWS Rekognition Face
Liveness, ID R&D, FaceTec) is the honest answer.

`FACE_FORENSICS_ENFORCE=0` turns the gate back into reporting, which is what to
reach for if a deployment starts refusing genuine punches before you have had a
chance to recalibrate.

## What this cannot stop

A virtual camera (OBS and friends) feeding a genuine recording of the real
employee. The frame is real and the face is real, so no model objects, and the
recording carries no display grid for the forensics to find. Only a kiosk
device you physically control fixes that.

---

# Shared web hosting (no Python)

If you are on shared hosting — Hostinger and similar — none of the above will
run. ONNX inference needs a native runtime held in memory, which means a
long-running process, and shared plans kill processes when the request ends.
That is true in **any** language: a Node sidecar hits the same wall, and PHP FFI
is essentially never enabled because it can call arbitrary native code.

You are not stuck, though, because the strongest single check needs no ML at all.

## App\Services\FlashFrameVerifier — pure PHP + GD

`LivenessVerifier` already issues the right challenge: a shuffled, single-use
sequence of screen colours the attacker cannot predict. The weakness was never
the challenge — it was that the *browser* measured the response and posted
numbers, which a tampered client can simply invent.

`FlashFrameVerifier` measures the pixels server-side instead. The client sends
the frames; the server decides. Three checks, each catching what the others
miss:

| Check | Config | What it catches |
|---|---|---|
| Face brightens under a bright segment | `min_delta` | a still image resubmitted (delta 0) |
| Face brightens **more than** the background | `min_face_bg_delta` | a flat print — light falls off with distance, so a real head outshines the wall behind it; a print is all at one distance |
| Face takes the segment's colour cast | `min_hue_shift` | a recording — it is a real 3-D person and passes both checks above, but its colours were fixed before the server chose the sequence |

Measured on synthetic frames in `tests/Feature/FlashFrameVerifierTest.php`:

    live face          PASS     delta 85.8   face/bg 71.5   hue +0.21
    flat photograph    REJECT   delta 104.9  face/bg -0.1   <- brightness alone admits it
    recording          REJECT   delta 85.8   face/bg 71.5   hue -0.11
    same still x4      REJECT   delta 0

Note the second row: the print brightened **more** than the real face. Anything
relying on brightness alone lets it through. The falloff differential is what
refuses it.

## What this does not give you

Identity. It proves something live was in front of the lens, not *whose* face it
was. On shared hosting the embedding is still computed in the browser, so a
tampered client can still assert a stolen descriptor together with genuinely
live frames of the attacker's own face. Two mitigations:

* The **QR path** (encrypted token + 1:1 verify) does not rely on the camera
  alone, and remains the stronger option where identity really matters.
* **Keep the frames.** Storing the submitted images against the punch makes
  impersonation *detectable after the fact* even where it cannot be prevented,
  which for buddy punching is most of the deterrent.

## Wiring it in

`FlashFrameVerifier` is written and tested but **not yet connected** to the
punch endpoint — that needs the client to upload small JPEG frames tagged with
the segment that was showing, instead of the luma numbers it sends today. The
service is deliberately independent of how the frames arrive so that change is
contained to `AttendancePortalController::punch()` and the portal script.
