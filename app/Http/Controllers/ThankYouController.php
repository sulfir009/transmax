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
        // Get page data from service
        $pageData = $this->thankYouService->getPageData();
        
        // Check user authentication status
        $isAuthenticated = $this->thankYouService->isUserAuthenticated();
        
        // Get redirect URL based on authentication status
        $redirectUrl = $this->thankYouService->getRedirectUrl($isAuthenticated);
        
        // Get global variables for layout compatibility
        global $Router, $Db, $User;
        
        return view('booking.thank-you', [
            'pageData' => $pageData,
            'page_data' => $pageData, // For legacy layout compatibility
            'isAuthenticated' => $isAuthenticated,
            'redirectUrl' => $redirectUrl,
            'lang' => app()->getLocale(),
            'Router' => $Router,
            'header_class' => 'header_blue'
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
