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
                <div class="flex-row gap-30">
                    <div class="col-lg-6">
                        <a href="<?php echo $Router->writelink(1)?>" class="login_backlink h3_title flex_ac">
                            <img src="<?php echo  asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU']?>
                        </a>
                        <div class="login_form_wrapper">
                            <div class="login_page_title h2_title">
                                <?php echo $GLOBALS['dictionary']['MSG_MSG_REGISTER_REGISTRACIYA']?>
                            </div>
                            <div class="login_tabs">
                                <div class="login_inputs_wrapper">
                                    <div class="login_input_row">
                                        <input class="c_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_REGISTER_IMYA']?>" id="name">
                                    </div>
                                    <div class="login_input_row">
                                        <input class="c_input" type="text" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_LOGIN_EMAIL']?>" id="email">
                                    </div>
                                    <div class="login_input_row">
                                        <input class="c_input" type="password" placeholder="<?php echo $GLOBALS['dictionary']['MSG_MSG_REGISTER_PAROLI']?>" id="password">
                                    </div>
                                    <div class="login_input_row">
                                        <button class="send_login_code_btn h4_title blue_btn flex_ac" onclick="register()">
                                            <?php echo $GLOBALS['dictionary']['MSG_MSG_REGISTER_ZAREGISTRIROVATISYA']?>
                                        </button>
                                    </div>
                                    <div class="login_social_auth">
                                        <div class="login_input_row">
                                            <?
                                            $params = array(
                                                'client_id'     => '656617614134-pkpjqd3th5i1k392729d42oj3bsc6254.apps.googleusercontent.com',
                                                'redirect_uri'  => 'https://trans.restartweb.site/social/google.php',
                                                'response_type' => 'code',
                                                'scope'         => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
                                                'state'         => '123'
                                            );
                                            $googleAuthLink = 'https://accounts.google.com/o/oauth2/auth?'.urldecode(http_build_query($params)); ?>
                                            <a href="<?php echo $googleAuthLink?>" class="social_auth_link google flex_ac">
                                                <img src="<?php echo  asset('images/legacy/google.svg'); ?>" alt="" class="fit_img">
                                                Google
                                            </a>
                                        </div>
                                        <div class="login_input_row">
                                            <?/*
                                            $params = array(
                                                'client_id'     => 'Идентификатор приложения',
                                                'redirect_uri'  => 'https://trans.restartweb.site/social/facebook.php',
                                                'scope'         => 'email',
                                                'response_type' => 'code',
                                                'state'         => '123'
                                            );

                                            $facebookAuthLink = 'https://www.facebook.com/dialog/oauth?' . urldecode(http_build_query($params));
                                            echo  '<a href="' . $url . '">Войти через Facebook</a>';*/
                                        ?>
                                        <a href="javascript:void(0)" class="social_auth_link google flex_ac"
                                           onclick="out('В разработке')">
                                            <img src="<?php echo  asset('images/legacy/facebook.svg'); ?>" alt=""
                                                 class="fit_img">
                                            Facebook
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="login_clarification par">
                                <? $loginPageTxt = $Db->getOne("SELECT text_" . $Router->lang . " AS text FROM `" . DB_PREFIX . "_txt_blocks` WHERE id = '6' ") ?>
                                <?php echo  $loginPageTxt['text'] ?>
                                <p>
                                    <?php echo  __('dictionary.MSG_MSG_LOGIN_UMOVI') ?><a
                                        href="<?php echo  route('offer') ?>"><?php echo  __('dictionary.MSG_MSG_LOGIN_PUBLICHNO_OFERTI') ?></a> <?php echo  __('dictionary.MSG_MSG_LOGIN_TA') ?>
                                    <a href="<?php echo  route('privacy.policy') ?>"><?php echo  __('dictionary.MSG_MSG_LOGIN_POLITIKI_KONFIDENCIJNOSTI') ?></a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login_logo">
                        <img src="<?php echo  asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo"
                             class="fit_img">
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

    <script>
    function register() {
        var name = $.trim($('#name').val());
        var email = $.trim($('#email').val());
        var password = $.trim($('#password').val());
        if (!isEmail(email)) {
            out('<?php echo  __("dictionary.MSG_MSG_REGISTER_NEVERNYJ_EMAIL")?>', '<?php echo  __("dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO")?>');
            return false;
        }
        initLoader();
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '/ajax/ru',
            data: {
                'request': 'register',
                'name': name,
                'email': email,
                'password': password
            },
            success: function (response) {
                removeLoader();
                if ($.trim(response.data) == 'ok') {
                    <?php if (session()->has('order')){?>
                    location.href = '<?php echo $Router->writelink(86)?>';
                    <?php }else{?>
                    location.href = '<?php echo $Router->writelink(80)?>';
                    <?php }?>
                } else {
                    $('.required_inp').val('');
                    out(response);
                }
            }
        })
    }

    function isEmail(email) {
        if (email.length < 5) {
            return false;
        }

        var parts = email.split('@');
        if (parts.length !== 2) {
            return false;
        }

        var domain = parts[1];
        if (domain.length < 4) {
            return false;
        }


        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    function out(msg, txt) {
        if( msg == undefined || msg == '' || $('.alert').length > 0 ){
            return false;
        }

        let alert = document.createElement('div');
        $(alert).addClass('alert');

        let alertContent = document.createElement('div');
        $(alertContent).addClass('alert_content').appendTo(alert);

        let appendOverlay = document.createElement('div');
        $(appendOverlay).addClass('alert_overlay').appendTo(alert);

        let alertTitle = document.createElement('div');
        $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

        if( txt!='' ){
            let alertTxt = document.createElement('div');
            $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
        }

        let closeBtn = document.createElement('button');
        $(closeBtn).addClass('alert_ok').text( close_btn ).appendTo(alertContent);

        $('body').append(alert);
        $(alert).fadeIn();

        $('.alert_ok,.alert_overlay').on('click', function(){
            $('.alert').fadeOut();
            setTimeout(function(){
                $('.alert').remove();
            },350)
        });

    }

    function initLoader() {
        let loader = document.createElement("div");
        loader.classList.add("loader");
        let loaderIcon = document.createElement("i");
        loaderIcon.className = "fas fa-3x fa-sync-alt fa-spin";
        loader.append(loaderIcon);
        document.querySelector("body").prepend(loader);
    }
</script>
</body>
</html>
