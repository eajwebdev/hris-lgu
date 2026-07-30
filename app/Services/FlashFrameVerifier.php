<?php

namespace App\Services;

/**
 * Verifies the screen-flash liveness response from the submitted IMAGE FRAMES,
 * in pure PHP, using only GD.
 *
 * WHY THIS EXISTS
 * ---------------
 * LivenessVerifier already issues a strong challenge: a shuffled, single-use
 * sequence of screen colours the attacker cannot predict. The weakness was never
 * the challenge — it was who measured the answer. The browser measured the light
 * falling on the face and posted the numbers, so a tampered client could simply
 * report the response the server wanted to see.
 *
 * This class measures the pixels itself. The client sends the frames; the server
 * decides. That removes the fabrication path without needing ONNX, Python, a
 * sidecar or a long-running process — which makes it the one meaningful
 * hardening available on shared web hosting.
 *
 * It is the same principle as commercial "flashmark" liveness: illuminate the
 * face with a sequence chosen after the attacker has committed to their attack,
 * and check the face reacts to it.
 *
 * WHAT IT CATCHES
 * ---------------
 *   * A printed photograph. Paper is flat and matte: it takes the colour cast
 *     but does not produce the face/background falloff a real head does.
 *   * A replayed video or screenshot. A recording cannot react to a colour
 *     sequence that was chosen after it was recorded — this is the strongest of
 *     the four checks and the hardest to work around.
 *   * A still image submitted repeatedly as "different" frames, because
 *     genuinely different frames are never pixel-identical.
 *
 * WHAT IT DOES NOT CATCH
 * ----------------------
 *   * Identity. This proves something live was in front of the lens, not WHOSE
 *     face it was. The embedding is still computed in the browser on shared
 *     hosting, so identity remains client-asserted; the QR path (encrypted
 *     token + 1:1 verify) is still the stronger option where that matters.
 *   * A live accomplice standing in for the employee. They are live, so every
 *     check here passes. Only identity matching addresses that.
 */
class FlashFrameVerifier
{
    /**
     * Which channel each screen colour should push hardest.
     * 'white' and 'dark' carry the brightness checks instead of a hue check.
     */
    private const HUE = [
        'red'   => 0,
        'green' => 1,
        'blue'  => 2,
    ];

    /**
     * Measure one frame: mean RGB of the face box, and of the surrounding
     * border as a stand-in for the background.
     *
     * The regions are scaled down to a few pixels first so GD's own C
     * resampling does the averaging — a per-pixel PHP loop over a full frame
     * costs tens of milliseconds each and there are several frames per attempt.
     *
     * @param  array{0:float,1:float,2:float,3:float}  $box  face box in image pixels
     * @return array{face:array{0:float,1:float,2:float},bg:array{0:float,1:float,2:float},hash:string}|null
     */
    public function measure(string $binary, array $box): ?array
    {
        $img = @imagecreatefromstring($binary);

        if ($img === false) {
            return null;
        }

        try {
            $w = imagesx($img);
            $h = imagesy($img);

            [$x1, $y1, $x2, $y2] = $box;
            $x1 = max(0, (int) $x1);
            $y1 = max(0, (int) $y1);
            $x2 = min($w, (int) $x2);
            $y2 = min($h, (int) $y2);

            if ($x2 - $x1 < 8 || $y2 - $y1 < 8) {
                return null;
            }

            $face = $this->meanOf($img, $x1, $y1, $x2 - $x1, $y2 - $y1);

            // Background: the whole frame averaged, then the face's contribution
            // removed. Cheaper and steadier than sampling corner patches, which
            // can land on a dark doorway and skew the differential.
            $whole = $this->meanOf($img, 0, 0, $w, $h);
            $faceArea = ($x2 - $x1) * ($y2 - $y1);
            $share = min(0.9, $faceArea / max(1, $w * $h));

            $bg = [];
            for ($c = 0; $c < 3; $c++) {
                $bg[$c] = max(0.0, ($whole[$c] - $face[$c] * $share) / max(0.1, 1 - $share));
            }

            return [
                'face' => $face,
                'bg'   => $bg,
                // A coarse perceptual fingerprint, used only to notice that two
                // "different" frames are in fact the same picture.
                'hash' => $this->fingerprint($img),
            ];
        } finally {
            imagedestroy($img);
        }
    }

