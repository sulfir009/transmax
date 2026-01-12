<div class="routes_title h2_title">
    @lang('MSG__NASHI_NAPRAVLENNYA')
</div>
<div class="routes_subtitle par">
    @lang('MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI-YAKOMU_NAPRYAMKU')
</div>

<div class="routes_lists_wrapper">
    {{-- Країни --}}
    <div class="route_list_block">
        <div class="route_list_title h3_title">
            @lang('MSG_ALL_KRANI')
        </div>
        <div class="route_list">
            @foreach($countries as $country)
                <div>
                    <a href="{{ url('/rozklad?country=' . $country->id) }}"
                       class="shedule_link">
                        {{ $country->title }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Розклад --}}
    <div class="route_list_block">
        <a href="{{ url('/rozklad') }}" class="route_list_title h3_title">
            @lang('MSG_ALL_ROZKLAD')
        </a>
        <div class="route_list">
            @foreach($cities as $city)
                <div>
                    <a href="{{ url('/rozklad?city=' . $city->id) }}"
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
                    <a href="{{ url('/rozklad?departure=' . $tour['departure_city_id'] . '&arrival=' . $tour['arrival_city_id']) }}"
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
                    <a href="{{ url('/rozklad?departure=' . $tour['departure_city_id'] . '&arrival=' . $tour['arrival_city_id']) }}"
                       class="shedule_link">
                        {{ $tour['departure_city'] }} → {{ $tour['arrival_city'] }}
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</div>
