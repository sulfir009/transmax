<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFeedbackRequest;
use App\Service\Contact\ContactService;
use App\Service\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private ContactService $contactService
    ) {
    }

    /**
     * Отображение страницы контактов
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $lang = Site::lang();

        // Получаем все данные для страницы через сервис
        $data = $this->contactService->getPageData($lang);
        $data['lang'] = $lang;

        return view('contacts.index', $data);
    }

    /**
     * Отправка формы обратной связи
     *
     * @param ContactFeedbackRequest $request
     * @return JsonResponse
     */
    public function sendFeedback(ContactFeedbackRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            $result = $this->contactService->processFeedback($validated);
            
            if ($result) {
                return response()->json(['status' => 'ok'], 200);
            }
            
            return response()->json(['status' => 'error'], 500);
        } catch (\Exception $e) {
            \Log::error('Contact feedback error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
