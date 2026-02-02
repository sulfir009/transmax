<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_orders';

        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'bonus_redeemed_cents')) {
                $table->unsignedBigInteger('bonus_redeemed_cents')->default(0);
            }
            if (!Schema::hasColumn($table->getTable(), 'bonus_cashback_cents')) {
                $table->unsignedBigInteger('bonus_cashback_cents')->default(0);
            }
            if (!Schema::hasColumn($table->getTable(), 'bonus_use_requested')) {
                $table->boolean('bonus_use_requested')->default(false);
            }
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_orders';

        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'bonus_redeemed_cents')) {
                $table->dropColumn('bonus_redeemed_cents');
            }
            if (Schema::hasColumn($table->getTable(), 'bonus_cashback_cents')) {
                $table->dropColumn('bonus_cashback_cents');
            }
            if (Schema::hasColumn($table->getTable(), 'bonus_use_requested')) {
                $table->dropColumn('bonus_use_requested');
            }
        });
    }
};