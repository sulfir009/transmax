<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="<?php echo  mix('js/legacy/googletagmager.js') ?>"></script>

<!-- End Google Tag Manager -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
    $seo = $seo ?? [];
    $seoTitle = $seo['title']
        ?? data_get($page_data ?? [], 'page_title')
        ?? data_get($pageData ?? [], 'page_title')
        ?? 'Page title';
    $seoDescription = $seo['description']
        ?? data_get($page_data ?? [], 'meta_d')
        ?? data_get($pageData ?? [], 'meta_description')
        ?? 'Page description';
@endphp
<title>{!! $seoTitle !!}</title>
<meta name="description" content="{{ $seoDescription }}">

<meta name="keywords" content="

@if(isset($page_data['meta_k']))
{{ $page_data['meta_k'] }}
@else
{{ 'Page keywords'}}
@endif
">

@if(!empty($seo['canonical']))
    <link rel="canonical" href="{{ $seo['canonical'] }}">
@endif

@if(!empty($seo['hreflangs']))
    @foreach($seo['hreflangs'] as $langCode => $href)
        <link rel="alternate" hreflang="{{ $langCode }}" href="{{ $href }}">
    @endforeach
@endif

@if(!empty($seo['openGraph']))
    <meta property="og:title" content="{{ $seo['openGraph']['title'] ?? '' }}">
    <meta property="og:description" content="{{ $seo['openGraph']['description'] ?? '' }}">
    <meta property="og:url" content="{{ $seo['openGraph']['url'] ?? '' }}">
    <meta property="og:type" content="{{ $seo['openGraph']['type'] ?? 'website' }}">
    <meta property="og:image" content="{{ $seo['openGraph']['image'] ?? '' }}">
    <meta property="og:site_name" content="{{ $seo['openGraph']['site_name'] ?? 'MaxTrans' }}">
@endif

@if(!empty($seo['jsonLd']))
    @foreach($seo['jsonLd'] as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endforeach
@endif


<link rel="shortcut icon" type="image/png" href="<?php echo  asset('images/legacy/upload/logos/favicon.svg');?>"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500&family=Manrope:wght@400;700&family=Montserrat:wght@400;500&family=Play:wght@400;700&display=swap" rel="stylesheet"><script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/libs/nice_select/nice-select.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/libs/slick/slick.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/bootstrap/bootstrap.min.css'); ?>>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.17/css/intlTelInput.min.css" />

<link rel="stylesheet" href=<?php echo  mix('css/nag.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/common.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/style.css'); ?> />
<link rel="stylesheet" href=<?php echo  mix('css/mobile.css'); ?> />
<link rel="stylesheet" href=<?php echo  mix('css/header-new.css'); ?> />
<link rel="stylesheet" href="<?php echo  asset('css/components/filter-calendar.css'); ?>" />

<script>
    var close_btn = 'OK';
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var forms = document.querySelectorAll('form');

        // Устанавливаем обработчик события submit для каждой формы
        forms.forEach(function(form) {
            form.addEventListener('submit', function(event) {
                // Удаляем обработчик события beforeunload при отправке формы
                window.onbeforeunload = null;
            });
        });

        // Сброс события beforeunload при попытке покинуть страницу
        window.addEventListener('beforeunload', function(event) {
            // Обнуляем обработчик, чтобы избежать предупреждения
            window.onbeforeunload = null;
        });
    });
</script>
