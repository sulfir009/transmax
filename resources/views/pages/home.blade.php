@extends('layout.app')

@section('header_class', 'index_header')

@section('content')
    <div class="main_index_block">
        @if(!empty($mainBanner))
            <img src="{{ asset('images/legacy/upload/main/' . $mainBanner['image']) }}"
                 alt="main_img"
                 class="fit_img mib_back_img">
            <div class="mib_content">
                <div class="container">
                    <h1 class="h1_title mib_content_header">
                        @lang('main_title_home')
                    </h1>

                    @include('layout/components/filter/filter', [
                        'cities' => $cities,
                        'filterDeparture' => $filterDeparture,
                        'filterArrival' => $filterArrival,
                        'filterDate' => $filterDate,
                        'filterAdults' => $filterAdults,
                        'filterKids' => $filterKids,
                        'dictionary' => $dictionary,
                        'lang' => $lang,
                        'formAction' => $formAction ?? \App\Helpers\LocaleHelper::localizedRoute('tickets.index')
                    ])
                </div>
            </div>
        @endif
    </div>
{{-- Баннер приложения MaxTrans --}}
<style>
    .maxtrans_app_banner {
        background: linear-gradient(90deg, #44a0fb 0%, #bbaaf7 55%, #c8c5fa 100%);
        overflow: hidden;
        position: relative; /* чтобы всё аккуратно резалось внутри секции */
    }

    .maxtrans_app_banner__inner { padding: 26px 0; }

    .maxtrans_app_banner__wrap{
        display:flex; align-items:center; justify-content:space-between;
        gap:36px; min-height:190px;
    }

    .maxtrans_app_banner__left { max-width:560px; }
    .maxtrans_app_banner__logo { width:220px; height:auto; display:block; margin-bottom:14px; }

    .maxtrans_app_banner__title{
        margin:0 0 10px; color:#fff; font-family:Montserrat,system-ui;
        font-weight:700; font-size:clamp(18px,1.6vw,22px); line-height:1.2;
    }

    .maxtrans_app_banner__text{
        margin:0; color:rgba(255,255,255,.92); font-family:Montserrat,system-ui;
        font-weight:400; font-size:clamp(13px,1.2vw,16px); line-height:1.35;
    }

    .maxtrans_app_banner__right{
        display:flex; align-items:center; justify-content:flex-end;
        gap:22px; flex:0 0 auto;
    }

    .maxtrans_app_banner__phones_img{
        width:300px;
        height:auto;
        display:block;
        filter: drop-shadow(0 10px 22px rgba(0,0,0,.22));
    }

    .maxtrans_app_banner__qrs{
        display:grid; grid-template-columns:repeat(2,1fr);
        gap:14px; align-items:start;
    }

    .maxtrans_app_banner__qr{ display:grid; gap:8px; justify-items:center; }

    .maxtrans_app_banner__qr_img{
        width:122px; height:122px; object-fit:cover; border-radius:4px;
        background:rgba(255,255,255,.95);
        box-shadow:0 6px 16px rgba(0,0,0,.12);
    }

    .maxtrans_app_banner__store_btn{
        display:inline-block; line-height:0; border-radius:6px;
        transition:transform .15s ease, opacity .15s ease;
        cursor:pointer;
    }

    .maxtrans_app_banner__store_badge{ width:120px; height:auto; display:block; }
    .maxtrans_app_banner__store_btn:hover{ transform:translateY(-1px); opacity:.92; }

    /* ТВОЙ tablet-брейкпоинт оставляем как был */
    @media (max-width: 992px) {
        .maxtrans_app_banner__wrap { flex-direction:column; align-items:flex-start; gap:18px; min-height:unset; }
        .maxtrans_app_banner__right { width:100%; justify-content:flex-start; flex-wrap:wrap; }
        .maxtrans_app_banner__phones_img { width:280px; }
    }

    /* =========================
       MOBILE как на картинке
       ========================= */
    @media (max-width: 768px) {
        /* чтобы телефоны "выпирали" вниз и резались */
        .maxtrans_app_banner__inner { padding: 26px 0 0; }

        .maxtrans_app_banner__wrap{
            flex-direction:column;
            align-items:center;
            text-align:center;
            gap:16px;
        }

        .maxtrans_app_banner__left{
            max-width: 520px;
            display:flex;
            flex-direction:column;
            align-items:center;
        }

        .maxtrans_app_banner__logo{
            width: 170px;
            margin-bottom: 12px;
        }

        .maxtrans_app_banner__title{
            font-size: 22px;      /* ближе к макету */
            line-height: 1.25;
            margin-bottom: 10px;
        }

        .maxtrans_app_banner__text{
            font-size: 14px;
            line-height: 1.35;
            max-width: 320px;     /* чтобы строки были как на макете */
        }

        .maxtrans_app_banner__right{
            width:166%;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            gap:18px;
        }

        /* ВАЖНО: сначала кнопки, потом телефоны (как в макете),
           хотя в HTML телефоны стоят выше */
        .maxtrans_app_banner__qrs{ 
            order: 1;
            grid-template-columns: 1fr; /* кнопки в столбик */
            gap: 12px;
            justify-items: center;
        }

        /* На мобилке QR не видно на макете — прячем */
        .maxtrans_app_banner__qr_img{ display:none; }

        /* делаем бейджи крупными, как на скрине */
        .maxtrans_app_banner__store_badge{
            width: min(240px, 78vw);
        }

        /* телефоны внизу, крупные, частично обрезаны */
        .maxtrans_app_banner__phones_img{
            order: 2;
            width: min(520px, 132vw);
            max-width: 520px;
            margin-top: 8px;
            margin-bottom: -44px;        /* “обрезаем” низ */
            transform: translateY(18px); /* опускаем вниз */
        }
    }

    @media (max-width: 420px) {
        .maxtrans_app_banner__phones_img{
            width: 91vw;               /* ещё чуть шире, чтобы было “вау” как на макете */
            margin-bottom: 58px;
            transform: translateY(24px);
        }
    }
    
/* Календарь всегда поверх */
.flatpickr-calendar,
.flatpickr-calendar.open,
.flatpickr-calendar.static.open {
    z-index: 2147483647 !important;
}

/* Пока календарь открыт — опускаем слой пассажиров (и любых их вариаций классов) */
body.fp-filter-open .filter_block_wrapper.passagers_filter_wrapper,
body.fp-filter-open .filter_block_wrapper.passengers_filter_wrapper,
body.fp-filter-open .passagers_filter_wrapper,
body.fp-filter-open .passengers_filter_wrapper {
    position: relative !important;
    z-index: 1 !important;
}

/* Доп.страховка: если внутри пассажиров есть “шапка/лейбл” с z-index */
body.fp-filter-open .filter_block_wrapper.passagers_filter_wrapper *,
body.fp-filter-open .filter_block_wrapper.passengers_filter_wrapper *,
body.fp-filter-open .passagers_filter_wrapper *,
body.fp-filter-open .passengers_filter_wrapper * {
    z-index: 1 !important;
}

/* (опционально, но я бы включил) пока календарь открыт — пассажиры не кликабельны */
body.fp-filter-open .filter_block_wrapper.passagers_filter_wrapper,
body.fp-filter-open .filter_block_wrapper.passengers_filter_wrapper,
body.fp-filter-open .passagers_filter_wrapper,
body.fp-filter-open .passengers_filter_wrapper {
    pointer-events: none !important;
}

/* =========================
   HOME HERO (MOBILE) — FIX: не уезжает под header + ровный цвет инпутов
   ========================= */
@media (max-width: 768px) {

    /* 1) Чтобы контент не залезал под фикс-хедер */
    .main_index_block .mib_content {
        /* даём отступ сверху = высота шапки + небольшой запас */
        padding-top: calc(var(--mt-header-h, 70px) + 14px) !important;
    }

    /* Если у тебя контейнеру задан свой padding/margin — страхуем */
    .main_index_block .mib_content > .container {
        padding-top: 0 !important;
    }

    /* 2) Делаем hero как "экран" на мобилке, чтобы фильтр был видим */
    .main_index_block {
        position: relative;
        min-height: 100vh;  /* чтобы блок точно был высотой экрана */
        overflow: hidden;
    }

    /* Картинка фоном */
    .main_index_block .mib_back_img {
        position: absolute !important;
        inset: 0;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        object-position: 30%; /* видим левую часть */
        transform: none !important;
        z-index: 0 !important;
    }

    /* Затемнение */
    .main_index_block::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.28);
        z-index: 1;
        pointer-events: none;
    }

    /* Контент поверх */
    .main_index_block .mib_content {
        position: relative;
        z-index: 2;
    }

    /* Заголовок пожирнее */
    .main_index_block .mib_content_header {
        font-weight: 800 !important;
        text-transform: uppercase;
        text-shadow: 0 2px 10px rgba(0,0,0,.35);
    }

    /* =========================
       3) Инпуты: единый серый фон ПОЛНОСТЬЮ (как на скрине)
       ВАЖНО: красим "блок поля", а внутренности делаем прозрачными
       ========================= */

    /* Унифицированный цвет */
    .main_index_block .filter_block_wrapper .filter_block,
    .main_index_block .filter_block_wrapper .filter_block * {
        box-shadow: none !important;
    }

    /* Именно "плашка поля" — серым */
    .main_index_block .filter_block_wrapper .filter_block {
        background: #E6E8EC !important;
        border: 1px solid rgba(0,0,0,.10) !important;
        border-radius: 4px !important;
        overflow: hidden; /* чтобы серый был "целиком" */
    }

    /* Внутренние input/select делаем прозрачными, чтобы не было белых вставок */
    .main_index_block .filter_block_wrapper .filter_block input,
    .main_index_block .filter_block_wrapper .filter_block select,
    .main_index_block .filter_block_wrapper .filter_block textarea {
        background: transparent !important;
        border: 0 !important;
        outline: none !important;
    }

    /* Если у тебя select2/кастомный селект — тоже прозрачный внутри */
    .main_index_block .select2-container .select2-selection--single,
    .main_index_block .select2-container .select2-selection--multiple {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    /* Лейблы немного спокойнее (как на скрине) */
    .main_index_block .filter_block_wrapper label,
    .main_index_block .filter_block_wrapper .filter_label,
    .main_index_block .filter_block_wrapper .filter_title {
        color: rgba(0,0,0,.55) !important;
        font-weight: 500 !important;
    }
}
@media (max-width: 992px) {
    .main_filter {
        padding: 90px 0;
        margin-top: 115%;
    }
}
</style>


