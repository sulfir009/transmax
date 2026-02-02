<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_clients';

        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'bonus_balance_cents')) {
                $table->unsignedBigInteger('bonus_balance_cents')->default(0);
            }
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_clients';

        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'bonus_balance_cents')) {
                $table->dropColumn('bonus_balance_cents');
            }
        });
    }
};