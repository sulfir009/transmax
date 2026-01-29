<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    private string $table = 'mt_orders'; // ✅ ВАЖНО: реальная таблица

    public function up(): void
    {
        // ✅ защита: чтобы миграции не падали, если таблицы нет
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            // ✅ защита от повторного добавления (если колонка уже есть)
            if (!Schema::hasColumn($this->table, 'mono_invoice_id')) {
                $table->string('mono_invoice_id', 255)->nullable()->index();
            }
            if (!Schema::hasColumn($this->table, 'mono_status')) {
                $table->string('mono_status', 255)->nullable()->index();
            }
            if (!Schema::hasColumn($this->table, 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn($this->table, 'mono_page_url')) {
                $table->text('mono_page_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable($this->table)) {
            return;
        }

        Schema::table($this->table, function (Blueprint $table) {
            // dropColumn безопасно делать только если колонка есть
            $cols = [];
            if (Schema::hasColumn($this->table, 'mono_invoice_id')) $cols[] = 'mono_invoice_id';
            if (Schema::hasColumn($this->table, 'mono_status'))     $cols[] = 'mono_status';
            if (Schema::hasColumn($this->table, 'paid_at'))         $cols[] = 'paid_at';
            if (Schema::hasColumn($this->table, 'mono_page_url'))   $cols[] = 'mono_page_url';

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
