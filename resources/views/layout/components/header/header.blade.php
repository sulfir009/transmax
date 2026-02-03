<div class="{{ $header_class }}">
    <div class="container">
        <div class="header-link-block">
            <div class="header-logo-container-prop">
                <a href="{{ route('main') }}">
                    <picture class="logo flex_ac">
                        <source srcset="/images/legacy/{{ $mob_image_logo }}" media="(max-width: 768px)">
                        <source srcset="/images/legacy/{{ $image_logo }}" media="(min-width: 769px)">
                        <img src="/images/legacy/{{ $image_logo }}" alt="Responsive image" class="fit_img">
                    </picture>
                </a>
            </div>
            <div class="central-links-header flex_ac">
                <div class="regular_tours_wrapper header_races">
                    <button class="link-underline-auto {{ $links_class }}" data-open-popup-regular>{{ __('dictionary.MSG_REGULAR_TOURS') }}</button>
                </div>
                <div>
                    <a href="{{ route('avtopark') }}" class="{{ $links_class }} link-underline-auto hidden-md hidden-sm hidden-xs">
                        @lang('dictionary.MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR')
                    </a>
                </div>
            </div>
            <div class="last-link-block flex_ac" >
                <div class="language-select-wrapper hidden-xs">
                    @php

                    @endphp
                    <select class="{{ $lang_select_class }}" id="change-lang">
                        @foreach ($siteLangs as $langInfo)
                            <option value="{{ $langInfo->code }}" {{ ($langInfo->code === \App\Service\Site::lang()) ? 'selected' : ''  }}>
                                {{ strtoupper($langInfo->code) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="support-links flex_ac hidden-md hidden-sm hidden-xs">
                    <div class="phone-dropdown-header">
                        <img src="/images/legacy/{{ $contact_logo }}" alt="phones" class="phone-icon-header" onclick="togglePhoneDropdown()">
                        <div class="phone-menu-header" id="phoneMenu-header">
                            <div class="phone-item-header">
                                <a href="tel:<?php echo  str_replace(" ","",__('settings.SUPPORT_PHONE_2'))?>">
                                    <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar"> {{ __('settings.SUPPORT_PHONE_2') }}
                                </a>
                            </div>
                            <div class="phone-item-header">
                                <a href="tel:<?php echo  str_replace(" ","", __('settings.SUPPORT_PHONE_1'))?>">
                                    <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell"> {{ __('settings.SUPPORT_PHONE_1') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ \App\Service\User::isAuth() ? route('future_races') : route('auth') }}">
                            <img src="/images/legacy/{{ $cabinet_logo }}" alt="cab-img">
                        </a>
                        @if(\App\Service\User::isAuth())
                            <button class="link" onclick="exitAccount()">
                                @lang('exit')
                            </button>
                        @endif
                    </div>
                </div>
                <button class="burger" onclick="toggleMobileMenu()">
                    <img src="/images/legacy/{{ $burger_img }}" alt="burger">
                </button>
            </div>
        </div>
    </div>
    <div class="mobile_menu blue_popup">
        <div class="mobile_menu_content">
            <button class="close_menu" onclick="toggleMobileMenu()">
                <img src="<?php echo  asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
            <div class="mobile_menu_links">
                <ul>
                    <li><a href="{{ route('main') }}" class="mobile_menu_link manrope {{ Route::is('main') ? 'active' : '' }}">@lang('pages_title_main')</a></li>
                    <li><a href="#" data-open-popup-regular class="mobile_menu_link manrope {{ Route::is('regular_races') ? 'active' : '' }}">@lang('pages_title_regular_races')</a></li>
                    <li><a href="{{ route('schedule') }}" class="mobile_menu_link manrope {{ Route::is('schedule') ? 'active' : '' }}">@lang('pages_menu_title_schedule')</a></li>
                    <li><a href="{{ route('avtopark') }}" class="mobile_menu_link manrope {{ Route::is('avtopark') ? 'active' : '' }}">@lang('pages_menu_title_avtopark')</a></li>
                    <li><a href="{{ route('about.us') }}" class="mobile_menu_link manrope {{ Route::is('about.us') ? 'active' : '' }}"> @lang('pages_menu_title_about_us')</a></li>
                    <li><a href="{{ route('kontakti') }}" class="mobile_menu_link manrope {{ Route::is('kontakti') ? 'active' : '' }}">@lang('pages_menu_title_kontakti')</a></li>
                    <li><a href="{{ route('faq') }}" class="mobile_menu_link manrope {{ Route::is('faq') ? 'active' : '' }}">@lang('pages_menu_title_faq')</a></li>
                </ul>
            </div>
            <div class="mobile_menu_social">
                <div class="mobile_menu_social_header btn_txt">
                    @lang('dictionary.MSG_ALL_MI_U_SOCMEREZHAH')
                </div>
                <div class="mobile_menu_social_links flex_ac">
                    <a href="{{ __('settings.VIBER') }}">
                        <img src="<?php echo  asset('images/legacy/common/viber.svg'); ?>" alt="viber">
                    </a>
                    <a href="{{ __('settings.TELEGRAM') }}">
                        <img src="<?php echo  asset('images/legacy/common/telegram.svg'); ?>" alt="telegram">
                    </a>
                    <a href="{{ __('settings.FB') }}">
                        <img src="<?php echo  asset('images/legacy/common/facebook.svg'); ?>" alt="facebook">
                    </a>
                    <a href="{{ __('settings.INST') }}">
                        <img src="<?php echo  asset('images/legacy/common/instagram.svg'); ?>" alt="instagram">
                    </a>
                </div>
            </div>
            <div class="menu_links mobile hidden-xxl hidden-xl hidden-lg">
                <div class="language-select-wrapper">
                    @php

                    @endphp
                    <select class="{{ $lang_select_class }}" id="change-lang">
                        @foreach ($siteLangs as $langInfo)
                            <option value="{{ $langInfo->code }}" {{ ($langInfo->code === \App\Service\Site::lang()) ? 'selected' : ''  }}>
                                {{ strtoupper($langInfo->code) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="regular_tours_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport2(this)">
                        {{ __('dictionary.MSG_REGULAR_TOURS') }}
                    </button>
                    <div class="regular_tours">
                        @foreach ($regularRaces as $race)
                            <a href="{{ route('regular_races', ['tour' => $race->alias])  }}" class="regular_tour">
                                {{ $race->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="support_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport(this)">
                        <?php echo  __('dictionary.MSG_ALL_SLUZHBA_PIDTRIMKI')?>                                        </button>
                    <div class="support_phones">
                        <a href="tel:<?php echo  str_replace(" ","",__('settings.SUPPORT_PHONE_2'))?>">
                            <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar"> {{ __('settings.SUPPORT_PHONE_2') }}
                        </a>
                        <a href="tel:<?php echo  str_replace(" ","", __('settings.SUPPORT_PHONE_1'))?>">
                            <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell"> {{ __('settings.SUPPORT_PHONE_1') }}
                        </a>
                    </div>
                </div>
                <a href="<?php echo $privateLink?>" class="link">
                    {{ __('dictionary.MSG_ALL_OSOBISTIJ_KABINET') }}
                </a>
                @if(\App\Service\User::isAuth())
                    <button class="link" onclick="exitAccount()">
                        @lang('exit')
                    </button>
                @endif
            </div>
        </div>
    </div>
    <div class="mobile_menu_overlay overlay" onclick="toggleMobileMenu()"></div>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</div>

<div class="popup-overlay-regular" id="popup-regular">
    <div class="popup-regular">
        <div id="step-country" class="fade">
            <p>@lang('choose_country_to_regular_races')<span style="color: red">*</span></p>
            <div class="countries-regular">
                @foreach ($regularRaces as $race)
                    <div class="country-regular">
                        <a href="{{ route('regular_races', ['tour' => $race->alias])  }}" class="regular_tour">
                            {{ $race->title }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function (){
        $('#change-lang').on('change', function (e){
            let lang = $(this).val();
            $.ajax({
                type: "POST",
                url: '/ajax/site/lang',
                dataType: 'json',
                data: {lang: lang},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    window.location.reload();
                },
                error: function (xhr) {

                }
            });
        });
    });
</script>



