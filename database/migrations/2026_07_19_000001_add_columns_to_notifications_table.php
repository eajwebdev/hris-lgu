<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'empid')) {
                    $table->string('empid')->nullable()->after('id');
                }
                if (!Schema::hasColumn('notifications', 'lapp_id')) {
                    $table->unsignedBigInteger('lapp_id')->nullable()->after('empid');
                }
                if (!Schema::hasColumn('notifications', 'esign_id')) {
                    $table->unsignedBigInteger('esign_id')->nullable()->after('lapp_id');
                }
                if (!Schema::hasColumn('notifications', 'category')) {
                    $table->integer('category')->nullable()->after('esign_id');
                }
                if (!Schema::hasColumn('notifications', 'utype')) {
                    $table->string('utype')->nullable()->after('category');
                }
                if (!Schema::hasColumn('notifications', 'module')) {
                    $table->string('module')->nullable()->after('utype');
                }
                if (!Schema::hasColumn('notifications', 'status')) {
                    $table->integer('status')->default(0)->after('module');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $columns = ['empid', 'lapp_id', 'esign_id', 'category', 'utype', 'module', 'status'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('notifications', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
