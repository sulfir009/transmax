<!DOCTYPE html>
<html lang="<?=$Router->lang?>">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/head.php'?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/header.php' ?>
    </div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="login_container">
                Phone
                <div class="flex-row gap-30">
                    <div class="col-md-6">
                        <a href="<?=route('main')?>" class="login_backlink h3_title flex_ac">
                            <img src="<?= asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                            <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU']?>
                        </a>
                        <div class="login_page_title h2_title">
                            <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA']?>
                        </div>
                        <div class="login_inputs_wrapper">
                            <div class="row login_input_row">
                                <label class="par input_label">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_NOMER']?>
                                    <span>
                                       +380733456789
                                    </span>
                                </label>
                                <input class="c_input" type="text" placeholder="<?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA']?>">
                            </div>
                            <div class="row login_input_row">
                                <button class="send_login_code_btn h4_title flex_ac blue_btn">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_PIDTVERDITI']?>
                                </button>
                            </div>
                            <div class="login_clarification par">
                                <p>
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA']?> <a href="#"><?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH']?></a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="login_logo">
                            <img src="<?= asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo" class="fit_img">
                        </div>
                    </div>
                </div>
                Email
                <div class="flex-row gap-30">
                    <div class="col-md-6">
                        <a href="<?=route('main')?>" class="login_backlink h3_title flex_ac">
                            <img src="<?= asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                            <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU']?>
                        </a>
                        <div class="login_page_title h2_title">
                            <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA']?>
                        </div>
                        <div class="login_inputs_wrapper">
                            <div class="row login_input_row">
                                <label class="par input_label">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_EMAIL']?>
                                    <span>
                                       yoremail@gmail.com
                                    </span>
                                </label>
                                <input class="c_input" type="text" placeholder="<?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA']?>">
                            </div>
                            <div class="row login_input_row">
                                <button class="send_login_code_btn h4_title flex_ac blue_btn">
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_PIDTVERDITI']?>
                                </button>
                            </div>
                            <div class="login_clarification par">
                                <p>
                                    <?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA']?> <a href="#"><?=$GLOBALS['dictionary']['MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH']?></a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="login_logo">
                            <img src="<?= asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo" class="fit_img">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer.php'?>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/footer_scripts.php'?>
</body>
</html>
