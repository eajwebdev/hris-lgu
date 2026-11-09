<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The e-signature is stored as the encrypted PNG (Crypt::encrypt of the file
 * bytes, then base64) — thousands of characters for even a tiny image. The
 * column was a VARCHAR(255), so every upload failed with "Data too long",
 * nobody had a signature, and no leave application could be signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'esign')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->longText('esign')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Not narrowed back: that would truncate every stored signature.
    }
};
