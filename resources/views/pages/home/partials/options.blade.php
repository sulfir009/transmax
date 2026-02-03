<div class="index_options_block">
    <div class="container">
        <div class="flex-row gap-30">
            <div class="col-xl-4 col-md-12">
                <div class="index_option flex_ac shadow_block">
                    <div class="index_option_img">
                        <img src="{{ asset('images/legacy/calendar_option.svg') }}" alt="calendar">
                    </div>
                    <div class="index_option_description">
                        <a href="{{ url('/rozklad') }}" class="index_option_title h3_title">
                            @lang('MSG_ALL_ROZKLAD_AVTOBUSIV')
                        </a>
                        <div class="index_option_subtitle par">
                            @lang('MSG_ALL_ROZKLAD_MARSHRUTI_STANCI')
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12">
                <div class="index_option flex_ac shadow_block">
                    <div class="index_option_img">
                        <img src="{{ asset('images/legacy/return_option.svg') }}" alt="return">
                    </div>
                    <div class="index_option_description">
                        <div class="index_option_title h3_title">
                            <a href="{{ route('return.conditions') }}">
                                @lang('MSG_ALL_POVERNENNYA_KVITKIV')
                            </a>
                        </div>
                        <div class="index_option_subtitle par">
                            @lang('MSG_ALL_ZMINILISI_PLANI_POVERNITI_KOSHTI_ZA_KVITOK_CHEREZ_NASH_SAJT')
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-12">
                <div class="index_option flex_ac shadow_block">
                    <div class="index_option_img">
                        <img src="{{ asset('images/legacy/phone_option.svg') }}" alt="phone">
                    </div>
                    <div class="index_option_description">
                        <a href="{{ route('kontakti') }}" class="index_option_title h3_title">
                            @lang('MSG_ALL_BEZ_KAS_TA_CHERG')
                        </a>
                        <div class="index_option_subtitle par">
                            @lang('MSG_ALL_KVITKI_ONLAJN_U_BUDI-YAKIJ_CHAS_NA_NASHOMU_SAJTI_DLYA_ZRUCHNOGO_PRIDBANNYA_ABO_BRONYUVANNYA')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
