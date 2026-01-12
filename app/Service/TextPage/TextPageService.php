<?php

namespace App\Service\TextPage;

use App\Repository\TextPage\TextPageRepository;
use App\Service\Site;

class TextPageService
{
    public function __construct(
        private TextPageRepository $repository
    ) {
    }

    /**
     * Get page data by route URL
     */
    public function getPageByRoute(string $route): array
    {
        $lang = Site::lang();
        
        // Get raw page data from repository
        $pageData = $this->repository->getPageByRoute($route, $lang);
        
        if (empty($pageData)) {
            return [];
        }
        
        // Format data for view
        return $this->formatPageData($pageData);
    }

    /**
     * Get page data by slug
     */
    public function getPageBySlug(string $slug): array
    {
        $lang = Site::lang();
        
        // Get raw page data from repository
        $pageData = $this->repository->getPageBySlug($slug, $lang);
        
        // Если не нашли по slug, пробуем через txt_blocks
        if (empty($pageData)) {
            $pageData = $this->repository->getPageFromTxtBlocks($slug, $lang);
        }
        
        if (empty($pageData)) {
            return [];
        }
        
        // Format data for view
        return $this->formatPageData($pageData);
    }

    /**
     * Format page data for view
     */
    private function formatPageData(array $data): array
    {
        return [
            'title' => $data['title'] ?? '',
            'text' => $data['text'] ?? '',
            'page_title' => $data['page_title'] ?? $data['title'] ?? '',
            'meta_description' => $data['meta_description'] ?? $data['meta_d'] ?? '',
            'meta_keywords' => $data['meta_keywords'] ?? $data['meta_k'] ?? '',
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    /**
     * Process content placeholders
     */
    public function processContent(string $content): string
    {
        if (empty($content)) {
            return '';
        }
        
        $replacements = [
            '{{site_name}}' => config('app.name'),
            '{{current_year}}' => date('Y'),
            '{{site_url}}' => config('app.url'),
            '{{contact_email}}' => config('contacts.email', 'info@example.com'),
            '{{contact_phone}}' => config('contacts.phone', '+380 XX XXX XX XX'),
        ];

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }
}
