<?php
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
// session_start() - handled by Laravel
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/libs/captcha/simple-php-captcha.php';
session()->put('captcha', simple_php_captcha());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/template/head.php'?>
</head>
<body class="hold-transition login-page">

<?php
if (session()->has('login_err.invalid_credentials') && session()->put('login_err.invalid_credentials', '1')){
    echo  '<div class="alert alert-danger">Неверный логин или пароль</div>';
}elseif (session()->has('login_err.invalid_captcha') && session()->put('login_err.invalid_captcha', '1')){
    echo  '<div class="alert alert-danger">Неверная капча!</div>';
}elseif (session()->has('login_err.empty_fields') && session()->put('login_err.empty_fields', '1')){
    echo  '<div class="alert alert-danger">Введите логин и пароль</div>';
}
session()->forget('login_err');
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
            <p class="login-box-msg">Введите Ваши данные для входа на тестовый сервер</p>

            <form method="post" action="public/pages/auth_test.php">
                <div class="input-group mb-3">
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
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary btn-block" name="auth">Войти</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php echo  view('layout.components.footer.footer_scripts', [
    'page_data' => $page_data,
])->render(); ?>
</body>
</html>
