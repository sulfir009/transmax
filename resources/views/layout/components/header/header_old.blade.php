<div class="{{ $class }}">
    <div class="container">
        <div class="header_content flex_ac">
            <div class="logo">
                <a href="<?php echo route('main')?>">
                    <img src="<?php echo  asset('images/legacy/upload/logos/' .  $image_logo); ?>" alt="logo" class="fit_img">
                </a>
            </div>
            <div class="menu flex_ac">
                <div class="menu_links hidden-md hidden-sm hidden-xs">
                    <div class="regular_tours_wrapper">
                        <button class="link dropdown_link" onclick="toggleSupport2(this)">
                            {{ __('dictionary.MSG_REGULAR_TOURS') }}
                            <?php echo  $arrowDown ?>
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
                            <?php echo  __('dictionary.MSG_ALL_SLUZHBA_PIDTRIMKI')?>
                            <?php echo  $arrowDown ?>
                        </button>
                        <div class="support_phones">
                            <a href="tel:<?php echo  str_replace(" ","", __('settings.SUPPORT_PHONE_1'))?>"
                               class="support_phone">
                                <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell">
                                {{ __('settings.SUPPORT_PHONE_1') }}
                            </a>
                            <a href="tel:<?php echo  str_replace(" ","", __('settings.SUPPORT_PHONE_2'))?>"
                               class="support_phone">
                                <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar">
                                {{ __('settings.SUPPORT_PHONE_2') }}
                            </a>
                        </div>
                    </div>
                    <a href="<?php echo $privateLink?>" class="link">
                        {{ __('dictionary.MSG_ALL_OSOBISTIJ_KABINET') }}
                    </a>
                    @if(\App\Service\User::isAuth())
                        <button class="link" onclick="exitAccount()">
                            Выход
                        </button>
                    @endif
                </div>
                <div class="langs_block">
                    <select class="langs_select <?php echo  $langs_class?>" onchange="location.href = $(this).val();">
                        @foreach ($siteLangs as $langInfo)
                            <option value="{{ ($langInfo->code === $lang) ? 'selected' : ''  }}">
                                {{ strtoupper($langInfo->code) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="burger" onclick="toggleMobileMenu()">
                    <img src="<?php echo  asset('images/legacy/common/' . $burger); ?>" alt="burger">
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
                    <li>
                        <a href="{{ route('main') }}"
                           class="mobile_menu_link manrope {{ Route::is('main') ? 'active' : '' }}">
                            @lang('pages_main')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('about.us') }}"
                           class="mobile_menu_link manrope {{ Route::is('about.us') ? 'active' : '' }}">
                            @lang('pages_about_us')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('avtopark') }}"
                           class="mobile_menu_link manrope {{ Route::is('avtopark') ? 'active' : '' }} ">
                            @lang('pages_avtopark')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('schedule') }}"
                           class="mobile_menu_link manrope {{ Route::is('schedule') ? 'active' : '' }}">
                            @lang('pages_rozklad')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('faq') }}"
                           class="mobile_menu_link manrope {{ Route::is('faq') ? 'active' : '' }}">
                            @lang('pages_faq')
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('kontakti') }}"
                           class="mobile_menu_link manrope {{ Route::is('kontakti') ? 'active' : '' }}">
                            @lang('pages_kontakti')
                        </a>
                    </li>
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
                <div class="regular_tours_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport2(this)">
                        @lang('dictionary.MSG_REGULAR_TOURS')
                        {{ $arrowDown }}
                    </button>
                    <div class="regular_tours">
                        @foreach ($regularRaces as $race)
                            <a href="{{ route('regular_races', ['tour' => $race->alias]) }}" class="regular_tour">
                                {{ $race->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="support_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport(this)">
                        {{ __('dictionary.MSG_ALL_SLUZHBA_PIDTRIMKI') }}
                        <?php echo  $arrowDown ?>
                    </button>
                    <div class="support_phones">
                        <a href="tel:<?php echo  str_replace(" ","", __('settings.SUPPORT_PHONE_1'))?>">
                            <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell">
                            {{ __('settings.SUPPORT_PHONE_1') }}

                        </a>
                        <a href="tel:<?php echo  str_replace(" ","",__('settings.SUPPORT_PHONE_2'))?>">
                            <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar">
                            {{ __('settings.SUPPORT_PHONE_2') }}

                        </a>
                    </div>
                </div>
                <a href="<?php echo $privateLink?>" class="link">
                    {{ __('dictionary.MSG_ALL_OSOBISTIJ_KABINET') }}

                </a>
            </div>
        </div>
    </div>
    <div class="mobile_menu_overlay overlay" onclick="toggleMobileMenu()"></div>
    <meta name="csrf-token" content="<?php echo  csrf_token() ?>">
</div>
