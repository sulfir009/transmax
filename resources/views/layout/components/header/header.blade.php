{{-- ===========================
   HEADER (GLOBAL) — Sticky + Home Transparent + Desktop Icons
   Desktop: logo + (regular tours + rent buses) + lang + support(icon dropdown) + cabinet(icon) + burger(MENU)
   Mobile: lang/support/cabinet moved into burger + CENTER BUTTON "Регулярные рейсы"
   =========================== --}}

<style>
    /* ===========================
       VARIABLES
       =========================== */
    :root {
        --mt-header-h: 78px;
    }
    @media (max-width: 768px) {
        :root { --mt-header-h: 70px; }
    }

    /* ===========================
       HEADER BASE (STICKY)
       =========================== */
    .mt_header_blue {
        background: #40A6FF !important;
        height: var(--mt-header-h);
        position: fixed !important;
        top: 0; left: 0; right: 0;
        width: 100%;
        z-index: 5000;
    }

    /* чтобы контент страниц не уходил под fixed header */
    body {
        padding-top: var(--mt-header-h);
    }

    /* ===========================
       HOME: TRANSPARENT HEADER + IMAGE VISIBLE UNDER IT
       (важно: класс index_header должен быть на главной)
       =========================== */
    .index_header.mt_header_blue {
        background: transparent !important;
    }

    /* Поднимаем первый экран вверх на высоту хедера, чтобы фон/картинка начинались от самого верха */
    .index_header.mt_header_blue ~ .main_index_block {
        margin-top: calc(-1 * var(--mt-header-h));
    }

    /* Но контент внутри первого экрана опускаем вниз, чтобы не залез под header */
    .index_header.mt_header_blue ~ .main_index_block .mib_content {
        padding-top: var(--mt-header-h);
    }

    /* ===========================
       INNER LAYOUT
       =========================== */
    .mt_header_blue .container { height: 100%; }

    .mt_header_blue .header-link-block {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
    }

    /* logo */
    .mt_header_blue .header-logo-container-prop {
        display: flex;
        align-items: center;
        min-width: 180px;
    }
    .mt_header_blue .header-logo-container-prop .logo img {
        height: 30px;
        width: auto;
        display: block;
    }

    /* ===========================
       DESKTOP CENTER NAV (as in screenshot)
       "Регулярные рейсы ▾" + "Аренда автобусов" (underlined)
       =========================== */
    .mt_header_blue .central-links-header {
        display: flex !important;
        align-items: center;
        justify-content: center;
        flex: 1 1 auto;
        min-width: 260px;
    }

    .mt_header_blue .mt_center_nav {
        display: inline-flex;
        align-items: center;
        gap: 44px;
        padding-left: 34px; /* чтобы было как на фото — пункты меню не прилипают к лого */
    }

    .mt_header_blue .mt_nav_btn,
    .mt_header_blue .mt_nav_link {
        appearance: none;
        border: 0;
        background: transparent;
        cursor: pointer;

        display: inline-flex;
        align-items: center;
        gap: 10px;

        padding: 10px 8px;
        margin: 0;

        color: rgba(255,255,255,0.70) !important;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 500;
        font-size: 14px;
        line-height: 1;
        white-space: nowrap;
        text-decoration: none;
        position: relative;
    }

    /* лёгкая подсветка при hover */
    .mt_header_blue .mt_nav_btn:hover,
    .mt_header_blue .mt_nav_link:hover {
        color: rgba(255,255,255,0.92) !important;
    }

    /* underline like screenshot (hover line) */
    .mt_header_blue .mt_nav_btn::after,
    .mt_header_blue .mt_nav_link::after {
        content: "";
        position: absolute;
        left: 8px;
        right: 8px;
        bottom: 2px;
        height: 2px;
        background: rgba(255,255,255,0.85);
        border-radius: 10px;
        transform: scaleX(0);
        transform-origin: left center;
        opacity: 0;
        transition: transform .18s ease, opacity .18s ease;
    }

    .mt_header_blue .mt_nav_btn:hover::after,
    .mt_header_blue .mt_nav_link:hover::after {
        transform: scaleX(1);
        opacity: 1;
    }

    /* ACTIVE underline (for "Аренда автобусов" like on screenshot) */
    .mt_header_blue .mt_nav_link.is_active {
        color: rgba(255,255,255,0.92) !important;
    }
    .mt_header_blue .mt_nav_link.is_active::after {
        transform: scaleX(1);
        opacity: 1;
    }

    /* dropdown chevron for "Регулярные рейсы" */
    .mt_header_blue .mt_nav_chev {
        width: 12px;
        height: 12px;
        display: inline-block;
        transform: translateY(1px);
        background: no-repeat center/12px 12px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M6 9l6 6 6-6' stroke='%23FFFFFF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        opacity: 0.85;
    }

    /* ===========================
       MOBILE CENTER BUTTON (hidden on desktop)
       =========================== */
    .mt_header_blue .mt_mobile_center_nav{
        display: none; /* desktop: hidden */
    }

    .mt_header_blue .mt_mobile_regular_btn{
        appearance: none;
        border: 1px solid rgba(255,255,255,0.22);
        background: rgba(255,255,255,0.18);
        color: #FFFFFF;

        height: 36px;
        padding: 0 16px;
        border-radius: 999px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 12px;
        line-height: 1;

        white-space: nowrap;
        max-width: 210px;
        overflow: hidden;
        text-overflow: ellipsis;

        cursor: pointer;
        transition: background .18s ease, border-color .18s ease, transform .06s ease;
    }

    .mt_header_blue .mt_mobile_regular_btn:hover{
        background: rgba(255,255,255,0.26);
        border-color: rgba(255,255,255,0.30);
    }

    .mt_header_blue .mt_mobile_regular_btn:active{
        transform: translateY(1px);
    }

    .mt_header_blue .mt_mobile_regular_btn:focus-visible{
        outline: 2px solid rgba(255,255,255,0.55);
        outline-offset: 2px;
    }

    /* ===========================
       RIGHT: DESKTOP ICONS + LANG + BURGER
       =========================== */
    .mt_header_blue .last-link-block {
        display: flex;
        align-items: center;
    }

    .mt_header_blue .mt_actions {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* ===========================
       ICON BUTTONS (Support/Cabinet) — desktop only
       =========================== */
    .mt_header_blue .mt_icon_btn,
    .mt_header_blue .mt_icon_link {
        appearance: none;
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        cursor: pointer;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;

        border-radius: 12px;
        text-decoration: none;
    }

    .mt_header_blue .mt_icon_btn:hover,
    .mt_header_blue .mt_icon_link:hover {
        background: rgba(255, 255, 255, 0.10);
    }

    .mt_header_blue .mt_icon {
        width: 22px;
        height: 22px;
        display: block;
        background: no-repeat center/22px 22px;
    }

    /* Headphones icon (support) */
    .mt_header_blue .mt_icon_support {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M4 12a8 8 0 0 1 16 0' stroke='%23FFFFFF' stroke-width='2' stroke-linecap='round'/%3E%3Cpath d='M4 12v6a2 2 0 0 0 2 2h1v-8H6a2 2 0 0 0-2 2Z' stroke='%23FFFFFF' stroke-width='2' stroke-linejoin='round'/%3E%3Cpath d='M20 12v6a2 2 0 0 1-2 2h-1v-8h1a2 2 0 0 1 2 2Z' stroke='%23FFFFFF' stroke-width='2' stroke-linejoin='round'/%3E%3C/svg%3E");
    }

    /* User icon (cabinet) */
    .mt_header_blue .mt_icon_user {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M20 21a8 8 0 1 0-16 0' stroke='%23FFFFFF' stroke-width='2' stroke-linecap='round'/%3E%3Cpath d='M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z' stroke='%23FFFFFF' stroke-width='2'/%3E%3C/svg%3E");
    }

    /* ===========================
       LANGUAGE (desktop) — "UA ▾"
       =========================== */
    .mt_header_blue .language-select-wrapper {
        display: inline-flex !important;
        align-items: center;
        position: relative;
        height: 40px;
        padding: 0 6px;
        border-radius: 12px;
    }

    .mt_header_blue .language-select-wrapper:hover {
        background: rgba(255, 255, 255, 0.10);
    }

    .mt_header_blue select.mt_lang_select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;

        border: 0;
        outline: none;
        background: transparent;

        padding: 0 18px 0 6px;
        margin: 0;
        cursor: pointer;

        color: #FFFFFF !important;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 12px;
        line-height: 1;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        white-space: nowrap;
        height: 40px;
    }

    .mt_header_blue .mt_lang_chev {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-45%);
        pointer-events: none;

        width: 12px;
        height: 12px;
        display: inline-block;
        background: no-repeat center/12px 12px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none'%3E%3Cpath d='M6 9l6 6 6-6' stroke='%23FFFFFF' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        opacity: 0.95;
    }

    /* ===========================
       SUPPORT DROPDOWN
       (keeps your togglePhoneDropdown() + id="phoneMenu-header")
       =========================== */
    .mt_header_blue .mt_support_dd {
        position: relative;
    }

    .mt_header_blue .phone-menu-header {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        min-width: 240px;

        background: #FFFFFF;
        border-radius: 12px;
        border: 1px solid rgba(64, 166, 255, 0.25);
        box-shadow: 0 14px 44px rgba(0, 0, 0, 0.14);
        padding: 10px;

        display: none; /* открывает твой togglePhoneDropdown() */
        z-index: 6000;
    }

    .mt_header_blue .phone-item-header a {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 10px;
        border-radius: 10px;
        text-decoration: none;

        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 14px;
        color: #303233;
    }

    .mt_header_blue .phone-item-header a:hover {
        background: rgba(64, 166, 255, 0.08);
    }

    .mt_header_blue .phone-item-header img {
        width: 20px;
        height: 20px;
        display: block;
    }

    /* ===========================
       BURGER with MENU label (desktop + mobile)
       =========================== */
    .mt_header_blue .burger {
        width: 46px;
        height: 46px;
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        cursor: pointer;

        display: inline-flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 6px;

        border-radius: 12px;
    }

    .mt_header_blue .burger:hover {
        background: rgba(255, 255, 255, 0.10);
    }

    .mt_header_blue .burger img { display: none !important; } /* прячем старую картинку */

    .mt_header_blue .burger .mt_bline {
        display: block;
        height: 2px;
        width: 22px;
        background: #FFFFFF;
        border-radius: 10px;
        opacity: 0.95;
    }

    .mt_header_blue .burger .mt_burger_label {
        margin-top: 2px;
        color: #FFFFFF !important;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 700;
        font-size: 12px;
        letter-spacing: 0.12em;
        line-height: 1;
    }

    /* ===========================
       MOBILE RULES
       - hide desktop nav + support/cabinet/lang -> inside burger
       - show center button "Регулярные рейсы"
       - make header layout: logo | center button | burger
       =========================== */
    @media (max-width: 768px) {
        /* скрываем на мобилке поддержку/кабинет/язык в шапке */
        .mt_header_blue .mt_support_dd,
        .mt_header_blue .mt_cabinet_desktop,
        .mt_header_blue .language-select-wrapper {
            display: none !important;
        }

        /* скрываем десктоп-центр меню на мобилке */
        .mt_header_blue .central-links-header {
            display: none !important;
        }

        /* делаем грид, чтобы кнопка была реально по центру */
        .mt_header_blue .header-link-block{
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 12px;
            justify-content: unset;
        }

        /* лого на мобилке не держим 180px, иначе центр не помещается */
        .mt_header_blue .header-logo-container-prop{
            min-width: unset;
        }

        .mt_header_blue .header-logo-container-prop .logo img{
            height: 28px;
        }

        /* показываем центр-кнопку */
        .mt_header_blue .mt_mobile_center_nav{
            display: flex !important;
            align-items: center;
            justify-content: center;
            min-width: 0; /* важно для grid на узких экранах */
        }

        /* правая часть прижата вправо */
        .mt_header_blue .last-link-block{
            justify-self: end;
        }

        .mt_header_blue .mt_actions { gap: 10px; }
    }

    /* Hover: светло-голубой + плавность */
    #popup-regular .countries-regular a.regular_tour{
        transition: color .18s ease, background-color .18s ease, text-decoration-color .18s ease;
    }

    /* Вариант 1: меняем цвет текста на светло-голубой */
    #popup-regular .countries-regular a.regular_tour:hover{
        color: #35BAF0; /* светло-голубой */
    }

    /* (опционально) если хочешь ещё и подсветку фоном как "плашка" */
    #popup-regular .countries-regular a.regular_tour{
        display: inline-block;        /* чтобы background работал аккуратно */
        padding: 6px 10px;            /* можно подогнать */
        border-radius: 8px;           /* мягкое скругление */
    }

    #popup-regular .countries-regular a.regular_tour:hover{
        background: rgba(53, 186, 240, 0.10); /* лёгкая заливка */
        text-decoration: none;                /* если нужно убрать подчеркивание */
    }

    html.popup-open,
    body.popup-open{
        overflow: hidden !important;
        height: 100%;
    }
