<?php

if (!isset($_SESSION)) {
    session_start();
}

error_reporting(0);

date_default_timezone_set($GLOBALS['timezone']);

require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/db.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CMain.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CRouter.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CDb.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/functions.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/User.php");
global $Router, $db;
$db =  mysqli_connect(DB_HOST, DB_LOGIN, DB_PASS, DB_NAME);
mysqli_set_charset($db , "utf8" );

$Db = new CDb($db);
$Router = new CRouter($db);
$Main = new CMain($db);

$User = new User($Db);

$GLOBALS['site_settings'] = $Main->GetDefineSettings();
$GLOBALS['auth_fields'] = array('email');

if (substr_count($_SERVER['REQUEST_URI'], "/" . ADMIN_PANEL) > 0) {
    require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CAdmin.php");
    $Admin = new CAdmin();
    $Router->lang = $Admin->lang;
}
?>