    /** Mean R, G, B of a region, averaged by GD rather than in PHP. */
    private function meanOf($img, int $x, int $y, int $w, int $h): array
    {
        $small = imagecreatetruecolor(4, 4);
        imagecopyresampled($small, $img, 0, 0, $x, $y, 4, 4, $w, $h);

        $sum = [0.0, 0.0, 0.0];

        for ($py = 0; $py < 4; $py++) {
            for ($px = 0; $px < 4; $px++) {
                $rgb = imagecolorat($small, $px, $py);
                $sum[0] += ($rgb >> 16) & 255;
                $sum[1] += ($rgb >> 8) & 255;
                $sum[2] += $rgb & 255;
            }
        }

        imagedestroy($small);

        return [$sum[0] / 16, $sum[1] / 16, $sum[2] / 16];
    }

    /** 8x8 greyscale average hash — enough to spot a re-sent frame. */
    private function fingerprint($img): string
    {
        $small = imagecreatetruecolor(8, 8);
        imagecopyresampled($small, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));

        $vals = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $vals[] = (($rgb >> 16 & 255) * 0.299) + (($rgb >> 8 & 255) * 0.587) + (($rgb & 255) * 0.114);
            }
        }

        imagedestroy($small);

        $mean = array_sum($vals) / count($vals);
        $bits = '';
        foreach ($vals as $v) {
            $bits .= $v >= $mean ? '1' : '0';
        }

        return $bits;
    }

    /**
     * Does the measured response match the sequence the server issued?
     *
     * @param  array<int,string>  $sequence  segment names, in the order issued
     * @param  array<int,array>   $measured  measure() output per frame, same order
     * @return array{ok:bool,reason:?string,detail:array}
     */
    public function verify(array $sequence, array $measured): array
    {
        $limits = (array) config('face.liveness_flash_frames', []);

        if (count($measured) !== count($sequence) || count($sequence) < 2) {
            return $this->fail('frame_count', ['expected' => count($sequence), 'got' => count($measured)]);
        }

        $lumaOf = fn (array $rgb) => $rgb[0] * 0.299 + $rgb[1] * 0.587 + $rgb[2] * 0.114;

        $byName = [];
        foreach ($sequence as $i => $name) {
            if (! isset($measured[$i]['face'])) {
                return $this->fail('frame_unreadable', ['index' => $i]);
            }
            $byName[$name][] = $measured[$i];
        }

        $detail = [];

        // ---- 1. The face is brighter under a bright segment than a dark one.
        if (isset($byName['white'], $byName['dark'])) {
            $lit = $lumaOf($byName['white'][0]['face']);
            $unlit = $lumaOf($byName['dark'][0]['face']);
            $detail['flash_delta'] = round($lit - $unlit, 2);

            if ($lit - $unlit < (float) ($limits['min_delta'] ?? 6.0)) {
                return $this->fail('no_flash_response', $detail);
            }

            // ---- 2. The face brightens MORE than the background does.
            // Light falls off with distance, so the screen lights a real head
            // far more than the wall behind it. A flat photo held up to the lens
            // brightens uniformly, because every part of it is the same distance
            // away — which is what this separates.
            $faceGain = $lit - $unlit;
            $bgGain = $lumaOf($byName['white'][0]['bg']) - $lumaOf($byName['dark'][0]['bg']);
            $detail['face_bg_delta'] = round($faceGain - $bgGain, 2);

            if ($faceGain - $bgGain < (float) ($limits['min_face_bg_delta'] ?? 3.0)) {
                return $this->fail('flat_surface', $detail);
            }
        }

        // ---- 3. Hue response: does a channel's share of the face RISE when the
        // screen turns that colour?
        //
        // MEASURED AGAINST THE FACE'S OWN COLOUR, not against a flat 1/3.
        //
        // This used to compare the share to a fixed 1/3 baseline and demand it
        // come out above it. That is wrong for the only subject this system ever
        // sees. Human skin is strongly red-dominant, so under a GREEN segment a
        // real face's green share still sits below a third — red keeps leading —
        // and the check refused every live employee whenever the randomly drawn
        // colour was green or blue. A red draw passed, which is what made it
        // look intermittent rather than broken.
        //
        // The physical question was never "is this channel over a third of the
        // face". It is "did this channel GAIN when the screen turned that
        // colour", which is skin-tone independent: whatever the face's natural
        // balance, the screen's light has to shift it. The baseline is therefore
        // the same channel's share under the segments that are NOT that colour
        // (white and dark are ideal — neutral light of two intensities).
        $hueChecked = 0;

        foreach (self::HUE as $colour => $channel) {
            if (! isset($byName[$colour])) {
                continue;
            }

            $rgb = $byName[$colour][0]['face'];
            $total = array_sum($rgb);

            if ($total <= 0) {
                return $this->fail('frame_unreadable', ['segment' => $colour]);
            }

            $baseline = $this->baselineShare($byName, $colour, $channel);

            // Nothing to compare against — a sequence of one colour and nothing
            // else. issueFlash() always seeds white and dark so this should not
            // arise, but guessing a baseline would be worse than not testing.
            if ($baseline === null) {
                continue;
            }

            $share = $rgb[$channel] / $total;
            $shift = $share - $baseline;

            $detail["hue_{$colour}"] = round($shift, 4);
            $detail["hue_{$colour}_base"] = round($baseline, 4);
            $hueChecked++;

            if ($shift < (float) ($limits['min_hue_shift'] ?? 0.02)) {
                return $this->fail('no_colour_response', $detail);
            }
        }

        $detail['hue_segments_checked'] = $hueChecked;

        // ---- 4. The frames are genuinely different pictures. A still image
        // resubmitted under different segment labels fails here even if the
        // attacker also tampers with the brightness.
        $hashes = array_filter(array_column($measured, 'hash'));

        if (count($hashes) !== count(array_unique($hashes))) {
            return $this->fail('duplicate_frames', $detail);
        }

        return ['ok' => true, 'reason' => null, 'detail' => $detail];
    }

    /**
     * The face's natural share of one channel, taken from every segment that is
     * NOT the colour being tested.
     *
     * White and dark carry it in practice: they are neutral light at two
     * intensities, so they describe the subject's own colouring rather than
     * anything the screen imposed. Averaging both means a face that happens to
     * be over- or under-exposed in one of them does not skew the baseline.
     *
     * @param  array<string, array<int, array>>  $byName  measurements per segment
     * @return float|null  null when there is nothing to compare against
     */
    private function baselineShare(array $byName, string $colour, int $channel): ?float
    {
        $shares = [];

        foreach ($byName as $segment => $frames) {
            if ($segment === $colour) {
                continue;
            }

            foreach ($frames as $frame) {
                $rgb   = $frame['face'] ?? null;
                $total = is_array($rgb) ? array_sum($rgb) : 0;

                if ($total > 0) {
                    $shares[] = $rgb[$channel] / $total;
                }
            }
        }

        if (! $shares) {
            return null;
        }

        return array_sum($shares) / count($shares);
    }

    /** Operator-facing wording. */
    public function explain(?string $reason): string
    {
        return [
            'frame_count'        => 'The captured frames did not match the challenge. Please try again.',
            'frame_unreadable'   => 'A captured frame could not be read. Please try again.',
            'no_flash_response'  => 'The face did not react to the screen light. A photograph or a screen cannot be used.',
            'flat_surface'       => 'The image looks flat rather than three-dimensional. Please face the camera directly.',
            'no_colour_response' => 'The face did not react to the screen colours. A photograph or a recording cannot be used.',
            'duplicate_frames'   => 'The same image was submitted more than once.',
        ][$reason] ?? 'Liveness could not be confirmed. Please try again.';
    }

    private function fail(string $reason, array $detail = []): array
    {
        return ['ok' => false, 'reason' => $reason, 'detail' => $detail];
    }
}
