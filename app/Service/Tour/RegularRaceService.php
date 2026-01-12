<?php

namespace App\Service\Tour;

use App\Repository\Races\Params\RegularRaceParams;
use App\Repository\Races\RegularRacesRepository;
use App\Repository\Races\TourStopPricesRepository;
use App\Repository\Races\TourStopsRepository;
use App\Service\Tour\Enums\TourEnum;

class RegularRaceService
{
    public function __construct(
        private RegularRacesRepository $regularRacesRepository,
        private TourStopsRepository    $tourStopsRepository,
        private TourStopPricesRepository $stopPricesRepository,

    )
    {
    }

    public function getImagesByAlias($alias)
    {
        return $this->regularRacesRepository->getImagesByAlias($alias);
    }

    public function getNightsRegularRaces(RegularRaceParams $params)
    {
        return $this->regularRacesRepository->getNightRegularRacesById($params);
    }

    public function getDaysRegularRaces(RegularRaceParams $params)
    {
        return $this->regularRacesRepository->getDaysRegularRacesById($params);
    }

    public function getAllRegularRacesWithStopsByIds(RegularRaceParams $params)
    {
        $races = $this->regularRacesRepository->getRegularRacesById($params);

        return $this->prepareDataWithStops($races);
    }

    public function getStations($regularRaces, $firstStopId = 0)
    {
        $stations = [];
        foreach($regularRaces as $race) {
            if ($firstStopId > 0 && $race->stops->first()->stop_id != $firstStopId) {
                continue;
            }
            $stations[$race->id] = $race->stops;
        }
        return collect($stations);
    }

    public function getNightsRegularRacesWithStops(RegularRaceParams $params)
    {
        $races = $this->regularRacesRepository->getNightRegularRacesById($params);
        $races = $this->prepareDataWithStops($races);
        return $this->prepareDataWithDaysOfWeek($races);
    }

    public function getDaysRegularRacesWithStops(RegularRaceParams $params)
    {
        $races = $this->regularRacesRepository->getDaysRegularRacesById($params);
        $races = $this->prepareDataWithStops($races);
        return $this->prepareDataWithDaysOfWeek($races);
    }

    public function getTourStopPrices($tourIds)
    {
        $result = [];
        $stops = $this->stopPricesRepository->getStopPricesByTourIds($tourIds);
        foreach ($stops as $stop) {
            $result[$stop->tour_id][$stop->from_stop][$stop->to_stop]['price'] = $stop->price;
        }

        return $result;
    }

    public function prepareDataWithDaysOfWeek($races)
    {
        foreach ($races as $item) {
            $daysArray = explode(',', $item->days);

            $item->days_ru = implode(', ', array_map(fn($d) => TourEnum::DAYS_MAPPING[$d]['ru'], $daysArray));
            $item->days_ua = implode(', ', array_map(fn($d) => TourEnum::DAYS_MAPPING[$d]['ua'], $daysArray));
            $item->days_en = implode(', ', array_map(fn($d) => TourEnum::DAYS_MAPPING[$d]['en'], $daysArray));
        }

        return $races;
    }


    private function prepareDataWithStops($races)
    {
        $tourIds = $races->pluck('id')->toArray();
        $daysRegularStops = $this->tourStopsRepository->getStopsByTourIds($tourIds)->sortBy('stop_num');

        return $races->each(function ($race, $key) use ($daysRegularStops, $races) {
            $stops = $daysRegularStops->where('tour_id', $race->id);
            if ($stops->isNotEmpty()) {
                $race->stops = $stops;
            } else {
                $races->forget($key);
            }
        });
    }

    public function getStopsByTourIds($tourIds)
    {
        return $this->tourStopsRepository->getStopsByTourIds($tourIds)->unique('stop_id')->sortBy('stop_num');
    }
}
