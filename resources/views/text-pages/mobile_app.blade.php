@extends('text-pages.base')

@php
    $pageData = $pageData ?? ['page_title' => 'Мобільний додаток MaxTrans', 'text' => ''];


    $APPSTORE_URL  = 'https://play.google.com/store/apps/details?id=com.maxtransltd.android&pli=1';  
    $GOOGLEPLAY_URL = 'https://apps.apple.com/app/maxtrans-%D0%BA%D0%B2%D0%B8%D1%82%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B0%D0%B2%D1%82%D0%BE%D0%B1%D1%83%D1%81/id6739133361';  
@endphp

@section('title', $pageData['page_title'] ?? 'Мобільний додаток MaxTrans')
@section('meta_description', $pageData['meta_description'] ?? '')
@section('meta_keywords', $pageData['meta_keywords'] ?? '')

@section('page-styles')
<style>
    /* =========================
       MaxTrans Mobile App Page
       ========================= */

    .mxapp {
        background: #fff;
        padding: 34px 0 60px;
    }

    /* Верхняя синяя плашка */
    .mxapp__bonusbar {
        max-width: 1277px;
        margin: 0 auto 44px;
        background: #40A6FF;
        border-radius: 60px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 18px;
        box-sizing: border-box;
    }

    /* Левая капсула "1 бонус = 1 гривня" */
    .mxapp__bonus-pill {
        position: relative;
        flex: 0 0 auto;
        border-radius: 60px;
        border: 3px solid #fff;
        padding: 14px 22px 14px 52px;
        color: #fff;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 20px;
        line-height: 1.2;
        white-space: nowrap;
        box-sizing: border-box;
    }

    /* декоративная пунктирная штука слева (похожа на скрин) */
    .mxapp__bonus-pill:before {
        content: "";
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 22px;
        height: 22px;
        border: 2px dashed rgba(255,255,255,0.95);
        border-radius: 8px;
        opacity: 0.9;
    }

    /* центральный текст */
    .mxapp__bonus-text {
        flex: 1 1 auto;
        text-align: center;
        color: #fff;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 16px;
        line-height: 1.6;
        padding: 0 10px;
    }

    /* кнопка справа */
    .mxapp__bonus-btn {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 64px;
        padding: 0 34px;
        background: #fff;
        color: #40A6FF;
        border-radius: 60px;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 20px;
        text-decoration: none;
        white-space: nowrap;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .mxapp__bonus-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(0,0,0,.12);
    }

    /* Логотип по центру (ОДНА картинка) */
    .mxapp__brand {
        text-align: center;
        margin: 0 0 36px;
    }
    .mxapp__logo {
        width: 260px; /* при необходимости увеличь до 300-320 */
        height: auto;
        display: inline-block;
    }

    /* Основная сетка: телефоны слева, текст справа */
    .mxapp__main {
        max-width: 1277px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: minmax(420px, 1fr) minmax(420px, 1fr);
        column-gap: 70px;
        row-gap: 26px;
        align-items: center;
    }

    .mxapp__phones img {
        width: 100%;
        max-width: 620px;
        height: auto;
        display: block;
    }

    .mxapp__copy h1 {
        margin: 0 0 16px;
        color: #40A6FF;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 32px;
        line-height: 1.25;
    }

    .mxapp__lead {
        margin: 0 0 18px;
        color: #303233;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 700;
        font-size: 18px;
        line-height: 1.5;
    }

    .mxapp__desc {
        margin: 0;
        color: #303233;
        font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        font-weight: 600;
        font-size: 18px;
        line-height: 1.6;
    }

    /* QR + badges под левым блоком */
    .mxapp__download {
        grid-column: 1 / 2;
        display: flex;
        gap: 34px;
        align-items: flex-start;
        margin-top: 10px;
    }

    .mxapp__store {
        width: 252px;
        text-align: center;
    }

    .mxapp__qr {
        width: 252px;
        height: 252px;
        object-fit: cover;
        display: block;
        margin: 0 auto 12px;
    }

    .mxapp__badge {
        width: 226px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .mxapp__store a {
        text-decoration: none;
        display: inline-block;
    }

    /* =========================
       Responsive
       ========================= */
    @media (max-width: 1100px) {
        .mxapp__main {
            grid-template-columns: 1fr;
            column-gap: 0;
        }

        .mxapp__download {
            grid-column: 1 / -1;
            margin-top: 18px;
            justify-content: center;
        }

        .mxapp__phones img {
            max-width: 640px;
            margin: 0 auto;
        }

        .mxapp__copy {
            max-width: 640px;
            margin: 0 auto;
        }
    }

    @media (max-width: 768px) {
        .mxapp { padding: 18px 0 44px; }

        .mxapp__bonusbar {
            border-radius: 28px;
            padding: 14px;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .mxapp__bonus-pill {
            width: 100%;
            text-align: center;
            padding: 12px 18px;
            font-size: 16px;
        }
        .mxapp__bonus-pill:before { display: none; }

        .mxapp__bonus-text {
            text-align: center;
            font-size: 14px;
            line-height: 1.5;
        }

        .mxapp__bonus-btn {
            width: 100%;
            height: 52px;
            font-size: 16px;
        }

        .mxapp__download {
            flex-direction: column;
            gap: 18px;
            align-items: center;
        }

        .mxapp__store {
            width: 100%;
            max-width: 320px;
        }

        .mxapp__qr {
            width: 220px;
            height: 220px;
        }
    }
</style>
@endsection

@section('content')
<div class="content">
    <div class="mxapp">
        <div class="container">

            {{-- Верхняя синяя плашка --}}
            <div class="mxapp__bonusbar">
                <div class="mxapp__bonus-pill">1 бонус = 1 гривня</div>

                <div class="mxapp__bonus-text">
                    Перетворюйте 10% від суми квитка в бонуси при оплаті в додатку
                    та використовуйте їх для майбутніх подорожей!
                </div>

                <a href="#mxapp-download"
                   class="mxapp__bonus-btn"
                   id="mxapp-download-btn"
                   data-appstore="{{ $APPSTORE_URL }}"
                   data-play="{{ $GOOGLEPLAY_URL }}">
                    Завантажити
                </a>
            </div>

            {{-- Логотип (одна картинка) --}}
            <div class="mxapp__brand">
                <img class="mxapp__logo"
                     src="{{ asset('images/maxtrans/logo.png') }}"
                     alt="MaxTrans">
            </div>

            <div class="mxapp__main">
                {{-- СЛЕВА: одно изображение с 2 телефонами --}}
                <div class="mxapp__phones">
                    <img src="{{ asset('images/maxtrans/phones-double.png') }}"
                         alt="MaxTrans Mobile App"
                         loading="lazy">
                </div>

                {{-- СПРАВА: текст --}}
                <div class="mxapp__copy">
                    <h1>Мобільний додаток<br>MaxTrans</h1>

                    <p class="mxapp__lead">
                        Завантажуйте додаток MaxTrans на свій смартфон і подорожуйте вигідніше!
                    </p>

                    <p class="mxapp__desc">
                        Бронюйте квитки на автобуси, отримуйте бонуси, стежте за рейсами в реальному часі
                        та першими користуйтеся акційними пропозиціями. З MaxTrans ваші подорожі стануть ще
                        зручнішими та доступнішими!
                    </p>
                </div>

                {{-- НИЖЕ СЛЕВА: QR + кнопки --}}
                <div class="mxapp__download" id="mxapp-download">
                    <div class="mxapp__store">
                        <a href="{{ $APPSTORE_URL }}" target="_blank" rel="noopener noreferrer">
                            <img class="mxapp__qr"
                                 src="{{ asset('images/maxtrans/qr-ios.png') }}"
                                 alt="QR App Store">

                            <img class="mxapp__badge"
                                 src="{{ asset('images/maxtrans/appstore-badge.png') }}"
                                 alt="Download on the App Store">
                        </a>
                    </div>

                    <div class="mxapp__store">
                        <a href="{{ $GOOGLEPLAY_URL }}" target="_blank" rel="noopener noreferrer">
                            <img class="mxapp__qr"
                                 src="{{ asset('images/maxtrans/qr-android.png') }}"
                                 alt="QR Google Play">

                            <img class="mxapp__badge"
                                 src="{{ asset('images/maxtrans/googleplay-badge.png') }}"
                                 alt="Get it on Google Play">
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('mxapp-download-btn');
        if (!btn) return;

        const appStore = btn.getAttribute('data-appstore') || '';
        const playStore = btn.getAttribute('data-play') || '';

        btn.addEventListener('click', function (e) {
            const ua = navigator.userAgent || '';
            const isIOS = /iPhone|iPad|iPod/i.test(ua);
            const isAndroid = /Android/i.test(ua);

            // С телефона — открываем нужный стор сразу
            if (isIOS && appStore) {
                e.preventDefault();
                window.location.href = appStore;
                return;
            }
            if (isAndroid && playStore) {
                e.preventDefault();
                window.location.href = playStore;
                return;
            }

            // С десктопа — скроллим к QR
            const hash = btn.getAttribute('href');
            if (hash && hash.startsWith('#')) {
                const target = document.querySelector(hash);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
</script>
@endsection
