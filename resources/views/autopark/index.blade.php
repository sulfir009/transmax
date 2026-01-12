@extends('layout.app')

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            <div class="container">
                {{-- Заголовок страницы --}}
                <div class="our_buses_title h2_title">
                    {{ $pageTitle }}
                </div>
                <div class="our_buses_subtitle par">
                    {!! $pageSubtitle !!}
                </div>

                {{-- Список автобусов --}}
                <div class="our_buses_container">
                    @include('autopark.partials.buses-list', ['buses' => $buses])
                </div>

                {{-- Кнопка "Загрузить еще" --}}
                @if($showMoreButton)
                    <button class="more_buses_btn h4_title" onclick="moreBuses()">
                        @lang('MSG_MSG_BUSES_ZAVANTAZHITI_SCHE')
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Попап заказа автобуса --}}
    @include('autopark.partials.order-bus-popup')
@endsection

@section('page-scripts')
    @include('autopark.partials.scripts')
@endsection
