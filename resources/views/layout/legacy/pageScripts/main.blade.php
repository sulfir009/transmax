<script>
    <?php if (!empty($_SESSION['invalid_social_auth'])) { ?>
    $out('@lang('dictionary.MSG_ALL_NE_UDALOSI_AVTORIZOVATISYA_CHEREZ')<?php echo $_SESSION['invalid_social_auth'] ?>', '@lang('dictionary.MSG_ALL_POPROBUJTE_POZZHE')');
        <?php unset($_SESSION['invalid_social_auth']);
    } ?>

    $('.advantages_slider').slick({
        dots: true,
        dotsClass: 'advantages_slider_nav slick_slider_nav',
        arrows: false,
    });
    $('.why_we_slider').slick({
        dots: true,
        dotsClass: 'why_we_slider_nav slick_slider_nav',
        arrows: false,
    });
    $('.reviews_slider').slick({
        slidesToShow: 2,
        slidesToScroll: 2,
        dots: true,
        dotsClass: 'reviews_slider_nav slick_slider_nav',
        arrows: false,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1.04,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 576,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            }
        ]
    })
</script>
