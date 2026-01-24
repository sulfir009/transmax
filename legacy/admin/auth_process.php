<?php
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/engine/db.php';
if (isset($_POST['auth'])){
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_POST['captcha']) || $_POST['captcha'] == '' || (strtolower($_POST['captcha']) != strtolower($_SESSION['captcha']['code']))){
        $_SESSION['login_err']['invalid_captcha'] = '1';
        redirect('/admin/auth.php')->send();
    }else{
        if ((isset($_POST['login']) && $_POST['login']) && isset($_POST['password']) && $_POST['password']){
            $cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            //$hashPass = password_hash($cleanPost['password'], PASSWORD_DEFAULT); print_r($hashPass); exit();
            $checkAdmin = mysqli_query($db,"SELECT `id`,`pass` FROM `" .  DB_PREFIX . "_users`WHERE `login` = '".$cleanPost['login']."' LIMIT 1 ");
            if ((int)mysqli_num_rows($checkAdmin) == 1){
                $adminInfo = mysqli_fetch_assoc($checkAdmin);
                if (password_verify($cleanPost['password'],$adminInfo['pass'])){
                    $loginHash = crypt($cleanPost['login'],'8lsWOzaDak8Mix6jUsWE');
                    $_SESSION['admin']['hash'] = $loginHash;
                    $upd = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_users`SET login_hash = '".$loginHash."' WHERE id = '".$adminInfo['id']."'  ");
                    redirect('/admin')->send();
                }else{
                    $_SESSION['login_err']['invalid_credentials'] = '1';
                    redirect('/admin/auth.php')->send();
                    header('Location:/'.ADMIN_PANEL.'/auth.php');
                }
            }else{
                $_SESSION['login_err']['invalid_credentials'] = '1';
                redirect('/admin/auth.php')->send();
            }
        }else{
            $_SESSION['login_err']['empty_fields'] = '1';
            redirect('/admin/auth.php')->send();
            header('Location:/'.ADMIN_PANEL.'/auth.php');
        }
    }
}
?>
