<div class="flex-row gap-30 welcome_block_wrapper">
    <div class="col-lg-6 col-xs-12">
        <div class="welcome_txt_block">
            <div class="welcome_title h2_title">
                {!! $welcomeInfo['title'] !!}
            </div>
            <div class="welcome_txt par">
                {!! $welcomeInfo['text'] !!}
            </div>
            <a href="{{ url('/pro-nas') }}" class="about_details h4_title blue_btn">
                @lang('MSG__DETALINISHE_PRO_NAS')
            </a>
        </div>
    </div>
    <div class="col-lg-6 col-xs-12">
        <div class="welcome_img">
            <img src="{{ asset('images/legacy/upload/wellcome/' . $welcomeInfo['image']) }}"
                 alt="welcome"
                 class="fit_img">
        </div>
    </div>
</div>
