<?php

namespace App\Repository\Races\Params;

class TicketParams
{
    public function __construct(
        private ?int    $departureSectionId,
        private ?int    $arrivalSectionId,
        private ?string $date,
        private string $lang = 'ru',
    )
    {
    }

    public function getDepartureSectionId(): ?int
    {
        return $this->departureSectionId;
    }

    public function getArrivalSectionId(): ?int
    {
        return $this->arrivalSectionId;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }
}
