<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Proving there is a person in front of the camera, not a picture of one.
 *
 * The browser cannot be trusted to answer that question — anyone can open the
 * console and claim they blinked. So nothing here believes a flag the client
 * sets. Every conclusion is drawn from face descriptors, which the client can
 * only produce by putting a real face (or a real image of one) in front of a
 * lens, and which the server compares against what HR actually enrolled.
 *
 * The one thing a photograph cannot do is turn its head when told to. That is
 * the whole basis of this check.
 */
class LivenessVerifier
{
    private const CACHE_PREFIX = 'attendance:challenge:';

    public function __construct(private FaceEmbeddingService $faces)
    {
    }

    // ---------------------------------------------------------------- challenge

    /**
     * Mint a single-use challenge naming a random sequence of head gestures.
     *
     * Several distinct gestures in a shuffled order, not one: a challenge that
     * asked for a single random pose could be re-rolled until it named the pose
     * the attacker happens to hold a photo of. Demanding a sequence they cannot
     * predict means a printed photo or a phone screen is useless no matter how
     * many times they ask.
     */
    public function issue(string $ip): array
    {
        $nonce = Str::random(40);

        $pool  = (array) config('face.liveness.pose_pool', ['left', 'right']);
        $count = min((int) config('face.liveness.pose_count', 2), count($pool));

        shuffle($pool);

        $poses = array_slice($pool, 0, max(1, $count));
        $flash = $this->issueFlash();

        $ttl = (int) config('face.liveness.challenge_ttl', 90);

        Cache::put(self::CACHE_PREFIX . $nonce, [
            'poses'      => $poses,
            'flash'      => $flash,
            'ip'         => $ip,
            'issued_at'  => now()->timestamp,
        ], $ttl);

        return [
            'nonce'      => $nonce,
            'poses'      => $poses,
            'flash'      => $flash,
            'expires_in' => $ttl,
        ];
    }

    /**
     * The screen-colour sequence for this attempt.
     *
     * Seeded rather than drawn purely at random: every attempt must contain at
     * least one white, one dark and one colour, because those three carry the
     * three different checks in checkFlash(). A uniform random draw would now
     * and then produce an all-white sequence that tests almost nothing. The
     * remainder is filled at random and the whole thing shuffled, so the ORDER
     * — the part an attacker would have to predict — stays unguessable.
     *
     * 0 is the kill switch; any nonzero count is raised to 3 so the seeded
     * trio always fits.
     */
    private function issueFlash(): array
    {
        $count = (int) config('face.liveness.flash_count', 4);

        if ($count <= 0) {
            return [];
        }

        $palette = array_values((array) config('face.liveness.flash_palette', ['white', 'dark', 'red', 'green', 'blue']));
        $colours = array_values(array_diff($palette, ['white', 'dark']));

        $flash = ['white', 'dark'];

        if ($colours) {
            $flash[] = $colours[array_rand($colours)];
        }

        for ($i = count($flash); $i < max(3, $count); $i++) {
            $flash[] = $palette[array_rand($palette)];
        }

        shuffle($flash);

        return $flash;
    }

    /**
     * Redeem a challenge. Single-use: burned on the first attempt, pass or fail.
     *
     * Burning it on failure is deliberate. If a failed attempt left the nonce
     * alive, an attacker could keep firing descriptors at the same challenge
     * until something stuck.
     */
    public function redeem(?string $nonce, string $ip): ?array
    {
        if (! is_string($nonce) || $nonce === '') {
            return null;
        }

        $key       = self::CACHE_PREFIX . $nonce;
        $challenge = Cache::get($key);

        Cache::forget($key);

        if (! $challenge) {
            return null;
        }

        // Bound to the requester, so a challenge cannot be handed to someone else
        // to answer.
        if (! hash_equals((string) $challenge['ip'], $ip)) {
            return null;
        }

        return $challenge;
    }

    // ---------------------------------------------------------------- verdict

    /**
     * Does this sequence of frames come from the living employee it claims to,
     * performing the gestures this challenge demanded?
     *
     * Returns null when satisfied, or a human-readable reason when not. The
     * reasons are deliberately vague on screen — telling an attacker *which*
     * check they tripped tells them what to fix.
     */
    public function check(Employee $employee, array $frames, array $challenge = [], array $flash = []): ?string
    {
        $config = (array) config('face.liveness');

        $neutral = array_values(array_filter($frames, fn ($f) => $f['stage'] === 'neutral'));
        $posed   = array_values(array_filter($frames, fn ($f) => $f['stage'] === 'pose'));

        if (! $neutral) {
            return 'Face check incomplete. Please try again.';
        }

        if (count($neutral) < (int) $config['min_neutral_frames']) {
            return 'Face check incomplete. Please try again.';
        }

        if ($reason = $this->checkTiming($frames, $config)) {
            return $reason;
        }

        if ($reason = $this->checkNeutrals($employee, $neutral, $config)) {
            return $reason;
        }

        if ($reason = $this->checkPoses($employee, $neutral, $posed, $challenge, $config)) {
            return $reason;
        }

        return $this->checkFlash($employee, $flash, $challenge, $config);
    }

