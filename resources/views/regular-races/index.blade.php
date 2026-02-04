<?php
/**
 * @var $daysRegularRaces \Illuminate\Support\Collection
 * @var $nightRegularRaces \Illuminate\Support\Collection
 * @var $regularRaces \Illuminate\Support\Collection
 * @var $stops \Illuminate\Support\Collection
 * @var $tourStopPrices array
 */
?>
@extends('layout.app')
@section('page-styles')
    <link rel="stylesheet" href=<?php echo  mix('css/legacy/style_table.css'); ?>>
    <link rel="stylesheet" href=<?php echo  mix('css/responsive.css'); ?>>
@endsection
@section('content')
    <div class="content">

        <section class="section_blocks">
            <div class="container">
                <div class="right_blocks_line">
                    <img src="{{ asset('images/legacy/schedule_right_line.png') }}" alt="srl">
                </div>
                <div class="mob_right_blocks_line">
                    <img src="{{ asset('images/legacy/mob_right_block_l.png') }}" alt="srl">
                </div>
                <div class="mob_left_blocks_line">
                    <img src="{{ asset('images/legacy/mob_left_block_l.png') }}" alt="srl">
                </div>
                <div class="mob_pin_bus_block">
                    <img src="{{ asset('images/legacy/mob_pin.png') }}" alt="tpb">
                </div>
                <div class="mob_pin_bus_block_m">
                    <img src="{{ asset('images/legacy/mob_pin.png') }}" alt="tpb">
                </div>
                <h1 class="element" style="margin-top: 55px;">@lang('dictionary.ROUTE_AND_SCHEDULE')</h1>
                
                <div data-content-way>
                    @include('regular-races.components.regular-races',
                        [
                            'regularRaces' => $regularRaces,
                            'stations' => $stations,
                        ]
                    )
                </div>
            </div>
        </section>
        <section class="section_table">
                        @php
                $priceRoutes = [];
                $defaultRaceId = null;

                foreach ($regularRaces as $races) {
                    foreach ($races as $race) {
                        $defaultRaceId = $defaultRaceId ?? $race->id;
                        $priceRoutes[$race->id] = [
                            'label' => trim(($race->departure ?? '') . ' — ' . ($race->arrive ?? '')),
                            'html' => view('regular-races.partials.price-table', [
                                'race' => $race,
                                'tourStopPrices' => $tourStopPrices,
                            ])->render(),
                        ];
                    }
                }
            @endphp
            <div class="container">
                <div class="section_table_line">
                    <img src="{{ asset('images/legacy/line_table_section.png') }}" alt="lts">
                </div>
                <div class="mob_table_line_r">
                    <img src="{{ asset('images/legacy/mob_table_l.png') }}" alt="lts">
                </div>
                <div class="mob_table_line_l">
                    <img src="{{ asset('images/legacy/mob_table_left_l.png') }}" alt="lts">
                </div>
                <div class="mob_pin_bus_table">
                    <img src="{{ asset('images/legacy/mob_pin.png') }}" alt="tpb">
                </div>
                <div class="mob_pin_bus_table_m">
                    <img src="{{ asset('images/legacy/mob_pin.png') }}" alt="tpb">
                </div>
                <div class="pin_bus_table">
                    <img src="{{ asset('images/legacy/pin_bus.png') }}" alt="tpb">
                </div>
                <h1>@lang('road_price')</h1>
                <div class="custom-select-schedule-container price-select-container">
                    <div class="custom-schedule-select-wrapper">
                        <select class="custom-schedule-styled-select" id="priceDirectionSelect">
                            @foreach($priceRoutes as $raceId => $priceRoute)
                                <option value="{{ $raceId }}" {{ $raceId === $defaultRaceId ? 'selected' : '' }}>
                                    {{ $priceRoute['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="priceTableContainer">
                    {!! $defaultRaceId ? ($priceRoutes[$defaultRaceId]['html'] ?? '') : '' !!}
                </div>
            </div>
        </section>
        @if(!empty($priceRoutes))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const select = document.getElementById('priceDirectionSelect');
                    const container = document.getElementById('priceTableContainer');
                    const tables = @json($priceRoutes);

                    if (!select || !container) return;

                    select.addEventListener('change', function () {
                        const selected = select.value;
                        if (tables[selected] && tables[selected].html) {
                            container.innerHTML = tables[selected].html;
                        }
                    });
                });
            </script>
        @endif
        <section class="addition_section">
            <h1>@lang('reg_race_additional_services')</h1>
            <div class="addition_back">
                <div class="row">
                    <div class="col-4">
                        <div class="addition_block">
                            <div class="addition_img">
                                <img src="{{ asset('images/legacy/add1.png') }}" alt="add1" class="img-fluid">
                            </div>
                            <h2>@lang('reg_race_service_title_1')</h2>
                            <p>@lang('reg_race_service_desc_1')</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="addition_block">
                            <div class="addition_img">
                                <img src="{{ asset('images/legacy/add2.png') }}" alt="add1" class="img-fluid">
                            </div>
                            <h2>@lang('reg_race_service_title_2')</h2>
                            <p>@lang('reg_race_service_desc_2')</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="addition_block">
                            <div class="addition_img">
                                <img src="{{ asset('images/legacy/add3.png') }}" alt="add1" class="img-fluid">
                            </div>
                            <h2>@lang('reg_race_service_title_3')</h2>
                            <p>@lang('reg_race_service_desc_3')</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="contact">
            <div class="right_contact_line">
                <img src="{{ asset('images/legacy/contact_right_line.png') }}" alt="crl">
            </div>
            <div class="right_contact_line_m">
                <img src="{{ asset('images/legacy/mob_contact_l.png') }}" alt="crl">
            </div>
            <div class="mob_pin_bus_contact_m">
                <img src="{{ asset('images/legacy/mob_pin.png') }}" alt="crl">
            </div>
            <h2 id="form-callback-reserve">@lang('contact_form')</h2>
            <div class="contact_container">
                <form>
                    <div class="form-container">
                        <div class="contact_block">
                            <div class="form-group">
                                <label class="icons_input" for="date">
                                    <img src="{{ asset('images/legacy/icon_date.png') }}" alt="icon_date">
                                </label>
                                <input id="table_date" type="text" name="date" placeholder="@lang('date')" readonly>
                            </div>
                            <div class="form-group">
                                <label class="icons_input" for="callback_departure">
                                    <img src="{{ asset('images/legacy/icon_a.png') }}" alt="icon_from">
                                </label>
                                <select id="callback_departure" name="departure_callback">
                                    <option value="">@lang('travel_from')</option>
                                    @foreach($stops as $stop)
                                        @php
                                            $has = collect($tourStopPrices)->first(function ($inner) use ($stop) {
                                                return array_key_exists($stop->stop_id, $inner);
                                            });
                                        @endphp
                                        @if($has)
                                            <option
                                                value="{{ $stop->stop_id }}">{!! $stop->stopCity . ' ' . $stop->stopTitle !!}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="icons_input" for="callback_arrival">
                                    <img src="{{ asset('images/legacy/icon_b.png') }}" alt="icon_to">
                                </label>
                                <select id="callback_arrival" name="callback_arrival">
                                    <option value="">@lang('travel_to')</option>
                                    @foreach($stops as $stop)
                                        @php
                                            $has = collect($tourStopPrices)->first(function ($inner) use ($stop) {
                                                return array_key_exists($stop->stop_id, $inner);
                                            });
                                        @endphp
                                        @if($has)
                                            <option
                                                value="{{ $stop->stop_id }}">{!! $stop->stopCity . ' ' . $stop->stopTitle !!}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="icons_input" for="name">
                                    <img src="{{ asset('images/legacy/icon_name.png') }}" alt="icon_name">
                                </label>
                                <input type="text" id="name" name="name" placeholder="@lang('form_fio')">
                            </div>
                            <div class="callback-group d-flex align-items-center gap-2">
                                <label class="icon_input_phone" for="phone">
                                    <img src="{{ asset('images/legacy/icon_phone.png') }}" alt="icon_number">
                                </label>
                                <select class="cb_custom_select cb_input_tel call_select_pop"
                                        id="phone_code"
                                        name="phone_code"
                                        style="max-width: 120px;"
                                        onchange="changeInputMask(this)"
                                >
                                    @foreach($phoneCodes as $k => $code)
                                        <option value="{{ $code->id }}" data-mask="{{ $code->phone_mask }}"
                                                data-placeholder="{{ $code->phone_example }}" {{ $k == 0 ? 'selected' : '' }}> {{ $code->phone_country }}</option>
                                    @endforeach
                                </select>
                                <input class="cb_input_tel form-control cb_phone_input" type="tel" id="callback_phone" name="callback_phone" placeholder="@lang('alias_phone')" style="min-width: 180px;">
                            </div>
                        </div>
                        <div class="form-group textarea_block">
                            <textarea id="callback_message" class="cb_text_area" name="comment" rows="4"
                                      placeholder="@lang('comment')"></textarea>
                        </div>
                        <div class="form-group">
                            <button class="requestCallback send_request_btn" style=" background-color: #4cafef; color: white; cursor: pointer; max-width: 574px; min-width: 323px; padding: 18px 218px;border-radius: 50px;" type="button">
                                @lang('send')
                            </button>
                        </div>
                    </div>

                </form>
            </div>
            <div class="bus_foot">
                <img src="{{ asset('images/legacy/blue_bus_foot.png') }}" alt="">
            </div>
        </section>
    </div>


    <div id="calendar-modal" class="modal">
        <div class="modal-content">
            <p class="calendar-header">@lang('calendar_desc')</p>
            <div id="calendar" class="calendar"></div>
            <div class="modal-buttons">
                <button id="cancel-btn" class="calendar_btn_cancel">@lang('cancel')</button>
                <button id="save-btn" class="calendar_btn">@lang('save')</button>
            </div>
        </div>
    </div>

@endsection
@section('page-scripts')
    <script src="{{ mix('js/regularReces/sripts.js') }}"></script>
@endsection
<style>
    #successModal {
        top:0 !important;
    }

    /* Стили для поля даты */
    #table_date {
        cursor: pointer !important;
        background-color: #FFFFFF !important;
    }

    /* Убираем стандартные стили для readonly поля */
    #table_date[readonly] {
        opacity: 1 !important;
        background-color: #FFFFFF !important;
        cursor: pointer !important;
    }
    /* =========================
   CONTACT FORM — FIX overflow on mobile
   ========================= */
