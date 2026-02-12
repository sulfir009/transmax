{{-- Маршрут (V2 под дизайн как на фото) --}}
<div class="route_block shadow_block">
    <div class="block_title">@lang('dictionary.BOOKING_ROUTE_TITLE')</div>

    @php
        $depTime = date('H:i', strtotime($ticketInfo['departure_time'] ?? '00:00'));
        $arrTime = date('H:i', strtotime($ticketInfo['arrival_time'] ?? '00:00'));

        if (($ticketInfo['departure_city'] ?? '') != ($ticketInfo['departure_station'] ?? '')) {
            $depTitle = trim(($ticketInfo['departure_city'] ?? '') . ' ' . ($ticketInfo['departure_station'] ?? ''));
        } else {
            $depTitle = trim($ticketInfo['departure_city'] ?? '');
        }

        if (($ticketInfo['arrival_city'] ?? '') != ($ticketInfo['arrival_station'] ?? '')) {
            $arrTitle = trim(($ticketInfo['arrival_city'] ?? '') . ' ' . ($ticketInfo['arrival_station'] ?? ''));
        } else {
            $arrTitle = trim($ticketInfo['arrival_city'] ?? '');
        }

        $depTitle = html_entity_decode($depTitle, ENT_QUOTES, 'UTF-8');
        $arrTitle = html_entity_decode($arrTitle, ENT_QUOTES, 'UTF-8');

        // Длительность: пытаемся взять из данных, иначе считаем по времени
        $durationText = $ticketInfo['duration'] ?? $ticketInfo['travel_time'] ?? '';

        if (empty($durationText)) {
            try {
                $depStr = substr($ticketInfo['departure_time'] ?? '00:00', 0, 5);
                $arrStr = substr($ticketInfo['arrival_time'] ?? '00:00', 0, 5);

                $dep = \Carbon\Carbon::createFromFormat('H:i', $depStr);
                $arr = \Carbon\Carbon::createFromFormat('H:i', $arrStr);

                if ($arr->lessThan($dep)) {
                    $arr->addDay();
                }

                $diffMin = $dep->diffInMinutes($arr);
                $h = intdiv($diffMin, 60);
                $m = $diffMin % 60;

                $durationText = __('dictionary.BOOKING_TRAVEL_TIME_FORMAT', ['hours' => $h, 'minutes' => $m]);
            } catch (\Throwable $e) {
                $durationText = __('dictionary.BOOKING_DASH_PLACEHOLDER');
            }
        }

        // Пути к иконкам
        $iconFrom = asset('images/booking/left.svg');
        $iconTo   = asset('images/booking/right.svg');
        $iconCal  = asset('images/booking/calendar.png');

        // ===== ВАЖНО ДЛЯ ПЕРЕСЧЁТА ЦЕНЫ В JS =====

        // Валюта (оставляю "грн", как у тебя в верстке)
        $currency = __('dictionary.BOOKING_CURRENCY_UAH');

        $pricePerPassengerCents = isset($pricePerPassengerCents)
            ? (int) $pricePerPassengerCents
            : \App\Support\Money::priceToKopeksFromDb($ticketInfo['price'] ?? 0);

        $totalPriceCents = isset($totalPriceCents)
            ? (int) $totalPriceCents
            : ($pricePerPassengerCents * (int) ($passengers ?? 1));
    @endphp

    {{-- Верхняя часть: время + 2 иконки городов + линия --}}
    <div class="b2_route_top b2_route_top--mobileLike">
        {{-- LEFT --}}
        <div class="b2_route_side">
            <div class="b2_route_time">{{ $depTime }}</div>
            <div class="b2_route_time_underline"></div>
            <img class="b2_route_city_icon" src="{{ $iconFrom }}" alt="" loading="lazy">
            <div class="b2_route_city">{{ $depTitle }}</div>
        </div>

{{-- MID --}}
<div class="b2_route_mid">
    <div class="b2_route_duration">
        @lang('dictionary.BOOKING_TRAVEL_TIME_LABEL') <span class="b2_route_duration_val">{{ $durationText }}</span>
    </div>
    <div class="b2_route_line"></div>
