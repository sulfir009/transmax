<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'seo:generate-sitemap';

    protected $description = 'Generate sitemap.xml and store it in public directory.';

    public function handle(SitemapGenerator $generator): int
    {
        $xml = $generator->generate();
        File::put(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap generated at public/sitemap.xml');

        return self::SUCCESS;
    }
}