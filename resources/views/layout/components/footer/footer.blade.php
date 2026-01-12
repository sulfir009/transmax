<div class="container">
    <div class="d_none">
        <? $out([]) ?>
    </div>
    {{--<div class="callback_pop-up-btn" onclick="popUpForm()">
        <div class="blue_btn callback_btn">
            <div class="callback_img">
                <svg>
                    <use xlink:href="<?php echo  asset('images/legacy/upload/logos/callback.svg#callback'); ?>"></use>
                </svg>
            </div>
            <div class="callback_title">@lang('dictionary.MSG_ALL_DIZNATIS_VARTIST')</div>
        </div>
    </div>--}}

    <div class="flex-row gap-24">
        <div class="col-lg-6 col-xs-12">
            <div class="footer_block footer_left">
                <div class="footer_logo">
                    <img src="<?php echo  asset('images/legacy/upload/logos/' . $logo['white_logo']); ?>
" alt="logo" class="fit_img">
                </div>
                <div class="footer_txt par">
                    {!! $footerTxt !!}
                </div>
                <div class="paymethod_logos">
                    <img src="<?php echo  asset('images/legacy/common/maestro.svg'); ?>" alt="maestro">
                    <img src="<?php echo  asset('images/legacy/common/mastercard.svg'); ?>" alt="mastercard">
                    <img src="<?php echo  asset('images/legacy/common/visa.svg'); ?>" alt="visa">
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-12">
            <div class="footer_block footer_center">
                <div class="footer_block_header h3_title">
                    @lang('dictionary.MSG_ALL_INFORMACIYA')
                </div>
                <div class="footer_links">
                    <ul class="h5_title footer_links_list">
                        <li>
                            <a href="{{ route('main') }}">
                                @lang('pages_main')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('about.us') }}">
                                @lang('pages_about_us')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('avtopark') }}">
                                @lang('pages_avtopark')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('schedule') }}">
                                @lang('pages_schedule')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('faq') }}">
                                @lang('pages_faq')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('kontakti') }}">
                                @lang('pages_kontakti')
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-xs-12">
            <div class="footer_right">
                <div class="footer_block_header h3_title">
                    @lang('dictionary.MSG_ALL_ZVYAZHISI_Z_NAMI')
                </div>
                <div class="footer_contacts h5_title">
                    <div>
                        @lang('dictionary.MSG_CONTACTS_ADRESA') @lang('dictionary.MSG_MSG_CONTACTS_65000_M_ODESA_VUL_STAROSINNA_7')
                    </div>
                    <div>
                        @lang('dictionary.MSG_CONTACTS_TELEFON')
                        <a href="tel:{{ __('settings.CONTACT_PHONE') }}">@lang('settings.CONTACT_PHONE')</a>
                    </div>
                    <div>
                        @lang('dictionary.MSG_CONTACTS_EMAIL')
                        <a href="mailto:@lang('settings.CONTACT_EMAIL')">@lang('settings.CONTACT_EMAIL')</a>
                    </div>
                </div>
                <div class="footer_contacts_bottom">
                    <div class="footer_contacts_bottom_title h5_title">
                        @lang('dictionary.MSG_ALL_MI_U_SOCMEREZHAH')
                    </div>
                    <div class="footer_social">
                        <a href="{{ __('settings.VIBER') }}" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/viber.svg'); ?>" alt="viber">
                        </a>
                        <a href="{{ __('settings.TELEGRAM') }}" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/telegram.svg'); ?>" alt="telegram">
                        </a>
                        <a href="{{ __('settings.FB') }}" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/facebook.svg'); ?>" alt="facebook">
                        </a>
                        <a href="{{ __('settings.INST') }}" target="_blank">
                            <img src="<?php echo  asset('images/legacy/common/instagram.svg'); ?>" alt="instagram">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="footer_bottom_txt par">
            {!! $footerCookie !!}
        </div>
        <div class="footer_bottom_list flex_ac par">
            <div class="footer_bottom_links">
                <a href="{{ route('privacy.policy') }}" class="footer_bottom_link">@lang('alias_policy') </a>
                <a href="{{ route('offer') }}"
                   class="footer_bottom_link fbl_offer">@lang('alias_offer_agreement')</a>
            </div>
            <div class="copyrights">
                © @lang('dictionary.MSG_ALL_VSI_PRAVA_ZAHISCHENI') | MaxTrans 2024
            </div>
        </div>
    </div>
</div>


<div class="callback_pop-up-btn" onclick="popUpForm()">
    <div class="pulse-circle">
        <div class="pulse-inner">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path fill="white" d="M6.62 10.79a15.053 15.053 0 006.59 6.59l2.2-2.2a1 1 0 011.05-.24c1.12.37 2.33.57 3.54.57a1 1 0 011 1V20a1 1 0 01-1 1C10.61 21 3 13.39 3 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.21.2 2.42.57 3.54a1 1 0 01-.24 1.05l-2.21 2.2z"/>
            </svg>
        </div>
        <span class="pulse-text">@lang('dictionary.MSG_ALL_DIZNATIS_VARTIST')</span>
    </div>
</div>

<!-- Попап -->
<div class="callback_popup_overlay" onclick="popUpForm()"></div>

<div class="callback_popUp" id="callback_popUp">
    <div class="callback_form">
        <div class="callback-title">@lang('dictionary.MSG_ALL_DIZNATIS_VARTIST')</div>
        <div class="callback-form-grid">
            <div class="callback-left">
                <div class="callback-group d-flex align-items-center gap-2">
                    <label class="icons_input" for="phone">
                        <img src="{{ asset('images/legacy/icon_phone.png') }}" alt="icon_number">
                    </label>
                    <select class="cb_custom_select cb_input_tel call_select_pop" id="callback_departure" name="departure_callback" style="max-width: 120px;">
                        @foreach($phoneCodes as $k => $code)
                            <option value="{{ $code->id }}" data-mask="{{ $code->phone_mask }}"
                                    data-placeholder="{{ $code->phone_example }}" {{ $k == 0 ? 'selected' : '' }}> {{ $code->phone_country }}</option>
                        @endforeach
                    </select>
                    <input class="cb_input_tel form-control cb_phone_input" type="tel" id="callback_phone" name="callback_phone" placeholder="@lang('alias_phone')" style="min-width: 180px;">
                </div>
                <div class="cb_select_wrapper">
                    <select class="cb_custom_select" id="callback_departure" name="from_location">
                        <option value="" disabled selected>@lang('from')</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cb_select_wrapper">
                    <select class="cb_custom_select" id="callback_arrival" name="to_location">
                        <option value="" disabled selected>@lang('to_go')</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <textarea class="cb_text_area" id="callback_message" placeholder="{{ __('message') }}*"></textarea>
        </div>
        <div class="callback-btn">
            <button class="callback-send-btn" onclick="sendOrderRequest()">@lang('send')</button>
        </div>
    </div>
</div>

<div class="modal" id="successModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('thanks')</h5>
            </div>
            <div class="modal-body">
                <p>@lang('popup_request_callback_sent')</p>
            </div>
            <div class="modal-footer">
                <button type="button" data-close class="btn btn-secondary" data-dismiss="modal" style="background-color: #0a58ca">@lang('ok')</button>
            </div>
        </div>
    </div>
</div>
