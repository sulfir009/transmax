<?php

namespace App\Console\Commands;

use App\Service\TextPage\TextPageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckAllTextPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'textpage:check-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all text pages availability';

    /**
     * List of all text page routes
     */
    private array $textPages = [
        '/politika-konfidencijnosti/' => 'Политика конфиденциальности',
        '/usloviya-ispolzovaniya/' => 'Условия использования',
        '/oferta/' => 'Публичная оферта',
        '/pravila-perevozok/' => 'Правила перевозок',
        '/usloviya-vozvrata/' => 'Условия возврата',
        '/instrukciya-po-udaleniyu-dannyh/' => 'Инструкция по удалению данных',
    ];

    /**
     * Execute the console command.
     */
    public function handle(TextPageService $service): int
    {
        $prefix = config('database.prefix', 'mt');
        $lang = 'ru';
        
        $this->info('Checking All Text Pages');
        $this->info('========================');
        
        $results = [];
        
        foreach ($this->textPages as $route => $title) {
            $this->info("\nChecking: {$title}");
            $this->line("Route: {$route}");
            
            // Check in routes table
            $routeExists = DB::table($prefix . '_routes')
                ->where('route', $route)
                ->exists();
            
            // Check through service
            $pageData = $service->getPageByRoute($route);
            $hasContent = !empty($pageData) && !empty($pageData['text']);
            
            $status = 'Not configured';
            $statusColor = 'red';
            
            if ($routeExists && $hasContent) {
                $status = 'Working';
                $statusColor = 'green';
            } elseif ($routeExists && !$hasContent) {
                $status = 'No content';
                $statusColor = 'yellow';
            } elseif (!$routeExists) {
                $status = 'Route missing';
                $statusColor = 'red';
            }
            
            $results[] = [
                'Page' => $title,
                'Route' => trim($route, '/'),
                'DB Route' => $routeExists ? '✓' : '✗',
                'Content' => $hasContent ? '✓' : '✗',
                'Status' => $status,
            ];
            
            if ($statusColor === 'green') {
                $this->info("Status: {$status}");
            } elseif ($statusColor === 'yellow') {
                $this->warn("Status: {$status}");
            } else {
                $this->error("Status: {$status}");
            }
        }
        
        $this->info("\n========================");
        $this->info('Summary');
        $this->info('========================');
        
        $this->table(
            ['Page', 'Route', 'DB Route', 'Content', 'Status'],
            $results
        );
        
        // Count statistics
        $total = count($results);
        $working = count(array_filter($results, fn($r) => $r['Status'] === 'Working'));
        $noContent = count(array_filter($results, fn($r) => $r['Status'] === 'No content'));
        $missing = count(array_filter($results, fn($r) => $r['Status'] === 'Route missing'));
        
        $this->info("\nStatistics:");
        $this->line("Total pages: {$total}");
        $this->info("Working: {$working}");
        if ($noContent > 0) {
            $this->warn("No content: {$noContent}");
        }
        if ($missing > 0) {
            $this->error("Missing: {$missing}");
        }
        
        if ($working === $total) {
            $this->info("\n✓ All text pages are working properly!");
            return Command::SUCCESS;
        } else {
            $this->warn("\n⚠ Some text pages need attention.");
            
            if ($missing > 0) {
                $this->info("\nTo fix missing routes, add them to the mt_routes table:");
                $this->line("INSERT INTO {$prefix}_routes (route, page_id, lang) VALUES");
                foreach ($results as $result) {
                    if ($result['Status'] === 'Route missing') {
                        $this->line("('/{$result['Route']}/', [page_id], '{$lang}'),");
                    }
                }
            }
            
            return Command::FAILURE;
        }
    }
}