</div>


        {{-- RIGHT --}}
        <div class="b2_route_side right">
            <div class="b2_route_time">{{ $arrTime }}</div>
            <div class="b2_route_time_underline"></div>
            <img class="b2_route_city_icon" src="{{ $iconTo }}" alt="" loading="lazy">
            <div class="b2_route_city">{{ $arrTitle }}</div>
        </div>
    </div>

    {{-- Разделитель как в дизайне --}}
    <div class="b2_divider b2_divider--thin"></div>

    {{-- Когда (справа иконка календаря) --}}
    <div class="b2_row b2_row--with_icon">
        <span>@lang('dictionary.BOOKING_WHEN_LABEL'):</span>
        <strong class="b2_row_right">
            {{ $formattedDate ?? date('d.m.Y') }}
            <img class="b2_row_icon" src="{{ $iconCal }}" alt="" loading="lazy" decoding="async">
        </strong>
    </div>

    {{-- Пассажиров --}}
    <div class="b2_row">
        <span>@lang('dictionary.BOOKING_PASSENGERS_LABEL'):</span>
        <strong id="js_passengers_count">{{ $passengers }}</strong>
    </div>

    <div class="b2_divider"></div>

    {{-- Цена / К оплате --}}
    <div class="b2_price_row">
        <span>@lang('dictionary.BOOKING_PRICE_LABEL'):</span>
        <span class="val">{{ $ticketInfo['price'] ?? 0 }} {{ $currency }}</span>
    </div>

    <div class="b2_price_row">
        <span>@lang('dictionary.BOOKING_TO_PAY_LABEL'):</span>
        <span class="val">
            <span id="js_total_price" data-total-cents="{{ $totalPriceCents }}">{{ $totalPrice }}</span>
            <span id="js_currency">{{ $currency }}</span>
        </span>
    </div>
    
        @if(!empty($bonusEligible))
        <div class="b2_divider"></div>
        <div class="b2_bonus_block"
             data-bonus-balance-cents="{{ $bonusBalanceCents ?? 0 }}"
             data-order-id="{{ $order['order_db_id'] ?? 0 }}">
            <div class="b2_row">
                <span>@lang('dictionary.BOOKING_BONUS_BALANCE_LABEL'):</span>
                <strong>{{ $bonusBalanceFormatted ?? '0' }} {{ $currency }}</strong>
            </div>
            <label class="b2_bonus_checkbox">
                <input type="checkbox"
                       id="js_use_bonus"
                       {{ ($bonusBalanceCents ?? 0) > 0 ? '' : 'disabled' }} />
                <span>@lang('dictionary.BOOKING_PAY_WITH_BONUSES_LABEL')</span>
            </label>
            <div class="b2_row b2_bonus_row">
                <span>@lang('dictionary.BOOKING_BONUSES_WILL_BE_REDEEMED_LABEL'):</span>
                <strong><span id="js_bonus_redeem">0</span> {{ $currency }}</strong>
            </div>
        </div>
    @endif


    {{-- СКРЫТЫЕ ДАННЫЕ ДЛЯ JS-ПЕРЕСЧЁТА --}}
    <div id="js_price_meta"
         data-price-per-passenger-cents="{{ $pricePerPassengerCents }}"
         data-currency="{{ $currency }}"
         style="display:none !important;"></div>

    <button class="b2_pay_btn" type="button" onclick="goPaymentV2()">@lang('dictionary.BOOKING_GO_TO_PAYMENT_BUTTON')</button>
</div>

