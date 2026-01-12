<?php
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/engine/db.php';
session_start();
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/libs/captcha/simple-php-captcha.php';
$_SESSION['captcha'] = simple_php_captcha();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/template/head.php'?>
</head>
<body class="hold-transition login-page">

<?php
if (isset($_SESSION['login_err']['invalid_credentials']) && $_SESSION['login_err']['invalid_credentials'] == '1'){
    echo '<div class="alert alert-danger">Неверный логин или пароль</div>';
}elseif (isset($_SESSION['login_err']['invalid_captcha']) && $_SESSION['login_err']['invalid_captcha'] == '1'){
    echo '<div class="alert alert-danger">Неверная капча!</div>';
}elseif (isset($_SESSION['login_err']['empty_fields']) && $_SESSION['login_err']['empty_fields'] == '1'){
    echo '<div class="alert alert-danger">Введите логин и пароль</div>';
}
unset($_SESSION['login_err']);
?>

<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="#" class="h1" target="_blank">
                <b>Max Trans</b>
            </a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Введите Ваши данные для входа</p>

            <form method="post" action="auth_process.php">
                <div class="input-group mb-3">
                    <input type="hidden" name="_token" value="<?= csrf_token() ?>" />
                    <input type="text" class="form-control" placeholder="Login" name="login">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-envelope"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" placeholder="Password" name="password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5">
                        <img src="<?php echo $_SESSION['captcha']['image_src']?>" alt="CAPTCHA code" style="width: 100%;max-width: 100%;max-height:38px" id="captcha_img">
                    </div>
                    <div class="col-2">
                        <button class="btn" type="button" onclick="refreshCaptcha()">
                            <i <?php if (isset($Elem['active']) && $Elem['active'] == '0'){?>
                                    class="fas fa-toggle-off"
                                <?php } else { ?>
                                    class="fas fa-toggle-on"
                                <?php } ?>>
                            </i>
                        </button>
                    </div>
                    <div class="col-5">
                        <input type="text" class="form-control" placeholder="Captcha" name="captcha">
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary btn-block" name="auth">Войти</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/template/footer_scripts.php'?>
<script>
    function refreshCaptcha(){
        $.ajax({
            type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data:{
                'request':'refresh_captcha',
            },
            success:function(a){
                if ($.trim(a) != ''){
                    $('#captcha_img').attr('src',a);
                }
            }
        })
    }
</script>
</body>
</html>
