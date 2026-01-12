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
                        'formAction' => $formAction ?? route('tickets.index')
                    ])
                </div>
            </div>
        @endif
    </div>

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
