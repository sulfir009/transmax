<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPZPXJNJ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="wrapper">
    <div class="header index_header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
    <div class="content">
        <div class="main_index_block">
            <? $mainBanner = $Db->getOne("SELECT image,title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_main_banner` ") ?>
            <img src="<?php echo  asset('images/legacy/upload/main/' . $mainBanner['image']); ?>" alt="main_img" class="fit_img mib_back_img">
            <div class="mib_content">
                <div class="container">
                    <h1 class="h1_title mib_content_header">
                        <?php echo  $mainBanner['title'] ?>
                    </h1>
                    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/filter.php' ?>
                </div>
            </div>
        </div>
        <div class="advantages_slider_block">
            <div class="container">
                <div class="flex-row gap-30">
                    <div class="col-xxl-8 col-lg-7 col-xs-12">
                        <div class="advantages_slider_wrapper">
                            <div class="advantages_slider">
                                <? $getAdvantages = $Db->getAll("SELECT image,title_" . $Router->lang . " AS title,preview_".$Router->lang." AS preview FROM `" . DB_PREFIX . "_advantages` WHERE active = '1' ORDER BY sort DESC");
                                foreach ($getAdvantages as $k => $advantage) {
                                    ?>
                                    <div class="advantage_slide">
                                        <div class="advantage_slide_content">
                                            <div class="advantage_img">
                                                <img src="<?php echo  asset('images/legacy/upload/advantage/' . $advantage['image']); ?>" alt="advantage" class="fit_img">
                                            </div>
                                            <div class="advantage_description">
                                                <div
                                                    class="advantage_title h2_title">
                                                    <?php
                                                    foreach(explode('#', $advantage['title']) as $t) {
                                                        ?>
                                                        <p> <?php echo $t ?></p>
                                                    <?php } ?>
                                                </div>
                                                <div class="advantage_txt par">
                                                    <?php foreach(explode('#', $advantage['preview']) as $t) {
                                                        ?>
                                                        <p><?php echo htmlspecialchars_decode($t) ?></p>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>
                            </div>
                            <div class="advantages_slider_nav slick_slider_nav"></div>
                        </div>
                    </div>
                    <div class="col-xxl-4 col-lg-5 col-xs-12">
                        <div class="advantages_card">
                            <div class="advantages_card_top">
                                <div class="advantage_card_title h2_title">
                                    <?php echo $GLOBALS['dictionary']['MSG__MAX_TRANS_TEPER_BLABLACAR']?>
                                </div>
                                <div class="advantage_card_subtitle par">
                                    <?php echo $GLOBALS['dictionary']['MSG__TI_ZH_AVTOBUSNI_REJSI_ZA_BILISH_VIGIDNOYU_CINOYU']?>
                                </div>
                            </div>
                            <div class="advantages_card_middle">
                            </div>
                            <div class="advantages_card_bottom">
                                <a href="<?php echo $GLOBALS['site_settings']['BLABLACAR']?>" class="advantage_card_btn btn_txt" target="_blank">
                                    <?php echo $GLOBALS['dictionary']['MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR']?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="welcome_block">
            <div class="container">
                <div class="flex-row gap-30 welcome_block_wrapper">
                    <?php $wellcomeInfo = $Db->getOne("SELECT image,title_".$Router->lang." AS title,text_".$Router->lang." AS text FROM `".DB_PREFIX."_wellcome` ");?>
                    <div class="col-lg-6 col-xs-12">
                        <div class="welcome_txt_block">
                            <div class="welcome_title h2_title">
                                <?php echo $wellcomeInfo['title']?>
                            </div>
                            <div class="welcome_txt par">
                                <?php echo $wellcomeInfo['text']?>
                            </div>
                            <a href="<?php echo $Router->writelink(71)?>" class="about_details h4_title blue_btn">
                                <?php echo $GLOBALS['dictionary']['MSG__DETALINISHE_PRO_NAS']?>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xs-12">
                        <div class="welcome_img">
                            <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $wellcomeInfo['image']); ?>" alt="welcome" class="fit_img">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="routes_block">
            <div class="container">
                <div class="routes_title h2_title">
                    <?php echo $GLOBALS['dictionary']['MSG__NASHI_NAPRAVLENNYA']?>
                </div>
                <div class="routes_subtitle par">
                    <?php echo $GLOBALS['dictionary']['MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI-YAKOMU_NAPRYAMKU']?>
                </div>
                <div class="routes_lists_wrapper">
                    <div class="route_list_block">
                        <div class="route_list_title h3_title"><?php echo $GLOBALS['dictionary']['MSG_ALL_KRANI']?></div>
                        <div class="route_list">
                            <?php $getCountries = $Db->getall("SELECT id,title_".$Router->lang." AS title FROM `".DB_PREFIX."_cities` WHERE active = '1' AND section_id = '0' AND show_home = '1' ORDER BY sort DESC");
                            foreach ($getCountries AS $k=>$country){?>
                                <div>
                                    <a href="<?php echo $Router->writelink(73)?>?country=<?php echo $country['id']?>" class="shedule_link"><?php echo $country['title']?></a>
                                </div>
                            <?php }?>
                        </div>
                    </div>
                    <div class="route_list_block">
                        <a href="<?php echo $Router->writelink(73)?>" class="route_list_title h3_title"><?php echo $GLOBALS['dictionary']['MSG_ALL_ROZKLAD']?></a>
                        <div class="route_list">
                            <?php $getCities = $Db->getAll("SELECT id,title_".$Router->lang." AS title FROM `".DB_PREFIX."_cities` WHERE active = '1' AND section_id != 0 AND section_id != '175' AND station = '0' ORDER BY sort DESC LIMIT 10");
                            foreach ($getCities AS $k=>$city){?>
                                <div>
                                    <a href="<?php echo $Router->writelink(73)?>?city=<?php echo $city['id']?>" class="shedule_link"><?php echo $city['title']?></a>
                                </div>
                            <?php }?>
                        </div>
                    </div>
                    <div class="route_list_block">
                        <div class="route_list_title h3_title"><?php echo $GLOBALS['dictionary']['MSG_ALL_MIZHNARODNI']?></div>
                        <div class="route_list">
                            <?php $getInternationalTours = $Db->getAll("SELECT t.id,t.departure,t.arrival,departure_city.title_".$Router->lang." AS departure_city, arrival_city.title_".$Router->lang." AS arrival_city,
                            departure_city.id AS departure_city_id,arrival_city.id AS arrival_city_id
                                FROM `" . DB_PREFIX . "_tours` t
                                 JOIN `" . DB_PREFIX . "_cities` departure_city ON t.departure = departure_city.id
                                 JOIN `" . DB_PREFIX . "_cities` arrival_city ON t.arrival = arrival_city.id
                                 WHERE departure_city.section_id != arrival_city.section_id");
                        $printedRoutes = array();
                        foreach ($getInternationalTours as $k => $internationalTour) {
                            $routeString = $internationalTour['departure_city_id'] . "_" . $internationalTour['arrival_city_id'];

                            if (!in_array($routeString, $printedRoutes)) {
                                ?>
                                <div>
                                    <a href="<?php echo  route('schedule') ?>?departure=<?php echo  $internationalTour['departure_city_id'] ?>&arrival=<?php echo  $internationalTour['arrival_city_id'] ?>"
                                       class="shedule_link"><?php echo  $internationalTour['departure_city'] ?>
                                        → <?php echo  $internationalTour['arrival_city'] ?></a>
                                </div>
                                <?php $printedRoutes[] = $routeString;
                            }
                        } ?>
                    </div>
                </div>
                <div class="route_list_block">
                    <div class="route_list_title h3_title"><?php echo  $GLOBALS['dictionary']['MSG_ALL_VNUTRISHNI'] ?></div>
                    <div class="route_list">
                        <?php $getHomeTours = $Db->getAll("SELECT t.id,t.departure,t.arrival,departure_city.title_" . $Router->lang . " AS departure_city, arrival_city.title_" . $Router->lang . " AS arrival_city,
                            departure_city.id AS departure_city_id,arrival_city.id AS arrival_city_id
                                FROM `" . DB_PREFIX . "_tours` t
                                 JOIN `" . DB_PREFIX . "_cities` departure_city ON t.departure = departure_city.id
                                 JOIN `" . DB_PREFIX . "_cities` arrival_city ON t.arrival = arrival_city.id
                                 WHERE departure_city.section_id = '13' AND arrival_city.section_id = '13' ");
                        $printedRoutes = array();

                        foreach ($getHomeTours as $k => $homeTour) {
                            $routeString = $homeTour['departure_city_id'] . "_" . $homeTour['arrival_city_id'];
                            if (!in_array($routeString, $printedRoutes)) {
                                ?>
                                <div>
                                    <a href="<?php echo  route('schedule') ?>?departure=<?php echo  $homeTour['departure_city_id'] ?>&arrival=<?php echo  $homeTour['arrival_city_id'] ?>"
                                       class="shedule_link"><?php echo  $homeTour['departure_city'] ?>
                                        → <?php echo  $homeTour['arrival_city'] ?></a>
                                </div>
                                <?php $printedRoutes[] = $routeString;
                            }
                        }
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="index_options_block">
        <div class="container">
            <div class="flex-row gap-30">
                <div class="col-xl-4 col-md-12">
                    <div class="index_option flex_ac shadow_block">
                        <div class="index_option_img">
                            <img src="<?php echo  asset('images/legacy/calendar_option.svg'); ?>" alt="calendar">
                        </div>
                        <div class="index_option_description">
                            <a href="<?php echo  route('schedule') ?>" class="index_option_title h3_title">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_ROZKLAD_AVTOBUSIV'] ?>
                            </a>
                            <div class="index_option_subtitle par">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_ROZKLAD_MARSHRUTI_STANCI'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="index_option flex_ac shadow_block">
                        <div class="index_option_img">
                            <img src="<?php echo  asset('images/legacy/return_option.svg'); ?>" alt="return">
                        </div>
                        <div class="index_option_description">
                            <div class="index_option_title h3_title">
                                <a href="<?php echo  $Router->writelink(87) ?>">
                                    <?php echo  $GLOBALS['dictionary']['MSG_ALL_POVERNENNYA_KVITKIV'] ?>
                                </a>
                            </div>
                            <div class="index_option_subtitle par">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_ZMINILISI_PLANI_POVERNITI_KOSHTI_ZA_KVITOK_CHEREZ_NASH_SAJT'] ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-12">
                    <div class="index_option flex_ac shadow_block">
                        <div class="index_option_img">
                            <img src="<?php echo  asset('images/legacy/phone_option.svg'); ?>" alt="phone">
                        </div>
                        <div class="index_option_description">
                            <a href="<?php echo  $Router->writelink(76) ?>" class="index_option_title h3_title">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_BEZ_KAS_TA_CHERG'] ?>
                            </a>
                            <div class="index_option_subtitle par">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_KVITKI_ONLAJN_U_BUDI-YAKIJ_CHAS_NA_NASHOMU_SAJTI_DLYA_ZRUCHNOGO_PRIDBANNYA_ABO_BRONYUVANNYA'] ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="index_numbers_block">
        <div class="container">
            <div class="flex-row gap-30 numbers_wrapper">
                <? $nubmbersInfo = $Db->getOne("SELECT image,title_" . $Router->lang . " AS title,text1_" . $Router->lang . " AS text1,text2_" . $Router->lang . " AS text2,text3_" . $Router->lang . " AS text3,text4_" . $Router->lang . " AS text4,number1,number2,number3,number4 FROM `" . DB_PREFIX . "_about_numbers` "); ?>
                <div class="col-xxl-6 col-xs-12">
                    <div class="index_numbers">
                        <div class="index_numbers_block_title h2_title"><?php echo  $nubmbersInfo['title'] ?></div>
                        <div class="number_blocks_wrapper">
                            <?php if (!empty($nubmbersInfo['number1'])): ?>
                                <div class="number_block">
                                    <div class="number_block_title h3_title"><?php echo  $nubmbersInfo['text1'] ?></div>

                                    <!--?$busesQty = $Db->getOne("SELECT COUNT(id) FROM `".DB_PREFIX."_buses` WHERE active = '1'");-->
                                    <? $busesNum = str_pad($nubmbersInfo['number1'], 3, '0', STR_PAD_LEFT); ?>
                                    <div class="number_block_value">
                                        <div class="index_number_wrapper flex_ac">
                                            <div class="index_number h2_title"><?php echo  $nubmbersInfo['number1'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($nubmbersInfo['number2'])): ?>
                                <div class="number_block">
                                    <div class="number_block_title h3_title"><?php echo  $nubmbersInfo['text2'] ?></div>
                                    <div class="number_block_value">
                                        <? $ordersQty = $Db->getOne("SELECT COUNT(id) FROM `" . DB_PREFIX . "_orders` WHERE active = '1'");
                                        $ordersNum = str_pad($nubmbersInfo['number2'], 3, '0', STR_PAD_LEFT); ?>
                                        <div class="index_number_wrapper flex_ac">
                                            <div class="index_number h2_title"><?php echo  $nubmbersInfo['number2'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($nubmbersInfo['number3'])): ?>
                                <div class="number_block">
                                    <div class="number_block_title h3_title"><?php echo  $nubmbersInfo['text3'] ?></div>
                                    <div class="number_block_value">
                                        <? $ukCitiesQty = $Db->getOne("SELECT COUNT(id) FROM `" . DB_PREFIX . "_cities` WHERE active = '1' AND section_id = '13' ");
                                        $ukCitiesNum = str_pad($nubmbersInfo['number3'], 3, '0', STR_PAD_LEFT); ?>
                                        <div class="index_number_wrapper flex_ac">
                                            <div class="index_number h2_title"><?php echo  $nubmbersInfo['number3'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($nubmbersInfo['number4'])): ?>
                                <div class="number_block">
                                    <div class="number_block_title h3_title"><?php echo  $nubmbersInfo['text4'] ?></div>
                                    <div class="number_block_value">
                                        <? $countriesQty = $Db->getOne("SELECT COUNT(id) FROM `" . DB_PREFIX . "_cities` WHERE active = '1' AND section_id = '0' ");
                                        $countriesNum = str_pad($nubmbersInfo['number4'], 3, '0', STR_PAD_LEFT); ?>
                                        <div class="index_number_wrapper flex_ac">
                                            <div class="index_number h2_title"><?php echo  $nubmbersInfo['number4'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo  $Router->writelink(76) ?>" class="h4_title buy_ticket_link blue_btn">
                            <?php echo  $GLOBALS['dictionary']['MSG__ZAMOVITI_KVITOK'] ?>
                        </a>
                    </div>
                </div>
                <div class="col-xxl-5 col-xs-12">
                    <div class="index_map">
                        <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $nubmbersInfo['image']); ?>" alt="map"
                             class="fit_img">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="why_we_block">
        <div class="container">
            <div class="flex-row gap-30">
                <div class="col-xl-4 col-lg-5 col-md-12">
                    <div class="why_we_card">
                        <div class="why_we_card_top">
                            <div
                                class="why_we_card_title h2_title"><?php echo  $GLOBALS['dictionary']['MSG_ALL_NASHI_AVTOBUSI'] ?></div>
                            <div class="why_we_card_description par">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_OUR_BUSES_SUBTITLE'] ?>
                            </div>
                        </div>
                        <div class="why_we_card_middle">
                            <div class="why_we_card_logo">
                                <img
                                    src="<?php echo  asset('images/legacy/upload/logos/' . (new \App\Repository\Site\ImageRepository())->getLogo()['white_logo']); ?>"
                                    alt="logo" class="fit_img">
                            </div>
                        </div>
                        <div class="why_we_card_bottom">
                            <a href="<?php echo  route('avtopark') ?>" class="autopark_link h4_title">
                                <?php echo  $GLOBALS['dictionary']['MSG_ALL_PEREGLYANUTI_AVTOPARK'] ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8 col-lg-7 col-md-12">
                    <div class="why_we_slider_wrapper">
                        <div class="why_we_slider">
                            <? $getWhyWe = $Db->getAll("SELECT image,title_" . $Router->lang . " AS title,subtitle_" . $Router->lang . " AS subtitle, preview_" . $Router->lang . " AS preview FROM `" . DB_PREFIX . "_why_we` WHERE active = '1' ORDER BY sort ASC");
                            foreach ($getWhyWe as $k => $why) {
                                ?>
                                <div class="why_we_slide">
                                    <div class="why_we_slide_content">
                                        <div class="why_we_slide_image">
                                            <img src="<?php echo  asset('images/legacy/upload/why_we/' . $why['image']); ?>"
                                                 alt="slide" class="fit_img">
                                        </div>
                                        <div class="why_we_slide_description">
                                            <div class="why_we_slide_title h2_title"><?php echo  $why['title'] ?></div>
                                            <div class="why_we_slide_subtitle manrope"><?php echo  $why['subtitle'] ?></div>
                                            <div class="why_we_slide_txt par"><?php echo  $why['preview'] ?></div>
                                        </div>
                                    </div>
                                </div>
                            <? } ?>
                        </div>
                        <div class="why_we_slider_nav slick_slider_nav"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="reviews_block">
        <div class="container">
            <div class="reviews_block_title h2_title">
                <?php echo  $GLOBALS['dictionary']['MSG_ALL_VIDGUKI'] ?>
            </div>
        </div>
        <div class="slider_container">
            <div class="reviews_slider_wrapper">
                <div class="reviews_slider">
                    <? $getReviews = $Db->getAll("SELECT image,name,review_" . $Router->lang . " AS review FROM `" . DB_PREFIX . "_reviews` WHERE active = '1' ORDER BY sort DESC");
                    foreach ($getReviews as $k => $review) {
                        ?>
                        <div class="review_slide">
                            <div class="review_slide_content shadow_block">
                                <div class="review_slide_icon">
                                    <img src="<?php echo  asset('images/legacy/common/review_icon.svg'); ?>" alt="review icon">
                                </div>
                                <div class="review_slide_txt par">
                                    <?php echo  $review['review'] ?>
                                </div>
                                <div class="review_slide_reviewer_info flex_ac">
                                    <div class="review_slider_reviewer_image">
                                        <img src="<?php echo  asset('images/legacy/upload/reviews/' . $review['image']); ?>"
                                             alt="<?php echo  $review['name'] ?>">
                                    </div>
                                    <div class="review_slider_reviewer_name">
                                        <?php echo  $review['name'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <? } ?>
                </div>
                <div class="reviews_slider_nav slick_slider_nav"></div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php echo  view('layout.components.footer.footer', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
</div>
    <?php echo  view('layout.components.footer.footer_scripts', [
        'page_data' => $page_data,
    ])->render(); ?>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
    <?php if ($_SESSION['invalid_social_auth']){?>
      out('<?php echo $GLOBALS['dictionary']['MSG_ALL_NE_UDALOSI_AVTORIZOVATISYA_CHEREZ']?> <?php echo $_SESSION['invalid_social_auth']?>','<?php echo $GLOBALS['dictionary']['MSG_ALL_POPROBUJTE_POZZHE']?>');
    <?php
        unset($_SESSION['invalid_social_auth']);
    }?>
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
    });
</script>
</body>
</html>
