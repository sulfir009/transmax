<!DOCTYPE html>
<html lang="<?php echo  $Router->lang ?>">
<head>
    <link rel="stylesheet" href=<?php echo  mix('css/legacy/libs/slick/slick.css'); ?>>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?></div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="welcome_block about_us_welcome">
                <div class="container">
                    <? $wellcomeInfo = $Db->getOne("SELECT image,title_" . $Router->lang . " AS title,text_" . $Router->lang . " AS text FROM `" . DB_PREFIX . "_wellcome` "); ?>
                    <div class="flex-row gap-30 welcome_block_wrapper">
                        <div class="col-lg-6">
                            <div class="welcome_txt_block">
                                <div class="welcome_title h2_title">
                                    <?php echo  $wellcomeInfo['title'] ?>
                                </div>
                                <div class="welcome_txt par">
                                    <?php echo  $wellcomeInfo['text'] ?>
                                </div>
                                <a href="<?php echo  $Router->writelink(76) ?>" class="about_details h4_title blue_btn">
                                    <?php echo  $GLOBALS['dictionary']['MSG___ZABRONYUVATI_BILET'] ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="welcome_img">
                                <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $wellcomeInfo['image']); ?>"
                                     alt="welcome" class="fit_img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="advantages_slider_block">
                <div class="container">
                    <div class="flex-row gap-30">
                        <div class="col-xxl-8 col-lg-7 col-xs-12">
                            <div class="advantages_slider_wrapper">
                                <div class="advantages_slider">
                                    <? $getAdvantages = $Db->getAll("SELECT image,title_" . $Router->lang . " AS title,preview_" . $Router->lang . " AS preview FROM `" . DB_PREFIX . "_advantages` WHERE active = '1' ORDER BY sort DESC");
                                    foreach ($getAdvantages as $k => $advantage) {
                                        ?>
                                        <div class="advantage_slide">
                                            <div class="advantage_slide_content">
                                                <div class="advantage_img">
                                                    <img
                                                        src="<?php echo  asset('images/legacy/upload/advantage/' . $advantage['image']); ?>"
                                                        alt="advantage" class="fit_img">
                                                </div>
                                                <div class="advantage_description">
                                                    <div
                                                        class="advantage_title h2_title">
                                                        <?php
                                                        foreach(explode('#', $advantage['title']) as $t) {
                                                            ?>
                                                            <p> <?= $t ?></p>
                                                        <?php } ?>
                                                    </div>
                                                    <div class="advantage_txt par">
                                                        <?php foreach(explode('#', $advantage['preview']) as $t) {
                                                        ?>
                                                        <p><?= $t ?></p>
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
                                        <?php echo  $GLOBALS['dictionary']['MSG__MAX_TRANS_TEPER_BLABLACAR'] ?>
                                    </div>
                                    <div class="advantage_card_subtitle par">
                                        <?php echo  $GLOBALS['dictionary']['MSG__TI_ZH_AVTOBUSNI_REJSI_ZA_BILISH_VIGIDNOYU_CINOYU'] ?>
                                    </div>
                                </div>
                                <div class="advantages_card_bottom">
                                    <button class="advantage_card_btn btn_txt">
                                        <?php echo  $GLOBALS['dictionary']['MSG__KUPUJ_BEZPECHNO_NA_BLABLACAR'] ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="about_us">
                <div class="container">
                    <div class="flex-row gap-30">
                        <? $aboutUsTxt = $Db->getOne("SELECT image,title_" . $Router->lang . " AS title, text_" . $Router->lang . " AS text,title_2_" . $Router->lang . " AS title_2,text_2_" . $Router->lang . " AS text_2 FROM `" . DB_PREFIX . "_about_us` WHERE id = '1' ") ?>
                        <div class="col-lg-6">
                            <div class="about_us_txt_wrapper">
                                <div class="about_us_txt">
                                    <div class="about_us_txt_title h2_title"><?php echo  $aboutUsTxt['title'] ?></div>
                                    <div class="about_us_description par">
                                        <?php echo  $aboutUsTxt['text'] ?>
                                    </div>
                                </div>
                                <div class="about_us_txt">
                                    <div class="about_us_txt_title h2_title"><?php echo  $aboutUsTxt['title_2'] ?></div>
                                    <div class="about_us_description par">
                                        <?php echo  $aboutUsTxt['text_2'] ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="about_us_img">
                                <img src="<?php echo  asset('images/legacy/upload/wellcome/' . $aboutUsTxt['image']); ?>"
                                     alt="about us" class="fit_img">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="company_docs_wrapper">
                <div class="company_docs_slider_container">
                    <div
                        class="h3_title company_docs_slider_title"><?php echo  $GLOBALS['dictionary']['MSG_MSG_ABOUT_DOKUMENTI_NA_ORGANIZACIYU_PEREVEZENI'] ?></div>
                    <div class="company_docs_slider">
                        <? $getCompanyDocs = $Db->getAll("SELECT image FROM `" . DB_PREFIX . "_company_docs` WHERE active = '1' ORDER BY sort DESC");
                        foreach ($getCompanyDocs as $k => $companyDoc) {
                            ?>
                            <div class="company_docs_slide">
                                <img src="<?php echo  asset('images/legacy/upload/company_docs/' . $companyDoc['image']); ?>"
                                     alt="doc 1" class="fit_img">
                            </div>
                        <? } ?>
                    </div>
                    <div class="booking_link_wrapper">
                        <a href="<?php echo  $Router->writelink(76) ?>" class="h4_title booking_link blue_btn flex_ac">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_ABOUT__ZABRONYUVATI_BILET'] ?>
                        </a>
                    </div>
                </div>
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
<script src="<?php echo  mix('js/legacy/libs/slick.min.js') ?>"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        $('.advantages_slider').slick({
            dots: true,
            dotsClass: 'advantages_slider_nav slick_slider_nav',
            arrows: false,
        });

        $('.company_docs_slider').slick({
            dots: false,
            arrows: false,
            slidesToScroll: 1,
            slidesToShow: 3,
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    });
</script>
</body>
</html>
