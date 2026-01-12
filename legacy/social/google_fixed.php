<?php
// Only start session if it's not already started by Laravel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для логирования в файл
function logDebug($message) {
    $logFile = storage_path('logs/google_oauth.log');
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

logDebug("Google OAuth: Script accessed with URL: " . $_SERVER['REQUEST_URI']);

if (!empty($_GET['code'])) {
    logDebug("Google OAuth: Processing authentication with code: " . substr($_GET['code'], 0, 20) . "...");

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        logDebug("Google OAuth: CURL Error: " . $curlError);
        die("Error: Unable to connect to Google servers");
    }

    if ($httpCode !== 200) {
        logDebug("Google OAuth: HTTP Error: " . $httpCode . " Response: " . $data);
        die("Error: Failed to authenticate with Google");
    }

    $data = json_decode($data, true);

    if (!empty($data['access_token'])) {
        // Токен получили, получаем данные пользователя.
        $params = array(
            'access_token' => $data['access_token'],
            'id_token'     => $data['id_token'],
            'token_type'   => 'Bearer',
            'expires_in'   => 3599
        );

        $ch = curl_init('https://www.googleapis.com/oauth2/v1/userinfo?' . urldecode(http_build_query($params)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $info = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            logDebug("Google OAuth: User Info CURL Error: " . $curlError);
            die("Error: Unable to fetch user information");
        }

        if ($httpCode !== 200) {
            logDebug("Google OAuth: User Info HTTP Error: " . $httpCode . " Response: " . $info);
            die("Error: Failed to fetch user information");
        }

        $info = json_decode($info, true);

        if (!$info || !isset($info['email'])) {
            logDebug("Google OAuth: Invalid user data received: " . print_r($info, true));
            die("Error: Invalid user data received from Google");
        }

        logDebug("Google OAuth: Successfully got user info for: " . $info['email']);

        // Load legacy system
        $legacyPath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']);
        logDebug("Google OAuth: Legacy path: " . $legacyPath);

        require_once($legacyPath.'/config.php');
        require_once($legacyPath."/". ADMIN_PANEL ."/includes.php");

        logDebug("Google OAuth: Legacy config loaded successfully");

        // Check if user exists
        $email = mysqli_real_escape_string($db, $info['email']);
        $googleId = mysqli_real_escape_string($db, $info['id']);

        $getClientInfo = mysqli_query($db, "SELECT id FROM `".DB_PREFIX."_clients` WHERE email = '$email' AND uid = '$googleId'");

        if (!$getClientInfo) {
            logDebug("Google OAuth: Database error in user lookup: " . mysqli_error($db));
            die("Error: Database error during authentication");
        }

        $clientInfo = mysqli_fetch_assoc($getClientInfo);
        $crypt = hash('sha512', uniqid() . time());
        $now = date("Y-m-d H:i:s", time());

        logDebug("Google OAuth: User exists: " . ($clientInfo ? 'Yes (ID: ' . $clientInfo['id'] . ')' : 'No'));

        // Always redirect to private area
        $redirectUrl = '/majbutni-pozdki';

        if ($clientInfo){
            // Update existing user
            $updateQuery = "UPDATE `".DB_PREFIX."_clients` SET `crypt` = '$crypt', `last_auth_date` = '$now' WHERE id = '".$clientInfo['id']."'";
            $auth = mysqli_query($db, $updateQuery);

            if ($auth) {
                $_SESSION['user']['crypt'] = $crypt;
                logDebug("Google OAuth: User updated successfully. Redirecting to: " . $redirectUrl);
                header("Location: " . $redirectUrl);
                exit();
            } else {
                logDebug("Google OAuth: Failed to update user: " . mysqli_error($db));
                die("Error: Failed to update user information");
            }
        } else {
            // Create new user
            $firstName = mysqli_real_escape_string($db, $info['given_name'] ?? '');
            $lastName = mysqli_real_escape_string($db, $info['family_name'] ?? '');
            $email = mysqli_real_escape_string($db, $info['email']);

            $insertQuery = "INSERT INTO `".DB_PREFIX."_clients`
                (`name`, `second_name`, `email`, `crypt`, `registration_date`, `last_auth_date`, `uid`) VALUES
                ('$firstName', '$lastName', '$email', '$crypt', '$now', '$now', '$googleId')";

            $addUser = mysqli_query($db, $insertQuery);

            if ($addUser) {
                $_SESSION['user']['crypt'] = $crypt;
                logDebug("Google OAuth: New user created successfully. Redirecting to: " . $redirectUrl);
                header("Location: " . $redirectUrl);
                exit();
            } else {
                logDebug("Google OAuth: Failed to create user: " . mysqli_error($db));
                die("Error: Failed to create new user account");
            }
        }
    } else {
        logDebug("Google OAuth: No access token received. Response: " . print_r($data, true));
        die("Error: Failed to obtain access token from Google");
    }
} else {
    logDebug("Google OAuth: No authorization code received");
    die("Error: No authorization code received from Google");
}
?>