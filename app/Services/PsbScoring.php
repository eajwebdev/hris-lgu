<?php

namespace App\Services;

/**
 * The arithmetic behind the two Personnel Selection Board forms.
 *
 * Every weight table here is asserted to total 100 by
 * tests/Feature/PsbScoringTest.php, because a set that quietly totals 95 or 105
 * produces a ranking nobody can defend at an audit.
 *
 * Two separate 100-point scales are in play, and they are not the same thing:
 *
 *   INTERVIEW FORM        seven traits, each rated 1-10 by each panel member,
 *                         weighted 10/10/20/20/10/15/15 to a 100-point total.
 *                         Panel members are averaged, not summed.
 *
 *   COMPARATIVE ASSESSMENT  the preliminary evaluation, six components weighted
 *                         35/15/10/20/10/10 to a 100-point total. The interview
 *                         result above feeds the 10-point Potential component;
 *                         it is not added on top.
 */
class PsbScoring
{
    /** Interview Form. Keys are the stored score keys; values are percent weights. */
    public const INTERVIEW_WEIGHTS = [
        'communication_skill' => 10,
        'appearance'          => 10,
        'alertness'           => 20,
        'present_ideas'       => 20,
        'judgment'            => 10,
        'emotional_stability' => 15,
        'self_confidence'     => 15,
    ];

    /** Printed column headings, in the order the form lays them out. */
    public const INTERVIEW_LABELS = [
        'communication_skill' => 'COMMUNICATION SKILL',
        'appearance'          => 'APPEARANCE',
        'alertness'           => 'ALERTNESS',
        'present_ideas'       => 'ABILITY TO PRESENT IDEAS',
        'judgment'            => 'JUDGMENT',
        'emotional_stability' => 'EMOTIONAL STABILITY',
        'self_confidence'     => 'SELF-CONFIDENCE',
    ];

    /** Each trait is rated on a 1-10 scale. */
    public const INTERVIEW_MAX_RAW = 10;

    /** Comparative Assessment Form, preliminary evaluation. */
    public const ASSESSMENT_WEIGHTS = [
        'performance_rating' => 35,
        'education_points'   => 15,
        'training_points'    => 10,
        'experience_points'  => 20,
        'potential_points'   => 10,
        'psychosocial_points' => 10,
    ];

    public const ASSESSMENT_LABELS = [
        'performance_rating'  => 'PERFORMANCE RATING',
        'education_points'    => 'EDUCATION',
        'training_points'     => 'TRAINING',
        'experience_points'   => 'EXPERIENCE',
        'potential_points'    => 'POTENTIAL',
        'psychosocial_points' => 'PSYCHOSOCIAL ATTRIBUTES',
    ];

    /**
     * One panel member's interview score, out of 100.
     *
     * A trait left unrated contributes 0 rather than being dropped, so a
     * half-finished sheet cannot outrank a complete one.
     */
    public function interviewTotal(array $rawScores): float
    {
        $total = 0.0;

        foreach (self::INTERVIEW_WEIGHTS as $key => $weight) {
            $raw = (float) ($rawScores[$key] ?? 0);
            $raw = max(0, min($raw, self::INTERVIEW_MAX_RAW));
            $total += ($raw / self::INTERVIEW_MAX_RAW) * $weight;
        }

        return round($total, 2);
    }

    /** The same, per trait, for showing the breakdown on screen. */
    public function interviewBreakdown(array $rawScores): array
    {
        $out = [];

        foreach (self::INTERVIEW_WEIGHTS as $key => $weight) {
            $raw = max(0, min((float) ($rawScores[$key] ?? 0), self::INTERVIEW_MAX_RAW));
            $out[$key] = [
                'label'    => self::INTERVIEW_LABELS[$key],
                'weight'   => $weight,
                'raw'      => $raw,
                'weighted' => round(($raw / self::INTERVIEW_MAX_RAW) * $weight, 2),
            ];
        }

        return $out;
    }

    /**
     * The board's interview score for a candidate: the mean of the panel, not
     * the sum. Five panellists must not produce a 500-point candidate.
     */
    public function panelAverage(array $memberTotals): float
    {
        $memberTotals = array_values(array_filter(
            $memberTotals,
            fn ($v) => $v !== null && $v !== ''
        ));

        if ($memberTotals === []) {
            return 0.0;
        }

        return round(array_sum(array_map('floatval', $memberTotals)) / count($memberTotals), 2);
    }

    /**
     * Convert a score held on one scale onto another.
     *
     * Used to bring a feeder result into its Comparative Assessment column —
     * e.g. an interview total out of 100 becoming the 10-point Potential
     * component, or a 5-point IPCR rating becoming the 35-point Performance
     * component.
     */
    public function rescale(?float $score, float $fromMax, float $toMax): float
    {
        if ($score === null || $fromMax <= 0) {
            return 0.0;
        }

        $score = max(0, min($score, $fromMax));

        return round(($score / $fromMax) * $toMax, 2);
    }

    /**
     * Preliminary evaluation total, out of 100.
     *
     * Each component is already expressed in its own weighted points (a
     * 15-point Education column holds 0-15), so this sums and clamps rather
     * than re-weighting.
     */
    public function preliminaryTotal(array $components): float
    {
        $total = 0.0;

        foreach (self::ASSESSMENT_WEIGHTS as $key => $weight) {
            $value = (float) ($components[$key] ?? 0);
            $total += max(0, min($value, $weight));
        }

        return round($total, 2);
    }

    /**
     * Overall points = preliminary evaluation + further assessment.
     *
     * The written exam / skills test is an extra column on the form rather than
     * part of the 100, so the overall figure can exceed 100 by design. Ranking
     * uses this number.
     */
    public function overallPoints(float $preliminaryTotal, ?float $furtherAssessment): float
    {
        return round($preliminaryTotal + (float) ($furtherAssessment ?? 0), 2);
    }

    /**
     * Rank rows by overall points, highest first.
     *
     * Ties share a rank and the next rank skips accordingly (1, 2, 2, 4) — the
     * standard competition ranking, so two equal candidates are not arbitrarily
     * ordered by whoever was keyed in first.
     *
     * @param  array<int|string, float>  $overallByKey
     * @return array<int|string, int>
     */
    public function rank(array $overallByKey): array
    {
        arsort($overallByKey);

        $ranks = [];
        $position = 0;
        $seen = 0;
        $previous = null;

        foreach ($overallByKey as $key => $points) {
            $seen++;
            $points = round((float) $points, 2);

            if ($previous === null || $points < $previous) {
                $position = $seen;
                $previous = $points;
            }

            $ranks[$key] = $position;
        }

        return $ranks;
    }

    /** Guards against a weight table being edited into something that is not 100. */
    public static function assertWeightsAreWhole(): void
    {
        foreach (['INTERVIEW_WEIGHTS' => self::INTERVIEW_WEIGHTS,
                  'ASSESSMENT_WEIGHTS' => self::ASSESSMENT_WEIGHTS] as $name => $weights) {
            $sum = array_sum($weights);
            if ((int) $sum !== 100) {
                throw new \LogicException("PsbScoring::{$name} totals {$sum}%, not 100%.");
            }
        }
    }
}
