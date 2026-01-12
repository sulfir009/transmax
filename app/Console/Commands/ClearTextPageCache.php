<?php

namespace App\Console\Commands;

use App\Repository\TextPage\TextPageRepository;
use Illuminate\Console\Command;

class ClearTextPageCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'textpage:clear-cache {route?} {lang?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear text page cache';

    /**
     * Execute the console command.
     */
    public function handle(TextPageRepository $repository): int
    {
        $route = $this->argument('route');
        $lang = $this->argument('lang');
        
        if ($route && $lang) {
            $repository->clearCache($route, $lang);
            $this->info("Cache cleared for route: {$route}, lang: {$lang}");
        } else {
            $repository->clearCache();
            $this->info('All text page cache cleared');
        }
        
        return Command::SUCCESS;
    }
}
