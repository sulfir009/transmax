<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapGenerator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapGenerator $generator)
    {
    }

    public function index(): Response
    {
        $xml = Cache::remember('seo.sitemap.xml', 3600, function () {
            return $this->generator->generate();
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}