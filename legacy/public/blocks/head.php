<meta charset="UTF-8">
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KPZPXJNJ');</script>
<!-- End Google Tag Manager -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php if(isset($page_data)){ echo  $page_data['page_title']; }else{?> Page title <?php }?></title>
<meta name="description" content="<?php if(isset($page_data)){ echo  $page_data['meta_d']; }else{?>Page description<?php }?>">
<meta name="keywords" content="<?php if(isset($page_data)){ echo  $page_data['meta_k']; }else{?>Page keywords<?php }?>">

<link rel="shortcut icon" type="image/png" href="<?php echo  asset('images/legacy/upload/logos/favicon.svg');?>"/>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@500&family=Manrope:wght@400;700&family=Montserrat:wght@400;500&family=Play:wght@400;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href=<?php echo  mix('css/legacy/libs/nice_select/nice-select.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/libs/slick/slick.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/bootstrap/bootstrap.min.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/legacy/style_table.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/responsive.css'); ?>>

<link rel="stylesheet" href=<?php echo  mix('css/nag.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/common.css'); ?>>
<link rel="stylesheet" href=<?php echo  mix('css/style.css'); ?> />
<link rel="stylesheet" href=<?php echo  mix('css/mobile.css'); ?> />

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
