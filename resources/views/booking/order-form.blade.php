{{-- Форма оформления билета --}}
@php
    // Нормализуем количество пассажиров (чтобы не было строк/нулей)
    $passengersCount = (int)($passengers ?? 1);
    if ($passengersCount < 1) { $passengersCount = 1; }
@endphp

<div class="ticket_order_block shadow_block">
    <div class="block_title h2_title">
        @lang('dictionary.MSG_MSG_BOOKING_OFORMLENNYA_KVITKA')
    </div>

    <div class="ticket_order_block_subtitle par">
        @lang('dictionary.MSG_MSG_BOOKING_ZAZNACHENI_DANI_NEOBHIDNI_DLYA_ZDIJSNENNYA_BRONYUVANNYA_I_BUDUTI_PEREVIRENI_PID_CHAS_POSADKI_V_AVTOBUS')
    </div>

    <div class="customer_data" id="b2_passengers_wrap" data-max-passengers="{{ $passengersCount }}">

        {{-- Пассажир №1 (всегда видимый) --}}
        <div class="b2_passenger_title">
            Контактные данные пассажира №1
        </div>

        <div class="b2_grid">
            <div class="row">
                <input
                    type="text"
                    class="c_input par req_input"
                    data-passengers-family-name
                    placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')"
                    id="family_name"
                    value="{{ $clientInfo['second_name'] ?? '' }}"
                >
            </div>

            <div class="row">
                <input
                    type="text"
                    class="c_input par req_input"
                    data-passengers-family-name
                    placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')"
                    id="name"
                    value="{{ $clientInfo['name'] ?? '' }}"
                >
            </div>
        </div>

        <div class="b2_free_seat">
            @lang('dictionary.MSG_MSG_BOOKING_VILINA_ROZSADKA') <span class="b2_req">*</span>
        </div>

        {{-- Остальные пассажиры (сразу в DOM, но скрыты) --}}
        @for ($i = 1; $i < $passengersCount; $i++)
            <div class="b2_passenger_wrap js_passenger_block is_hidden" data-passenger-index="{{ $i }}" style="display:none;">
                <div class="b2_passenger_title">
                    Контактные данные пассажира №{{ $i + 1 }}
                    <span class="b2_remove_dot" title="Пассажир добавлен"></span>
                </div>

                <div class="b2_grid">
                    <div class="row">
                        <input
                            type="text"
                            class="c_input par req_input"
                            data-passengers-family-name
                            placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')"
                            name="passengers[{{ $i }}][family_name]"
                            value=""
                        >
                    </div>

                    <div class="row">
                        <input
                            type="text"
                            class="c_input par req_input"
                            data-passengers-family-name
                            placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')"
                            name="passengers[{{ $i }}][name]"
                            value=""
                        >
                    </div>

                    <input type="hidden" name="passengers[{{ $i }}][patronymic]" value="">
                    <input type="hidden" name="passengers[{{ $i }}][birthdate]" value="">
                </div>
            </div>
        @endfor

        {{-- Строка “Добавить пассажира” — РЕНДЕРИМ ВСЕГДА.
            Если пассажиров реально нет, JS сам спрячeт. --}}
        <div class="b2_add_row" id="b2_add_row">
            <button type="button" class="b2_add_btn" id="b2_add_passenger_btn">+</button>

            <button type="button" class="b2_add_text_btn" id="b2_add_passenger_text">
                Добавить пассажира<span class="b2_req">*</span>
            </button>
        </div>

    </div>
</div>
