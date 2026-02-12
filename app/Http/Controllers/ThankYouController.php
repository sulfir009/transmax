<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Booking\ThankYouService;
use App\Repositories\Booking\ThankYouRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThankYouController extends Controller
{
    protected ThankYouService $thankYouService;
    protected ThankYouRepository $thankYouRepository;

    public function __construct(
        ThankYouService $thankYouService,
        ThankYouRepository $thankYouRepository
    ) {
        $this->thankYouService = $thankYouService;
        $this->thankYouRepository = $thankYouRepository;
    }

    /**
     * Display the thank you page after booking
     *
     * @param Request $request
     * @return View
     */
public function index(Request $request): View
{
    $pageData = $this->thankYouService->getPageData();

    $isAuthenticated = $this->thankYouService->isUserAuthenticated();
    $redirectUrl = $this->thankYouService->getRedirectUrl($isAuthenticated);

    // legacy globals (если реально нужны во view)
    global $Router, $db, $User;

    // 1) Laravel session
    $sessionOrderId = $request->session()->get('order.id')
        ?? $request->session()->get('order_id')
        ?? $request->session()->get('order.order_id');

    $sessionOrderUniq = $request->session()->get('order.uniq')
        ?? $request->session()->get('order_uniq')
        ?? $request->session()->get('order.order_uniq');

    $sessionPaymentMethod = $request->session()->get('order.payment_method')
        ?? $request->session()->get('payment_method')
        ?? $request->session()->get('order.method');

    // 2) PHP native session fallback (если legacy пишет в $_SESSION)
    if (($sessionOrderId === null || $sessionOrderId === '') && isset($_SESSION) && is_array($_SESSION)) {
        if (isset($_SESSION['order']) && is_array($_SESSION['order'])) {
            $sessionOrderId = $sessionOrderId ?? ($_SESSION['order']['id'] ?? $_SESSION['order']['order_id'] ?? null);
            $sessionOrderUniq = $sessionOrderUniq ?? ($_SESSION['order']['uniq'] ?? $_SESSION['order']['order_uniq'] ?? null);
            $sessionPaymentMethod = $sessionPaymentMethod ?? ($_SESSION['order']['payment_method'] ?? $_SESSION['order']['method'] ?? null);
        } else {
            $sessionOrderId = $sessionOrderId ?? ($_SESSION['order_id'] ?? null);
            $sessionOrderUniq = $sessionOrderUniq ?? ($_SESSION['order_uniq'] ?? null);
            $sessionPaymentMethod = $sessionPaymentMethod ?? ($_SESSION['payment_method'] ?? null);
        }
    }

    return view('booking.thank-you', [
        'pageData' => $pageData,
        'page_data' => $pageData,
        'isAuthenticated' => $isAuthenticated,
        'redirectUrl' => $redirectUrl,
        'lang' => app()->getLocale(),
        'Router' => $Router ?? null,
        'header_class' => 'header_blue',

        // ✅ теперь переменные всегда объявлены (могут быть null, но не вызовут fatal)
        'sessionOrderId' => $sessionOrderId,
        'sessionOrderUniq' => $sessionOrderUniq,
        'sessionPaymentMethod' => $sessionPaymentMethod,
    ]);
}


    /**
     * Clear session booking data via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearSessionData(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Clear booking session data through service
            $this->thankYouService->clearBookingSessionData();
            
            return response()->json([
                'status' => 'success',
                'data' => 'ok'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear session data'
            ], 500);
        }
    }
}
