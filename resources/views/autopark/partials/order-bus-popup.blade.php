<div class="order_bus_popup blue_popup">
    <div class="order_bus_popup_content_wrapper">
        <div class="close_order_bus_wrapper">
            <button class="close_menu" onclick="toggleOrderBus()">
                <img src="{{ asset('images/legacy/common/arrow_left.svg') }}" alt="arrow left">
            </button>
        </div>
        <div class="order_bus_popup_content">
            {{-- Имя --}}
            <div class="order_bus_row">
                <div class="order_bus_row_title">@lang('MSG_MSG_BUSES_ZVIDKI')</div>
                <input type="text" class="c_input order_bus_input par req_input" 
                       placeholder="@lang('MSG_MSG_BOOKING_IMYA_')" 
                       id="name">
            </div>
            
            {{-- Телефон --}}
            <div class="order_bus_row">
                <div class="order_bus_row_title">@lang('MSG_MSG_BUSES_KUDI')</div>
                <div class="flex_ac">
                    <select class="phone_country_code flex_ac" onchange="changeInputMask(this)" id="phone_code">
                        @if(isset($phoneCodes['codes']) && !empty($phoneCodes['codes']))
                            @foreach($phoneCodes['codes'] as $index => $phoneCode)
                                @php
                                    $code = (array) $phoneCode;
                                @endphp
                                <option value="{{ $code['id'] }}" 
                                        data-mask="{{ $code['phone_mask'] }}" 
                                        data-placeholder="{{ $code['phone_example'] }}"
                                        @if($index == 0) selected @endif>
                                    {{ $code['phone_country'] }}
                                </option>
                            @endforeach
                        @else
                            <option value="1" data-mask="(999) 999-9999" data-placeholder="(999) 999-9999" selected>
                                Default
                            </option>
                        @endif
                    </select>
                    <input type="text" class="c_input order_bus_phone order_bus_input inter req_input" 
                           placeholder="{{ $phoneCodes['default']['example'] ?? '(999) 999-9999' }}" 
                           id="phone">
                </div>
            </div>
            
            {{-- Дата --}}
            <div class="order_bus_row order_bus_date">
                <div class="order_bus_row_title">@lang('MSG_MSG_BUSES_KOLI')</div>
                <div class="order_bus_date_wrapper">
                    <input type="text" class="order_bus_date_input filter_date" 
                           value="{{ $filterData['date'] }}">
                    <button class="filter_calendar_btn order_bus_calendar_btn" onclick="toggleDateCalendar()">
                        <img src="{{ asset('images/legacy/common/filter_calendar.svg') }}" alt="calendar" class="fit_img">
                    </button>
                </div>
            </div>
            
            {{-- Пассажиры --}}
            <div class="order_bus_row">
                <div class="order_bus_passengers_wrapper">
                    <div class="order_bus_row_title">@lang('MSG_MSG_BUSES_PASAZHIRI')</div>
                    <div class="order_bus_row_value flex_ac" onclick="toggleOrderBusSubmenu(this)">
                        <div>
                            <span class="adults_total">{{ $filterData['adults'] }}</span> @lang('MSG_ALL_DOROSLIH')
                        </div>
                        <div>
                            <span class="kids_total">{{ $filterData['kids'] }}</span> @lang('MSG_MSG_BUSES_DITEJ')
                        </div>
                    </div>

                    <div class="order_bus_row_submenu">
                        {{-- Взрослые --}}
                        <div class="passengers_counter_block flex_ac adult_passagers">
                            <div class="passengers_counter_title h5_title">@lang('MSG_MSG_BUSES_DOROSLIH')</div>
                            <div class="passengers_counter flex_ac">
                                <button class="counter_btn minus" onclick="countPassagers(this,'minus','adults')">
                                    <img src="{{ asset('images/legacy/common/minus.svg') }}" alt="minus">
                                </button>
                                <div class="p_counter_value par">{{ $filterData['adults'] }}</div>
                                <button class="counter_btn plus" onclick="countPassagers(this,'plus','adults', $seats)">
                                    <img src="{{ asset('images/legacy/common/plus.svg') }}" alt="plus">
                                </button>
                            </div>
                        </div>
                        
                        {{-- Дети --}}
                        <div class="passengers_counter_block flex_ac">
                            <div class="passengers_counter_title h5_title">
                                @lang('MSG_MSG_BUSES_DITEJ')
                                <span>@lang('MSG_MSG_BUSES_DO_3_ROKIV_-_BEZKOSHTOVNO')</span>
                            </div>
                            <div class="passengers_counter flex_ac">
                                <button class="counter_btn minus" onclick="countPassagers(this,'minus','kids')">
                                    <img src="{{ asset('images/legacy/common/minus.svg') }}" alt="minus">
                                </button>
                                <div class="p_counter_value par">{{ $filterData['kids'] }}</div>
                                <button class="counter_btn plus" onclick="countPassagers(this,'plus','kids', $seats)">
                                    <img src="{{ asset('images/legacy/common/plus.svg') }}" alt="plus">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <button class="blue_btn flex_ac h4_title order_bus_btn" onclick="orderBus()">
                @lang('MSG_MSG_BUSES_ZABRONYUVATI_AVTOBUS')
            </button>
        </div>
        
        {{-- Правила бронирования --}}
        <div class="order_bus_rules">
            <div class="order_bus_rules_title h3_title flex_ac"
                 onclick="$(this).next().slideToggle();$(this).find('img').toggleClass('rotate')">
                @lang('MSG_MSG_BUSES_PRAVILA_BRONYUVANNYA_AVTOBUSA')
                <img src="{{ asset('images/legacy/common/arrow_down.svg') }}" alt="arrow">
            </div>
            <div class="order_bus_rules_txt par">
                {!! $bookingRules['text'] !!}
                <div class="order_bus_rules_txt_warning">
                    <div class="warning_img">
                        <img src="{{ asset('images/legacy/common/warning.svg') }}" alt="warning">
                    </div>
                    <div class="warning_txt par">
                        {!! $bookingRules['warning'] !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="order_bus_overlay overlay" onclick="toggleOrderBus()"></div>

{{-- Скрытое поле для хранения ID выбранного автобуса --}}
<input type="hidden" id="selected_bus_id" value="">
