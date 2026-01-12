<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?></head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?></div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="login_container">
                Phone
                <div class="flex-row gap-30">
                    <div class="col-md-6">
                        <a href="<?php echo route('main')?>" class="login_backlink h3_title flex_ac">
                            <img src="<?php echo  asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU']?>
                        </a>
                        <div class="login_page_title h2_title">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA']?>
                        </div>
                        <div class="login_inputs_wrapper">
                            <div class="row login_input_row">
                                <label class="par input_label">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_NOMER']?>
                                    <span>
                                       +380733456789
                                    </span>
                                </label>
                                <input class="c_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA']?>">
                            </div>
                            <div class="row login_input_row">
                                <button class="send_login_code_btn h4_title flex_ac blue_btn">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_PIDTVERDITI']?>
                                </button>
                            </div>
                            <div class="login_clarification par">
                                <p>
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA']?> <a href="#"><?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH']?></a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="login_logo">
                            <img src="<?php echo  asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo" class="fit_img">
                        </div>
                    </div>
                </div>
                Email
                <div class="flex-row gap-30">
                    <div class="col-md-6">
                        <a href="<?php echo route('main')?>" class="login_backlink h3_title flex_ac">
                            <img src="<?php echo  asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU']?>
                        </a>
                        <div class="login_page_title h2_title">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA']?>
                        </div>
                        <div class="login_inputs_wrapper">
                            <div class="row login_input_row">
                                <label class="par input_label">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_EMAIL']?>
                                    <span>
                                       yoremail@gmail.com
                                    </span>
                                </label>
                                <input class="c_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA']?>">
                            </div>
                            <div class="row login_input_row">
                                <button class="send_login_code_btn h4_title flex_ac blue_btn">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_PIDTVERDITI']?>
                                </button>
                            </div>
                            <div class="login_clarification par">
                                <p>
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA']?> <a href="#"><?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH']?></a>.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="login_logo">
                            <img src="<?php echo  asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo" class="fit_img">
                        </div>
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

</body>
</html>
