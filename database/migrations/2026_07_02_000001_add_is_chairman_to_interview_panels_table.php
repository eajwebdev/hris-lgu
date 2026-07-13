<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_panels', function (Blueprint $table) {
            $table->boolean('is_chairman')->default(false)->after('emp_id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_panels', function (Blueprint $table) {
            $table->dropColumn('is_chairman');
        });
    }
};
