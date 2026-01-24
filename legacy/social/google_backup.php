<?php
// Only start session if it's not already started by Laravel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log that script was accessed
error_log("Google OAuth: Script accessed with URL: " . $_SERVER['REQUEST_URI']);

if (!empty($_GET['code'])) {
    error_log("Google OAuth: Processing authentication with code: " . substr($_GET['code'], 0, 20) . "...");

    // Отправляем код для получения токена (POST-запрос).
    $params = array(
        'client_id'     => '1047739033954-v7dqa3vbh69hu7j0drp36vvj2mbs6un3.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri'  => 'https://www.maxtransltd.com/social/google.php',
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code']
    );

    $ch = curl_init('https://accounts.google.com/o/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($data, true);

    if (!empty($data['access_token'])) {
        // Токен получили, получаем данные пользователя.
        $params = array(
            'access_token' => $data['access_token'],
            'id_token'     => $data['id_token'],
            'token_type'   => 'Bearer',
            'expires_in'   => 3599
        );

        $info = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?' . urldecode(http_build_query($params)));
        $info = json_decode($info, true);

        if (!$info || !isset($info['email'])) {
            error_log("Google OAuth: Failed to get user info. Response: " . $info);
            $_SESSION['invalid_social_auth'] = 'Google';
            header("Location:/");
            exit;
        }

        error_log("Google OAuth: Successfully got user info for: " . $info['email']);
        $legacyPath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']);
        error_log("Google OAuth: Legacy path: " . $legacyPath);

        require_once($legacyPath.'/config.php');
        require_once($legacyPath."/". ADMIN_PANEL ."/includes.php");

        error_log("Google OAuth: Legacy config loaded successfully");
        $getClientInfo = mysqli_query($db,"SELECT id FROM `".DB_PREFIX."_clients` WHERE email = '".$info['email']."' AND uid = '".$info['id']."' ");
        $clientInfo = mysqli_fetch_assoc($getClientInfo);
        $crypt = hash('sha512', uniqid() . time());
        $now = date("Y-m-d H:i:s", time());
              // Set default language if not set in session
        $lang = isset($_SESSION['last_lang']) ? $_SESSION['last_lang'] : 'ua';
        $redirectLink = $Db->getOne("SELECT route FROM `".DB_PREFIX."_routes` WHERE `page_id` = '80' AND `lang` = '".$lang."' ");

        // Fallback to hardcoded route if database query fails
        if (!$redirectLink) {
            $redirectLink = ['route' => '/majbutni-pozdki'];
        }

        if ($clientInfo){
            $auth = mysqli_query($db,"UPDATE `".DB_PREFIX."_clients` SET `crypt` = '".$crypt."',`last_auth_date` = '".$now."' WHERE id = '".$clientInfo['id']."' ");
            if ($auth){
                $_SESSION['user']['crypt'] = $crypt;
                if (isset($_SESSION['order'])){
                    $redirectLinkPayment = $Db->getOne("SELECT route FROM `".DB_PREFIX."_routes` WHERE `page_id` = '86' AND `lang` = '".$lang."' ");
                    if (!$redirectLinkPayment) {
                        $redirectLinkPayment = ['route' => '/oplata']; // Fallback payment page
                    }
                    $paymentUrl = $redirectLinkPayment['route'];
                    error_log("Google OAuth: Redirecting to payment: " . $paymentUrl);
                    header("Location:".$paymentUrl);
                }else{
                    $redirectUrl = $redirectLink['route'];
                // Debug: Log the redirect for troubleshooting
                error_log("Google OAuth: Redirecting to: " . $redirectUrl);
                header("Location:".$redirectUrl);
                }
            }else{
                $_SESSION['invalid_social_auth'] = 'Google';
                $_SESSION['invalid_social_auth'] = 'Google';
                header("Location:/");
            }
        }else{
            $addUser = mysqli_query($db,"INSERT INTO `".DB_PREFIX."_clients`
            (`name`,`second_name`,`email`,`crypt`,`registration_date`,`last_auth_date`,`uid`) VALUES
            ('".$info['given_name']."','".$info['family_name']."','".$info['email']."','".$crypt."','".$now."','".$now."','".$info['id']."') ");
            if ($addUser){
                $_SESSION['user']['crypt'] = $crypt;
                if (isset($_SESSION['order'])){
                    $redirectLinkPayment = $Db->getOne("SELECT route FROM `".DB_PREFIX."_routes` WHERE `page_id` = '86' AND `lang` = '".$lang."' ");
                    if (!$redirectLinkPayment) {
                        $redirectLinkPayment = ['route' => '/oplata']; // Fallback payment page
                    }
                    $paymentUrl = $redirectLinkPayment['route'];
                    error_log("Google OAuth: Redirecting to payment: " . $paymentUrl);
                    header("Location:".$paymentUrl);
                }else{
                    $redirectUrl = $redirectLink['route'];
                // Debug: Log the redirect for troubleshooting
                error_log("Google OAuth: Redirecting to: " . $redirectUrl);
                header("Location:".$redirectUrl);
                }
            }else{
                $_SESSION['invalid_social_auth'] = 'Google';
                $_SESSION['invalid_social_auth'] = 'Google';
                header("Location:/");
            }
        }
    }else{
        error_log("Google OAuth: No code parameter received");
        $_SESSION['invalid_social_auth'] = 'Google';
        echo "Error: No authorization code received from Google";
        exit;
    }
} else {
    echo "Google OAuth: Waiting for authorization code...";
}
