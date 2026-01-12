<?php

namespace App\Http\Controllers;

use App\Service\TextPage\TextPageService;
use Illuminate\Http\Request;

class TextPageController extends Controller
{
    public function __construct(
        private TextPageService $textPageService
    ) {
    }

    /**
     * Display privacy policy page
     */
    public function privacyPolicy(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/politika-konfidencijnosti/');

        if (empty($pageData)) {
            abort(404);
        }

        return view('text-pages.privacy-policy', compact('pageData'));
    }

    /**
     * Display terms of use page
     */
    public function termsOfUse(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/usloviya-ispolzovaniya/');

        if (empty($pageData)) {
            abort(404);
        }

        return view('text-pages.terms-of-use', compact('pageData'));
    }

    /**
     * Display offer page
     */
    public function offer(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/oferta/');

        // Если основной путь не дал результатов, пробуем альтернативные варианты
        if (empty($pageData) || empty($pageData['text'])) {
            // Пробуем без слеша в конце
            $pageData = $this->textPageService->getPageByRoute('/dogovir-oferti/');
        }

        // Пробуем получить через slug
        if (empty($pageData) || empty($pageData['text'])) {
            $pageData = $this->textPageService->getPageBySlug('/dogovir-oferti/');
        }

        // Если все еще нет данных, показываем 404
        if (empty($pageData) || empty($pageData['text'])) {
            abort(404);
        }

        return view('text-pages.offer', compact('pageData'));
    }

    /**
     * Display transport rules page
     */
    public function transportRules(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/pravila-perevozok/');

        if (empty($pageData)) {
            abort(404);
        }

        return view('text-pages.transport-rules', compact('pageData'));
    }

    /**
     * Display return conditions page
     */
    public function returnConditions(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/usloviya-vozvrata/');

        if (empty($pageData)) {
            abort(404);
        }

        return view('text-pages.return-conditions', compact('pageData'));
    }

    /**
     * Display data deletion instructions page
     */
    public function dataDeletionInstructions(Request $request)
    {
        $pageData = $this->textPageService->getPageByRoute('/instrukciya-po-udaleniyu-dannyh/');

        if (empty($pageData)) {
            abort(404);
        }

        return view('text-pages.data-deletion-instructions', compact('pageData'));
    }
}
