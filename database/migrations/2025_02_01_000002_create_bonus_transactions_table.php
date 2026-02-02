<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_bonus_transactions';

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) use ($prefix) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id');
            $table->bigInteger('amount_cents');
            $table->string('type', 50);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'created_at']);
            $table->index('order_id');
            $table->index('type');

            if (Schema::hasTable($prefix . '_clients')) {
                $table->foreign('client_id')
                    ->references('id')
                    ->on($prefix . '_clients')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_bonus_transactions';

        if (Schema::hasTable($table)) {
            Schema::drop($table);
        }
    }
};
