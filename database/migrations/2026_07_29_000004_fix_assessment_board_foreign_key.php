<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add the foreign key that comparative_assessment_board_members was created
 * without.
 *
 * Deleting an assessment left its signatories behind. Beyond the clutter that
 * is a correctness problem: comparative_assessment_id is an AUTO_INCREMENT
 * value, so a later assessment landing on a freed id would silently inherit a
 * deleted sheet's board and print the wrong names under a signed form.
 *
 * Orphans are removed first — an orphan cannot be reattached to anything, and
 * the constraint will not apply while they exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comparative_assessment_board_members')) {
            return;
        }

        if ($this->hasForeignKey()) {
            return;
        }

        DB::table('comparative_assessment_board_members')
            ->whereNotIn('comparative_assessment_id', function ($q) {
                $q->select('id')->from('comparative_assessments');
            })
            ->delete();

        // Named explicitly. The name Laravel derives from this table and column
        // is 70 characters, past MySQL's 64-character identifier limit, which
        // is why the key was missing in the first place.
        Schema::table('comparative_assessment_board_members', function (Blueprint $table) {
            $table->foreign('comparative_assessment_id', 'cabm_assessment_fk')
                ->references('id')->on('comparative_assessments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('comparative_assessment_board_members') || ! $this->hasForeignKey()) {
            return;
        }

        Schema::table('comparative_assessment_board_members', function (Blueprint $table) {
            $table->dropForeign('cabm_assessment_fk');
        });
    }

    private function hasForeignKey(): bool
    {
        return count(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ?',
            [DB::getDatabaseName(), 'comparative_assessment_board_members']
        )) > 0;
    }
};
