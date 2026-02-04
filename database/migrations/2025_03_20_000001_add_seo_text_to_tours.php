<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mt_tours', function (Blueprint $table) {
            $table->longText('seo_text_uk')->nullable();
            $table->longText('seo_text_ru')->nullable();
            $table->longText('seo_text_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mt_tours', function (Blueprint $table) {
            $table->dropColumn(['seo_text_uk', 'seo_text_ru', 'seo_text_en']);
        });
    }
};