@php
    $iosUrl = 'https://play.google.com/store/apps/details?id=com.maxtransltd.android&pli=1';
    $androidUrl = 'https://apps.apple.com/app/maxtrans-%D0%BA%D0%B2%D0%B8%D1%82%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B0%D0%B2%D1%82%D0%BE%D0%B1%D1%83%D1%81/id6739133361';

    $logo = 'images/maxtrans/logo.png';
    $qrIos = 'images/maxtrans/qr-ios.png';
    $qrAndroid = 'images/maxtrans/qr-android.png';
    $badgeIos = 'images/maxtrans/appstore-badge.png';
    $badgeAndroid = 'images/maxtrans/googleplay-badge.png';
@endphp

<section class="maxtrans_app_banner">
    <div class="maxtrans_app_banner__inner">
        <div class="container">
            <div class="maxtrans_app_banner__wrap">

                <div class="maxtrans_app_banner__left">
                    <img class="maxtrans_app_banner__logo" src="{{ asset($logo) }}" alt="MAXTRANS" loading="lazy">

                    <h2 class="maxtrans_app_banner__title">
                        Подорожуй зручно з застосунком MaxTrans
                    </h2>

                    <p class="maxtrans_app_banner__text">
                        Усі рейси й квитки під рукою, найактуальніша<br>
                        інформація та відстеження свого автобуса онлайн.
                    </p>
                </div>

                <div class="maxtrans_app_banner__right">
                    {{-- Телефоны (ОДНО фото) --}}
                    <img class="maxtrans_app_banner__phones_img"
                         src="{{ asset('images/maxtrans/phones-double.png') }}"
                         alt="MaxTrans App"
                         loading="lazy">

                    {{-- QR + кнопки --}}
                    <div class="maxtrans_app_banner__qrs">

                        <div class="maxtrans_app_banner__qr">
                            <img class="maxtrans_app_banner__qr_img" src="{{ asset($qrIos) }}" alt="QR iOS" loading="lazy">
                            <a class="maxtrans_app_banner__store_btn" href="{{ $iosUrl }}" target="_blank" rel="noopener">
                                <img class="maxtrans_app_banner__store_badge" src="{{ asset($badgeIos) }}" alt="App Store" loading="lazy">
                            </a>
                        </div>

                        <div class="maxtrans_app_banner__qr">
                            <img class="maxtrans_app_banner__qr_img" src="{{ asset($qrAndroid) }}" alt="QR Android" loading="lazy">
                            <a class="maxtrans_app_banner__store_btn" href="{{ $androidUrl }}" target="_blank" rel="noopener">
                                <img class="maxtrans_app_banner__store_badge" src="{{ asset($badgeAndroid) }}" alt="Google Play" loading="lazy">
                            </a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

                    </div>

                </div>
            </div>
        </div>
    </section>


    {{-- Блок преимуществ --}}
    <div class="advantages_slider_block">
        <div class="container">
            <div class="flex-row gap-30">
                <div class="col-xxl-8 col-lg-7 col-xs-12">
                    @include('pages.home.partials.advantages', ['advantages' => $advantages])
                </div>
                <div class="col-xxl-4 col-lg-5 col-xs-12">
                    @include('pages.home.partials.blablacar-card', [
                        'site_settings' => $site_settings
                    ])
                </div>
            </div>
        </div>
    </div>

    {{-- Блок приветствия --}}
    @if(!empty($welcomeInfo))
        <div class="welcome_block">
            <div class="container">
                @include('pages.home.partials.welcome', ['welcomeInfo' => $welcomeInfo])
            </div>
        </div>
    @endif

    {{-- Блок маршрутов --}}
    <div class="routes_block">
        <div class="container">
            @include('pages.home.partials.routes', [
                'countries' => $countries,
                'cities' => $cities,
                'internationalTours' => $internationalTours,
                'homeTours' => $homeTours
            ])
        </div>
    </div>

    {{-- Блок опций --}}
    @include('pages.home.partials.options')

    {{-- Блок с цифрами --}}
    @if(!empty($numbersInfo))
        <div class="index_numbers_block">
            <div class="container">
                @include('pages.home.partials.numbers', ['numbersInfo' => $numbersInfo])
            </div>
        </div>
    @endif

    {{-- Блок "Почему мы" --}}
    @if(!empty($whyWeData))
        <div class="why_we_block">
            <div class="container">
                @include('pages.home.partials.why-we', [
                    'whyWeData' => $whyWeData,
                    'logo' => $logo
                ])
            </div>
        </div>
    @endif

    {{-- Блок отзывов --}}
    @if(!empty($reviews))
        <div class="reviews_block">
            <div class="container">
                @include('pages.home.partials.reviews', ['reviews' => $reviews, 'dictionary' => $dictionary])
            </div>
        </div>
    @endif
