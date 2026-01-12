<?php

namespace App\Services\Booking;

use App\Repositories\Booking\ThankYouRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ThankYouService
{
    protected ThankYouRepository $thankYouRepository;

    public function __construct(ThankYouRepository $thankYouRepository)
    {
        $this->thankYouRepository = $thankYouRepository;
    }

    /**
     * Get page data for the thank you page
     *
     * @return array
     */
    public function getPageData(): array
    {
        // Get page metadata from repository
        $pageMetadata = $this->thankYouRepository->getPageMetadata();
        
        return [
            'title' => $pageMetadata['title'] ?? __('dictionary.MSG_MSG_THX_PAGE_DYAKUYU_ZA_BRONYUVANNYA_BILETU'),
            'description' => $pageMetadata['description'] ?? '',
            'keywords' => $pageMetadata['keywords'] ?? '',
            'og_title' => $pageMetadata['og_title'] ?? '',
            'og_description' => $pageMetadata['og_description'] ?? '',
            'og_image' => $pageMetadata['og_image'] ?? ''
        ];
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isUserAuthenticated(): bool
    {
        // Check Laravel authentication
        if (Auth::check()) {
            return true;
        }
        
        // Check legacy session authentication for backward compatibility
        if (Session::has('user.auth') && Session::get('user.auth')) {
            return true;
        }
        
        // Check global session for legacy code
        if (isset($_SESSION['user']['auth']) && $_SESSION['user']['auth']) {
            return true;
        }
        
        return false;
    }

    /**
     * Get redirect URL based on authentication status
     *
     * @param bool $isAuthenticated
     * @return string
     */
    public function getRedirectUrl(bool $isAuthenticated): string
    {
        if (!$isAuthenticated) {
            // For non-authenticated users, redirect to legacy login page
            // Using legacy router link (page ID 77 is typically login/register page)
            return $this->getLegacyRouteLink(77);
        }
        
        // For authenticated users, redirect to main page
        // TODO: When cabinet/future races page is implemented, update this route
        return route('main');
    }

    /**
     * Clear booking session data
     *
     * @return void
     */
    public function clearBookingSessionData(): void
    {
        // Clear Laravel session data
        Session::forget('booking_data');
        Session::forget('payment_data');
        Session::forget('order_data');
        Session::forget('ticket_data');
        
        // Clear specific booking related session keys
        $bookingKeys = [
            'selected_seats',
            'passenger_data',
            'contact_data',
            'payment_method',
            'total_price',
            'booking_id',
            'transaction_id'
        ];
        
        foreach ($bookingKeys as $key) {
            Session::forget($key);
        }
        
        // Clear legacy session data for backward compatibility
        if (isset($_SESSION)) {
            unset($_SESSION['booking_data']);
            unset($_SESSION['payment_data']);
            unset($_SESSION['order_data']);
            unset($_SESSION['ticket_data']);
        }
    }

    /**
     * Get legacy route link
     *
     * @param int $pageId
     * @return string
     */
    private function getLegacyRouteLink(int $pageId): string
    {
        // Access global Router object for legacy compatibility
        global $Router;
        
        if ($Router && method_exists($Router, 'writelink')) {
            return $Router->writelink($pageId);
        }
        
        // Fallback to default login route
        return '/login';
    }
}
