<div class="flex-row gap-30">
    <div class="col-xl-4 col-lg-5 col-md-12">
        <div class="why_we_card">
            <div class="why_we_card_top">
                <div class="why_we_card_title h2_title">
                    @lang('MSG_ALL_NASHI_AVTOBUSI')
                </div>
                <div class="why_we_card_description par">
                    @lang('MSG_ALL_OUR_BUSES_SUBTITLE')
                </div>
            </div>
            <div class="why_we_card_middle">
                <div class="why_we_card_logo">
                    @if(isset($logo['white_logo']))
                        <img src="{{ asset('images/legacy/upload/logos/' . $logo['white_logo']) }}"
                             alt="logo"
                             class="fit_img">
                    @endif
                </div>
            </div>
            <div class="why_we_card_bottom">
                <a href="{{ url('/avtopark') }}" class="autopark_link h4_title">
                    @lang('MSG_ALL_PEREGLYANUTI_AVTOPARK')
                </a>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7 col-md-12">
        <div class="why_we_slider_wrapper">
            <div class="why_we_slider">
                @foreach($whyWeData as $why)
                    <div class="why_we_slide">
                        <div class="why_we_slide_content">
                            <div class="why_we_slide_image">
                                <img src="{{ asset('images/legacy/upload/why_we/' . $why->image) }}"
                                     alt="slide"
                                     class="fit_img">
                            </div>
                            <div class="why_we_slide_description">
                                <div class="why_we_slide_title h2_title">{{ $why->title }}</div>
                                <div class="why_we_slide_subtitle manrope">{{ $why->subtitle }}</div>
                                <div class="why_we_slide_txt par">{{ $why->preview }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="why_we_slider_nav slick_slider_nav"></div>
        </div>
    </div>
</div>
