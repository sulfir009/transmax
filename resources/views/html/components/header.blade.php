@php
    $isMain = true;
    $header_class = ($isMain) ? 'header' : 'header-white';
    $index_header = ($isMain) ? 'index_header' : 'index_header_other';
    $image_logo = ($isMain) ? 'logo-light-new.png' : 'logo-light-mobile-sec.png';
    $mob_image_logo = ($isMain) ? 'logo-light-mobile.png' : 'logo-lite-sec.png';
    $mob_image_logo = $image_logo;
    $links_class = ($isMain) ? 'link' : 'link-dark';
    $lang_select_class = ($isMain) ? 'language-select-header-light' : 'language-select-header-dark';
    $contact_logo = ($isMain) ? 'contact-new-logo.png' : 'contact-new-logo-dark.png';
    $cabinet_logo = ($isMain) ? 'cabinet-new-logo.png' : 'cabinet-new-logo-dark.png';
    $burger_img = ($isMain) ? 'burger-new.png' : 'burger-new-dark.png';
@endphp


<div class="{{ $header_class }} {{ $index_header }}">
    <div class="container">
        <div class="header-link-block">
            <picture class="logo flex_ac">
                <source srcset="/images/legacy/{{ $mob_image_logo }}" media="(max-width: 768px)">
                <source srcset="/images/legacy/{{ $image_logo }}" media="(min-width: 769px)">
                <img src="/images/legacy/{{ $image_logo }}" alt="Responsive image" class="fit_img">
            </picture>
            <div class="central-links-header flex_ac">
                <div class="regular_tours_wrapper header_races">
                    <button class="{{ $links_class }}" onclick="openPopupRegular()">Регулярные рейсы</button>
                </div>
                <div>
                    <a href="#" class="{{ $links_class }} link-underline-auto hidden-md hidden-sm hidden-xs">Аренда автобусов</a>
                </div>
            </div>
            <div class="last-link-block flex_ac" >
                <div class="language-select-wrapper">
                    <select class="{{ $lang_select_class }}">
                        <option value="ru" selected>RU</option>
                        <option value="uk">UK</option>
                        <option value="en">EN</option>
                    </select>
                </div>
                <div class="support-links flex_ac hidden-md hidden-sm hidden-xs">
                    <div class="phone-dropdown-header">
                        <img src="/images/legacy/{{ $contact_logo }}" alt="phones" class="phone-icon-header" onclick="togglePhoneDropdown()">
                        <div class="phone-menu-header" id="phoneMenu-header">
                            <div class="phone-item-header">
                                <img src="<?php echo  asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar"> +380 67 123 4567
                            </div>
                            <div class="phone-item-header">
                                <img src="<?php echo  asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell"> +380 73 123 4567
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="#">
                            <img src="/images/legacy/{{ $cabinet_logo }}" alt="cab-img">
                        </a>
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
                <img src="https://new.maxtransltd.com/images/legacy/common/arrow_left.svg" alt="arrow left">
            </button>
            <div class="mobile_menu_links">
                <ul>
                    <li><a href="https://new.maxtransltd.com" class="mobile_menu_link manrope ">Главная</a></li>
                    <li><a href="https://new.maxtransltd.com/o-nas" class="mobile_menu_link manrope active">О  нас</a></li>
                    <li><a href="https://new.maxtransltd.com/avtopark" class="mobile_menu_link manrope ">Автопарк</a></li>
                    <li><a href="https://new.maxtransltd.com/raspisanie" class="mobile_menu_link manrope ">Расписание</a></li>
                    <li><a href="https://new.maxtransltd.com/voprosi-i-otveti" class="mobile_menu_link manrope ">Вопросы и ответы</a></li>
                    <li><a href="https://new.maxtransltd.com/kontakti" class="mobile_menu_link manrope ">Контакты</a></li>
                </ul>
            </div>
            <div class="mobile_menu_social">
                <div class="mobile_menu_social_header btn_txt">
                    Мы в соцсетях                </div>
                <div class="mobile_menu_social_links flex_ac">
                    <a href="https://invite.viber.com/?g2=AQAbEDnFdQ17T0%2FFwK%2B29DQ9StGd">
                        <img src="https://new.maxtransltd.com/images/legacy/common/viber.svg" alt="viber">
                    </a>
                    <a href="https://t.me/MaxTrans_od ">
                        <img src="https://new.maxtransltd.com/images/legacy/common/telegram.svg" alt="telegram">
                    </a>
                    <a href="https://www.facebook.com/people/maxtransltdo/100083970371659/">
                        <img src="https://new.maxtransltd.com/images/legacy/common/facebook.svg" alt="facebook">
                    </a>
                    <a href="https://www.instagram.com/maxtrans.odessa/ ">
                        <img src="https://new.maxtransltd.com/images/legacy/common/instagram.svg" alt="instagram">
                    </a>
                </div>
            </div>
            <div class="menu_links mobile hidden-xxl hidden-xl hidden-lg">
                <div class="regular_tours_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport2(this)">
                        Регулярные рейсы                                            </button>
                    <div class="regular_tours">
                        <a href="#" class="regular_tour">
                            Одесса-Бухарест-Одесса                            </a>
                        <a href="#" class="regular_tour">
                            Киев-Афины-Киев                            </a>
                        <a href="#" class="regular_tour">
                            Одесса - Рим                            </a>
                        <a href="#" class="regular_tour">
                            Киев-Одесса                            </a>
                        <a href="#" class="regular_tour">
                            Кривой Рог-Одесса                            </a>
                    </div>
                </div>
                <div class="support_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport(this)">
                        Служба поддержки                                            </button>
                    <div class="support_phones">
                        <a href="tel:0674588510">
                            <img src="https://new.maxtransltd.com/images/legacy/common/lifecell.svg" alt="lifecell">
                            067 458 85 10                        </a>
                        <a href="tel:0971603474">
                            <img src="https://new.maxtransltd.com/images/legacy/common/kyivstar.svg" alt="kyivstar">
                            097 160 34 74                        </a>
                    </div>
                </div>
                <a href="/avtorizaciya" class="link">
                    Личный кабинет                </a>
            </div>
        </div>
    </div>
    <div class="mobile_menu_overlay overlay" onclick="toggleMobileMenu()"></div>
    <meta name="csrf-token" content="rO9nhqSZq1XBPzKin6Vx7EunhfQAgU7OhBuQ52Gw">
</div>

<div class="popup-overlay-regular" id="popup-regular">
  <div class="popup-regular">
    <div id="step-country" class="fade">
      <p>Кликните на интересующую страну, чтобы забронировать билет <span style="color: red">*</span></p>
      <div class="countries-regular">
        <div class="country-regular" onclick="selectCountryRegular(this, 'Болгария')">Болгария</div>
        <div class="country-regular" onclick="selectCountryRegular(this, 'Греция')">Греция</div>
        <div class="country-regular" onclick="selectCountryRegular(this, 'Румыния')">Румыния</div>
        <div class="country-regular" onclick="selectCountryRegular(this, 'Молдова')">Молдова</div>
      </div>
    </div>
  </div>
</div>





