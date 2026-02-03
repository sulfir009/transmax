@extends('layout.app')

@section('page-styles')
    {{-- Flatpickr стили подключаются глобально в footer_scripts.blade.php, здесь не нужны --}}
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/jquery_ui_slider/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ mix('css/legacy/style_table.css') }}">
    <link rel="stylesheet" href="{{ mix('css/responsive.css') }}">
@endsection

@section('content')
    <div class="content"
         data-filter-departure="{{ $filterDeparture }}"
         data-filter-arrival="{{ $filterArrival }}"
         data-filter-date="{{ $filterDate }}"
         data-adults="{{ $adults }}"
         data-kids="{{ $kids }}"
         data-min-price="{{ $minTicketsPrice }}"
         data-max-price="{{ $maxTicketsPrice }}"
         data-current-date="{{ date('Y-m-d') }}"
         data-ajax-url="/ajax/ru"
         data-route-tickets="{{ route('tickets.index') }}"
         data-route-next="{{ rtrim(url($Router->writelink(85)), '/') }}"
         data-csrf-token="{{ csrf_token() }}"
         data-msg-ticket-expired="{{ __('dictionary.MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL') }}">
        <div class="main_filter_wrapper">
            <div class="container">
                @include('layout.components.filter.filter', [
                    'cities' => $cities ?? [],
                    'filterDeparture' => $filterDeparture ?? 0,
                    'filterArrival' => $filterArrival ?? 0,
                    'filterDate' => $filterDate ?? date('Y-m-d'),
                    'filterAdults' => $adults ?? 1,
                    'filterKids' => $kids ?? 0,
                    'dictionary' => $dictionary ?? [],
                    'lang' => $lang ?? 'uk',
                    'formAction' => route('tickets.index')
                ])
            </div>
        </div>

        <div class="purchase_steps_wrapper">
            <div class="tabs_links_container">
                <div class="purchase_steps">
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title active">1. @lang('dictionary.MSG_MSG_TICKETS_VIBIR_AVTOBUSA')</div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">2. @lang('data_ticket_page')</div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">3. @lang('payment_ticket_page')</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page_content_wrapper">
            <div class="container">
                @if (empty($processedTickets))
                    <div class="ticket_page_title h2_title reccomend_title">
                        @lang('dictionary.MSG_MSG_TICKETS_RECOMMEND_DATES')
                    </div>
                    <div class="recommend_dates">
                        @foreach ($recommendedDates as $date)
                            <div class="reccomend_date blue_btn">
                                <a class="tour_date_link" href="#" data-date="{{ $date['date'] }}">
                                    {{ $date['day'] }} {{ $date['month'] }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="ticket_page_subtitle par">
                    @lang('dictionary.MSG_MSG_TICKETS_VIZD_TA_PRIBUTTYA_ZA_MISCEVIM_CHASOM')
                </div>

                <div class="ticket_page_title h2_title">
                    @lang('dictionary.MSG_MSG_TICKETS_ROZKLAD_AVTOBUSIV')
                    @if ($pageTitle)
                        {{ $pageTitle }}
                    @endif
                </div>

                <div class="sort_block hidden-xl hidden-lg hidden-md hidden-sm hidden-xs">
                    <div class="sort_block_tile h3_title">@lang('dictionary.MSG_MSG_TICKETS_SORTUVATI')</div>
                    <div class="sort_options flex_ac">
                        <button class="sort_option active h5_title flex_ac desc js-sort-btn"
                                data-sort="1"
                                data-sort-direction="1">
                            @lang('dictionary.MSG_MSG_TICKETS_CINA')
                        </button>
                        <button class="sort_option h5_title flex_ac desc js-sort-btn"
                                data-sort="2"
                                data-sort-direction="1">
                            @lang('dictionary.MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA')
                        </button>
                        <button class="sort_option h5_title flex_ac desc js-sort-btn"
                                data-sort="3"
                                data-sort-direction="1">
                            @lang('dictionary.MSG_MSG_TICKETS_CHAS_PRIBUTTYA')
                        </button>
                    </div>
                </div>

                <div class="mobile_sort_filter hidden-xxl flex_ac">
                    <select class="sort_select flex_ac">
                        <option value="" hidden selected disabled>@lang('dictionary.MSG_MSG_TICKETS_SORTUVATII_ZA')</option>
                        <option value="1">@lang('dictionary.MSG_MSG_TICKETS_CINA')</option>
                        <option value="2">@lang('dictionary.MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA')</option>
                        <option value="3">@lang('dictionary.MSG_MSG_TICKETS_CHAS_PRIBUTTYA')</option>
                        <option value="4">@lang('dictionary.MSG_MSG_TICKETS_POPULYATNISTI')</option>
                    </select>
                    <button class="mobile_filter_btn js-mobile-filter-toggle">
                        <img src="{{ asset('images/legacy/common/filter.svg') }}" alt="filter">
                    </button>
                </div>
            </div>

            <div class="catalog_filter_overlay overlay hidden-xxl js-mobile-filter-overlay"></div>

            <div class="tickets_catalog_wrapper">
                <div class="container">
                    <div class="tickets_catalog">
                        <div class="catalog_elements">
                            <div class="catalog_elements_title h3_title">
                                @lang('dictionary.MSG_MSG_TICKETS_ZNAJDENO') {{ count($processedTickets) }} @lang('dictionary.MSG_MSG_TICKETS_AVTOBUSIV')
                            </div>
                            <div class="catalog_elements_subtitle par">
                                @lang('dictionary.MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA_TA_PRIBUTTYA_MISCEVIJ')
                            </div>

                            <div class="ticket_cards_wrapper">
                                @foreach ($processedTickets as $ticket)
                                    <div class="ticket_card shadow_block">
                                        <div class="flex-row">
                                            <div class="col-lg-9 col-xs-12">
                                                <div class="ticket_info">
                                                    <div class="ticket_info_header flex_ac">
                                                        <div class="ticket_info_date_block flex_ac">
                                                            <img src="{{ asset('images/legacy/common/ticket_calendar.svg') }}" alt="calendar">
                                                            <span class="ticket_info_date par">
                                                                {{ $ticket['departure_date_formatted'] }}
                                                            </span>
                                                        </div>
                                                        <div class="ride_description_wrapper flex_ac">
                                                            <div class="ride_description par">
                                                                <span>@lang('dictionary.MSG_MSG_TICKETS_REJS')</span>
                                                                <span>{{ $ticket['departure_city'] }} — {{ $ticket['arrival_city'] }}</span>
                                                            </div>
                                                            <div class="ride_description par">
                                                                <span>@lang('dictionary.MSG_MSG_TICKETS_AVTOBUS')</span>
                                                                <span>{{ $ticket['bus_title'] }}</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="ticket_ride_info_block flex-row gap-30">
                                                        <div class="col-lg-4 col-sm-6 col-xs-12">
                                                            <div class="ticket_ride_departure ticket_ride_info">
                                                                <div class="ticket_ride_time flex_ac">
                                                                    <img src="{{ asset('images/legacy/common/clock.svg') }}" alt="clock">
                                                                    <span class="btn_txt">
                                                                        {{ date("H:i", strtotime($ticket['dep_time'])) }}
                                                                    </span>
                                                                </div>
                                                                <div class="ticket_ride_city btn_txt">
                                                                    {!! $ticket['departure_details']['city'] !!}
                                                                </div>
                                                                <div class="ticket_ride_checkpoint manrope">
                                                                    {!! $ticket['dep_station_title'] !!}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-4 hidden-md hidden-sm col-xs-12">
                                                            <div class="ticket_ride_info ride_total_time">
                                                                <div class="ticket_logo_wrapper">
                                                                    <img src="{{ asset('images/legacy/common/ticket_logo_2.svg') }}" alt="ticket logo" class="fit_img">
                                                                </div>
                                                                <div class="ticket_ride_total_time_wrapper">
                                                                    <div class="ticket_ride_total_time_info">
                                                                        <img src="{{ asset('images/legacy/common/info.svg') }}" alt="info">
                                                                        <div class="ticket_info_tooltip par">
                                                                            @lang('dictionary.MSG_MSG_TICKETS_VKAZANIJ_CHAS_NE_VRAHOVU_ZATRIMOK_NA_KORDONI')
                                                                        </div>
                                                                    </div>
                                                                    <div class="ticket_ride_total_time_data">
                                                                        <div class="ticket_ride_total_time par">
                                                                            {{ (int)explode(':', $ticket['ride_time'])[0] }} @lang('dictionary.MSG_MSG_TICKETS_GOD')
                                                                            {{ (int)explode(':', $ticket['ride_time'])[1] }} @lang('dictionary.MSG_MSG_TICKETS_HV_V_DOROZI')
                                                                        </div>
                                                                        @if ($ticket['international'])
                                                                            <div class="ticket_ride_status par">
                                                                                @lang('dictionary.MSG_MSG_TICKETS_MIZHNARODNIJ')
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-lg-4 col-sm-6 col-xs-12">
                                                            <div class="ticket_ride_arrival ticket_ride_info">
                                                                <div class="ticket_ride_time flex_ac">
                                                                    <img src="{{ asset('images/legacy/common/clock.svg') }}" alt="clock">
                                                                    <span class="btn_txt">
                                                                        {{ date('H:i', strtotime($ticket['arr_time'])) }}
                                                                    </span>
                                                                </div>
                                                                <div class="ticket_ride_city btn_txt">
                                                                    {!! $ticket['arrival_details']['city'] !!}
                                                                </div>
                                                                <div class="ticket_ride_checkpoint">
                                                                    {!! $ticket['arr_station_title'] !!}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <button class="ticket_details_btn shedule_link flex_ac hidden-md hidden-sm hidden-xs js-route-details-btn"
                                                        data-ticket-id="{{ $ticket['id'] }}"
                                                        data-departure-id="{{ $ticket['departure_details']['id'] }}"
                                                        data-arrival-id="{{ $ticket['arrival_details']['id'] }}">
                                                    @lang('dictionary.MSG_MSG_TICKETS_DETALINISHE')
                                                    <img src="{{ asset('images/legacy/common/arrow_down_2.svg') }}" alt="arrow down">
                                                </button>
                                            </div>

                                            <div class="col-lg-3 hidden-md hidden-sm hidden-xs">
                                                <div class="ticket_totals">
                                                    <div class="ticket_price">{{ $ticket['ticket_price'] }} ₴</div>
                                                    <button class="ticket_buy_btn flex_ac h5_title blue_btn js-buy-ticket-btn"
                                                            data-ticket-id="{{ $ticket['id'] }}"
                                                            data-departure-id="{{ $ticket['departure_details']['id'] }}"
                                                            data-arrival-id="{{ $ticket['arrival_details']['id'] }}"
                                                            data-filter-departure="{{ $filterDeparture }}"
                                                            data-filter-arrival="{{ $filterArrival }}">
                                                        @lang('dictionary.MSG_MSG_TICKETS_KUPITI_KVITOK')
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="hidden-xxl hidden-xl hidden-lg col-sm-12 hidden-xs">
                                                <div class="ride_total_time">
                                                    <div class="ticket_logo_wrapper">
                                                        <img src="{{ asset('images/legacy/common/ticket_logo_2.svg') }}" alt="ticket logo" class="fit_img">
                                                    </div>
                                                    <div class="mobile_ticket_ride_total_time_wrapper flex_ac">
                                                        <div class="ticket_ride_total_time_info flex_ac">
                                                            <img src="{{ asset('images/legacy/common/info.svg') }}" alt="info">
                                                            <div class="ticket_ride_total_time par">
                                                                {{ (int)explode(':', $ticket['ride_time'])[0] }} @lang('dictionary.MSG_MSG_TICKETS_GOD')
                                                                {{ (int)explode(':', $ticket['ride_time'])[1] }} @lang('dictionary.MSG_MSG_TICKETS_HV_V_DOROZI')
                                                            </div>
                                                        </div>
                                                        @if ($ticket['international'])
                                                            <div class="ticket_ride_status par">
                                                                @lang('dictionary.MSG_MSG_TICKETS_MIZHNARODNIJ')
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="hidden-xxl hidden-xl hidden-lg col-xs-12">
                                                <div class="mobile_ticket_totals flex_ac">
                                                    <div class="mobile_ticket_details flex_ac">
                                                        <div class="ticket_price">{{ $ticket['ticket_price'] }} ₴</div>
                                                        <button class="ticket_details_btn shedule_link flex_ac js-route-details-btn"
                                                                data-ticket-id="{{ $ticket['id'] }}"
                                                                data-departure-id="{{ $ticket['departure_details']['id'] }}"
                                                                data-arrival-id="{{ $ticket['arrival_details']['id'] }}">
                                                            @lang('dictionary.MSG_MSG_TICKETS_DETALINISHE')
                                                            <img src="{{ asset('images/legacy/common/arrow_down_2.svg') }}" alt="arrow down">
                                                        </button>
                                                    </div>
                                                    <button class="ticket_buy_btn flex_ac h5_title blue_btn js-buy-ticket-btn"
                                                            data-ticket-id="{{ $ticket['id'] }}"
                                                            data-departure-id="{{ $ticket['departure_details']['id'] }}"
                                                            data-arrival-id="{{ $ticket['arrival_details']['id'] }}"
                                                            data-filter-departure="{{ $filterDeparture }}"
                                                            data-filter-arrival="{{ $filterArrival }}">
                                                        @lang('dictionary.MSG_MSG_TICKETS_KUPITI_KVITOK')
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pagination_wrapper">
                                {{ $pagination['total'] > $pagination['per_page'] ? paginatePublic($pagination) : '' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="route_details_popup blue_popup"></div>
    <div class="route_details_overlay overlay js-route-details-overlay" data-ticket-id="0"></div>
@endsection

@section('page-scripts')
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"
            integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        // Получение данных из data-атрибутов
        function getFilterData() {
            const contentEl = document.querySelector('.content');
            return {
                departure: parseInt(contentEl.dataset.filterDeparture),
                arrival: parseInt(contentEl.dataset.filterArrival),
                date: contentEl.dataset.filterDate,
                adults: parseInt(contentEl.dataset.adults),
                kids: parseInt(contentEl.dataset.kids),
                minPrice: parseInt(contentEl.dataset.minPrice),
                maxPrice: parseInt(contentEl.dataset.maxPrice),
                currentDate: contentEl.dataset.currentDate,
                ajaxUrl: contentEl.dataset.ajaxUrl,
                routeTickets: contentEl.dataset.routeTickets,
                routeNext: contentEl.dataset.routeNext,
                csrfToken: contentEl.dataset.csrfToken,
                msgTicketExpired: contentEl.dataset.msgTicketExpired
            };
        }

        // Инициализация переменной с данными фильтра
        let filterData = getFilterData();

        // ========== ФУНКЦИИ ==========

        function buyTicket(item, id, departure, arrival, fromCity, toCity) {
            $('body').prepend('<div class="loader"></div>');
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': filterData.csrfToken
                },
                url: filterData.ajaxUrl,
                data: {
                    'request': 'remember_ticket',
                    'id': id,
                    'date': filterData.date !== 'today' ? filterData.date : filterData.currentDate,
                    'passengers': filterData.adults + filterData.kids,
                    'departure': departure,
                    'arrival': arrival,
                    'fromCity': fromCity,
                    'toCity': toCity
                },
                success: function (response) {
                    console.log(response);
                    $('.loader').remove();
                    if ($.trim(response.data) === 'ok') {
                        location.href = filterData.routeNext;
                    } else if ($.trim(response.data) === 'late') {
                        out(filterData.msgTicketExpired);
                    }
                }
            });
        }

        function out(msg, txt = '') {
            if (msg == undefined || msg == '' || $('.alert').length > 0) {
                return false;
            }

            let alert = document.createElement('div');
            $(alert).addClass('alert');

            let alertContent = document.createElement('div');
            $(alertContent).addClass('alert_content').appendTo(alert);

            let appendOverlay = document.createElement('div');
            $(appendOverlay).addClass('alert_overlay').appendTo(alert);

            let alertTitle = document.createElement('div');
            $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

            if (txt != '') {
                let alertTxt = document.createElement('div');
                $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
            }

            let closeBtn = document.createElement('button');
            $(closeBtn).addClass('alert_ok').text('OK').appendTo(alertContent);

            $('body').append(alert);
            $(alert).fadeIn();

            $('.alert_ok,.alert_overlay').on('click', function () {
                $('.alert').fadeOut();
                setTimeout(function () {
                    $('.alert').remove();
                }, 350);
            });
        }

        function toggleFilterParams(item) {
            $(item).next().slideToggle();
            setTimeout(function () {
                $(item).toggleClass('active');
            }, 400);
        }

        function toggleRouteDetails(id, departure, arrival) {
            if (parseInt(id) > 0) {
                $('body').prepend('<div class="loader"></div>');
                $.ajax({
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': filterData.csrfToken
                    },
                    url: filterData.ajaxUrl,
                    data: {
                        'request': 'route_details',
                        'id': id,
                        'departure': departure,
                        'arrival': arrival
                    },
                    success: function (response) {
                        $('.loader').remove();
                        if ($.trim(response) != 'err') {
                            $('.route_details_popup').html(response.data).toggleClass('active');
                            $('.route_details_overlay').fadeToggle();
                            $('body').toggleClass('overflow');
                        } else {
                            out('Ошибка');
                        }
                    }
                });
            } else {
                $('.route_details_popup').html('').toggleClass('active');
                $('.route_details_overlay').fadeToggle();
                $('body').toggleClass('overflow');
            }
        }

        function toggleInfoBlock(item) {
            $(item).next().slideToggle();
            $(item).find('img').toggleClass('rotate');
        }

        function toggleMobileFilter() {
            $('.catalog_filter').toggleClass('active');
            $('.catalog_filter_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }

        function changeSort(item) {
            $('.sort_option').not(item).removeClass('active');
            if ($(item).hasClass('active')) {
                $(item).toggleClass('desc').toggleClass('asc');
                if ($(item).hasClass('desc')) {
                    $(item).attr('data-sort-direction', '1');
                } else if ($(item).hasClass('asc')) {
                    $(item).attr('data-sort-direction', '2');
                }
                $('.ticket_cards_wrapper').toggleClass('reverse');
            }
            $(item).addClass('active');
            $('.sort_select').val($(item).attr('data-sort'));
            $('.sort_select').niceSelect('update');
        }

        function filterTickets() {
            let min_price = parseInt($('.filter_price_min').text());
            let max_price = parseInt($('.filter_price_max').text());
            let stops = $('.stops_option:checked').val();
            let departure_time = [];

            if ($('.departure_time_option:checked').length > 0) {
                $('.departure_time_option:checked').each(function () {
                    departure_time.push($(this).val());
                });
            }

            if (departure_time.includes('1') && departure_time.length === 1) {
                departure_time = [];
            }

            let arrival_time = [];
            if ($('.arrival_time_option:checked').length > 0) {
                $('.arrival_time_option:checked').each(function () {
                    arrival_time.push($(this).val());
                });
            }

            if (arrival_time.includes('1') && arrival_time.length === 1) {
                arrival_time = [];
            }

            let departure_station = [];
            if ($('.departure_station_checker:checked').length > 0) {
                $('.departure_station_checker:checked').each(function () {
                    departure_station.push($(this).val());
                });
            }

            let arrival_station = [];
            if ($('.arrival_station_checker:checked').length > 0) {
                $('.arrival_station_checker:checked').each(function () {
                    arrival_station.push($(this).val());
                });
            }

            let comfort = [];
            if ($('.bus_options_checker:checked').length > 0) {
                $('.bus_options_checker:checked').each(function () {
                    comfort.push($(this).val());
                });
            }

            let sort_option = $('.sort_option.active').attr('data-sort');
            let sort_direction = $('.sort_option.active').attr('data-sort-direction');

            $('body').prepend('<div class="loader"></div>');
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': filterData.csrfToken
                },
                url: filterData.ajaxUrl,
                data: {
                    'request': 'filter',
                    'stops': stops,
                    'departure_time': departure_time,
                    'arrival_time': arrival_time,
                    'departure_station': departure_station,
                    'arrival_station': arrival_station,
                    'comfort': comfort,
                    'sort_option': sort_option,
                    'sort_direction': sort_direction,
                    'arrival_city': filterData.arrival,
                    'departure_city': filterData.departure,
                    'adults': filterData.adults,
                    'kids': filterData.kids,
                    'date': filterData.date !== 'today' ? filterData.date : filterData.currentDate,
                    'min_price': min_price,
                    'max_price': max_price
                },
                success: function (response) {
                    $('.loader').remove();
                    if ($.trim(response) != 'err') {
                        $('.catalog_elements').html(response);
                        // Переинициализируем обработчики для новых элементов
                        initEventHandlers();
                    } else {
                        out('Ошибка');
                    }
                }
            });
        }

        // ========== ИНИЦИАЛИЗАЦИЯ ОБРАБОТЧИКОВ СОБЫТИЙ ==========

        function initEventHandlers() {
            // Обработчик для кнопок сортировки
            $(document).off('click', '.js-sort-btn').on('click', '.js-sort-btn', function(e) {
                e.preventDefault();
                changeSort(this);
            });

            // Обработчик для кнопки мобильного фильтра
            $(document).off('click', '.js-mobile-filter-toggle').on('click', '.js-mobile-filter-toggle', function(e) {
                e.preventDefault();
                toggleMobileFilter();
            });

            // Обработчик для оверлея мобильного фильтра
            $(document).off('click', '.js-mobile-filter-overlay').on('click', '.js-mobile-filter-overlay', function(e) {
                e.preventDefault();
                toggleMobileFilter();
            });

            // Обработчик для кнопок детальной информации о маршруте
            $(document).off('click', '.js-route-details-btn').on('click', '.js-route-details-btn', function(e) {
                e.preventDefault();
                const ticketId = $(this).data('ticket-id');
                const departureId = $(this).data('departure-id');
                const arrivalId = $(this).data('arrival-id');
                toggleRouteDetails(ticketId, departureId, arrivalId);
            });

            // Обработчик для оверлея детальной информации
            $(document).off('click', '.js-route-details-overlay').on('click', '.js-route-details-overlay', function(e) {
                e.preventDefault();
                const ticketId = $(this).data('ticket-id');
                toggleRouteDetails(ticketId);
            });

            // Обработчик для кнопок покупки билета
            $(document).off('click', '.js-buy-ticket-btn').on('click', '.js-buy-ticket-btn', function(e) {
                e.preventDefault();
                const ticketId = $(this).data('ticket-id');
                const departureId = $(this).data('departure-id');
                const arrivalId = $(this).data('arrival-id');
                const filterDeparture = $(this).data('filter-departure');
                const filterArrival = $(this).data('filter-arrival');
                buyTicket(this, ticketId, departureId, arrivalId, filterDeparture, filterArrival);
            });
        }

        // ========== ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ ==========

        $(document).ready(function() {
            // Инициализация обработчиков событий
            initEventHandlers();

            // Инициализация слайдера Slick
            $('.purchase_steps').slick({
                slidesToShow: 4,
                slidesToScroll: 1,
                dots: false,
                arrows: false,
                infinite: false,
                variableWidth: true,
                responsive: [
                    {
                        breakpoint: 576,
                        settings: {
                            infinite: false,
                            slidesToShow: 1
                        }
                    },
                ]
            });

            // Инициализация Nice Select
            $('.sort_select').niceSelect();

            // Инициализация слайдера цен
            $("#price_range").slider({
                range: true,
                min: filterData.minPrice,
                max: filterData.maxPrice,
                values: [filterData.minPrice, filterData.maxPrice],
                slide: function (event, ui) {
                    $(".filter_price_min").text(ui.values[0]);
                    $(".filter_price_max").text(ui.values[1]);
                },
                stop: function(event, ui) {
                    filterTickets();
                }
            });

            // Обработчик для фильтров
            $('.filter_option').on('change', function () {
                filterTickets();
            });

            // Обработчик для рекомендованных дат
            $('.tour_date_link').on('click', function(e) {
                e.preventDefault();
                const selectedDate = $(this).data('date');
                window.location.href = filterData.routeTickets + '?departure=' + filterData.departure + '&arrival=' + filterData.arrival + '&date=' + selectedDate;
            });
        });
    </script>
@endsection
