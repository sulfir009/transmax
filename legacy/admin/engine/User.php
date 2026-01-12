<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class User extends CDb
{

    var $id;
    var $name;
    var $email;
    var $auth;
    var $Db;


    function __construct($db = null) {
        global $Db;
        $this->Db = $db ?? $Db;
        $this->auth = false;
        $_SESSION['user']['auth'] = $this->auth;

        if(isset($_SESSION['user']['crypt']) ){
            $userData = $this->Db->getOne("SELECT `id`,`name`,`email` FROM `".DB_PREFIX."_clients` WHERE `crypt` = '".$_SESSION['user']['crypt']."'  AND active = 1 ");
            if( $userData != NULL ){
                $this->setUserData($userData);
            }
        }
    }



    private function setUserData($userData)
    {
        $this->auth = true;
        $_SESSION['user']['auth'] = $this->auth;
        $this->id = $userData['id'];
        $this->name = $userData['name'];
        $this->email = $userData['email'];

        $crypt = hash('sha512', uniqid() . time());

        $_SESSION['user']['crypt'] = $crypt;
        $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET crypt = '".$crypt."' WHERE id = ".$this->id." LIMIT 1");
        return true;
    }
    private function setAppUserData($userData)
    {
        $this->auth = true;
        $_SESSION['user']['auth'] = $this->auth;
        $this->id = $userData['id'];
        $this->name = $userData['name'];
        $this->email = $userData['email'];

        $crypt = hash('sha512', uniqid() . time());

        $_SESSION['user']['app_crypt'] = $crypt;
        $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET crypt = '".$crypt."' WHERE id = ".$this->id." LIMIT 1");
        return true;
    }




    public function auth( $login, $password ){
        $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`password` FROM `".DB_PREFIX."_clients` WHERE email = '".$login."' AND active = 1 ");
        if (!$userData) {
            return false; // email не найден
        } elseif (password_verify($password, $userData['password'])) {
            $this->setUserData($userData);
            $now = date("Y-m-d H:i:s", time());
            $this->Db->query("UPDATE `".DB_PREFIX."_clients` SET last_auth_date = '".$now."' WHERE id = ".$this->id);
            return true; // успешная авторизация
        } else {
            return null; // неправильный пароль
        }

    }

    public function appAuth($login){
        $userData = $this->Db->getOne("SELECT `id`,`name`,`email`,`password` FROM `".DB_PREFIX."_clients` WHERE email = '".$login."' AND active = 1 ");
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
}
