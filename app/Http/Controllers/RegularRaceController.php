<?php

namespace App\Http\Controllers;

use App\Repository\Races\Params\RegularRaceParams;
use App\Repository\Races\RegularRacesRepository;
use App\Repository\Races\TourStopsRepository;
use App\Repository\Site\PhoneCodesRepository;
use App\Service\Tour\Enums\TourEnum;
use App\Service\Tour\RegularRaceService;

class RegularRaceController
{
    public function __construct(
        private RegularRacesRepository $regularRacesRepository,
        private TourStopsRepository    $tourStopsRepository,
        private RegularRaceService     $regularRaceService,
        private PhoneCodesRepository   $phoneCodesRepository,
    ) {

    }

    public function index($tour)
    {
        $tourIds = $this->regularRacesRepository->getTourIdsByAlias($tour)->pluck('id')->toArray() ?? [];
        $phoneCodes = $this->phoneCodesRepository->getAll();
        $daysParams = new RegularRaceParams(
            $tourIds,
            TourEnum::DAYS_TOUR,
            TourEnum::NIGHT_TOUR
        );
        $nightParams = new RegularRaceParams(
            $tourIds,
            TourEnum::NIGHT_TOUR,
            TourEnum::DAYS_TOUR
        );


        $daysRegularRaces = $this->regularRaceService->getDaysRegularRacesWithStops($daysParams);
        $nightRegularRaces = $this->regularRaceService->getNightsRegularRacesWithStops($nightParams);
        $tourStopPrices = $this->regularRaceService->getTourStopPrices($tourIds);
        $stops = $this->regularRaceService->getStopsByTourIds($tourIds);
        $images = $this->regularRaceService->getImagesByAlias($tour)->first();

        $regularRaces = [
            'light_trans' => $daysRegularRaces,
            'night_trans' => $nightRegularRaces,
        ];

        $stations = [
            'light_trans' => $this->regularRaceService->getStations($daysRegularRaces),
            'night_trans' => $this->regularRaceService->getStations($nightRegularRaces),
        ];


        $data = [
            'daysRegularRaces' => $daysRegularRaces,
            'nightRegularRaces' => $nightRegularRaces,
            'regularRaces' => $regularRaces,
            'tourStopPrices' => $tourStopPrices,
            'stops' => $stops,
            'phoneCodes' => $phoneCodes,
            'tour' => $tour,
            'stopId' => 0,
            'stations' => $stations,
            'images' => $images
        ];

        //dd($data);

        return view('regular-races.index', $data);
    }
}
