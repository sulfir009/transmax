<?php
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['admin']);
redirect('/admin/auth.php')->send();
?>