</style>

<script>
(function(){
    let scrollY = 0;
    let isLocked = false;

    function lockScroll(){
        if (isLocked) return;
        isLocked = true;

        scrollY = window.scrollY || window.pageYOffset || 0;

        document.documentElement.classList.add('popup-open');
        document.body.classList.add('popup-open');

        // фиксируем body, чтобы не прыгало и не скроллилось
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
    }

    function unlockScroll(){
        if (!isLocked) return;
        isLocked = false;

        document.documentElement.classList.remove('popup-open');
        document.body.classList.remove('popup-open');

        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';

        window.scrollTo(0, scrollY);
    }

    function isPopupVisible(popup){
        if (!popup) return false;
        const st = window.getComputedStyle(popup);
        return st.display !== 'none' && st.visibility !== 'hidden' && st.opacity !== '0';
    }

    document.addEventListener('DOMContentLoaded', function(){
        const popup = document.getElementById('popup-regular');
        if (!popup) return;

        // 1) Следим за изменениями class/style попапа (jQuery fadeIn/display block/классы)
        const obs = new MutationObserver(() => {
            if (isPopupVisible(popup)) lockScroll();
            else unlockScroll();
        });
        obs.observe(popup, { attributes: true, attributeFilter: ['class','style'] });

        // 2) Подстраховка: когда жмут на кнопку открытия — через тик проверяем и лочим
        document.addEventListener('click', function(e){
            if (e.target.closest('[data-open-popup-regular]')) {
                setTimeout(function(){
                    if (isPopupVisible(popup)) lockScroll();
                }, 0);
            }
        }, true);

        // 3) Закрытие по клику на оверлей (если у тебя так задумано)
        popup.addEventListener('click', function(e){
            if (e.target === popup) {
                // если твой код закрывает попап сам — это не мешает
                popup.style.display = 'none';
                popup.classList.remove('is-open');
                unlockScroll();
            }
        });

        // 4) ESC
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape' && isPopupVisible(popup)) {
                popup.style.display = 'none';
                popup.classList.remove('is-open');
                unlockScroll();
            }
        });

        // 5) iOS safeguard: запрещаем touchmove пока попап открыт
        document.addEventListener('touchmove', function(e){
            if (document.body.classList.contains('popup-open')) e.preventDefault();
        }, { passive: false });
    });
})();
</script>

