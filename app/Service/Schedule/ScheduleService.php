<?php

namespace App\Service\Schedule;

use App\Models\TourStop;
use App\Repository\Schedule\ScheduleRepository;
use App\Service\Site;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository
    ) {
    }

    /**
     * Get filtered routes with additional processing
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFilteredRoutes(array $filters, int $page = 1, int $perPage = 16)
    {
        $routes = $this->scheduleRepository->getFilteredRoutes($filters, $page, $perPage);
        
        // Process each route to add additional data
        $routes->getCollection()->transform(function ($route) {
            // Get departure and arrival station details
            $departureDetails = $this->scheduleRepository->getStationDetails($route->departure, $route->id);
            $arrivalDetails = $this->scheduleRepository->getStationDetails($route->arrival, $route->id);
            
            // Get tour stops
            $tourStops = $this->scheduleRepository->getTourStops($route->id);
            
            // Add basic details to route
            $route->departure_details = $departureDetails;
            $route->arrival_details = $arrivalDetails;
            $route->is_international = ($route->departure_city_section_id != $route->arrival_city_section_id);
            
            // Calculate additional data if we have all required info
            if ($departureDetails && $arrivalDetails && $tourStops->count() > 0) {
                $lastStop = $tourStops->last();
                $arrivalDay = $lastStop->arrival_day ?? 0;
                $rideTime = $this->calculateTotalTravelTime(
                    $tourStops,
                    $departureDetails->id,
                    $arrivalDetails->id,
                    $arrivalDay
                );
                
                // Get ticket price
                $ticketPrice = $this->scheduleRepository->getTicketPrice(
                    $route->id,
                    $departureDetails->id,
                    $arrivalDetails->id
                );
                
                // Find nearest departure date
                $nearestDepartureDate = $this->findNearestDayOfWeek(
                    Carbon::now()->format('Y-m-d'),
                    explode(',', $route->days)
                );
                
                // Add calculated data to route
                $route->ride_time = $rideTime;
                $route->ticket_price = $ticketPrice;
                $route->nearest_departure_date = $nearestDepartureDate;
            } else {
                // Set default values if data is incomplete
                $route->ride_time = '00:00';
                $route->ticket_price = null;
                $route->nearest_departure_date = Carbon::now()->format('Y-m-d');
            }
            
            return $route;
        });
        
        // Group routes by country for display only if there are routes
        if ($routes->count() > 0) {
            $groupedRoutes = $this->groupRoutesByCountry($routes->getCollection());
            $routes->setCollection($groupedRoutes);
        }
        
        return $routes;
    }

    /**
     * Group routes by country
     *
     * @param Collection $routes
     * @return Collection
     */
    private function groupRoutesByCountry(Collection $routes)
    {
        $routesArray = [];
        $ukraineRoutes = [];

        foreach ($routes as $route) {
            if ($route->departure_city_section_id == 13) {
                // Routes from Ukraine
                $ukraineRoutes[$route->arrival_city_section_id][] = $route;
            } else {
                // Routes from other countries
                $routesArray[$route->departure_city_section_id][] = $route;
            }
        }

        // Add routes from Ukraine to corresponding sections
        foreach ($ukraineRoutes as $arrivalCitySectionId => $routesList) {
            if (isset($routesArray[$arrivalCitySectionId])) {
                foreach ($routesList as $route) {
                    $routesArray[$arrivalCitySectionId][] = $route;
                }
            } else {
                $routesArray[$arrivalCitySectionId] = $routesList;
            }
        }

        return collect($routesArray);
    }

    /**
     * Calculate total travel time between stops
     *
     * @param Collection $stops
     * @param int $departureId
     * @param int $arrivalId
     * @param int $arrivalDay
     * @return string
     */
    private function calculateTotalTravelTime($stops, $departureId, $arrivalId, $arrivalDay)
    {
        $departureStop = $stops->firstWhere('stop_id', $departureId);
        $arrivalStop = $stops->firstWhere('stop_id', $arrivalId);
        
        if (!$departureStop || !$arrivalStop) {
            return '00:00';
        }
        
        $departureTime = Carbon::createFromFormat('H:i:s', $departureStop->departure_time);
        $arrivalTime = Carbon::createFromFormat('H:i:s', $arrivalStop->arrival_time);
        
        // Add days if arrival is on a different day
        if ($arrivalDay > 0) {
            $arrivalTime->addDays($arrivalDay);
        }
        
        $diff = $arrivalTime->diff($departureTime);
        
        return sprintf('%02d:%02d', $diff->h + ($diff->days * 24), $diff->i);
    }

    /**
     * Find nearest day of week
     *
     * @param string $startDate
     * @param array $daysOfWeek
     * @return string
     */
    private function findNearestDayOfWeek($startDate, $daysOfWeek)
    {
        $start = Carbon::parse($startDate);
        $nearestDate = null;
        $minDiff = PHP_INT_MAX;
        
        foreach ($daysOfWeek as $day) {
            // Convert day number to Carbon day constant (1 = Monday, 7 = Sunday)
            $targetDay = $day % 7; // Adjust if needed based on your day numbering
            
            // Find next occurrence of this day
            $nextOccurrence = $start->copy()->next($targetDay);
            
            $diff = $nextOccurrence->diffInDays($start);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearestDate = $nextOccurrence;
            }
        }
        
        return $nearestDate ? $nearestDate->format('Y-m-d') : $start->format('Y-m-d');
    }

    /**
     * Get page title based on filters
     *
     * @param array $filters
     * @param mixed $routes
     * @return string
     */
    public function getPageTitle(array $filters, $routes)
    {
        if (empty($filters['departure']) && empty($filters['arrival']) && empty($filters['country']) && empty($filters['city'])) {
            return __('alias_schedule');
        }
        
        if (!empty($filters['country'])) {
            $countryTitle = $this->scheduleRepository->getCountryTitle($filters['country']);
            return __('dictionary.MSG_MSG_SCHEDULE_ROZKLAD_NAPRAVLENNYA') . ' ' . $countryTitle;
        }
        
        if (!empty($filters['departure']) && !empty($filters['arrival']) && $routes->count() > 0) {
            $collection = $routes->getCollection();
            $firstRoute = null;
            
            // Получаем первый маршрут из коллекции
            if ($collection->count() > 0) {
                $firstItem = $collection->first();
                if (is_array($firstItem)) {
                    // Если это сгруппированные данные
                    $firstRoute = reset($firstItem);
                } else {
                    // Если это обычный объект
                    $firstRoute = $firstItem;
                }
            }
            
            if ($firstRoute && isset($firstRoute->departure_city) && isset($firstRoute->arrival_city)) {
                return __('dictionary.MSG_MSG_SCHEDULE_ROZKLAD_NAPRAVLENNYA') . ' ' . 
                       $firstRoute->departure_city . ' - ' . $firstRoute->arrival_city;
            }
        }
        
        return __('alias_schedule');
    }

    /**
     * Get route details for popup
     *
     * @param int $tourId
     * @param int $departureId
     * @param int $arrivalId
     * @return array|null
     */
    public function getRouteDetails($tourId, $departureId, $arrivalId)
    {
        $tour = $this->scheduleRepository->getTourDetails($tourId);
        
        if (!$tour) {
            return null;
        }
        
        // Load stops with city relation
        $stops = TourStop::where('tour_id', $tourId)
            ->with('stopCity')
            ->orderBy('id', 'ASC')
            ->get();
            
        $departureDetails = $this->scheduleRepository->getStationDetails($tour->departure, $tourId);
        $arrivalDetails = $this->scheduleRepository->getStationDetails($tour->arrival, $tourId);
        
        return [
            'tour' => $tour,
            'stops' => $stops,
            'departureDetails' => $departureDetails,
            'arrivalDetails' => $arrivalDetails
        ];
    }

    /**
     * Get route prices for popup
     *
     * @param int $tourId
     * @param int $departureId
     * @param int $arrivalId
     * @return array|null
     */
    public function getRoutePrices($tourId, $departureId, $arrivalId)
    {
        $tour = $this->scheduleRepository->getTourDetails($tourId);
        
        if (!$tour) {
            return null;
        }
        
        $prices = $this->scheduleRepository->getTourStopPrices($tourId);
        
        return [
            'tour' => $tour,
            'prices' => $prices,
            'departureId' => $departureId,
            'arrivalId' => $arrivalId
        ];
    }

    /**
     * Remember ticket for booking
     *
     * @param int $tourId
     * @param int $passengers
     * @param int $departureId
     * @param int $arrivalId
     * @param string $date
     * @return string|bool
     */
    public function rememberTicket($tourId, $passengers, $departureId, $arrivalId, $date)
    {
        // Check if the tour date has already passed
        $tourDate = Carbon::parse($date);
        if ($tourDate->isPast()) {
            return 'late';
        }
        
        // Initialize session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store ticket information in session
        $_SESSION['ticket'] = [
            'tour_id' => $tourId,
            'passengers' => $passengers,
            'departure' => $departureId,
            'arrival' => $arrivalId,
            'date' => $date
        ];
        
        return true;
    }
}
