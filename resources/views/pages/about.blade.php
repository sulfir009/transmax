@extends('layout.app')

@section('styles')
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/slick/slick.css') }}">
@endsection

@section('content')
    <div class="page_content_wrapper">
        {{-- Блок приветствия --}}
        @if(!empty($welcome))
            <div class="welcome_block about_us_welcome">
                <div class="container">
                    <div class="flex-row gap-30 welcome_block_wrapper">
                        <div class="col-lg-6">
                            <div class="welcome_txt_block">
                                <div class="welcome_title h2_title">
                                    {{ $welcome['title'] }}
                                </div>
                                <div class="welcome_txt par">
                                    {!! $welcome['text'] !!}
                                </div>
                                <a href="{{ \App\Service\Site::url('/') }}" class="about_details h4_title blue_btn">
                                    {{ $dictionary['MSG___ZABRONYUVATI_BILET'] ?? 'Забронювати білет' }}
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="welcome_img">
                                <img src="{{ asset('images/legacy/upload/wellcome/' . $welcome['image']) }}"
                                     alt="welcome" class="fit_img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Слайдер преимуществ --}}
        <div class="advantages_slider_block">
            <div class="container">
                <div class="flex-row gap-30">
                    <div class="col-xxl-8 col-lg-7 col-xs-12">
                        <div class="advantages_slider_wrapper">
                            <div class="advantages_slider">
                                @foreach($advantages as $advantage)
                                    <div class="advantage_slide">
                                        <div class="advantage_slide_content">
                                            <div class="advantage_img">
                                                <img src="{{ asset('images/legacy/upload/advantage/' . $advantage->image) }}"
                                                     alt="advantage" class="fit_img">
                                            </div>
                                            <div class="advantage_description">
                                                <div class="advantage_title h2_title">{{ $advantage->title }}</div>
                                                <div class="advantage_txt par">{!! $advantage->preview !!}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="advantages_slider_nav slick_slider_nav"></div>
                        </div>
                    </div>
                    <div class="col-xxl-4 col-lg-5 col-xs-12">
                        <div class="advantages_card">
                            <div class="advantages_card_top">
                                <div class="advantage_card_title h2_title">
                                    {{ $dictionary['MSG__MAX_TRANS_TEPER_BLABLACAR'] ?? 'MaxTrans тепер на BlaBlaCar' }}
                                </div>
                                <div class="advantage_card_subtitle par">
                                    {{ $dictionary['MSG__TI_ZH_AVTOBUSNI_REJSI_ZA_BILISH_VIGIDNOYU_CINOYU'] ?? 'Ті ж автобусні рейси за більш вигідною ціною' }}
                                </div>
                            </div>
                            <div class="advantages_card_bottom">
                                <button class="advantage_card_btn btn_txt">
                                    {{ $dictionary['MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR'] ?? 'Купуй безпечно на BlaBlaCar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Блок "О нас" --}}
        @if(!empty($aboutUs))
            <div class="about_us">
                <div class="container">
                    <div class="flex-row gap-30">
                        <div class="col-lg-6">
                            <div class="about_us_txt_wrapper">
                                <div class="about_us_txt">
                                    <div class="about_us_txt_title h2_title">{{ $aboutUs['title'] }}</div>
                                    <div class="about_us_description par">
                                        {!! $aboutUs['text'] !!}
                                    </div>
                                </div>
                                @if(!empty($aboutUs['title_2']))
                                    <div class="about_us_txt">
                                        <div class="about_us_txt_title h2_title">{{ $aboutUs['title_2'] }}</div>
                                        <div class="about_us_description par">
                                            {!! $aboutUs['text_2'] !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="about_us_img">
                                <img src="{{ asset('images/legacy/upload/wellcome/' . $aboutUs['image']) }}"
                                     alt="about us" class="fit_img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Документы компании --}}
        @if(!empty($companyDocs))
            <div class="company_docs_wrapper">
                <div class="company_docs_slider_container">
                    <div class="h3_title company_docs_slider_title">
                        {{ $dictionary['MSG_MSG_ABOUT_DOKUMENTI_NA_ORGANIZACIYU_PEREVEZENI'] ?? 'Документи на організацію перевезень' }}
                    </div>
                    <div class="company_docs_slider">
                        @foreach($companyDocs as $doc)
                            <div class="company_docs_slide">
                                <img src="{{ asset('images/legacy/upload/company_docs/' . $doc->image) }}"
                                     alt="doc" class="fit_img">
                            </div>
                        @endforeach
                    </div>
                    <div class="booking_link_wrapper">
                        <a href="{{ \App\Service\Site::url('/') }}" class="h4_title booking_link blue_btn flex_ac">
                            {{ $dictionary['MSG_MSG_ABOUT__ZABRONYUVATI_BILET'] ?? 'Забронювати білет' }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="{{ mix('js/legacy/libs/slick.min.js') }}"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            $('.advantages_slider').slick({
                dots: true,
                dotsClass: 'advantages_slider_nav slick_slider_nav',
                arrows: false,
            });

            $('.company_docs_slider').slick({
                dots: false,
                arrows: false,
                slidesToScroll: 1,
                slidesToShow: 3,
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 1
                        }
                    }
                ]
            });
        });
    </script>
@endsection
