<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'orders';

    public function __construct()
    {
        $this->table = env('DB_PREFIX', 'mt') . '_orders';
    }

    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'mono_modified_at')) {
                $table->timestamp('mono_modified_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'mono_modified_at')) {
                $table->dropColumn('mono_modified_at');
            }
        });
    }
};
