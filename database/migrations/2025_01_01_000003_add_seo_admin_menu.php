<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix. '_menu_admin';

        if (!Schema::hasTable($table)) {
            return;
        }

        $seoSection = DB::table($table)
            ->where('title', 'SEO')
            ->where('section_id', 0)
            ->first();

        if (!$seoSection) {
            $seoSectionId = DB::table($table)->insertGetId([
                'title' => 'SEO',
                'link' => '#',
                'image' => 'fas fa-chart-line',
                'assoc_table' => '',
                'page_id' => 0,
                'section_id' => 0,
                'sort' => 1,
                'active' => 1,
                'access' => '1',
                'access_edit' => '1',
            ]);
        } else {
            $seoSectionId = $seoSection->id;
        }

        $templateLink = '{ADMIN_PANEL}/seo/templates';

        $existingTemplate = DB::table($table)
            ->where('link', $templateLink)
            ->where('section_id', $seoSectionId)
            ->first();

        if (!$existingTemplate) {
            DB::table($table)->insert([
                'title' => 'Шаблоны',
                'link' => $templateLink,
                'image' => 'far fa-file-alt',
                'assoc_table' => '',
                'page_id' => 0,
                'section_id' => $seoSectionId,
                'sort' => 1,
                'active' => 1,
                'access' => '1',
                'access_edit' => '1',
            ]);
        }
    }

    public function down(): void
    {
        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix. '_menu_admin';

        if (!Schema::hasTable($table)) {
            return;
        }

        $seoSection = DB::table($table)
            ->where('title', 'SEO')
            ->where('section_id', 0)
            ->first();

        if ($seoSection) {
            DB::table($table)->where('section_id', $seoSection->id)->delete();
            DB::table($table)->where('id', $seoSection->id)->delete();
        }
    }
};