@endsection

@section('page-scripts')
    <script>
        // Функция инициализации слайдеров
        function initSliders() {
            console.log('Инициализация слайдеров...');
            console.log('jQuery загружен:', typeof $ !== 'undefined');
            console.log('Slick загружен:', typeof $ !== 'undefined' && $.fn.slick);
            // Проверяем наличие элементов перед инициализацией
            if ($('.advantages_slider').length && !$('.advantages_slider').hasClass('slick-initialized')) {
                console.log('Инициализация advantages_slider. Количество слайдов:', $('.advantages_slider .advantage_slide').length);
                $('.advantages_slider').slick({
                    dots: true,
                    dotsClass: 'advantages_slider_nav slick_slider_nav',
                    arrows: false,
                });
                console.log('advantages_slider инициализирован!');
            } else {
                console.log('advantages_slider не найден или уже инициализирован');
            }

            if ($('.why_we_slider').length && !$('.why_we_slider').hasClass('slick-initialized')) {
                $('.why_we_slider').slick({
                    dots: true,
                    dotsClass: 'why_we_slider_nav slick_slider_nav',
                    arrows: false,
                });
            }

            if ($('.reviews_slider').length && !$('.reviews_slider').hasClass('slick-initialized')) {
                $('.reviews_slider').slick({
                    slidesToShow: 2,
                    slidesToScroll: 2,
                    dots: true,
                    dotsClass: 'reviews_slider_nav slick_slider_nav',
                    arrows: false,
                    responsive: [
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 1.04,
                                slidesToScroll: 1,
                            }
                        },
                        {
                            breakpoint: 576,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1,
                            }
                        }
                    ]
                });
            }
        }

        // Инициализация при загрузке страницы
        $(document).ready(function() {
            initSliders();
        });

        // Альтернативная инициализация на случай если jQuery загружается позже
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof $ !== 'undefined' && $.fn.slick) {
                initSliders();
            } else {
                // Если jQuery или Slick еще не загружены, пробуем позже
                setTimeout(function() {
                    if (typeof $ !== 'undefined' && $.fn.slick) {
                        initSliders();
                    }
                }, 500);
            }
        });
    </script>
@endsection
