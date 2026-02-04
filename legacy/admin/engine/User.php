<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain = getenv('SESSION_DOMAIN');
    if ($domain === false) {
        $domain = '';
    }
    if (!headers_sent()) {
        session_set_cookie_params(0, '/', $domain, $secure, true);
    }
    session_start();
}

class User extends CDb
{

    var $id;
    var $name;
    var $email;
    var $auth;
    var $Db;
    var $phone;
    var $phone_code;
    var $uid;
    var $authSource;


    function __construct($db = null) {
        global $Db;
        $this->Db = $db ?? $Db;
        $this->auth = false;
        $this->authSource = 'none';
        if (!isset($_SESSION['user']['auth'])) {
            $_SESSION['user']['auth'] = $this->auth;
        }

        $sessionUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
        $sessionClientId = isset($sessionUser['id']) ? (int)$sessionUser['id'] : 0;
        if ($sessionClientId > 0) {
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE `id` = '".$sessionClientId."' AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'session:client_id');
                return;
            }
        }

        if (isset($sessionUser['crypt'])) {
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE `crypt` = '".$sessionUser['crypt']."'  AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'session:crypt');
                return;
            }
        }

        $cookieClientId = isset($_COOKIE['mt_client_id']) ? (int)$_COOKIE['mt_client_id'] : 0;
        if ($cookieClientId > 0) {
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE `id` = '".$cookieClientId."' AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'cookie:client_id');
                return;
            }
        }

        $uid = $this->normalizeUid($sessionUser['uid'] ?? ($_COOKIE['mt_client_uid'] ?? null));
        if ($uid !== '') {
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE `uid` = '".$uid."' AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'cookie_or_session:uid');
                return;
            }
        }

        $email = $this->normalizeEmail($sessionUser['email'] ?? ($_COOKIE['mt_client_email'] ?? null));
        if ($email !== '') {
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE LOWER(TRIM(`email`)) = '".$email."' AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'cookie_or_session:email');
                return;
            }
        }

        $phoneCode = isset($sessionUser['phone_code']) ? (int)$sessionUser['phone_code'] : (isset($_COOKIE['mt_client_phone_code']) ? (int)$_COOKIE['mt_client_phone_code'] : 0);
        $phone = $this->normalizePhone($sessionUser['phone'] ?? ($_COOKIE['mt_client_phone'] ?? null));
        if ($phone !== '') {
            $phoneWhere = $phoneCode > 0 ? " AND `phone_code` = '".$phoneCode."'" : '';
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE REPLACE(REPLACE(REPLACE(REPLACE(`phone`, '+', ''), ' ', ''), '-', ''), '(', '') = '".$phone."' ".$phoneWhere." AND active = 1 ");
            if ($userData != NULL) {
                $this->setUserData($userData, 'cookie_or_session:phone');
                return;
            }
        }
    }



    private function setUserData($userData, $source = 'unknown')
    {
        $this->auth = true;
        $_SESSION['user']['auth'] = $this->auth;
        $_SESSION['user']['isAuth'] = true;
        $this->id = $userData['id'];
        $this->name = $userData['name'];
        $this->email = $userData['email'];
        $this->phone = $userData['phone'] ?? null;
        $this->phone_code = $userData['phone_code'] ?? null;
        $this->uid = $userData['uid'] ?? null;
        $this->authSource = $source;

        $_SESSION['user']['id'] = $this->id;
        $_SESSION['user']['email'] = $this->email;
        $_SESSION['user']['phone'] = $this->phone;
        $_SESSION['user']['phone_code'] = $this->phone_code;
        $_SESSION['user']['uid'] = $this->uid;

        $crypt = hash('sha512', uniqid() . time());

        $_SESSION['user']['crypt'] = $crypt;
        $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET crypt = '".$crypt."' WHERE id = ".$this->id." LIMIT 1");
        $this->setAuthCookies();
        return true;
    }
    private function setAppUserData($userData)
    {
        $this->auth = true;
        $_SESSION['user']['auth'] = $this->auth;
        $_SESSION['user']['isAuth'] = true;
        $this->id = $userData['id'];
        $this->name = $userData['name'];
        $this->email = $userData['email'];
        $this->phone = $userData['phone'] ?? null;
        $this->phone_code = $userData['phone_code'] ?? null;
        $this->uid = $userData['uid'] ?? null;
        $this->authSource = 'app';

        $_SESSION['user']['id'] = $this->id;
        $_SESSION['user']['email'] = $this->email;
        $_SESSION['user']['phone'] = $this->phone;
        $_SESSION['user']['phone_code'] = $this->phone_code;
        $_SESSION['user']['uid'] = $this->uid;

        $crypt = hash('sha512', uniqid() . time());

        $_SESSION['user']['app_crypt'] = $crypt;
        $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET crypt = '".$crypt."' WHERE id = ".$this->id." LIMIT 1");
        $this->setAuthCookies();
        return true;
    }




    public function auth( $login, $password ){
        $login = $this->normalizeEmail($login);
        $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`password`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE LOWER(TRIM(`email`)) = '".$login."' AND active = 1 ");
        if (!$userData) {
            return false; // email не найден
        } elseif (password_verify($password, $userData['password'])) {
            $this->setUserData($userData, 'auth:password');
            $now = date("Y-m-d H:i:s", time());
            $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET last_auth_date = '".$now."' WHERE id = ".$this->id);
            return true; // успешная авторизация
        } else {
            return null; // неправильный пароль
        }

    }

    public function appAuth($login){
        $login = $this->normalizeEmail($login);
        $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`password`,`phone`,`phone_code`,`uid` FROM `".DB_PREFIX."_clients` WHERE LOWER(TRIM(`email`)) = '".$login."' AND active = 1 ");
        if (!$userData) {
            return false; // email не найден
        } else {
            $this->setAppUserData($userData);
            $now = date("Y-m-d H:i:s", time());
            $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET last_auth_date = '".$now."' WHERE id = ".$this->id);
            return true; // успешная авторизация
        }
    }



    public function register($arFields, $arData, $login, $password)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $arFields = array_merge($arFields, array("password","registration_date"));
        $strFields = implode(",", $arFields);
        $arData = array_merge($arData, array("'".$passwordHash."'","NOW()"));
        $strData = implode(",", $arData);

        $addResult = $this->Db->query("INSERT INTO `".DB_PREFIX."_clients` (".$strFields.") VALUES (".$strData.") ");
        if( $addResult ){
            return $this->auth($login, $password);
        }else{
            return false;
        }


    }


    public function appRegister($arFields, $arData, $login, $password)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $arFields = array_merge($arFields, array("password","registration_date"));
        $strFields = implode(",", $arFields);
        $arData = array_merge($arData, array("'".$passwordHash."'","NOW()"));
        $strData = implode(",", $arData);

        $addResult = $this->Db->query("INSERT INTO `".DB_PREFIX."_clients` (".$strFields.") VALUES (".$strData.") ");
        if( $addResult ){
            return $this->appAuth($login, $password);
        }else{
            return false;
        }


    }

    private function normalizeEmail($email)
    {
        $email = strtolower(trim((string)$email));
        return $email;
    }

    private function normalizeUid($uid)
    {
        $uid = trim((string)$uid);
        return $uid;
    }

    private function normalizePhone($phone)
    {
        $phone = trim((string)$phone);
        $phone = preg_replace('/\D+/', '', $phone);
        return $phone;
    }

    private function getCookieDomain()
    {
        $domain = getenv('SESSION_DOMAIN');
        if ($domain === false) {
            $domain = '';
        }
        return $domain;
    }

    private function setAuthCookies()
    {
        if (headers_sent()) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $domain = $this->getCookieDomain();
        $expire = time() + (60 * 60 * 24 * 365);

        setcookie('mt_client_id', (string)$this->id, $expire, '/', $domain, $secure, true);
        if (!empty($this->uid)) {
            setcookie('mt_client_uid', (string)$this->uid, $expire, '/', $domain, $secure, true);
        }
        if (!empty($this->email)) {
            setcookie('mt_client_email', strtolower(trim((string)$this->email)), $expire, '/', $domain, $secure, true);
        }
        if (!empty($this->phone)) {
            $phone = $this->normalizePhone($this->phone);
            setcookie('mt_client_phone', $phone, $expire, '/', $domain, $secure, true);
        }
        if (!empty($this->phone_code)) {
            setcookie('mt_client_phone_code', (string)$this->phone_code, $expire, '/', $domain, $secure, true);
        }
    }
}
