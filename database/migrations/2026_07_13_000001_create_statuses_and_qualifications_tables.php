<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statuses and qualifications used to live in the separate PMS database.
 * The HRIS now runs on a single database, so they are created here.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('statuses')) {
            Schema::create('statuses', function (Blueprint $table) {
                $table->id();
                $table->string('status_name');
                $table->timestamps();
            });

            // Employment statuses for an LGU plantilla. 'Part-time/JO' is kept
            // because the employee forms filter it out and pair it with a
            // qualification instead.
            $now = now();
            DB::table('statuses')->insert(array_map(fn ($name) => [
                'status_name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'Permanent',
                'Casual',
                'Co-terminous',
                'Contractual',
                'Elective',
                'Job Order',
                'Part-time/JO',
            ]));
        }

        if (!Schema::hasTable('qualifications')) {
            Schema::create('qualifications', function (Blueprint $table) {
                $table->id();
                $table->string('qualification');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('statuses');
    }
};
