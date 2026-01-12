<?php

namespace App\Repository\Home;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomeContentRepository
{
    private string $dbPrefix;

    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }

    public function getMainBanner(string $lang): array
    {
        return Cache::remember("main_banner_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_main_banner')
                ->selectRaw("image, title_{$lang} AS title")
                ->first();

            return $result ? (array) $result : [];
        });
    }

    public function getAdvantages(string $lang): array
    {
        return DB::table($this->dbPrefix . '_advantages')
            ->selectRaw("image, title_{$lang} AS title, preview_{$lang} AS preview")
            ->where('active', '1')
            ->orderByDesc('sort')
            ->get()
            ->toArray();
    }

    public function getWelcomeInfo(string $lang): array
    {
        return Cache::remember("welcome_info_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_wellcome')
                ->selectRaw("image, title_{$lang} AS title, text_{$lang} AS text")
                ->first();

            return $result ? (array) $result : [];
        });
    }

    public function getNumbersInfo(string $lang): array
    {
        return Cache::remember("numbers_info_{$lang}", 3600, function () use ($lang) {
            $result = DB::table($this->dbPrefix . '_about_numbers')
                ->selectRaw("
                    image,
                    title_{$lang} AS title,
                    text1_{$lang} AS text1,
                    text2_{$lang} AS text2,
                    text3_{$lang} AS text3,
                    text4_{$lang} AS text4,
                    number1, number2, number3, number4
                ")
                ->first();

            return $result ? (array) $result : [];
        });
    }

    public function getWhyWeData(string $lang): array
    {
        return Cache::remember("why_we_{$lang}", 3600, function () use ($lang) {
            return DB::table($this->dbPrefix . '_why_we')
                ->selectRaw("
                    image,
                    title_{$lang} AS title,
                    subtitle_{$lang} AS subtitle,
                    preview_{$lang} AS preview
                ")
                ->where('active', '1')
                ->orderBy('sort')
                ->get()
                ->toArray();
        });
    }

    public function getReviews(string $lang): array
    {
        return Cache::remember("reviews_{$lang}", 3600, function () use ($lang) {
            return DB::table($this->dbPrefix . '_reviews')
                ->selectRaw("image, name, review_{$lang} AS review")
                ->where('active', '1')
                ->orderByDesc('sort')
                ->get()
                ->toArray();
        });
    }
}
