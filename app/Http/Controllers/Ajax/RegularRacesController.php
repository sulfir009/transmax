<?php

namespace App\Http\Controllers\Ajax;

use App\Repository\Races\Params\RegularRaceParams;
use App\Repository\Races\RegularRacesRepository;
use App\Repository\Races\TourStopsRepository;
use App\Service\Tour\Enums\TourEnum;
use App\Service\Tour\RegularRaceService;
use Symfony\Component\HttpFoundation\Request;

class RegularRacesController
{
    public function loadPartialRaces(
        Request $request,
        RegularRaceService $regularRaceService,
        RegularRacesRepository $regularRacesRepository
    ) {
        $stopId = $request->get('stop_id');
        $tour = $request->get('tour');
        $tourIds = $regularRacesRepository->getTourIdsByAlias($tour)->pluck('id')->toArray() ?? [];

        $daysParams = new RegularRaceParams(
            $tourIds,
            TourEnum::DAYS_TOUR,
            TourEnum::NIGHT_TOUR,
        );
        $nightParams = new RegularRaceParams(
            $tourIds,
            TourEnum::NIGHT_TOUR,
            TourEnum::DAYS_TOUR,
        );



        $daysRegularRaces = $regularRaceService->getDaysRegularRacesWithStops($daysParams);
        $nightRegularRaces = $regularRaceService->getNightsRegularRacesWithStops($nightParams);

        $regularRaces = [
            'light_trans' => $daysRegularRaces,
            'night_trans' => $nightRegularRaces,
        ];

        $stations = [
            'light_trans' => $regularRaceService->getStations($daysRegularRaces, $stopId),
            'night_trans' => $regularRaceService->getStations($nightRegularRaces, $stopId),
        ];

       //dd($stations['light_trans']->isEmpty());

        return view('regular-races.components.regular-races',
            [
                'regularRaces' => $regularRaces,
                'stopId' => $stopId,
                'stations' => $stations,
            ]
        );
    }
}
