<?php

namespace App\Services\Seo;

use App\Models\City;

class PageContext
{
    public function __construct(
        public readonly string $routeName,
        public readonly string $baseRouteName,
        public readonly string $locale,
        public readonly array $routeParams,
        public readonly array $viewData,
        public readonly ?City $departureCity = null,
        public readonly ?City $arrivalCity = null,
    ) {
    }

    public function hasRouteCities(): bool
    {
        return $this->departureCity !== null && $this->arrivalCity !== null;
    }

    public function getRouteTitle(): ?string
    {
        if (!$this->hasRouteCities()) {
            return null;
        }

        return $this->departureCity->getTitle($this->locale) . ' — ' . $this->arrivalCity->getTitle($this->locale);
    }
}