{{-- СТИЛИ только для route-info (чтобы не ломать остальное) --}}
<style>
    /* контейнер строки "иконка + город" */
    .booking_v2 .b2_route_city_line{
        display:flex;
        align-items:center;
        gap:6px;
        margin-top:6px;
    }

    .booking_v2 .b2_route_city_line.right{
        justify-content:flex-end;
    }

    /* 2 разные картинки (левая и правая) */
    .booking_v2 .b2_city_pic{
        width: 26px;
        height: 26px;
        display:block;
        object-fit:contain;
        flex: 0 0 auto;
    }

    /* тонкий разделитель после блока маршрута (как на фото) */
    .booking_v2 .b2_divider--thin{
        margin: 10px 0 8px;
        background:#E9ECEC;
    }

    /* строка "Когда" с иконкой календаря справа */
    .booking_v2 .b2_row--with_icon .b2_row_right{
        display:inline-flex;
        align-items:center;
        gap:8px;
    }

    .booking_v2 .b2_row_icon{
        width:16px;
        height:16px;
        display:block;
        object-fit:contain;
        flex:0 0 auto;
        opacity:.95;
    }

    /* чтобы правый блок не "скакал" по высоте */
    .booking_v2 .b2_route_col.right .b2_route_city_line{
        margin-top:6px;
    }

    .booking_v2 .b2_route_top--mobileLike{
        display:grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: start;
        margin-top: 10px;
    }

    .booking_v2 .b2_route_side{
        text-align: left;
    }
    .booking_v2 .b2_route_side.right{
        text-align: right;
    }
    
        .booking_v2 .b2_bonus_block{
        display:flex;
        flex-direction:column;
        gap:6px;
        font-size:14px;
        color:#303233;
    }

    .booking_v2 .b2_bonus_checkbox{
        display:flex;
        align-items:center;
        gap:8px;
        font-size:14px;
        cursor:pointer;
    }

    .booking_v2 .b2_bonus_checkbox input{
        width:16px;
        height:16px;
    }

    .booking_v2 .b2_bonus_row strong{
        font-weight:700;
    }


    .booking_v2 .b2_route_time{
        font-weight: 800;
        font-size: 14px;
        color:#303233;
    }

    .booking_v2 .b2_route_time_underline{
        width: 46px;
        height: 2px;
        background:#A3E8F9;
        border-radius: 2px;
        margin-top: 6px;
        margin-bottom: 10px;
    }
    .booking_v2 .b2_route_side.right .b2_route_time_underline{
        margin-left: auto;
    }

    .booking_v2 .b2_route_city_icon{
        width: 54px;
        height: 54px;
        object-fit: contain;
        display:block;
        margin: 0 0 6px;
    }
    .booking_v2 .b2_route_side.right .b2_route_city_icon{
        margin-left: auto;
    }

    .booking_v2 .b2_route_city{
        font-weight: 700;
        font-size: 10px;
        color:#6E7172;
        line-height: 1.2;
    }

    .booking_v2 .b2_route_mid{
        text-align:center;
        padding-top: 22px;
    }
    .booking_v2 .b2_route_duration{
        font-weight: 700;
        font-size: 10px;
        color:#303233;
        line-height: 1.15;
    }
    .booking_v2 .b2_route_duration_val{
        font-weight: 800;
    }

    .booking_v2 .b2_route_line{
        margin: 10px auto 0;
        width: 140px;
        height: 2px;
        background: linear-gradient(180deg,#63D5F8,#34B9F0);
        border-radius: 999px;
    }
    
    /* ===== ROUTE TOP (как на дизайне) ===== */
.booking_v2 .b2_route_top--mobileLike{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    margin-top:10px;
}

/* Левая/правая колонка */
.booking_v2 .b2_route_side{
    flex:0 0 165px;              /* ширина колонки как “в дизайне” */
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    text-align:left;
}

.booking_v2 .b2_route_side.right{
    align-items:flex-end;
    text-align:right;
}

/* Время сверху */
.booking_v2 .b2_route_time{
    font-weight:800;
    font-size:20px;              /* на макете заметно больше чем 14 */
    line-height:1;
    color:#303233;
}

/* Подчеркивание времени */
.booking_v2 .b2_route_time_underline{
    width:74px;                  /* как на картинке — длиннее чем “46px” */
    height:3px;
    background:#A3E8F9;
    border-radius:999px;
    margin-top:8px;
    margin-bottom:16px;
}

/* Иконка города */
.booking_v2 .b2_route_city_icon{
    width:46px;
    height:46px;
    object-fit:contain;
    display:block;
    margin:0 0 10px;
}

/* Название станции/города */
.booking_v2 .b2_route_city{
    font-weight:600;
    font-size:14px;              /* у тебя было 10 — слишком мелко */
    color:#6E7172;
    line-height:1.2;
    max-width:165px;
    word-break:break-word;
}

/* Средняя часть растягивается */
.booking_v2 .b2_route_mid{
    flex:1 1 auto;
    min-width:0;
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:0 10px;
    padding-top:clamp(26px, 4vw, 42px);  /* выравнивает “Время в пути” по высоте с иконками */
}

/* Текст “Время в пути 8 ч. 48 мин.” */
.booking_v2 .b2_route_duration{
    font-weight:600;
    font-size:14px;
    color:#303233;
    line-height:1.2;
    display:flex;
    gap:6px;
    align-items:baseline;
    justify-content:center;
    text-align:center;
    white-space:nowrap;          /* как на дизайне — одной строкой */
}

.booking_v2 .b2_route_duration_val{
    font-weight:800;
    font-size:15px;              /* чуть выразительнее */
}

/* Линия маршрута + стрелка справа */
.booking_v2 .b2_route_line{
    position:relative;
    height:3px;
    margin-top:18px;

    /* делаем шире, чтобы почти доходила до иконок */
    width:calc(100% + 195px);
    margin-left:-8px;
    margin-right:-8px;

    background:linear-gradient(90deg, #63D5F8, #34B9F0);
    border-radius:999px;
}

/* Стрелка */
.booking_v2 .b2_route_line:after{
    content:"";
    position:absolute;
    right:-1px;
    top:50%;
    transform:translateY(-50%);
    width:0;
    height:0;
    border-left:10px solid #34B9F0;
    border-top:6px solid transparent;
    border-bottom:6px solid transparent;
}

/* Адаптация под узкие экраны (если вдруг этот блок реально на мобиле) */
@media (max-width: 480px){
    .booking_v2 .b2_route_line{
    width:calc(100% + 24px);
}
.booking_v2 .b2_route_duration{
            width: 100px;
}
    .booking_v2 .b2_route_side{ flex-basis:130px; }
    .booking_v2 .b2_route_time{ font-size:18px; }
    .booking_v2 .b2_route_city{ font-size:12px; max-width:130px; }
    .booking_v2 .b2_route_city_icon{ width:40px; height:40px; }
    .booking_v2 .b2_route_duration{
        font-size:12px;
        white-space:normal;      /* на мобиле можно переносить, чтобы не ломало */
        flex-wrap:wrap;
        row-gap:4px;
    }
}

</style>
