<div class="route_block">
    <div class="route_block_title h3_title hidden-md hidden-sm hidden-xs">
        @lang('dictionary.MSG_MSG_BOOKING_MARSHRUT')
    </div>
    <div class="mobile_route_block_title flex_ac h3_title hidden-xxl hidden-xl hidden-lg" onclick="toggleRouteInfo(this)">
        @lang('dictionary.MSG_MSG_BOOKING_MARSHRUT')
        <img src="{!! asset('images/legacy/common/arrow_down_2.svg') !!}" alt="arrow down">
    </div>

    <div class="route">
        <div class="route_details_info">
            <div class="route_points">
                <div class="route_point_block par">
                    <div class="route_point active"></div>
                    <div class="route_time">
                        {!! date('H:i', strtotime($ticketInfo['departure_time'] ?? '00:00')) !!}
                    </div>
                    <div class="route_point_title">
                        @if(($ticketInfo['departure_city'] ?? '') != ($ticketInfo['departure_station'] ?? ''))
                            {!! $ticketInfo['departure_city'] ?? '' !!} {!! $ticketInfo['departure_station'] ?? '' !!}
                        @else
                            {!! $ticketInfo['departure_city'] ?? '' !!}
                        @endif
                    </div>
                </div>

                <div class="route_point_block par">
                    <div class="route_point"></div>
                    <div class="route_time">
                        {!! date('H:i', strtotime($ticketInfo['arrival_time'] ?? '00:00')) !!}
                    </div>
                    <div class="route_point_title">
                        @if(($ticketInfo['arrival_city'] ?? '') != ($ticketInfo['arrival_station'] ?? ''))
                            {!! $ticketInfo['arrival_city'] ?? '' !!} {!! $ticketInfo['arrival_station'] ?? '' !!}
                        @else
                            {!! $ticketInfo['arrival_city'] ?? '' !!}
                        @endif
                    </div>
                </div>
            </div>

            <div class="filter_block_wrapper">
                <div class="filter_date_wrapper">
                    <div class="filter_date_title par">@lang('dictionary.MSG_ALL_KOLI')</div>
                    <input type="text"
                           class="filter_date_booking"
                           name="date"
                           value="{!! $formattedDate ?? date('d.m.Y') !!}"
                           data-date="{!! $tourDate ?? date('Y-m-d') !!}"
                           readonly>
                    <button class="filter_calendar_btn" onclick="toggleFilterCalendar()" type="button">
                        <img src="{!! asset('images/legacy/common/filter_calendar.svg') !!}" alt="calendar" class="fit_img">
                    </button>
                </div>
            </div>

            <div class="route_options flex-row gap-y-20">
                @foreach ($busOptions as $k => $busOption)
                    <div class="col-md-{!! $k % 2 == 0 ? '5' : '7' !!}">
                        <div class="bus_option flex_ac par">
                            <div class="check_imitation"></div>
                            {!! $busOption['title'] !!}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="route_passagers h5_title">
                <span>@lang('dictionary.MSG_MSG_BOOKING_PASAZHIRIV')</span>
                <span>{!! $passengers !!}</span>
            </div>
        </div>

        <div class="route_details_delimiter"></div>

        <div class="route_details_info">
            <div class="route_price h4_title flex_ac">
                @lang('dictionary.MSG_MSG_BOOKING_CINA')
                <span class="total_price h3_title">
                    {!! $ticketInfo['price'] ?? 0 !!} @lang('dictionary.MSG_MSG_BOOKING_GRN')
                </span>
            </div>

            <div class="route_price h4_title flex_ac route_payment_price">
                @lang('dictionary.MSG_MSG_BOOKING_DO_SPLATI')
                <span class="total_price h3_title">
                    {!! $totalPrice !!} @lang('dictionary.MSG_MSG_BOOKING_GRN')
                </span>
            </div>

            <a href="{!! $Router->writelink(87) !!}" class="small_link">
                @lang('lincence')
            </a>
        </div>
    </div>
</div>