    /**
     * A human takes time to turn their head twice. A replayed payload does not.
     */
    private function checkTiming(array $frames, array $config): ?string
    {
        $times = array_column($frames, 't');

        // Monotonic: frames arriving out of order were assembled, not captured.
        $sorted = $times;
        sort($sorted);

        if ($times !== $sorted) {
            return 'Face check failed. Please try again.';
        }

        $elapsed = max($times) - min($times);

        if ($elapsed < (int) $config['min_duration_ms'] || $elapsed > (int) $config['max_duration_ms']) {
            return 'Face check failed. Please try again.';
        }

        return null;
    }

    /**
     * The straight-ahead frames must all be this employee, and they must not be
     * carbon copies of one another.
     */
    private function checkNeutrals(Employee $employee, array $neutral, array $config): ?string
    {
        foreach ($neutral as $frame) {
            if ($this->faces->verify($employee, $frame['descriptor']) === null) {
                return 'Face not recognised. Please try again.';
            }
        }

        // Two consecutive frames of a living face are never identical — the head
        // drifts, the eyes move, the sensor noise differs. A photograph held
        // still in front of the lens produces very nearly the same vector twice,
        // and that flatness is itself the tell.
        $spread = 0.0;

        for ($i = 0; $i < count($neutral); $i++) {
            for ($j = $i + 1; $j < count($neutral); $j++) {
                $spread = max($spread, $this->distance($neutral[$i]['descriptor'], $neutral[$j]['descriptor']));
            }
        }

        if ($spread < (float) $config['min_variation']) {
            return 'Please use your face, not a photo.';
        }

        return null;
    }

    /**
     * The challenged gestures, held to the letter of the challenge.
     *
     * Three things a picture cannot fake at once: the frames tagged with the
     * challenged poses must be there IN THE ISSUED ORDER (which the attacker
     * could not know before asking), every one of them must still verify as
     * this employee, and each must sit measurably away from the straight-ahead
     * master — a flat image swivelled in front of the lens produces embeddings
     * that barely move, while a real head turn moves them decisively.
     */
    private function checkPoses(Employee $employee, array $neutral, array $posed, array $challenge, array $config): ?string
    {
        $demanded = array_values((array) ($challenge['poses'] ?? []));

        // A challenge without poses (mid-rollout cache entry) falls back to the
        // neutral-drift verdict already given.
        if (! $demanded) {
            return null;
        }

        // First occurrence of each pose, in the order the frames were captured.
        usort($posed, fn ($a, $b) => $a['t'] <=> $b['t']);

        $performed = [];

        foreach ($posed as $frame) {
            $pose = $frame['pose'] ?? null;

            if ($pose !== null && ! in_array($pose, $performed, true)) {
                $performed[] = $pose;
            }
        }

        if ($performed !== $demanded) {
            return 'Face check failed. Please follow the on-screen instructions.';
        }

        $master = $this->faces->masterEmbedding(array_column($neutral, 'descriptor'));

        if ($master === null) {
            return 'Face check failed. Please try again.';
        }

        $minShift = (float) ($config['min_pose_shift'] ?? 0.05);

        foreach ($posed as $frame) {
            // Still this employee mid-gesture — enrolment includes turned
            // captures, so a genuine turn keeps verifying.
            if ($this->faces->verify($employee, $frame['descriptor']) === null) {
                return 'Face not recognised. Please try again.';
            }

            if ($this->distance($frame['descriptor'], $master) < $minShift) {
                return 'Face check failed. Please follow the on-screen instructions.';
            }
        }

        return null;
    }

    /**
     * What the screen's own light did to the subject.
     *
     * The kiosk painted a sequence of colours the client could not predict and
     * measured the face and the surrounding background under each. Three
     * separate properties are read out of those samples; see the long note in
     * config/face.php for why each one is here and what beats it alone.
     *
     * Ordered-list equality, not deduplicated like checkPoses(): the sequence
     * may legitimately repeat a colour.
     */
    private function checkFlash(Employee $employee, array $flash, array $challenge, array $config): ?string
    {
        $demanded = array_values((array) ($challenge['flash'] ?? []));

        // No sequence issued — the flash_count kill switch, or a challenge
        // minted before this shipped. Nothing to verify.
        if (! $demanded) {
            return null;
        }

        $samples = array_values((array) ($flash['samples'] ?? []));

        if (! $samples) {
            return 'The face security check did not run. Please reload the page and try again.';
        }

        usort($samples, fn ($a, $b) => $a['t'] <=> $b['t']);

        if (array_column($samples, 'seg') !== $demanded) {
            return 'Face check failed. Please follow the on-screen instructions.';
        }

        // One embedding taken during the burst, so the face the light was
        // measured on is provably the same person the poses identified — not
        // an accomplice stepping in once the gestures were done.
        $descriptor = $flash['descriptor'] ?? null;

        if (! is_array($descriptor)
            || ! $this->faces->isValidVector($descriptor)
            || $this->faces->verify($employee, $descriptor) === null) {
            return 'Face not recognised. Please try again.';
        }

        if ($reason = $this->checkFlashBrightness($samples, $config)) {
            return $reason;
        }

        return $this->checkFlashColour($samples, $config);
    }

