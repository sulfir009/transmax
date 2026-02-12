<?php

namespace Tests\Unit;

use App\Http\Controllers\TicketController;
use Carbon\Carbon;
use Tests\TestCase;

class TicketArrivalDateCalculationTest extends TestCase
{
    private function controller(): TicketController
    {
        return new class extends TicketController {
            public function __construct()
            {
            }

            public function resolveArrival(array $ticket, string $filterDate, ?Carbon $departureAt): ?Carbon
            {
                return $this->resolveArrivalAt($ticket, $filterDate, $departureAt);
            }

            public function buildDateTime(string $date, string $time): ?Carbon
            {
                return $this->buildDateTimeFromDateAndTime($date, $time);
            }
        };
    }

    public function test_arrival_time_less_than_departure_time_adds_next_day(): void
    {
        $controller = $this->controller();
        $departureAt = $controller->buildDateTime('2026-02-20', '23:30');

        $arrivalAt = $controller->resolveArrival([
            'id' => 1,
            'arr_time' => '01:10',
        ], '2026-02-20', $departureAt);

        $this->assertNotNull($arrivalAt);
        $this->assertSame('2026-02-21 01:10', $arrivalAt->format('Y-m-d H:i'));
    }

    public function test_arrival_time_greater_or_equal_departure_time_stays_same_day(): void
    {
        $controller = $this->controller();
        $departureAt = $controller->buildDateTime('2026-02-20', '08:00');

        $arrivalAt = $controller->resolveArrival([
            'id' => 2,
            'arr_time' => '12:45',
        ], '2026-02-20', $departureAt);

        $this->assertNotNull($arrivalAt);
        $this->assertSame('2026-02-20 12:45', $arrivalAt->format('Y-m-d H:i'));
    }

    public function test_duration_minutes_is_used_for_arrival_calculation(): void
    {
        $controller = $this->controller();
        $departureAt = $controller->buildDateTime('2026-02-20', '10:15');

        $arrivalAt = $controller->resolveArrival([
            'id' => 3,
            'duration_minutes' => 155,
        ], '2026-02-20', $departureAt);

        $this->assertNotNull($arrivalAt);
        $this->assertSame('2026-02-20 12:50', $arrivalAt->format('Y-m-d H:i'));
    }
}
