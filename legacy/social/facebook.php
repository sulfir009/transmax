<?php
// Only start session if it's not already started by Laravel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_GET['code'])) {
    // var_dump($_GET['state']);

    $params = array(
        'client_id'     => '1539278493315950',
        'client_secret' => 'cb59db99761efbf0f3389f02267ee8b',
        'redirect_uri'  => 'https://example.com/login-facebook.php',
        'code'          => $_GET['code']
    );

    // Получение access_token
    $data = file_get_contents('https://graph.facebook.com/oauth/access_token?' . urldecode(http_build_query($params)));
    $data = json_decode($data, true);

    if (!empty($data['access_token'])) {
        $params = array(
            'access_token' => $data['access_token'],
            'fields'       => 'id,email,first_name,last_name,picture'
        );

        // Получение данных пользователя
        $info = file_get_contents('https://graph.facebook.com/me?' . urldecode(http_build_query($params)));
        $info = json_decode($info, true);
        require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/config.php');
        require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/". ADMIN_PANEL ."/includes.php");
        $getClientInfo = mysqli_query($db,"SELECT id FROM `".DB_PREFIX."_clients` WHERE email = '".$info['email']."' ");
        $clientInfo = mysqli_fetch_assoc($getClientInfo);
        $crypt = hash('sha512', uniqid() . time());
        $now = date("Y-m-d H:i:s", time());
        $redirectLink = $Db->getOne("SELECT route FROM `".DB_PREFIX."_routes` WHERE `page_id` = '80' AND `lang` = '".$_SESSION['last_lang']."' ");
        if ($clientInfo){
            $auth = mysqli_query($db,"UPDATE `".DB_PREFIX."_clients` SET `crypt` = '".$crypt."',`last_auth_date` = '".$now."' WHERE id = '".$clientInfo['id']."' ");
            if ($auth){
                $_SESSION['user']['crypt'] = $crypt;
                header("Location:".$redirectLink['route']);
            }else{
                $_SESSION['invalid_social_auth'] = 'Facebook';
                $_SESSION['invalid_social_auth'] = 'Facebook';
                header("Location:/");
            }
        }else{
            $addUser = mysqli_query($db,"INSERT INTO `".DB_PREFIX."_clients`
            (`name`,`email`,`crypt`,`registration_date`,`last_auth_date`,`uid`) VALUES
            ('".$info['name']."','".$info['email']."','".$crypt."','".$now."','".$now."','".$info['id']."') ");
            if ($addUser){
                $_SESSION['user']['crypt'] = $crypt;
                header("Location:".$redirectLink['route']);
            }else{
                $_SESSION['invalid_social_auth'] = 'Facebook';
                $_SESSION['invalid_social_auth'] = 'Facebook';
                header("Location:/");
            }
        }
        //var_dump($info);
    }else{
        $_SESSION['invalid_social_auth'] = 'Facebook';
        $_SESSION['invalid_social_auth'] = 'Facebook';
        header("Location:/");
    }
}
?>
