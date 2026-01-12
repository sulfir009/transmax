@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/slick/slick.css') }}">
@endsection

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            {{-- Welcome Block --}}
            <div class="welcome_block about_us_welcome">
                <div class="container">
                    @if($welcomeInfo)
                        <div class="flex-row gap-30 welcome_block_wrapper">
                            <div class="col-lg-6">
                                <div class="welcome_txt_block">
                                    <div class="welcome_title h2_title">
                                        {{ $welcomeInfo['title'] }}
                                    </div>
                                    <div class="welcome_txt par">
                                        {!! $welcomeInfo['text'] !!}
                                    </div>
                                    <a href="{{ route('booking.index') }}" class="about_details h4_title blue_btn">
                                        @lang('MSG___ZABRONYUVATI_BILET')
                                    </a>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="welcome_img">
                                    <img src="{{ asset('images/legacy/upload/wellcome/' . $welcomeInfo['image']) }}"
                                         alt="welcome" class="fit_img">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Advantages Slider Block --}}
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
                                                    <div class="advantage_title h2_title">
                                                            {{ $advantage->title }}
                                                    </div>
                                                    <div class="advantage_txt par">
                                                            {{ $advantage->preview }}
                                                    </div>
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
                                        @lang('MSG__MAX_TRANS_TEPER_BLABLACAR')
                                    </div>
                                    <div class="advantage_card_subtitle par">
                                        @lang('MSG__TI_ZH_AVTOBUSNI_REJSI_ZA_BILISH_VIGIDNOYU_CINOYU')
                                    </div>
                                </div>
                                <div class="advantages_card_bottom">
                                    <button class="advantage_card_btn btn_txt">
                                        @lang('MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- About Us Section --}}
            <div class="about_us">
                <div class="container">
                    <div class="flex-row gap-30">
                        @if($aboutUsInfo)
                            <div class="col-lg-6">
                                <div class="about_us_txt_wrapper">
                                    <div class="about_us_txt">
                                        <div class="about_us_txt_title h2_title">{{ $aboutUsInfo['title'] }}</div>
                                        <div class="about_us_description par">
                                            {!! $aboutUsInfo['text'] !!}
                                        </div>
                                    </div>
                                    <div class="about_us_txt">
                                        <div class="about_us_txt_title h2_title">{{ $aboutUsInfo['title_2'] }}</div>
                                        <div class="about_us_description par">
                                            {!! $aboutUsInfo['text_2'] !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="about_us_img">
                                    <img src="{{ asset('images/legacy/upload/wellcome/' . $aboutUsInfo['image']) }}"
                                         alt="about us" class="fit_img">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Company Documents Section --}}
            <div class="company_docs_wrapper">
                <div class="company_docs_slider_container">
                    <div class="h3_title company_docs_slider_title">
                        @lang('MSG_MSG_ABOUT_DOKUMENTI_NA_ORGANIZACIYU_PEREVEZENI')
                    </div>
                    <div class="company_docs_slider">
                        @foreach($companyDocs as $companyDoc)
                            <div class="company_docs_slide">
                                <img src="{{ asset('images/legacy/upload/company_docs/' . $companyDoc->image) }}"
                                     alt="document" class="fit_img">
                            </div>
                        @endforeach
                    </div>
                    <div class="booking_link_wrapper">
                        <a href="{{ route('booking.index') }}" class="h4_title booking_link blue_btn flex_ac">
                            @lang('MSG_MSG_ABOUT__ZABRONYUVATI_BILET')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
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
