<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\CallbackRequest;
use App\Mail\CallbackFormMail;
use App\Repository\Order\CallbackRepository;
use App\Repository\Races\CityRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CallbackController extends Controller
{
    public function send(
        CallbackRequest $request,
        CallbackRepository $callbackRepository,
        CityRepository $cityRepository
    ) {
        $validated = $request->validated();
        $name = $validated['name'] ?? '';
        $data = [
            'date' => $validated['date'] ?? date('Y-m-d H:i:s'),
            'phone' => $validated['phone'] ?? '',
            'departure' => $validated['departure'] ?? '',
            'arrival' => $validated['arrival'] ?? '',
            'message' => 'ФИО: ' . $name . ' | Комментраий: ' . $validated['comment'] ?? '',
        ];
        $callbackRepository->add($data);

        $departureTitle = $cityRepository->getStationNameUk($validated['departure']) ?? '';
        $arrivalTitle = $cityRepository->getStationNameUk($validated['arrival']) ?? '';
        try {
            Mail::to(env('MAIL_ADMIN'))->send(new CallbackFormMail([
                'departure' => $departureTitle,
                'arrival' => $arrivalTitle,
                'phone' => $data['phone'],
                'message' => $data['message'],
                'date' => now()->format('Y-m-d H:i:s'),
            ]));
            return response()->json(['status' => 'ok', 'code' => 200], 200);

           // return redirect()->back()->with('success_callback', true);
        } catch (\Exception $e) {
            Log::error('Ошибка отправки письма: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Не удалось отправить письмо. Попробуйте позже.'], 500);
        }
    }
}
