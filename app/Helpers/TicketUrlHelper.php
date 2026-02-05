<?php

namespace App\Helpers;

use App\Repository\CityRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TicketUrlHelper
{
    public static function make(?int $from, ?int $to, array $query = [], ?string $lang = null): string
    {
        $lang = $lang ?: app()->getLocale();

        if (!$from || !$to) {
            $baseUrl = LocaleHelper::localizedRoute('tickets.index', [], true, $lang);
            return self::appendQuery($baseUrl, $query);
        }

        $slug = self::slug($from, $to, $lang);
        $baseUrl = LocaleHelper::localizedRoute('tickets.index', ['slug' => $slug], true, $lang);

        $query = array_merge(['from' => $from, 'to' => $to], $query);

        return self::appendQuery($baseUrl, $query);
    }

    public static function slug(?int $from, ?int $to, ?string $lang = null): ?string
    {
        if (!$from || !$to) {
            return null;
        }

        $lang = $lang ?: app()->getLocale();
        $cacheKey = "tickets.slug.{$lang}.{$from}.{$to}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($from, $to, $lang) {
            $cityRepository = app(CityRepository::class);
            $cityRepository->setLanguage($lang);

            $fromTitle = data_get($cityRepository->getCityTitle($from), 'title');
            $toTitle = data_get($cityRepository->getCityTitle($to), 'title');

            $rawSlug = trim((string)($fromTitle ?: $from)) . ' ' . trim((string)($toTitle ?: $to));

            return self::slugify($rawSlug);
        });
    }

    private static function slugify(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = mb_strtolower($ascii, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $lower) ?? '';
        $slug = trim($slug, '-');
        $slug = preg_replace('/-+/', '-', $slug) ?? '';

        return $slug;
    }

    private static function appendQuery(string $url, array $query): string
    {
        if (empty($query)) {
            return $url;
        }

        return $url . '?' . http_build_query($query);
    }
}