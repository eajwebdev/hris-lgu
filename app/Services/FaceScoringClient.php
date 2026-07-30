<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scores a face frame on the server instead of trusting the browser's numbers.
 *
 * The attendance and registration endpoints used to accept an embedding, an
 * anti-spoof score and flash luma readings — all computed in the browser. Every
 * one of those is an assertion by code the attacker controls, so a tampered
 * client could send a stolen descriptor with a perfect liveness score and the
 * server had no way to disagree. This sends the raw frame to a local inference
 * service and uses what comes back.
 *
 * The service is the Python sidecar in face-service/, which runs the same three
 * ONNX models the browser does. It is expected on localhost; it must never be
 * exposed publicly, because anything that can reach it can ask it to score
 * arbitrary images.
 *
 * FAILURE POLICY
 * Configurable, and the default is to fail CLOSED. A scoring service that is
 * down must not silently return the system to trusting the client — that is the
 * exact hole this class exists to close. Set face.scoring.required=false only
 * for a deployment that genuinely cannot run the sidecar, and understand that
 * doing so restores the old trust model.
 */
class FaceScoringClient
{
    public function enabled(): bool
    {
        return (bool) config('face.scoring.enabled', false);
    }

    public function required(): bool
    {
        return (bool) config('face.scoring.required', true);
    }

    /**
     * Score one frame.
     *
     * @param  string  $image  base64 JPEG/PNG, with or without a data: prefix
     * @return array{ok:bool,reason:?string,faces:int,embedding:?array,antispoof:?float,quality:?array}
     */
    public function score(string $image): array
    {
        return $this->scoreMany([$image])[0];
    }

    /**
     * Score several frames in one round trip.
     *
     * Registration scores four captures. As four separate requests that is four
     * lots of connection setup and framework overhead for work that is
     * identical apart from the pixels, and nothing can be said to the operator
     * until all four are back — so there is nothing to be gained by keeping
     * them apart.
     *
     * Results come back positionally: one entry per input, in the order given,
     * whatever happened to each. A caller can therefore always line result N up
     * with capture N.
     *
     * @param  array<int,string>  $images
     * @return array<int,array>
     */
    public function scoreMany(array $images): array
    {
        $images = array_values($images);

        if (! $images) {
            return [];
        }

        if (! $this->enabled()) {
            return array_fill(0, count($images), $this->unavailable('scoring_disabled'));
        }

        // The sidecar caps how many frames it will take at once, and a punch can
        // carry more than that (face.liveness.max_frames is 12). Chunking here
        // rather than letting the caller worry about it means no call site can
        // silently exceed the cap and have the whole punch fail closed on what
        // is really a configuration mismatch.
        $chunk = max(1, (int) config('face.scoring.max_batch', 8));

        if (count($images) > $chunk) {
            $out = [];

            foreach (array_chunk($images, $chunk) as $slice) {
                foreach ($this->scoreMany($slice) as $result) {
                    $out[] = $result;
                }
            }

            return $out;
        }

        $base = rtrim((string) config('face.scoring.url', 'http://127.0.0.1:8078'), '/');
        $token = (string) config('face.scoring.token', '');

        try {
            $response = Http::timeout((int) config('face.scoring.timeout', 8))
                ->withHeaders($token !== '' ? ['X-Face-Token' => $token] : [])
                ->post($base.'/score_batch', ['images' => $images]);
        } catch (\Throwable $e) {
            Log::warning('Face scoring service unreachable.', ['error' => $e->getMessage()]);

            return array_fill(0, count($images), $this->unavailable('service_unreachable'));
        }

        if (! $response->successful()) {
            Log::warning('Face scoring service refused a batch.', [
                'status' => $response->status(),
                'body'   => mb_substr($response->body(), 0, 200),
            ]);

            return array_fill(0, count($images), $this->unavailable('service_error'));
        }

        $body = $response->json();
        $results = is_array($body) ? ($body['results'] ?? null) : null;

        // A reply that is not one result per frame cannot be lined up with the
        // captures, and guessing the alignment would attach one frame's verdict
        // to another frame's embedding. Treat it as an outage instead.
        if (! ($body['ok'] ?? false) || ! is_array($results) || count($results) !== count($images)) {
            Log::warning('Face scoring service returned a malformed batch.', [
                'expected' => count($images),
                'got'      => is_array($results) ? count($results) : null,
            ]);

            return array_fill(0, count($images), $this->unavailable('service_error'));
        }

        return array_map(fn ($r) => $this->normalise(is_array($r) ? $r : []), $results);
    }

