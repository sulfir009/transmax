<?php

namespace App\Service\Faq;

use App\Models\FaqInfo;
use App\Service\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqService
{
    /**
     * Prepare FAQ data for view
     *
     * @param FaqInfo|null $faqInfo
     * @param Collection $faqs
     * @return array
     */
    public function prepareFaqData($faqInfo, Collection $faqs)
    {
        $lang = Site::lang();
        
        // Prepare FAQ info data
        $infoData = null;
        if ($faqInfo) {
            $infoData = [
                'title' => $faqInfo->getTitle($lang),
                'text' => $faqInfo->getText($lang),
                'image' => $faqInfo->image
            ];
        }
        
        // Process FAQs - add slugs for anchor links
        $processedFaqs = $faqs->map(function ($faq) {
            $faq->slug = Str::slug($faq->question, '-');
            $faq->answer_html = $this->processAnswer($faq->answer);
            return $faq;
        });
        
        return [
            'faqInfo' => $infoData,
            'faqs' => $processedFaqs,
            'totalCount' => $faqs->count(),
            'lang' => $lang
        ];
    }

    /**
     * Process answer text (convert line breaks, add formatting, etc.)
     *
     * @param string $answer
     * @return string
     */
    private function processAnswer(string $answer)
    {
        // Convert line breaks to paragraphs
        $paragraphs = explode("\n\n", $answer);
        $html = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                // Convert single line breaks to <br>
                $paragraph = nl2br($paragraph);
                $html .= "<p>{$paragraph}</p>";
            }
        }
        
        // Process lists if they exist
        $html = $this->processLists($html);
        
        // Process links
        $html = $this->processLinks($html);
        
        return $html;
    }

    /**
     * Process lists in the answer text
     *
     * @param string $text
     * @return string
     */
    private function processLists(string $text)
    {
        // Process unordered lists (lines starting with - or *)
        $text = preg_replace_callback('/(<p>)?(\*|-)\s+(.+?)(<\/p>)?/m', function ($matches) {
            return '<li>' . $matches[3] . '</li>';
        }, $text);
        
        // Wrap consecutive li elements in ul
        $text = preg_replace('/((<li>.*?<\/li>\s*)+)/s', '<ul>$1</ul>', $text);
        
        // Process ordered lists (lines starting with numbers)
        $text = preg_replace_callback('/(<p>)?(\d+)\.\s+(.+?)(<\/p>)?/m', function ($matches) {
            return '<li class="ordered">' . $matches[3] . '</li>';
        }, $text);
        
        // Wrap consecutive ordered li elements in ol
        $text = preg_replace('/((<li class="ordered">.*?<\/li>\s*)+)/s', '<ol>$1</ol>', $text);
        $text = str_replace('class="ordered"', '', $text);
        
        return $text;
    }

    /**
     * Process links in the answer text
     *
     * @param string $text
     * @return string
     */
    private function processLinks(string $text)
    {
        // Auto-link URLs
        $pattern = '/(https?:\/\/[^\s<]+)/i';
        $replacement = '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>';
        $text = preg_replace($pattern, $replacement, $text);
        
        // Auto-link email addresses
        $pattern = '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/';
        $replacement = '<a href="mailto:$1">$1</a>';
        $text = preg_replace($pattern, $replacement, $text);
        
        return $text;
    }

    /**
     * Prepare FAQs for AJAX response
     *
     * @param Collection $faqs
     * @return Collection
     */
    public function prepareFaqsForAjax(Collection $faqs)
    {
        return $faqs->map(function ($faq) {
            $faq->slug = Str::slug($faq->question, '-');
            $faq->answer_html = $this->processAnswer($faq->answer);
            return $faq;
        });
    }

    /**
     * Generate meta tags for FAQ page
     *
     * @param FaqInfo|null $faqInfo
     * @return array
     */
    public function generateMetaTags($faqInfo)
    {
        $lang = Site::lang();
        
        $title = __('alias_faq');
        $description = __('dictionary.MSG_FAQ_META_DESCRIPTION');
        
        if ($faqInfo) {
            $title = $faqInfo->getTitle($lang) . ' - ' . config('app.name');
            $infoText = strip_tags($faqInfo->getText($lang));
            $description = Str::limit($infoText, 160);
        }
        
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => __('dictionary.MSG_FAQ_META_KEYWORDS'),
            'og_title' => $title,
            'og_description' => $description,
            'og_type' => 'website'
        ];
    }

    /**
     * Generate structured data for FAQ page (Schema.org)
     *
     * @param Collection $faqs
     * @return array
     */
    public function generateStructuredData(Collection $faqs)
    {
        $mainEntity = [];
        
        foreach ($faqs as $faq) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer)
                ]
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity
        ];
    }

    /**
     * Get related FAQs based on keywords
     *
     * @param string $question
     * @param Collection $allFaqs
     * @param int $limit
     * @return Collection
     */
    public function getRelatedFaqs(string $question, Collection $allFaqs, int $limit = 3)
    {
        // Extract keywords from the question
        $keywords = $this->extractKeywords($question);
        
        // Score each FAQ based on keyword matches
        $scoredFaqs = $allFaqs->map(function ($faq) use ($keywords, $question) {
            if ($faq->question === $question) {
                return null; // Exclude the current FAQ
            }
            
            $score = 0;
            foreach ($keywords as $keyword) {
                if (stripos($faq->question, $keyword) !== false) {
                    $score += 2; // Higher score for question matches
                }
                if (stripos($faq->answer, $keyword) !== false) {
                    $score += 1; // Lower score for answer matches
                }
            }
            
            $faq->relevance_score = $score;
            return $faq;
        })->filter()->sortByDesc('relevance_score')->take($limit);
        
        return $scoredFaqs;
    }

    /**
     * Extract keywords from text
     *
     * @param string $text
     * @return array
     */
    private function extractKeywords(string $text)
    {
        // Remove common stop words (you can expand this list)
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were', 'и', 'в', 'на', 'с', 'для', 'что', 'как'];
        
        // Convert to lowercase and split into words
        $words = preg_split('/\s+/', mb_strtolower($text));
        
        // Filter out stop words and short words
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_values($keywords);
    }
}
