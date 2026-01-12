<?php

namespace App\Repository\Races\Params;

use Illuminate\Support\Collection;

class RegularRaceParams
{
    private array $tourIds;
    private string $firstDepartureTime;
    private string $lastDepartureTime;

    public function __construct(
        array $tourIds,
        string $firstDepartureTime,
        string $lastDepartureTime,
    )
    {
        $this->tourIds = $tourIds;
        $this->firstDepartureTime = $firstDepartureTime;
        $this->lastDepartureTime = $lastDepartureTime;
    }

    public function getTourIds(): array
    {
        return $this->tourIds;
    }

    public function getFirstDepartureTime()
    {
        return $this->firstDepartureTime;
    }

    public function getLastDepartureTime()
    {
        return $this->lastDepartureTime;
    }
}
