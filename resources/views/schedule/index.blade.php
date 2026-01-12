@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/slick/jquery.mCustomScrollbar.min.css') }}">
@endsection

@section('content')
<div class="content">
    <div class="main_filter_wrapper">
        <div class="container">
            @include('layout.components.filter.filter')
        </div>
    </div>
    
    <div class="page_content_wrapper">
        <div class="shedule_block">
            <div class="shedule_table_container">
                <div class="shedule_title h2_title">
                    {{ $pageTitle }}
                </div>
                
                <div class="shedule_table_wrapper">
                    <table class="shedule_table">
                        <thead class="shedule_th">
                            <tr>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_KRANA')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_REJS')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_MARSHRUT')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_VARTISTI')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_POSILANNYA_NA_BRONYUVANNYA')</th>
                            </tr>
                        </thead>
                        <tbody class="shedule_tbody">
                            @forelse($routes as $countryId => $countryRoutes)
                                @foreach($countryRoutes as $k => $route)
                                    <tr class="shedule_tr">
                                        @if($k == 0)
                                            <td class="shedule_td manrope" rowspan="{{ count($countryRoutes) }}">
                                                {{ $route->departure_country }}
                                            </td>
                                        @endif
                                        
                                        <td class="shedule_td manrope">
                                            {{ $route->departure_city }} - {{ $route->arrival_city }}
                                        </td>
                                        
                                        <td class="shedule_td">
                                            <button class="schedule_details_btn"
                                                    onclick="toggleRouteDetailsSchedule('{{ $route->id }}', '{{ optional($route->departure_details)->id ?? 0 }}', '{{ optional($route->arrival_details)->id ?? 0 }}')">
                                                @lang('dictionary.MSG_MSG_SCHEDULE_GRAFIK_I_RASPISANIE_REJSA')
                                            </button>
                                        </td>
                                        
                                        <td class="shedule_td h4_title">
                                            <button class="info_btn">
                                                <img src="{{ asset('images/legacy/common/info.svg') }}" alt="info">
                                            </button>
                                            <button class="schedule_details_btn"
                                                    onclick="toggleRoutePricesSchedule('{{ $route->id }}', '{{ optional($route->departure_details)->id ?? 0 }}', '{{ optional($route->arrival_details)->id ?? 0 }}', '{{ $route->nearest_departure_date ?? '' }}')">
                                                @lang('dictionary.MSG_MSG_SCHEDULE_PRICE_TABLE')
                                            </button>
                                        </td>
                                        
                                        <td class="shedule_td">
                                            <button class="buy_btn h5_title"
                                                    onclick="buyTicketFromSchedule(this, '{{ $route->id }}', '{{ optional($route->departure_details)->id ?? 0 }}', '{{ optional($route->arrival_details)->id ?? 0 }}', '{{ $route->nearest_departure_date ?? '' }}')">
                                                @lang('dictionary.MSG_MSG_SCHEDULE_KUPITI_KVITOK')
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        @lang('dictionary.MSG_MSG_SCHEDULE_NET_MARSHRUTOV')
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="shedule_table_pagination_wrapper">
                    {{ $routes->appends(request()->query())->links('layout.components.pagination') }}
                </div>
            </div>
        </div>
        
        <div class="routes_block">
            <div class="container">
                <div class="routes_title h2_title">
                    @lang('dictionary.MSG__NASHI_NAPRAVLENNYA')
                </div>
                <div class="routes_subtitle par">
                    @lang('dictionary.MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI')
                </div>
                
                <div class="routes_lists_wrapper">
                    {{-- Countries list --}}
                    <div class="route_list_block">
                        <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_KRANI')</div>
                        <div class="route_list">
                            @foreach($countries as $country)
                                <div>
                                    <a href="{{ route('schedule') }}?country={{ $country->id }}" 
                                       class="shedule_link">{{ $country->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Cities list --}}
                    <div class="route_list_block">
                        <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_ROZKLAD')</div>
                        <div class="route_list">
                            @foreach($cities as $city)
                                <div>
                                    <a href="{{ route('schedule') }}?city={{ $city->id }}" 
                                       class="shedule_link">{{ $city->title }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- International routes --}}
                    <div class="route_list_block">
                        <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_MIZHNARODNI')</div>
                        <div class="route_list">
                            @php
                                $printedRoutes = [];
                            @endphp
                            @foreach($internationalRoutes as $route)
                                @php
                                    $routeString = $route->departure_city_id . '_' . $route->arrival_city_id;
                                @endphp
                                @if(!in_array($routeString, $printedRoutes))
                                    <div>
                                        <a href="{{ route('schedule') }}?departure={{ $route->departure_city_id }}&arrival={{ $route->arrival_city_id }}" 
                                           class="shedule_link">{{ $route->departure_city }} → {{ $route->arrival_city }}</a>
                                    </div>
                                    @php
                                        $printedRoutes[] = $routeString;
                                    @endphp
                                @endif
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Domestic routes --}}
                    <div class="route_list_block">
                        <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_VNUTRISHNI')</div>
                        <div class="route_list">
                            @php
                                $printedRoutes = [];
                            @endphp
                            @foreach($domesticRoutes as $route)
                                @php
                                    $routeString = $route->departure_city_id . '_' . $route->arrival_city_id;
                                @endphp
                                @if(!in_array($routeString, $printedRoutes))
                                    <div>
                                        <a href="{{ route('schedule') }}?departure={{ $route->departure_city_id }}&arrival={{ $route->arrival_city_id }}" 
                                           class="shedule_link">{{ $route->departure_city }} → {{ $route->arrival_city }}</a>
                                    </div>
                                    @php
                                        $printedRoutes[] = $routeString;
                                    @endphp
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Popups --}}
<div class="schedule_route_details_popup"></div>
<div class="schedule_route_details_overlay overlay" onclick="toggleRouteDetailsSchedule('0')"></div>
@endsection

