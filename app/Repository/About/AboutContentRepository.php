<?php

namespace App\Repository\About;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AboutContentRepository
{
    private string $dbPrefix;

    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }

    /**
     * Получить данные блока приветствия
     */
    public function getWelcomeInfo(string $lang): array
    {
        return Cache::remember("about_welcome_info_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_wellcome')
                ->selectRaw("image, title_{$lang} AS title, text_{$lang} AS text")
                ->first();

            return $result ? (array) $result : [];
        });
    }

    /**
     * Получить список преимуществ
     */
    public function getAdvantages(string $lang): array
    {
        return DB::table($this->dbPrefix . '_advantages')
            ->selectRaw("image, title_{$lang} AS title, preview_{$lang} AS preview")
            ->where('active', '1')
            ->orderByDesc('sort')
            ->get()
            ->toArray();
    }

    /**
     * Получить информацию "О нас"
     */
    public function getAboutUsInfo(string $lang): array
    {
        return Cache::remember("about_us_info_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_about_us')
                ->selectRaw("
                    image,
                    title_{$lang} AS title,
                    text_{$lang} AS text,
                    title_2_{$lang} AS title_2,
                    text_2_{$lang} AS text_2
                ")
                ->where('id', 1)
                ->first();

            return $result ? (array) $result : [];
        });
    }

    /**
     * Получить документы компании
     */
    public function getCompanyDocs(): array
    {
        return Cache::remember("company_docs", 3600, function () {
            return DB::table($this->dbPrefix . '_company_docs')
                ->select('image')
                ->where('active', '1')
                ->orderByDesc('sort')
                ->get()
                ->toArray();
        });
    }
}
