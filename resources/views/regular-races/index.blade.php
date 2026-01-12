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
        <div class="banner_first">

            @isset($images)
                @if($images->image_desc)
                    <img src="{{ asset('images/pages/regular_races/' . $images->image_desc) }}" class="desk_banner" alt="banner">
                @endif

                @if($images->image_mob)
                    <img src="{{ asset('images/pages/regular_races/' . $images->image_mob) }}" class="mob_banner" alt="banner_mob">
                @endif
            @endisset
        </div>
        <section class="first_section">
            <div class="left_line_diag_sec">
                <img src="{{ asset('images/legacy/left_line_diag_sec.png') }}" alt="ll">
            </div>
            <div class="pin_bus_left">
                <img src="{{ asset('images/legacy/pin_bus.png') }}" alt="pb">
            </div>
            <div class="right_line_diag_sec">
                <img src="{{ asset('images/legacy/right_line_diag_sec.png') }}" alt="ll">
            </div>
            <div class="pin_bus_right">
                <img src="{{ asset('images/legacy/pin_bus.png') }}" alt="pb">
            </div>
            <div class="diagram-section">
                <div class="diagram-title">
                    <h3>@lang('title_page_regular_races')</h3>
                </div>
                <div class="container_diag">
                    <div class="straight_diagram_line_top">
                        <img src="{{ asset('images/legacy/straight-line.png') }}" alt="pb">
                    </div>
                    <div class="straight_diagram_line_bottom">
                        <img src="{{ asset('images/legacy/straight-line.png') }}" alt="pb">
                    </div>
                    <div class="left_diagram_line">
                        <img src="{{ asset('images/legacy/left_diagram_line.png') }}" alt="pb">
                    </div>
                    <div class="right_diagram_line">
                        <img src="{{ asset('images/legacy/right_diagram_line.png') }}" alt="pb">
                    </div>
                    <div class="mob_straight_l_top">
                        <img src="{{ asset('images/legacy/mob_straight_l.png') }}" alt="pb">
                    </div>
                    <div class="mob_straight_l_bottom">
                        <img src="{{ asset('images/legacy/mob_straight_l.png') }}" alt="pb">
                    </div>
                    <div class="mob_right_l">
                        <img src="{{ asset('images/legacy/mob_right_diag.png') }}" alt="pb">
                    </div>
                    <div class="mob_left_l">
                        <img src="{{ asset('images/legacy/mob_left_diag.png') }}" alt="pb">
                    </div>
                    @php
                        $i = 0;
                        $firstStop = '';
                        $lastStops = '';
                        $secondsStops = [];
                        foreach($regularRaces as $alias => $races) {
                            foreach($races as $race) {

                                if ($i == 0) {
                                    $firstStop = $race->stops->first()->stopCity;
                                    $lastStops = $race->stops->last()->stopCity;
                                    ++$i;
                                } else {
                                    $secondsStops[] = $race->stops->first()->stopCity;
                                }
                                $secondsStops = array_unique($secondsStops);
                            }
                        }

                        if (count($secondsStops) < 4 ) {
                            foreach ($stops as $stop) {
                                $secondsStops = array_unique($secondsStops);
                                $has = collect($tourStopPrices)->first(function ($inner) use ($stop) {
                                    return array_key_exists($stop->stop_id, $inner);
                                });
                                if($has && count($secondsStops) < 4 && $stop->stopCity != $firstStop && !in_array($stop->stopCity, $secondsStops)) {
                                    $secondsStops[] = $stop->stopCity;
                                }
                            }
                        }
                        $currentStop = '';
                    @endphp


                    <button class="button_diag left-button btn">{!! $firstStop !!} </button>
                    <div class="column_diag">
                        @for($i = 0; $i < 4; $i++)
                            @if (isset($secondsStops[$i]) && $i < 2 && $secondsStops[$i] != $firstStop && $secondsStops[$i] != $lastStops)
                                @php
                                    $currentStop = $secondsStops[$i] ?? '';
                                @endphp
                                <button class="button_diag top-button btn">{{ $currentStop }} </button>
                            @endif
                        @endfor
                        <button class="button_diag right-button btn">{!! $lastStops !!} </button>
                    </div>
                    <button
                        class="button_diag right-button btn">{!! last($secondsStops) !== $currentStop ? last($secondsStops) : '' !!}</button>


                </div>
            </div>

        </section>
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
                <h1 class="element">@lang('way_schedule')</h1>
                <div class="container-fluid schedule">
                    <div class="custom-select-schedule-container">
                        <div class="custom-schedule-select-wrapper">
                            <select class="custom-schedule-styled-select" id="stationSelect" data-tour="{{ $tour }}">
                                <option value="0">@lang('choose_first_station_regular_races')</option>
                                @foreach($regularRaces as $alias => $races)
                                    @foreach($races as $race)
                                        <option
                                            value="{{ $race->stops->first()->stop_id }}">@lang('bus_from') {!! $race->stops->first()->stopCity !!} {!! $race->stops->first()->stopTitle !!}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
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
                @foreach($regularRaces as $alias => $races)
                    @unless(empty($races))
                        @foreach($races as $race)
                            <h3 class="overlay-image-up"
                                style="display: block; position: relative;">{{ \Illuminate\Support\Carbon::createFromFormat('H:i:s', $race->depTime)->format('G:i') }}
                                [{{ $race->departure }} - {{$race->arrive }}]</h3>
                            <div class="table_container custom-scrollbar">
                                <table class="custom-table">
                                    <thead>
                                    <tr>
                                        <th></th>
                                        @foreach($race->stops as $stop)
                                            <th>{!! $stop->stopCity . ' ' . $stop->stopTitle !!}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($race->stops as $stopFirst)
                                        <tr>
                                            <td class="left-column">{!! $stopFirst->stopCity . ' ' . $stopFirst->stopTitle !!}</td>
                                            @foreach($race->stops as $stopSecond)
                                                <td>
                                                    {{ $tourStopPrices[$race->id][$stopFirst->stop_id][$stopSecond->stop_id]['price'] ?? '' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    @endunless
                @endforeach
            </div>
        </section>
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
</style>
