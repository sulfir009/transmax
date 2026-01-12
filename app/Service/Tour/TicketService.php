<?php

namespace App\Service\Tour;

use App\Repository\Races\Params\TicketParams;
use App\Repository\Races\TicketRepository;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function get(TicketParams $ticketParams)
    {
        $ticketRepository = new TicketRepository();
        $tickets = $ticketRepository->getAllByParams($ticketParams);
        $depTitle = $ticketRepository->getCityTitle($ticketParams->getDepartureSectionId(), $ticketParams->getLang());
        $arivalTitle = $ticketRepository->getCityTitle($ticketParams->getArrivalSectionId(), $ticketParams->getLang());
        $ticketsData = [];
        foreach ($tickets as $item) {
            $ticket = (array) $item;
            $departureStations = $ticketRepository->getTimeDepartureStation(
                $ticket['id'],
                $ticketParams->getDepartureSectionId(),
                $ticketParams->getLang(),
            );

            $arrivalStations = $ticketRepository->getTimeArrivalStation(
                $ticket['id'],
                $ticketParams->getArrivalSectionId(),
                $ticketParams->getLang(),
            );

            $transfer = $ticketRepository->getTransferStationIdByTourId($ticket['id']);
           foreach ($departureStations as  $departureStation) {
               foreach ($arrivalStations as $arrivalStation) {
                   $ticketStops = $ticketRepository->getStopsByTourId($ticket['id']);
                   $price = $ticketRepository->getPricesByStops($item->id, $departureStation->station_id, $arrivalStation->station_id);
                   $rideTime = $this->calculateTotalTravelTime(
                       $ticketStops->toArray(),
                       $departureStation->station_id,
                       $arrivalStation->station_id,
                       $arrivalStation->days
                   );
                   $rideTimeParts = explode(':', $rideTime);
                   $rideTimeHours = (int)$rideTimeParts[0]; // Получаем часы
                   $rideTimeMinutes = (int)$rideTimeParts[1];
                   $calculatedArrivalDateTime = $this->calculateArrivalDateTime(
                       $ticketParams->getDate() . ' ' . $departureStation->dep_time, $rideTimeHours, $rideTimeMinutes
                   );

                   $freeTickets = $ticketRepository->getFreeTickets(
                       tourId: $ticket['id'],
                       rangeOfStop: $departureStation->stop_num,
                       date: $ticketParams->getDate()
                   );

                   //$ticket['free_tickets'] += $freeTickets;

                   if ($price && !empty($price->price) && $ticket['free_tickets']) {
                       $ticket['isInternation'] = $ticket['departure_city_section_id'] !== $ticket['arrival_city_section_id'];
                       $ticket['departure_station_id'] = $departureStation->station_id;
                       $ticket['dep_station_title'] = $departureStation->dep_station_title;
                       $ticket['dep_time'] = $departureStation->dep_time;
                       $ticket['arrival_station_id'] = $arrivalStation->station_id;
                       $ticket['arr_station_title'] = $arrivalStation->arr_station_title;
                       $ticket['arr_time'] = $arrivalStation->arr_time;
                       $ticket['price'] = $price->price;
                       $ticket['transfers'] = $transfer->transfer_station_id ?? null;
                       $ticket['rideTime'] = $rideTime;
                       $ticket['ticket_arrival_city'] = $arivalTitle;
                       $ticket['calculated_arrival_time'] = $calculatedArrivalDateTime;
                       $ticketsData[] = $ticket;
                   }
               }
           }
        }
       /* dd($ticketsData);*/
        /*Log::error(json_encode($ticketsData));*/
        return $ticketsData;
    }

    private function calculateTotalTravelTime($stops, $startStopId, $endStopId, $arrival_day) {
        $startTime = null;
        $endTime = null;
        $totalTime = 0;
        if ($arrival_day >= 1) {
            $daysInTravel = $arrival_day - 1;
        } else {
            $daysInTravel = 0;
        }

        foreach ($stops as $stop) {
            if ($stop->stop_id == $startStopId) {
                $startTime = strtotime($stop->departure_time);
            } elseif ($stop->stop_id == $endStopId) {
                $endTime = strtotime($stop->arrival_time);
            }

            if ($startTime !== null && $endTime !== null) {
                if ($endTime < $startTime && $arrival_day > 1) {
                    $totalTime += $daysInTravel * (24 * 3600) - $startTime + $endTime;
                }
                elseif ($endTime < $startTime && $arrival_day <= 1) {
                    $totalTime += $startTime - $endTime;
                }
                else {
                    $totalTime +=$daysInTravel * (24 * 3600) + $endTime - $startTime;
                }
                $startTime = null;
                $endTime = null;
            }
        }

        // Convert total time to HH:MM:SS format
        $hours = floor($totalTime / 3600);
        $minutes = floor(($totalTime % 3600) / 60);

        $formattedTotalTime = sprintf('%02d:%02d', $hours, $minutes);

        return $formattedTotalTime;
    }

    private function calculateArrivalDateTime($departureDateTime, $durationHours, $durationMinutes) {
        $departureDateTime = new \DateTime($departureDateTime);
        $durationInSeconds = max(0, ($durationHours * 60 * 60) + ($durationMinutes * 60));
        $arrivalDateTime = clone $departureDateTime;
        $arrivalDateTime->add(new \DateInterval('PT' . $durationInSeconds . 'S'));
        return $arrivalDateTime->format('Y-m-d H:i:s');
    }
}
