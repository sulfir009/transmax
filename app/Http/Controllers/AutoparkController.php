<?php

namespace App\Http\Controllers;

use App\Service\Autopark\AutoparkService;
use App\Service\Site;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AutoparkController extends Controller
{
    public function __construct(
        private AutoparkService $autoparkService
    ) {
    }

    /**
     * Отображение страницы автопарка
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $lang = Site::lang();
        
        // Получаем все данные для страницы через сервис
        $data = $this->autoparkService->getPageData($lang);
        $data['lang'] = $lang;
        
        // Дополнительные данные из запроса для формы
        $data['filterData'] = [
            'date' => $request->get('date', date('Y-m-d')),
            'adults' => $request->get('adults', 1),
            'kids' => $request->get('kids', 0),
        ];
        
        return view('autopark.index', $data);
    }

    /**
     * AJAX загрузка дополнительных автобусов
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function loadMore(Request $request): JsonResponse
    {
        $lang = Site::lang();
        $currentCount = (int) $request->get('current', 0);
        
        $buses = $this->autoparkService->getMoreBuses($lang, $currentCount);
        
        if (empty($buses)) {
            return response()->json(['status' => 'error', 'message' => 'No more buses']);
        }
        
        $html = view('autopark.partials.buses-list', [
            'buses' => $buses,
            'lang' => $lang
        ])->render();
        
        return response()->json([
            'status' => 'success',
            'html' => $html
        ]);
    }

    /**
     * AJAX обработка заказа автобуса
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function orderBus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'phone_code' => 'required|integer',
            'date' => 'required|date',
            'adults' => 'required|integer|min:1',
            'kids' => 'required|integer|min:0',
            'bus_id' => 'nullable|integer',
        ]);
        
        $message = sprintf(
            "Заказ автобуса на %s. Пассажиры: взрослых - %d, детей - %d.",
            $validated['date'],
            $validated['adults'],
            $validated['kids']
        );
        
        $orderData = array_merge($validated, ['message' => $message]);
        
        $result = $this->autoparkService->processOrderBus($orderData);
        
        if ($result) {
            return response()->json([
                'status' => 'ok',
                'redirect' => route('thanks')
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Произошла ошибка при обработке заказа'
        ]);
    }
}
