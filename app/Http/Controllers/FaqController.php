<?php

namespace App\Http\Controllers;

use App\Repository\Faq\FaqRepository;
use App\Service\Faq\FaqService;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(
        private FaqRepository $faqRepository,
        private FaqService $faqService
    ) {
    }

    /**
     * Display the FAQ page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Получаем информационный блок
        $faqInfo = $this->faqRepository->getFaqInfo();

        // Получаем список вопросов и ответов
        $faqs = $this->faqRepository->getActiveFaqs();

        // Подготавливаем данные для view
        $data = $this->faqService->prepareFaqData($faqInfo, $faqs);

        return view('faq.index', $data);
    }

    /**
     * Search FAQs via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query', '');

        if (empty($query)) {
            $faqs = $this->faqRepository->getActiveFaqs();
        } else {
            $faqs = $this->faqRepository->searchFaqs($query);
        }

        $data = $this->faqService->prepareFaqsForAjax($faqs);

        return response()->json([
            'success' => true,
            'html' => view('faq.partials.faq-list', ['faqs' => $data])->render()
        ]);
    }
}