@media (max-width: 768px) {

  /* 0) страховка: чтобы padding не раздувал ширину */
  .contact_container,
  .contact_container * {
    box-sizing: border-box !important;
  }

  /* 1) сам контейнер — строго в экран */
  .contact_container {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden; /* чтобы точно не было горизонтального скролла */
    padding-left: 16px;
    padding-right: 16px;
  }

  /* 2) внутренний контейнер формы тоже не должен иметь фиксированных ширин */
  .contact_container .form-container,
  .contact_container form {
    width: 100% !important;
    max-width: 100% !important;
  }

  /* 3) все form-group на всю ширину */
  .contact_container .form-group,
  .contact_container .contact_block {
    width: 100% !important;
    max-width: 100% !important;
  }

  /* 4) блок телефона: разрешаем перенос и убираем min-width */
  .contact_container .callback-group {
    display: flex !important;
    flex-wrap: wrap !important;     /* ключевое: перенос на новую строку */
    gap: 10px !important;
    width: 100% !important;
  }

  /* иконка телефона */
  .contact_container .callback-group .icon_input_phone {
    flex: 0 0 34px; /* фикс ширина иконки */
  }

  /* код страны */
  .contact_container #phone_code {
    flex: 0 0 120px;
    width: 120px !important;
    max-width: 120px !important;
  }

  /* само поле телефона — растягивается, без min-width */
  .contact_container #callback_phone {
    flex: 1 1 auto;
    min-width: 0 !important;        /* ключевое: убираем раздувание */
    width: 1% !important;           /* трюк: заставляет flex нормально сжиматься */
  }

  /* 5) Кнопка отправки — 100% ширины (перебиваем твой inline style) */
  .contact_container .send_request_btn {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;

    padding: 16px 0 !important;     /* вместо 18px 218px */
    display: block !important;
    margin: 0 auto !important;
  }

  /* 6) textarea тоже на всю ширину */
  .contact_container .textarea_block,
  .contact_container .cb_text_area {
    width: 100% !important;
    max-width: 100% !important;
  }
}
.col-md-6 {
    margin-bottom: 15px;
}
@media (max-width: 768px) {
    .block_table_btn {
        margin-top: 16px;
        margin-left: 5px;
        padding: 8px 13px;
    }
}

</style>
