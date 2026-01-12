<?php
/**
 * @var string $lang
 * @var Illuminate\Support\Collection $regularRaces
 * @var \App\Service\DbRouter\Router $Router
 * @var string $privateLink
 * @var string $arrowDown
 * @var string $image_logo
 *
 * @var string $footerTxt
 * @var string $footerCookie
 */
?>
    <!DOCTYPE html>
<html lang="{{$lang}}">
<head>
    @include('layout.components.header.head')
    @yield('page-styles')
</head>

<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPZPXJNJ" height="0" width="0"
                  style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<div class="wraper">
@include('layout.components.header.header')
@yield('content')
</div>
<div class="footer">
@include('layout.components.footer.footer')
</div>
@include('layout.components.footer.footer_scripts', [
    'filterDate' => $filterDate ?? date('Y-m-d'),
    'lang' => $lang ?? 'uk'
])
@yield('page-scripts')
</body>
</html>


