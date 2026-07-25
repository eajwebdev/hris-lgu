<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spms_opcrs', function (Blueprint $table) {
            if (!Schema::hasColumn('spms_opcrs', 'prepared_by_name')) {
                $table->string('prepared_by_name')->nullable()->default('LUCRECIA C. NICOLAS, MAEd');
                $table->string('prepared_by_position')->nullable()->default('MGDH-I (GSO)/HRMO-Designate');
                $table->text('pmt_members')->nullable();
                $table->string('approved_by_name')->nullable()->default('ERNIE T. UY, RN, JD');
                $table->string('approved_by_position')->nullable()->default('Municipal Mayor / Head of Agency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spms_opcrs', function (Blueprint $table) {
            $table->dropColumn([
                'prepared_by_name',
                'prepared_by_position',
                'pmt_members',
                'approved_by_name',
                'approved_by_position',
            ]);
        });
    }
};