@section('page-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script>
    function toggleDetailsServices(item) {
        $(item).toggleClass('active');
        $('.sr_bus_options').slideToggle();
    }

    $(".shedule_table_wrapper").mCustomScrollbar({
        axis: "x",
        theme: 'maxtrans_theme'
    });

    function buyTicketFromSchedule(item, id, departure, arrival, date) {
        initLoader();
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '{{ route("schedule.remember-ticket") }}',
            data: {
                'id': id,
                'passengers': '1',
                'departure': departure,
                'date': date,
                'arrival': arrival
            },
            success: function (response) {
                removeLoader();
                if ($.trim(response) == 'ok') {
                    location.href = '{{ route("booking.index") }}';
                } else if ($.trim(response) === 'late') {
                    out('@lang("dictionary.MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL")');
                }
            }
        });
    }

    function toggleRouteDetailsSchedule(id, departure, arrival) {
        if (parseInt(id) > 0) {
            initLoader();
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '{{ route("schedule.route-details") }}',
                data: {
                    'id': id,
                    'departure': departure,
                    'arrival': arrival
                },
                success: function (response) {
                    removeLoader();
                    if (response.html) {
                        $('.schedule_route_details_popup').html(response.html).toggleClass('active');
                        $('.schedule_route_details_overlay').fadeToggle();
                        $('body').toggleClass('overflow');
                    } else {
                        out('Ошибка');
                    }
                }
            });
        } else {
            $('.schedule_route_details_popup').html('').toggleClass('active');
            $('.schedule_route_details_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }
    }

    function toggleRoutePricesSchedule(id, departure, arrival) {
        if (parseInt(id) > 0) {
            initLoader();
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '{{ route("schedule.route-prices") }}',
                data: {
                    'id': id,
                    'departure': departure,
                    'arrival': arrival
                },
                success: function (response) {
                    removeLoader();
                    if (response.data) {
                        $('.schedule_route_details_popup').html(response.data).toggleClass('active');
                        $('.schedule_route_details_overlay').fadeToggle();
                        $('body').toggleClass('overflow');
                    } else {
                        out('Ошибка');
                    }
                }
            });
        } else {
            $('.schedule_route_details_popup').html('').toggleClass('active');
            $('.schedule_route_details_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }
    }

    function initLoader() {
        $('body').prepend('<div class="loader"></div>');
    }

    function removeLoader() {
        document.querySelector(".loader").remove();
    }
</script>
@endsection
