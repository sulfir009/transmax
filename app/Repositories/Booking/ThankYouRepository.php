<?php

namespace App\Repositories\Booking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ThankYouRepository
{
    /**
     * Get page metadata for the thank you page
     *
     * @return array
     */
    public function getPageMetadata(): array
    {
        // Since mt_pages table doesn't exist or has different structure,
        // return default metadata values
        // These can be configured in config file or env variables in the future
        
        return [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og_title' => '',
            'og_description' => '',
            'og_image' => ''
        ];
    }

    /**
     * Get user cabinet link based on user type
     *
     * @param int $userId
     * @return string|null
     */
    public function getUserCabinetLink(int $userId): ?string
    {
        $user = DB::table('mt_users')
            ->where('id', $userId)
            ->first();
        
        if (!$user) {
            return null;
        }
        
        // Return different links based on user type if needed
        // This is placeholder logic - adjust based on your business rules
        // TODO: Implement proper cabinet routes when available
        
        return route('main');
    }

    /**
     * Check if user has recent bookings
     *
     * @param int $userId
     * @return bool
     */
    public function hasRecentBookings(int $userId): bool
    {
        return DB::table('mt_orders')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();
    }

    /**
     * Get recent booking information
     *
     * @param int $userId
     * @return object|null
     */
    public function getRecentBooking(int $userId): ?object
    {
        return DB::table('mt_orders')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
