{{-- Маршрут (V2 под дизайн как на фото) --}}
<div class="route_block shadow_block">
    <div class="block_title">Маршрут</div>

    @php
        $depTime = date('H:i', strtotime($ticketInfo['departure_time'] ?? '00:00'));
        $arrTime = date('H:i', strtotime($ticketInfo['arrival_time'] ?? '00:00'));

        if (($ticketInfo['departure_city'] ?? '') != ($ticketInfo['departure_station'] ?? '')) {
            $depTitle = trim(($ticketInfo['departure_city'] ?? '').' '.($ticketInfo['departure_station'] ?? ''));
        } else {
            $depTitle = trim($ticketInfo['departure_city'] ?? '');
        }

        if (($ticketInfo['arrival_city'] ?? '') != ($ticketInfo['arrival_station'] ?? '')) {
            $arrTitle = trim(($ticketInfo['arrival_city'] ?? '').' '.($ticketInfo['arrival_station'] ?? ''));
        } else {
            $arrTitle = trim($ticketInfo['arrival_city'] ?? '');
        }

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

                $durationText = $h.' ч. '.$m.' мин.';
            } catch (\Throwable $e) {
                $durationText = '—';
            }
        }

        // Пути к иконкам (замени под свою структуру, если нужно)
        $iconFrom = asset('images/booking/city-from.png');
        $iconTo   = asset('images/booking/city-to.png');
        $iconCal  = asset('images/booking/calendar.png');
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
            Время в пути<br>
            <span class="b2_route_duration_val">{{ $durationText }}</span>
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
        <span>Когда :</span>
        <strong class="b2_row_right">
            {{ $formattedDate ?? date('d.m.Y') }}
            <img class="b2_row_icon" src="{{ $iconCal }}" alt="" loading="lazy" decoding="async">
        </strong>
    </div>

    {{-- Пассажиров --}}
    <div class="b2_row">
        <span>Пассажиров:</span>
        <strong>{{ $passengers }}</strong>
    </div>

    <div class="b2_divider"></div>

    {{-- Цена / К оплате --}}
    <div class="b2_price_row">
        <span>Цена:</span>
        <span class="val">{{ $ticketInfo['price'] ?? 0 }} грн</span>
    </div>

    <div class="b2_price_row">
        <span>К оплате:</span>
        <span class="val">{{ $totalPrice }} грн</span>
    </div>

    <button class="b2_pay_btn" type="button" onclick="goPayment()">Перейти к оплате</button>
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

</style>