<div class="{{ $header_class }} mt_header_blue">
    <div class="container">
        <div class="header-link-block">

            {{-- LOGO --}}
            <div class="header-logo-container-prop">
                <a href="{{ route('main') }}">
                    <picture class="logo flex_ac">
                        <source srcset="/images/legacy/logo-light.svg" media="(max-width: 768px)">
                        <source srcset="/images/legacy/logo-light.svg" media="(min-width: 769px)">
                        <img src="/images/legacy/logo-light.svg" alt="MAXTRANS" class="fit_img">
                    </picture>
                </a>
            </div>

            {{-- MOBILE CENTER BUTTON: "Регулярные рейсы" --}}
            <div class="mt_mobile_center_nav">
                <button class="mt_mobile_regular_btn" type="button" data-open-popup-regular>
                    {{ __('dictionary.MSG_REGULAR_TOURS') }}
                </button>
            </div>

            {{-- CENTER NAV (DESKTOP): "Регулярные рейсы ▾" + "Аренда автобусов" --}}
            <div class="central-links-header flex_ac">
                <div class="mt_center_nav">

                    <button class="mt_nav_btn" type="button" data-open-popup-regular>
                        {{ __('dictionary.MSG_REGULAR_TOURS') }}
                        <span class="mt_nav_chev"></span>
                    </button>

                    {{-- ВАЖНО: тут я использую route('avtopark') как страницу аренды.
                       Если у тебя отдельный роут аренды — просто замени route(...) --}}
                    <a href="{{ route('avtopark') }}"
                       class="mt_nav_link {{ Route::is('avtopark') ? 'is_active' : '' }}">
                        Аренда автобусов
                    </a>

                </div>
            </div>

            {{-- RIGHT --}}
            <div class="last-link-block flex_ac">
                <div class="mt_actions">

                    {{-- LANGUAGE (desktop only; mobile -> burger) --}}
                    <div class="language-select-wrapper">
                        <select class="mt_lang_select" id="change-lang-desktop">
                            @foreach ($siteLangs as $langInfo)
                                <option value="{{ $langInfo->code }}" {{ ($langInfo->code === \App\Service\Site::lang()) ? 'selected' : '' }}>
                                    {{ strtoupper($langInfo->code) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="mt_lang_chev"></span>
                    </div>

                    {{-- SUPPORT ICON (desktop only; mobile -> burger) --}}
                    <div class="phone-dropdown-header mt_support_dd">
                        <button type="button"
                                class="mt_icon_btn"
                                onclick="togglePhoneDropdown()"
                                aria-label="{{ __('dictionary.MSG_ALL_SLUZHBA_PIDTRIMKI') }}">
                            <span class="mt_icon mt_icon_support"></span>
                        </button>

                        <div class="phone-menu-header" id="phoneMenu-header">
                            <div class="phone-item-header">
                                <a href="tel:<?php echo str_replace(' ','', __('settings.SUPPORT_PHONE_2')) ?>">
                                    <img src="<?php echo asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar">
                                    {{ __('settings.SUPPORT_PHONE_2') }}
                                </a>
                            </div>
                            <div class="phone-item-header">
                                <a href="tel:<?php echo str_replace(' ','', __('settings.SUPPORT_PHONE_1')) ?>">
                                    <img src="<?php echo asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell">
                                    {{ __('settings.SUPPORT_PHONE_1') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- CABINET ICON (desktop only; mobile -> burger) --}}
                    <a class="mt_icon_link mt_cabinet_desktop"
                       href="{{ \App\Service\User::isAuth() ? route('future_races') : route('auth') }}"
                       aria-label="{{ __('dictionary.MSG_ALL_OSOBISTIJ_KABINET') }}">
                        <span class="mt_icon mt_icon_user"></span>
                    </a>

                    {{-- BURGER --}}
                    <button class="burger" onclick="toggleMobileMenu()" type="button" aria-label="Menu">
                        <span class="mt_bline"></span>
                        <span class="mt_bline"></span>
                        <span class="mt_bline"></span>
                        <span class="mt_burger_label">MENU</span>

                        {{-- оставляем старый img (скрыт CSS), чтобы не ломать переменные/верстку --}}
                        <img src="/images/legacy/{{ $burger_img }}" alt="burger">
                    </button>

                </div>
            </div>

        </div>
    </div>

    {{-- MOBILE MENU (как было) --}}
    <div class="mobile_menu blue_popup">
        <div class="mobile_menu_content">
            <button class="close_menu" onclick="toggleMobileMenu()">
                <img src="<?php echo asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>

            <div class="mobile_menu_links">
                <ul>
                    <li><a href="{{ route('main') }}" class="mobile_menu_link manrope {{ Route::is('main') ? 'active' : '' }}">@lang('pages_title_main')</a></li>
                    <li><a href="#" data-open-popup-regular class="mobile_menu_link manrope {{ Route::is('regular_races') ? 'active' : '' }}">@lang('pages_title_regular_races')</a></li>
                    <li><a href="{{ route('schedule') }}" class="mobile_menu_link manrope {{ Route::is('schedule') ? 'active' : '' }}">@lang('pages_menu_title_schedule')</a></li>
                    <li><a href="{{ route('avtopark') }}" class="mobile_menu_link manrope {{ Route::is('avtopark') ? 'active' : '' }}">@lang('pages_menu_title_avtopark')</a></li>
                    <li><a href="{{ route('about.us') }}" class="mobile_menu_link manrope {{ Route::is('about.us') ? 'active' : '' }}">@lang('pages_menu_title_about_us')</a></li>
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
                        <img src="<?php echo asset('images/legacy/common/viber.svg'); ?>" alt="viber">
                    </a>
                    <a href="{{ __('settings.TELEGRAM') }}">
                        <img src="<?php echo asset('images/legacy/common/telegram.svg'); ?>" alt="telegram">
                    </a>
                    <a href="{{ __('settings.FB') }}">
                        <img src="<?php echo asset('images/legacy/common/facebook.svg'); ?>" alt="facebook">
                    </a>
                    <a href="{{ __('settings.INST') }}">
                        <img src="<?php echo asset('images/legacy/common/instagram.svg'); ?>" alt="instagram">
                    </a>
                </div>
            </div>

            {{-- ВАЖНО: это блок для мобилки (язык/поддержка/кабинет тут и остаются) --}}
            <div class="menu_links mobile hidden-xxl hidden-xl hidden-lg">

                <div class="language-select-wrapper">
                    <select class="mt_lang_select" id="change-lang-mobile">
                        @foreach ($siteLangs as $langInfo)
                            <option value="{{ $langInfo->code }}" {{ ($langInfo->code === \App\Service\Site::lang()) ? 'selected' : '' }}>
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
                            <a href="{{ route('regular_races', ['tour' => $race->alias]) }}" class="regular_tour">
                                {{ $race->title }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="support_wrapper">
                    <button class="link dropdown_link" onclick="toggleSupport(this)">
                        <?php echo __('dictionary.MSG_ALL_SLUZHBA_PIDTRIMKI') ?>
                    </button>
                    <div class="support_phones">
                        <a href="tel:<?php echo str_replace(' ','', __('settings.SUPPORT_PHONE_2')) ?>">
                            <img src="<?php echo asset('images/legacy/common/kyivstar.svg'); ?>" alt="kyivstar"> {{ __('settings.SUPPORT_PHONE_2') }}
                        </a>
                        <a href="tel:<?php echo str_replace(' ','', __('settings.SUPPORT_PHONE_1')) ?>">
                            <img src="<?php echo asset('images/legacy/common/lifecell.svg'); ?>" alt="lifecell"> {{ __('settings.SUPPORT_PHONE_1') }}
                        </a>
                    </div>
                </div>

                <a href="<?php echo $privateLink ?>" class="link">
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

{{-- Regular popup (оставляем как было) --}}
<div class="popup-overlay-regular" id="popup-regular">
    <div class="popup-regular">
        <div id="step-country" class="fade">
            <p>@lang('choose_country_to_regular_races')<span style="color: red">*</span></p>
            <div class="countries-regular">
                @foreach ($regularRaces as $race)
                    <div class="country-regular">
                        <a href="{{ route('regular_races', ['tour' => $race->alias]) }}" class="regular_tour">
                            {{ $race->title }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function () {
        $('#change-lang-desktop, #change-lang-mobile').on('change', function () {
            let lang = $(this).val();
            $.ajax({
                type: "POST",
                url: '/ajax/site/lang',
                dataType: 'json',
                data: {lang: lang},
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function () {
                    window.location.reload();
                },
                error: function () {
                    // можно оставить пустым, как было
                }
            });
        });
    });
</script>
