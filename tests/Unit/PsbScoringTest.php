<?php

namespace Tests\Unit;

use App\Services\PsbScoring;
use PHPUnit\Framework\TestCase;

/**
 * The two PSB forms are scoring instruments, so the arithmetic is the part
 * that has to be right. These lock down the weights and every edge the
 * ranking depends on.
 */
class PsbScoringTest extends TestCase
{
    private PsbScoring $psb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->psb = new PsbScoring();
    }

    /** Both weight tables must total exactly 100. */
    public function test_weight_tables_total_one_hundred(): void
    {
        $this->assertSame(100, array_sum(PsbScoring::INTERVIEW_WEIGHTS));
        $this->assertSame(100, array_sum(PsbScoring::ASSESSMENT_WEIGHTS));

        PsbScoring::assertWeightsAreWhole();
        $this->addToAssertionCount(1);
    }

    /** The weights are the ones printed on the Interview Form. */
    public function test_interview_weights_match_the_printed_form(): void
    {
        $this->assertSame([
            'communication_skill' => 10,
            'appearance'          => 10,
            'alertness'           => 20,
            'present_ideas'       => 20,
            'judgment'            => 10,
            'emotional_stability' => 15,
            'self_confidence'     => 15,
        ], PsbScoring::INTERVIEW_WEIGHTS);
    }

    /** The weights are the ones printed on the Comparative Assessment Form. */
    public function test_assessment_weights_match_the_printed_form(): void
    {
        $this->assertSame([
            'performance_rating'  => 35,
            'education_points'    => 15,
            'training_points'     => 10,
            'experience_points'   => 20,
            'potential_points'    => 10,
            'psychosocial_points' => 10,
        ], PsbScoring::ASSESSMENT_WEIGHTS);
    }

    public function test_a_perfect_interview_scores_exactly_one_hundred(): void
    {
        $perfect = array_fill_keys(array_keys(PsbScoring::INTERVIEW_WEIGHTS), 10);

        $this->assertSame(100.0, $this->psb->interviewTotal($perfect));
    }

    public function test_a_midpoint_interview_scores_exactly_half(): void
    {
        $mid = array_fill_keys(array_keys(PsbScoring::INTERVIEW_WEIGHTS), 5);

        $this->assertSame(50.0, $this->psb->interviewTotal($mid));
    }

    /** A trait is worth its weight, not an equal share. */
    public function test_each_trait_contributes_its_own_weight(): void
    {
        // Alertness alone, rated 10/10, is worth its full 20 points.
        $this->assertSame(20.0, $this->psb->interviewTotal(['alertness' => 10]));

        // Judgment alone, rated 10/10, is worth only 10.
        $this->assertSame(10.0, $this->psb->interviewTotal(['judgment' => 10]));
    }

    public function test_unrated_traits_count_as_zero_not_as_absent(): void
    {
        // Only appearance rated: 10/10 of a 10-point trait = 10, not 100.
        $this->assertSame(10.0, $this->psb->interviewTotal(['appearance' => 10]));
        $this->assertSame(0.0, $this->psb->interviewTotal([]));
    }

    public function test_raw_scores_are_clamped_to_the_one_to_ten_scale(): void
    {
        $over = array_fill_keys(array_keys(PsbScoring::INTERVIEW_WEIGHTS), 99);
        $under = array_fill_keys(array_keys(PsbScoring::INTERVIEW_WEIGHTS), -5);

        $this->assertSame(100.0, $this->psb->interviewTotal($over));
        $this->assertSame(0.0, $this->psb->interviewTotal($under));
    }

    public function test_interview_breakdown_sums_to_the_total(): void
    {
        $scores = [
            'communication_skill' => 8, 'appearance' => 7, 'alertness' => 9,
            'present_ideas' => 6, 'judgment' => 10, 'emotional_stability' => 5,
            'self_confidence' => 7,
        ];

        $breakdown = $this->psb->interviewBreakdown($scores);
        $sum = round(array_sum(array_column($breakdown, 'weighted')), 2);

        $this->assertSame($this->psb->interviewTotal($scores), $sum);

        // Hand-checked: 8+7+18+12+10+7.5+10.5
        $this->assertSame(73.0, $sum);
    }

    /** Panel members are averaged. Five raters must not yield 500 points. */
    public function test_panel_members_are_averaged_not_summed(): void
    {
        $this->assertSame(80.0, $this->psb->panelAverage([80, 80, 80, 80, 80]));
        $this->assertSame(70.0, $this->psb->panelAverage([60, 80]));
        $this->assertSame(0.0, $this->psb->panelAverage([]));
    }

    public function test_panel_average_ignores_panellists_who_have_not_rated(): void
    {
        // Two of four submitted; the average is of those two.
        $this->assertSame(75.0, $this->psb->panelAverage([70, null, 80, '']));
    }

    /** A feeder score moves onto its column's scale. */
    public function test_rescale_moves_a_score_between_scales(): void
    {
        // An interview total of 73/100 becomes the 10-point Potential column.
        $this->assertSame(7.3, $this->psb->rescale(73, 100, 10));

        // A 4.5/5 IPCR becomes the 35-point Performance column.
        $this->assertSame(31.5, $this->psb->rescale(4.5, 5, 35));

        $this->assertSame(0.0, $this->psb->rescale(null, 5, 35));
        $this->assertSame(0.0, $this->psb->rescale(3, 0, 35));
    }

    public function test_preliminary_total_sums_the_six_components(): void
    {
        $full = [
            'performance_rating' => 35, 'education_points' => 15,
            'training_points' => 10, 'experience_points' => 20,
            'potential_points' => 10, 'psychosocial_points' => 10,
        ];

        $this->assertSame(100.0, $this->psb->preliminaryTotal($full));
    }

    /** No component may exceed its own weight, however it was keyed in. */
    public function test_a_component_cannot_exceed_its_weight(): void
    {
        $over = [
            'performance_rating' => 999, 'education_points' => 999,
            'training_points' => 999, 'experience_points' => 999,
            'potential_points' => 999, 'psychosocial_points' => 999,
        ];

        $this->assertSame(100.0, $this->psb->preliminaryTotal($over));
    }

    public function test_overall_points_add_the_further_assessment(): void
    {
        $this->assertSame(92.5, $this->psb->overallPoints(82.5, 10));
        $this->assertSame(82.5, $this->psb->overallPoints(82.5, null));
    }

    public function test_ranking_orders_by_overall_points_highest_first(): void
    {
        $ranks = $this->psb->rank(['ana' => 88.0, 'ben' => 92.5, 'cruz' => 71.25]);

        $this->assertSame(1, $ranks['ben']);
        $this->assertSame(2, $ranks['ana']);
        $this->assertSame(3, $ranks['cruz']);
    }

    /** Ties share a rank and the next rank skips: 1, 2, 2, 4. */
    public function test_tied_candidates_share_a_rank_and_the_next_one_skips(): void
    {
        $ranks = $this->psb->rank([
            'a' => 95.0, 'b' => 88.0, 'c' => 88.0, 'd' => 70.0,
        ]);

        $this->assertSame(1, $ranks['a']);
        $this->assertSame(2, $ranks['b']);
        $this->assertSame(2, $ranks['c']);
        $this->assertSame(4, $ranks['d']);
    }

    /**
     * End to end, the way the board actually works it: panel interview feeds
     * the 10-point Potential column, and the sheet totals 100 before the
     * written exam is added.
     */
    public function test_a_full_candidate_walkthrough(): void
    {
        $panelA = $this->psb->interviewTotal([
            'communication_skill' => 8, 'appearance' => 8, 'alertness' => 9,
            'present_ideas' => 8, 'judgment' => 8, 'emotional_stability' => 8,
            'self_confidence' => 8,
        ]);   // 8 + 8 + 18 + 16 + 8 + 12 + 12 = 82.0
        $panelB = $this->psb->interviewTotal([
            'communication_skill' => 7, 'appearance' => 7, 'alertness' => 7,
            'present_ideas' => 7, 'judgment' => 7, 'emotional_stability' => 7,
            'self_confidence' => 7,
        ]);   // a flat 7/10 across every trait = 70% of 100

        $this->assertSame(82.0, $panelA);
        $this->assertSame(70.0, $panelB);

        $interview = $this->psb->panelAverage([$panelA, $panelB]);
        $this->assertSame(76.0, $interview);

        $potential = $this->psb->rescale($interview, 100, 10);
        $this->assertSame(7.6, $potential);

        $preliminary = $this->psb->preliminaryTotal([
            'performance_rating'  => 31.5,
            'education_points'    => 13.0,
            'training_points'     => 9.0,
            'experience_points'   => 18.0,
            'potential_points'    => $potential,
            'psychosocial_points' => 8.0,
        ]);
        $this->assertSame(87.1, $preliminary);
        $this->assertLessThanOrEqual(100.0, $preliminary);

        $overall = $this->psb->overallPoints($preliminary, 12.0);
        $this->assertSame(99.1, $overall);
    }
}
