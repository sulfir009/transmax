<?php
$token = isset($_GET['token']) ? $_GET['token'] : null;
$email = isset($_GET['email']) ? $_GET['email'] : null;
?>

<div class="content">
    <div class="login_container">
        <div class="flex-row gap-30">
            <div class="col-lg-6">
                <a href="<?= route('main') ?>" class="login_backlink h3_title flex_ac">
                    <img src="<?= asset('images/legacy/common/blue_arrow_left_2.svg'); ?>" alt="">
                    <?= __('dictionary.MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU') ?>
                </a>
                <div class="login_form_wrapper">
                    <?php
                    if (!$email || !$token) { // Если email или token не переданы
                    ?>

                    <div class="login_page_title h2_title">
                        <?= __('dictionary.MSG_MSG__PASS_RESET') ?>
                    </div>
                    <div class="login_tabs">
                        <div class="row login_input_row">
                            <input class="c_input" type="text"
                                   placeholder="<?= $GLOBALS['dictionary']['MSG_MSG_LOGIN_EMAIL'] ?>" id="email"
                                   pattern="[^\u0400-\u04FF]*" maxlength="255"
                                   oninput="this.value = this.value.replace(/[^\x00-\x7F]/g, '');">
                        </div>
                        <div class="row login_input_row">
                            <button class="send_login_code_btn h4_title blue_btn flex_ac" onclick="resetPassMail()">
                                <?= __('dictionary.MSG_MSG__PASS_RESET') ?>
                            </button>
                        </div>

                        <?php
                        } else { ?>

                        <div class="login_page_title h2_title">
                            <?= __('dictionary.MSG_MSG__PASS_RESET_ENTER_PASS')?>
                        </div>
                        <div class="login_tabs">
                            <div class="row login_input_row">
                                <input class="c_input" type="password"
                                       placeholder="<?= __('dictionary.MSG_MSG_LOGIN_PAROLI') ?>"
                                       id="password">
                            </div>
                            <div class="row login_input_row">
                                <button class="send_login_code_btn h4_title blue_btn flex_ac"
                                        onclick="newPassword('<?= $email ?>', '<?= $token ?>')">
                                    <?= __('dictionary.MSG_MSG__PASS_RESET_SAVE') ?>
                                </button>
                            </div>


                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login_logo">
                        <img src="<?= asset('images/legacy/common/login_page_logo.png'); ?>" alt="login logo"
                             class="fit_img">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/footer_scripts.php' ?>
</div>
<script>
    function resetPassMail() {
        var email = $.trim($('#email').val());

        if (!isEmail(email)) {
            out('<?= __("dictionary.MSG_MSG_REGISTER_NEVERNYJ_EMAIL")?>', '<?=__("dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO")?>');
            return false;
        }
        initLoader();
        $.ajax({
            type: 'post',
            url: '/ajax/ru',
            data: {
                'request': 'resetPassMail',
                'email': email
            },
            success: function (response) {
                removeLoader();
                if ($.trim(response) == 'ok') {
                    out('<?= __("dictionary.MSG_MSG__PASS_RESET_INSTR")?>');
                } else if ($.trim(response) == 'email_not_found') {
                    out('<?= __("dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO")?>');
                } else {
                    out($.trim(response));
                }
            }
        });
    }

    function newPassword(email, token) {
        var password = $.trim($('#password').val());

        if (password.length < 6) {
            out('<?= __("dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE")?>');
            return false;
        }

        initLoader();
        $.ajax({
            type: 'post',
            url: '/ajax/ru',
            data: {
                'request': 'newPass',
                'email': email,
                'token': token,
                'password': password
            },
            success: function (response) {
                removeLoader();
                if ($.trim(response) == 'ok') {
                    out('<?=__("dictionary.MSG_MSG__PASS_RESET_SAVED")?>', function () {
                        window.location.href = '<?=$Router->writelink(77)?>';
                    });
                } else if ($.trim(response) == 'token_expired') {
                    out('<?= __("dictionary.MSG_MSG__PASS_RESET_EXPIRED")?>');
                } else {
                    out('<?= __("dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE")?>');
                }
            }
        });
    }
</script>
