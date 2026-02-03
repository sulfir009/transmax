<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');

        Schema::create($prefix . '_seo_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('lang', 2);
            $table->text('template_text');
            $table->timestamps();

            $table->unique(['key', 'lang']);
        });
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');

        Schema::dropIfExists($prefix . '_seo_templates');
    }
};