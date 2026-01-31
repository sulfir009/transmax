@php
    $APPSTORE_URL  = $APPSTORE_URL  ?? 'https://play.google.com/store/apps/details?id=com.maxtransltd.android&pli=1';
    $GOOGLEPLAY_URL = $GOOGLEPLAY_URL ?? 'https://apps.apple.com/app/maxtrans-%D0%BA%D0%B2%D0%B8%D1%82%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B0%D0%B2%D1%82%D0%BE%D0%B1%D1%83%D1%81/id6739133361';
@endphp


<div class="main_filter_wrapper mf">
    <div class="mf__bg">
        <div class="container">
            <div class="mf__inner">
                <div class="mf__hero">
                    <div class="mf__left">
                        <img class="mf__logo" src="{{ asset('images/maxtrans/logo.png') }}" alt="MaxTrans">

                        <div class="mf__title">Подорожуй зручно з застосунком MaxTrans</div>
                        <p class="mf__desc">
                            Усі рейси й квитки під рукою, найактуальніша інформація та відстеження свого автобуса онлайн.
                        </p>
                    </div>

                    <div class="mf__right">
                        {{-- phones-double.png = одно изображение с двумя телефонами --}}
                        <img class="mf__phones" src="{{ asset('images/maxtrans/phones-double.png') }}" alt="MaxTrans App" loading="lazy">

<div class="mf__stores">
    <a class="mf__store" href="{{ $APPSTORE_URL }}" target="_blank" rel="noopener noreferrer">
        <img class="mf__qr" src="{{ asset('images/maxtrans/qr-ios.png') }}" alt="QR iOS" loading="lazy">
        <img class="mf__badge" src="{{ asset('images/maxtrans/appstore-badge.png') }}" alt="App Store" loading="lazy">
    </a>

    <a class="mf__store" href="{{ $GOOGLEPLAY_URL }}" target="_blank" rel="noopener noreferrer">
        <img class="mf__qr" src="{{ asset('images/maxtrans/qr-android.png') }}" alt="QR Android" loading="lazy">
        <img class="mf__badge" src="{{ asset('images/maxtrans/googleplay-badge.png') }}" alt="Google Play" loading="lazy">
    </a>
</div>

                    </div>
                </div>

                {{-- Твой существующий фильтр: НЕ МЕНЯЕМ, только оборачиваем --}}
                <div class="mf__form_shell">
                    @include('layout.components.filter.filter', [
                        'cities' => $cities ?? [],
                        'filterDeparture' => $filterDeparture ?? null,
                        'filterArrival' => $filterArrival ?? null,
                        'filterDate' => $filterDate ?? date('Y-m-d'),
                        'filterAdults' => $adults ?? 1,
                        'filterKids' => $kids ?? 0,
                        'dictionary' => $dictionary ?? [],
                        'lang' => $lang ?? 'uk',
                        'formAction' => $formAction ?? route('tickets.index'),
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
