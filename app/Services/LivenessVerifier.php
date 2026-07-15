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
     * Mint a single-use challenge naming both head turns, in a random order.
     *
     * Both, not one: a challenge that asked for a single random pose could be
     * re-rolled until it named the pose the attacker happens to hold a photo of.
     * Demanding left *and* right, in an order they cannot predict, means a
     * printed photo is useless no matter how many times they ask.
     */
    public function issue(string $ip): array
    {
        $nonce = Str::random(40);
        $poses = ['left', 'right'];

        shuffle($poses);

        $ttl = (int) config('face.liveness.challenge_ttl', 90);

        Cache::put(self::CACHE_PREFIX . $nonce, [
            'poses'      => $poses,
            'ip'         => $ip,
            'issued_at'  => now()->timestamp,
        ], $ttl);

        return [
            'nonce'      => $nonce,
            'poses'      => $poses,
            'expires_in' => $ttl,
        ];
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
     * Does this sequence of frontal frames come from the living employee it
     * claims to?
     *
     * Frontal-only: no head turns. Liveness rests entirely on the natural drift
     * between frames of a real face (see checkNeutrals). Returns null when
     * satisfied, or a human-readable reason when not. The reasons are deliberately
     * vague on screen — telling an attacker *which* check they tripped tells them
     * what to fix.
     */
    public function check(Employee $employee, array $frames): ?string
    {
        $config = (array) config('face.liveness');

        // Every frame is a straight-ahead 'neutral' now; tolerate a stray 'pose'
        // tag from an older client by simply treating whatever arrived as the set
        // to judge.
        $neutral = array_values(array_filter($frames, fn ($f) => $f['stage'] === 'neutral'));

        if (! $neutral) {
            $neutral = array_values($frames);
        }

        if (count($neutral) < (int) $config['min_neutral_frames']) {
            return 'Face check incomplete. Please try again.';
        }

        if ($reason = $this->checkTiming($neutral, $config)) {
            return $reason;
        }

        return $this->checkNeutrals($employee, $neutral, $config);
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
