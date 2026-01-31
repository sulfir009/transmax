<?php

namespace App\Services\Seo;

use App\Helpers\LocaleHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SitemapGenerator
{
    public function generate(): string
    {
        $urls = $this->collectUrls();

        $xml = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>'
        );

        foreach ($urls as $entry) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $entry['loc']);
            if (!empty($entry['lastmod'])) {
                $url->addChild('lastmod', $entry['lastmod']);
            }
        }

        return $xml->asXML();
    }

    private function collectUrls(): array
    {
        $entries = [];
        $now = Carbon::now()->toDateString();

        $staticRoutes = [
            'main',
            'schedule',
            'avtopark',
            'about.us',
            'kontakti',
            'faq',
            'privacy.policy',
            'terms.of.use',
            'offer',
            'transport.rules',
            'return.conditions',
            'data.deletion.instructions',
            'mobile.app',
        ];

        foreach (LocaleHelper::getSupportedLocales() as $locale) {
            foreach ($staticRoutes as $routeName) {
                $entries[] = [
                    'loc' => LocaleHelper::localizedRoute($routeName, [], true, $locale),
                    'lastmod' => $now,
                ];
            }
        }

        foreach ($this->getRoutePairs() as $pair) {
            foreach (LocaleHelper::getSupportedLocales() as $locale) {
                $fromSlug = $pair['departure_slug_' . $locale] ?? Str::slug($pair['departure_title_' . $locale]);
                $toSlug = $pair['arrival_slug_' . $locale] ?? Str::slug($pair['arrival_title_' . $locale]);

                $entries[] = [
                    'loc' => LocaleHelper::localizedRoute(
                        'schedule.route',
                        ['from' => $fromSlug, 'to' => $toSlug],
                        true,
                        $locale
                    ),
                    'lastmod' => $pair['lastmod'] ?? $now,
                ];
            }
        }

        return $entries;
    }

    private function getRoutePairs(): array
    {
        $prefix = env('DB_PREFIX', 'mt');
        $citiesTable = $prefix . '_cities';
        $toursTable = $prefix . '_tours';

        if (!Schema::hasTable($toursTable) || !Schema::hasTable($citiesTable)) {
            return [];
        }

        return DB::table($toursTable . ' as t')
            ->join($citiesTable . ' as departure_city', 't.departure', '=', 'departure_city.id')
            ->join($citiesTable . ' as arrival_city', 't.arrival', '=', 'arrival_city.id')
            ->where('t.active', '1')
            ->select([
                't.updated_at',
                'departure_city.slug_ru as departure_slug_ru',
                'departure_city.slug_uk as departure_slug_uk',
                'departure_city.slug_en as departure_slug_en',
                'arrival_city.slug_ru as arrival_slug_ru',
                'arrival_city.slug_uk as arrival_slug_uk',
                'arrival_city.slug_en as arrival_slug_en',
                'departure_city.title_ru as departure_title_ru',
                'departure_city.title_uk as departure_title_uk',
                'departure_city.title_en as departure_title_en',
                'arrival_city.title_ru as arrival_title_ru',
                'arrival_city.title_uk as arrival_title_uk',
                'arrival_city.title_en as arrival_title_en',
            ])
            ->distinct()
            ->get()
            ->map(function ($row) {
                return [
                    'departure_slug_ru' => $row->departure_slug_ru,
                    'departure_slug_uk' => $row->departure_slug_uk,
                    'departure_slug_en' => $row->departure_slug_en,
                    'arrival_slug_ru' => $row->arrival_slug_ru,
                    'arrival_slug_uk' => $row->arrival_slug_uk,
                    'arrival_slug_en' => $row->arrival_slug_en,
                    'departure_title_ru' => $row->departure_title_ru,
                    'departure_title_uk' => $row->departure_title_uk,
                    'departure_title_en' => $row->departure_title_en,
                    'arrival_title_ru' => $row->arrival_title_ru,
                    'arrival_title_uk' => $row->arrival_title_uk,
                    'arrival_title_en' => $row->arrival_title_en,
                    'lastmod' => $row->updated_at ? Carbon::parse($row->updated_at)->toDateString() : null,
                ];
            })
            ->toArray();
    }
}