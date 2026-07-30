<?php

use App\Services\PsbScoring;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bring saved interview ratings onto the PSB Interview Form.
 *
 * Two things change for rows written before this:
 *
 *   1. The first trait was stored as `voice_speech`; the form calls it
 *      COMMUNICATION SKILL, so the JSON key is renamed. Left alone, every
 *      historical rating would read as an unrated first trait.
 *
 *   2. interview_total held a raw sum of seven 1-10 scores (max 70). The form
 *      weights the traits 10/10/20/20/10/15/15 to a 100-point total, so the
 *      stored figure is recomputed from the raw scores that are already there.
 *
 * The raw per-trait scores are never touched — only the key they are filed
 * under and the total derived from them — so no panellist's judgement is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('interview_ratings')) {
            return;
        }

        $psb = new PsbScoring();

        DB::table('interview_ratings')->orderBy('id')->chunkById(200, function ($rows) use ($psb) {
            foreach ($rows as $row) {
                $scores = json_decode($row->interview_scores ?? '[]', true);

                if (! is_array($scores)) {
                    $scores = [];
                }

                // 1. Rename the trait, keeping its value.
                if (array_key_exists('voice_speech', $scores)) {
                    $scores['communication_skill'] = $scores['communication_skill'] ?? $scores['voice_speech'];
                    unset($scores['voice_speech']);
                }

                // 2. Recompute the total under the form's weights.
                $total = $psb->interviewTotal($scores);

                DB::table('interview_ratings')->where('id', $row->id)->update([
                    'interview_scores' => json_encode($scores),
                    'interview_total'  => $total,
                    // total_score is the working sum the interview screen shows
                    // alongside the potential rubric; keep the two consistent.
                    'total_score'      => $total + (float) $row->potential_total,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('interview_ratings')) {
            return;
        }

        DB::table('interview_ratings')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $scores = json_decode($row->interview_scores ?? '[]', true);

                if (! is_array($scores)) {
                    continue;
                }

                if (array_key_exists('communication_skill', $scores)) {
                    $scores['voice_speech'] = $scores['communication_skill'];
                    unset($scores['communication_skill']);
                }

                // Back to the unweighted sum of the seven 1-10 scores.
                $raw = array_sum(array_map('floatval', $scores));

                DB::table('interview_ratings')->where('id', $row->id)->update([
                    'interview_scores' => json_encode($scores),
                    'interview_total'  => $raw,
                    'total_score'      => $raw + (float) $row->potential_total,
                ]);
            }
        });
    }
};
