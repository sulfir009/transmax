<?php

namespace App\Service\Tour\Enums;

enum TourEnum
{
    public const DAYS_TOUR = '05:00:00';
    public const NIGHT_TOUR = '17:00:00';

    public const DAYS_MAPPING = [
        1 => ['ru' => 'пн', 'ua' => 'пн', 'en' => 'Mon'],
        2 => ['ru' => 'вт', 'ua' => 'вт', 'en' => 'Tue'],
        3 => ['ru' => 'ср', 'ua' => 'ср', 'en' => 'Wed'],
        4 => ['ru' => 'чт', 'ua' => 'чт', 'en' => 'Thu'],
        5 => ['ru' => 'пт', 'ua' => 'пт', 'en' => 'Fri'],
        6 => ['ru' => 'сб', 'ua' => 'сб', 'en' => 'Sat'],
        7 => ['ru' => 'вс', 'ua' => 'нд', 'en' => 'Sun'],
    ];
}
