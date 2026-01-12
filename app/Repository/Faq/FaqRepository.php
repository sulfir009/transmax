<?php

namespace App\Repository\Faq;

use App\Models\Faq;
use App\Models\FaqInfo;
use App\Service\Site;
use Illuminate\Support\Collection;

class FaqRepository
{
    /**
     * Get FAQ information block
     *
     * @return FaqInfo|null
     */
    public function getFaqInfo()
    {
        return FaqInfo::first();
    }

    /**
     * Get all active FAQs sorted by sort field
     *
     * @return Collection
     */
    public function getActiveFaqs()
    {
        $lang = Site::lang();
        
        return Faq::active()
            ->sorted()
            ->select([
                'id',
                "question_{$lang} as question",
                "answer_{$lang} as answer"
            ])
            ->get();
    }

    /**
     * Search FAQs by query
     *
     * @param string $query
     * @return Collection
     */
    public function searchFaqs(string $query)
    {
        $lang = Site::lang();
        
        return Faq::active()
            ->where(function ($q) use ($query, $lang) {
                $q->where("question_{$lang}", 'LIKE', "%{$query}%")
                  ->orWhere("answer_{$lang}", 'LIKE', "%{$query}%");
            })
            ->sorted()
            ->select([
                'id',
                "question_{$lang} as question",
                "answer_{$lang} as answer"
            ])
            ->get();
    }

    /**
     * Get FAQ by ID
     *
     * @param int $id
     * @return Faq|null
     */
    public function getFaqById(int $id)
    {
        return Faq::find($id);
    }

    /**
     * Get popular FAQs (most viewed or top by sort)
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularFaqs(int $limit = 5)
    {
        $lang = Site::lang();
        
        return Faq::active()
            ->sorted()
            ->limit($limit)
            ->select([
                'id',
                "question_{$lang} as question",
                "answer_{$lang} as answer"
            ])
            ->get();
    }

    /**
     * Get FAQs by category (if you have categories in the future)
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getFaqsByCategory(int $categoryId)
    {
        $lang = Site::lang();
        
        // This is a placeholder for future category functionality
        // For now, return all active FAQs
        return $this->getActiveFaqs();
    }

    /**
     * Count total active FAQs
     *
     * @return int
     */
    public function countActiveFaqs()
    {
        return Faq::active()->count();
    }

    /**
     * Get FAQs for sitemap
     *
     * @return Collection
     */
    public function getFaqsForSitemap()
    {
        return Faq::active()
            ->sorted()
            ->select(['id', 'updated_at'])
            ->get();
    }
}
