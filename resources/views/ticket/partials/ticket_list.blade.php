<div class="catalog_elements_title h3_title">
    @lang('dictionary.MSG_MSG_TICKETS_ZNAJDENO') {{ count($tickets) }} @lang('dictionary.MSG_MSG_TICKETS_AVTOBUSIV')
</div>
<div class="catalog_elements_subtitle par">
    @lang('dictionary.MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA_TA_PRIBUTTYA_MISCEVIJ')
</div>

<div class="ticket_cards_wrapper">
    @foreach ($tickets as $ticket)
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
                                            {{-- Скрыто по просьбе клиента (некорректный расчет)
                                            <div class="ticket_ride_total_time par">
                                                {{ (int)explode(':', $ticket['ride_time'])[0] }} @lang('dictionary.MSG_MSG_TICKETS_GOD')
                                                {{ (int)explode(':', $ticket['ride_time'])[1] }} @lang('dictionary.MSG_MSG_TICKETS_HV_V_DOROZI')
                                            </div>
                                            --}}
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

                    <button class="ticket_details_btn shedule_link flex_ac hidden-md hidden-sm hidden-xs"
                            onclick="toggleRouteDetails('{{ $ticket['id'] }}','{{ $ticket['departure_details']['id'] }}','{{ $ticket['arrival_details']['id'] }}')">
                        @lang('dictionary.MSG_MSG_TICKETS_DETALINISHE')
                        <img src="{{ asset('images/legacy/common/arrow_down_2.svg') }}" alt="arrow down">
                    </button>
                </div>

                <div class="col-lg-3 hidden-md hidden-sm hidden-xs">
                    <div class="ticket_totals">
                        <div class="ticket_price">{{ $ticket['ticket_price'] }} ₴</div>
                        <button class="ticket_buy_btn flex_ac h5_title blue_btn"
                                onclick="buyTicket(this,'{{ $ticket['id'] }}','{{ $ticket['departure_details']['id'] }}','{{ $ticket['arrival_details']['id'] }}', '{{ $filterDeparture }}', '{{ $filterArrival }}')">
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
                                {{-- Скрыто по просьбе клиента (некорректный расчет)
                                <div class="ticket_ride_total_time par">
                                    {{ (int)explode(':', $ticket['ride_time'])[0] }} @lang('dictionary.MSG_MSG_TICKETS_GOD')
                                    {{ (int)explode(':', $ticket['ride_time'])[1] }} @lang('dictionary.MSG_MSG_TICKETS_HV_V_DOROZI')
                                </div>
                                --}}
                            </div>
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
                            <button class="ticket_details_btn shedule_link flex_ac"
                                    onclick="toggleRouteDetails('{{ $ticket['id'] }}','{{ $ticket['departure_details']['id'] }}','{{ $ticket['arrival_details']['id'] }}')">
                                @lang('dictionary.MSG_MSG_TICKETS_DETALINISHE')
                                <img src="{{ asset('images/legacy/common/arrow_down_2.svg') }}" alt="arrow down">
                            </button>
                        </div>
                        <button class="ticket_buy_btn flex_ac h5_title blue_btn"
                                onclick="buyTicket(this,'{{ $ticket['id'] }}','{{ $ticket['departure_details']['id'] }}','{{ $ticket['arrival_details']['id'] }}', '{{ $filterDeparture }}', '{{ $filterArrival }}')">
                            @lang('dictionary.MSG_MSG_TICKETS_KUPITI_KVITOK')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
