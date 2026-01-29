<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyAjaxController extends Controller
{
    public function handleRequest(Request $request, $lang)
    {
        $requestType = $request->input('request');
        
        // Обработка запроса filter_date
        if ($requestType === 'filter_date') {
            return $this->handleFilterDate($request);
        }
        
        // Обработка запроса clear_session_data - перенаправляем на новый контроллер
        if ($requestType === 'clear_session_data') {
            // Используем новый контроллер для очистки сессии
            $thankYouController = app(\App\Http\Controllers\ThankYouController::class);
            $response = $thankYouController->clearSessionData($request);
            $content = json_decode($response->getContent(), true);
            
            if ($content && isset($content['data']) && $content['data'] === 'ok') {
                return response()->json(['data' => 'ok']);
            }
            return response()->json(['data' => 'error']);
        }
        
        // Обработка запроса feedback - перенаправляем на новый контроллер
        if ($requestType === 'feedback') {
            // Для обратной совместимости с legacy кодом
            // возвращаем 'ok' при успешной отправке
            $contactController = app(\App\Http\Controllers\ContactController::class);
            $response = $contactController->sendFeedback($request);
            $content = json_decode($response->getContent(), true);
            
            if ($content && isset($content['status']) && $content['status'] === 'ok') {
                return response('ok');
            }
            return response('error');
        }
        
        // Для всех остальных запросов используем legacy код
        $pathCandidates = [
            base_path('legacy/public/pages/ajax.php'),
            base_path('resources/views/legacy/public/pages/ajax.php'),
        ];

        $legacyPath = null;
        foreach ($pathCandidates as $candidate) {
            if (is_file($candidate)) {
                $legacyPath = $candidate;
                break;
            }
        }

        if (!$legacyPath) {
            Log::error('[LegacyAjaxController] legacy ajax.php not found', [
                'candidates' => $pathCandidates,
                'lang' => $lang,
                'request' => $request->all(),
            ]);

            return response('Legacy AJAX handler not found', 500);
        }

        $jsonPayload = [];
        $contentType = (string) $request->header('Content-Type');
        if (str_starts_with($contentType, 'application/json')) {
            $jsonPayload = json_decode((string) $request->getContent(), true) ?? [];
        }

        $_GET = $request->query();
        $_POST = $jsonPayload + $request->post();
        $_REQUEST = array_merge($_GET, $_POST);

        ob_start();
        require $legacyPath;
        $output = ob_get_clean();

        $decoded = $this->tryDecodeJson($output);
        if ($decoded !== null) {
            return response()->json($decoded);
        }

        return response()->json([
            'lang' => $lang,
            'data' => $output,
        ]);
    }

    private function tryDecodeJson(?string $output): ?array
    {
        $trimmed = trim((string) $output);
        if ($trimmed === '') {
            return null;
        }

        if (in_array($trimmed[0], ['{', '['], true)) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
    
    private function handleFilterDate(Request $request)
    {
        $departure = (int) $request->input('departure');
        $arrival = (int) $request->input('arrival');
        
        if (!$departure || !$arrival) {
            return response('');
        }
        
        try {
            // Исправленный запрос - добавляем section_id в SELECT для совместимости с ORDER BY
            $days = DB::table('mt_tours as t')
                ->leftJoin('mt_cities as dc', 'dc.id', '=', 't.departure')
                ->leftJoin('mt_cities as ac', 'ac.id', '=', 't.arrival')
                ->where('t.active', '=', 1)
                ->where(function($query) use ($departure) {
                    $query->where('t.departure', '=', $departure)
                        ->orWhereIn('t.id', function($subquery) use ($departure) {
                            $subquery->select('tour_id')
                                ->from('mt_tours_stops_prices')
                                ->whereIn('from_stop', function($subsubquery) use ($departure) {
                                    $subsubquery->select('id')
                                        ->from('mt_cities')
                                        ->where('section_id', '=', $departure);
                                });
                        });
                })
                ->where(function($query) use ($arrival) {
                    $query->where('t.arrival', '=', $arrival)
                        ->orWhereIn('t.id', function($subquery) use ($arrival) {
                            $subquery->select('tour_id')
                                ->from('mt_tours_stops_prices')
                                ->whereIn('to_stop', function($subsubquery) use ($arrival) {
                                    $subsubquery->select('id')
                                        ->from('mt_cities')
                                        ->where('section_id', '=', $arrival);
                                });
                        });
                })
                ->select('t.days', 'dc.section_id') // Добавляем section_id в SELECT
                ->distinct()
                ->orderBy('dc.section_id', 'asc')
                ->get();
            
            // Извлекаем уникальные дни недели
            $allDays = [];
            foreach ($days as $tour) {
                if ($tour->days) {
                    $tourDays = explode(',', $tour->days);
                    $allDays = array_merge($allDays, $tourDays);
                }
            }
            
            // Убираем дубликаты и сортируем
            $uniqueDays = array_unique($allDays);
            sort($uniqueDays);
            
            // Возвращаем результат в формате, который ожидает JavaScript
            return response(implode("\n", $uniqueDays));
            
        } catch (\Exception $e) {
            // В случае ошибки возвращаем пустой ответ
            \Log::error('Filter date error: ' . $e->getMessage());
            return response('');
        }
    }
}