    /** One service result, in the shape the callers expect. */
    private function normalise(array $body): array
    {
        if (! ($body['ok'] ?? false)) {
            return [
                'ok'        => false,
                'reason'    => $body['reason'] ?? 'service_error',
                'faces'     => (int) ($body['faces'] ?? 0),
                'embedding' => null,
                'antispoof' => null,
                'quality'   => null,
            ];
        }

        $faces = (int) ($body['faces'] ?? 0);

        // No face, or more than one, is a refusal rather than a guess. Picking
        // the largest of several would let somebody stand behind the employee
        // and be quietly ignored.
        if ($faces !== 1) {
            return [
                'ok'        => false,
                'reason'    => $body['reason'] ?? ($faces === 0 ? 'no_face' : 'multiple_faces'),
                'faces'     => $faces,
                'embedding' => null,
                'antispoof' => null,
                'quality'   => null,
            ];
        }

        // A face the sidecar could not align on has no trustworthy embedding —
        // it says so rather than returning a vector from an unaligned crop,
        // which would be a number in the wrong space rather than an error.
        if (($body['reason'] ?? null) === 'unalignable_face' || ! isset($body['embedding'])) {
            return [
                'ok'        => false,
                'reason'    => 'unalignable_face',
                'faces'     => 1,
                'embedding' => null,
                'antispoof' => null,
                'quality'   => null,
            ];
        }

        return [
            'ok'         => true,
            'reason'     => null,
            'faces'      => 1,
            'embedding'  => $body['embedding'],
            'antispoof'  => isset($body['antispoof']) ? (float) $body['antispoof'] : null,
            'quality'    => $body['quality'] ?? null,
            'bbox'       => $body['bbox'] ?? null,
            // Which physical checks fired, if any. Carried through so a refusal
            // can name what it saw instead of only that something was wrong.
            'flags'      => (array) ($body['forensic_flags'] ?? []),
            'forensics'  => $body['forensics'] ?? null,
        ];
    }

    /**
     * Whether a scored frame is good enough to enrol from.
     *
     * A blurred or distant capture yields a template that matches badly for as
     * long as it is on file, which surfaces later as the employee who "never
     * gets recognised". Refusing it at the door is far cheaper than diagnosing
     * it months later.
     *
     * @return array{ok:bool,reason:?string}
     */
    public function meetsEnrolmentQuality(array $scored): array
    {
        $q = $scored['quality'] ?? [];
        $limits = (array) config('face.scoring.enrolment', []);

        // A frame the sidecar's physical checks called a display or a print is
        // refused first, and by name. It has already been reported with an
        // anti-spoof score of 0 so the generic gate below would catch it too,
        // but "this looks like a screen" tells the operator something they can
        // act on where "not live" invites them to simply try again.
        if (config('face.scoring.reject_forensic_flags', true) && ! empty($scored['flags'])) {
            return ['ok' => false, 'reason' => (string) $scored['flags'][0]];
        }

        $checks = [
            'face_too_small' => ($q['face_px'] ?? 0) >= ($limits['min_face_px'] ?? 90),
            'too_blurry'     => ($q['focus'] ?? 0) >= ($limits['min_focus'] ?? 0.03),
            'no_detail'      => ($q['sharpness'] ?? 0) >= ($limits['min_sharpness'] ?? 5),
            'too_dark'       => ($q['brightness'] ?? 0) >= ($limits['min_brightness'] ?? 45),
            'too_bright'     => ($q['brightness'] ?? 255) <= ($limits['max_brightness'] ?? 235),
        ];

        foreach ($checks as $reason => $passed) {
            if (! $passed) {
                return ['ok' => false, 'reason' => $reason];
            }
        }

        $min = (float) ($limits['min_antispoof'] ?? config('face.antispoof.min_real', 0.7));

        if ($scored['antispoof'] !== null && $scored['antispoof'] < $min) {
            return ['ok' => false, 'reason' => 'not_live'];
        }

        return ['ok' => true, 'reason' => null];
    }

    /** Human wording for the reasons above, for the capture UI. */
    public function explain(?string $reason): string
    {
        return [
            'no_face'          => 'No face was found in the frame. Face the camera squarely.',
            'multiple_faces'   => 'More than one face is visible. Only the employee being registered may be in frame.',
            'face_too_small'   => 'Move closer to the camera.',
            'too_blurry'       => 'Hold still — the capture was blurred.',
            'no_detail'        => 'The camera saw almost no detail. Check the lens is uncovered and in focus.',
            'too_dark'         => 'Too dark. More light on the face is needed.',
            'too_bright'       => 'Too bright. Move out of direct glare or backlight.',
            'not_live'         => 'This does not look like a live face. A photograph or a screen cannot be registered.',
            'screen_moire'     => 'This looks like a face on a screen rather than a person. Register from the camera directly.',
            'unalignable_face' => 'The face could not be squared up — look straight at the camera and try again.',
            'undecodable'      => 'That capture could not be read. Please try again.',
            'scoring_disabled' => 'Server-side face scoring is switched off.',
            'service_unreachable' => 'The face scoring service is not running. Registration cannot be verified.',
            'service_error'    => 'The face scoring service could not read this frame.',
        ][$reason] ?? 'The capture could not be verified. Please try again.';
    }

    private function unavailable(string $reason): array
    {
        return [
            'ok'          => false,
            'reason'      => $reason,
            'faces'       => 0,
            'embedding'   => null,
            'antispoof'   => null,
            'quality'     => null,
            // Lets a caller distinguish "the frame was bad" from "we could not
            // check", which matters because only the second is a fail-closed
            // decision the operator can do nothing about.
            'unavailable' => true,
        ];
    }
}
