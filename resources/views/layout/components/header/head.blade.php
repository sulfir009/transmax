<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="<?php echo  mix('js/legacy/googletagmager.js') ?>"></script>

<!-- End Google Tag Manager -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>

    @if (isset($page_data['page_title']))
    {!!  $page_data['page_title'] !!}
    @elseif ($pageData['page_title'])
        {!! $pageData['page_title'] !!}
    @else
        {{ 'Page title' }}
    @endif
</title>
<meta name="description" content="
@if(isset($page_data['meta_d']))
{{ $page_data['meta_d'] }}
@else:
{{ 'Page description' }}
@endif
">

<meta name="keywords" content="

@if(isset($page_data['meta_k']))
{{ $page_data['meta_k'] }}
@else
{{ 'Page keywords'}}
@endif
">

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