    /**
     * Brightness, and — the part that actually stops a screen — how much more
     * the face moved than the background did.
     */
    private function checkFlashBrightness(array $samples, array $config): ?string
    {
        $faceWhite = $faceDark = $bgWhite = $bgDark = [];

        foreach ($samples as $sample) {
            $face = $this->luma($sample['face'] ?? []);
            $bg   = $this->luma($sample['bg'] ?? []);

            if ($sample['seg'] === 'white') {
                $faceWhite[] = $face;
                $bgWhite[]   = $bg;
            } elseif ($sample['seg'] === 'dark') {
                $faceDark[] = $face;
                $bgDark[]   = $bg;
            }
        }

        // issueFlash() always seeds one of each; a payload arriving without
        // both is malformed rather than merely failing.
        if (! $faceWhite || ! $faceDark) {
            return 'Face check failed. Please try again.';
        }

        $faceDelta = $this->mean($faceWhite) - $this->mean($faceDark);

        if ($faceDelta < (float) ($config['min_flash_delta'] ?? 8)) {
            return 'Please use your real face, not a photo or screen.';
        }

        // The screen is far closer to the face than to whatever is behind it,
        // so its light has to land unevenly. A phone held up to the lens fills
        // the frame with its own emission: face and background then carry the
        // same light and rise together, and this gap disappears.
        $bgDelta = ($bgWhite && $bgDark) ? $this->mean($bgWhite) - $this->mean($bgDark) : 0.0;

        if (($faceDelta - $bgDelta) < (float) ($config['min_face_bg_delta'] ?? 4)) {
            return 'Please use your real face, not a photo or screen.';
        }

        return null;
    }

    /**
     * Colour response: under a red screen a real face sends back
     * proportionally more red than it does on average across the sequence.
     *
     * Compared as each channel's SHARE of the face's total RGB rather than its
     * absolute value, so the test is unmoved by the camera's exposure or white
     * balance drifting during the burst — both of which scale all three
     * channels together and cancel out of the ratio.
     */
    private function checkFlashColour(array $samples, array $config): ?string
    {
        $channels = ['red' => 0, 'green' => 1, 'blue' => 2];
        $coloured = array_values(array_filter($samples, fn ($s) => isset($channels[$s['seg']])));

        // A sequence with no colour in it (an operator-trimmed palette) simply
        // does not carry this check.
        if (! $coloured) {
            return null;
        }

        $shares = [];

        foreach ($samples as $sample) {
            $shares[] = $this->channelShares($sample['face'] ?? []);
        }

        $baseline = [];

        foreach ([0, 1, 2] as $i) {
            $baseline[$i] = $this->mean(array_column($shares, $i));
        }

        foreach ($coloured as $sample) {
            $i     = $channels[$sample['seg']];
            $share = $this->channelShares($sample['face'] ?? [])[$i];

            if (($share - $baseline[$i]) < (float) ($config['min_chroma_shift'] ?? 0.03)) {
                return 'Please use your real face, not a photo or screen.';
            }
        }

        return null;
    }

    /** Rec. 709 luma from an [r, g, b] sample. */
    private function luma(array $rgb): float
    {
        return 0.2126 * (float) ($rgb[0] ?? 0)
             + 0.7152 * (float) ($rgb[1] ?? 0)
             + 0.0722 * (float) ($rgb[2] ?? 0);
    }

    /** Each channel as a fraction of the sample's total energy. */
    private function channelShares(array $rgb): array
    {
        $r = max(0.0, (float) ($rgb[0] ?? 0));
        $g = max(0.0, (float) ($rgb[1] ?? 0));
        $b = max(0.0, (float) ($rgb[2] ?? 0));

        $total = $r + $g + $b;

        if ($total <= 0.0) {
            return [0.0, 0.0, 0.0];
        }

        return [$r / $total, $g / $total, $b / $total];
    }

    private function mean(array $values): float
    {
        return $values ? array_sum($values) / count($values) : 0.0;
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Both sides are normalised here rather than at the call sites. Normalising
     * an already-unit vector is a no-op, and getting this wrong silently inflates
     * every distance — which would quietly weaken every threshold above.
     */
    private function distance(array $a, array $b): float
    {
        return sqrt($this->faces->distanceSquared(
            $this->faces->normalize(array_map('floatval', $a)),
            $this->faces->normalize(array_map('floatval', $b))
        ));
    }
}
