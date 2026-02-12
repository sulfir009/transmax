<div class="routes_title h2_title">
    @lang('MSG__NASHI_NAPRAVLENNYA')
</div>
<div class="routes_subtitle par">
    @lang('MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI-YAKOMU_NAPRYAMKU')
</div>
@php
    $todayKyiv = \Carbon\Carbon::now('Europe/Kyiv')->toDateString();
@endphp

<div class="routes_lists_wrapper">
    {{-- Країни --}}
    <div class="route_list_block">
        <div class="route_list_title h3_title">
            @lang('MSG_ALL_KRANI')
        </div>
        <div class="route_list">
            @foreach($homeCountries as $country)
                <div>
                <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}?country={{ $country['id'] }}"
                       class="shedule_link">
                        {{ $country['title'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Розклад --}}
    <div class="route_list_block">
        <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}" class="route_list_title h3_title">
            @lang('MSG_ALL_ROZKLAD')
        </a>
        <div class="route_list">
            @foreach($cities as $city)
                <div>
                    <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}?city={{ $city->id }}"
                       class="shedule_link">
                        {{ $city->title }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Міжнародні маршрути --}}
    <div class="route_list_block">
        <div class="route_list_title h3_title">
            @lang('MSG_ALL_MIZHNARODNI')
        </div>
        <div class="route_list">
            @foreach($internationalTours as $tour)
                <div>
<a href="{{ \App\Helpers\TicketUrlHelper::make($tour['departure_city_id'], $tour['arrival_city_id'], ['from' => $tour['departure_city_id'], 'to' => $tour['arrival_city_id'], 'date' => $todayKyiv]) }}"
                       class="shedule_link">
                        {{ $tour['departure_city'] }} → {{ $tour['arrival_city'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Внутрішні маршрути --}}
    <div class="route_list_block">
        <div class="route_list_title h3_title">
            @lang('MSG_ALL_VNUTRISHNI')
        </div>
        <div class="route_list">
            @foreach($homeTours as $tour)
                <div>
<a href="{{ \App\Helpers\TicketUrlHelper::make($tour['departure_city_id'], $tour['arrival_city_id'], ['from' => $tour['departure_city_id'], 'to' => $tour['arrival_city_id'], 'date' => $todayKyiv]) }}"
                       class="shedule_link">
                        {{ $tour['departure_city'] }} → {{ $tour['arrival_city'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
