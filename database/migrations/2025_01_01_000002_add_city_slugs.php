<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_cities';

        if (!Schema::hasTable($table)) {
            return;
        }

        $hasSlugRu = Schema::hasColumn($table, 'slug_ru');
        $hasSlugUk = Schema::hasColumn($table, 'slug_uk');
        $hasSlugEn = Schema::hasColumn($table, 'slug_en');

        Schema::table($table, function (Blueprint $table) use ($hasSlugRu, $hasSlugUk, $hasSlugEn) {
            if (!$hasSlugRu) {
                $table->string('slug_ru')->nullable()->index();
            }
            if (!$hasSlugUk) {
                $table->string('slug_uk')->nullable()->index();
            }
            if (!$hasSlugEn) {
                $table->string('slug_en')->nullable()->index();
            }
        });

        $cities = DB::table($table)->select('id', 'title_ru', 'title_uk', 'title_en')->get();

        foreach ($cities as $city) {
            DB::table($table)
                ->where('id', $city->id)
                ->update([
                    'slug_ru' => Str::slug($city->title_ru ?? ''),
                    'slug_uk' => Str::slug($city->title_uk ?? ''),
                    'slug_en' => Str::slug($city->title_en ?? ''),
                ]);
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_cities';

        if (!Schema::hasTable($table)) {
            return;
        }

        $hasSlugRu = Schema::hasColumn($table, 'slug_ru');
        $hasSlugUk = Schema::hasColumn($table, 'slug_uk');
        $hasSlugEn = Schema::hasColumn($table, 'slug_en');

        Schema::table($table, function (Blueprint $table) use ($hasSlugRu, $hasSlugUk, $hasSlugEn) {
            if ($hasSlugRu) {
                $table->dropColumn('slug_ru');
            }
            if ($hasSlugUk) {
                $table->dropColumn('slug_uk');
            }
            if ($hasSlugEn) {
                $table->dropColumn('slug_en');
            }
        });
    }
};