<?php
session_start();
if (!isset($_SESSION['admin'])){
    redirect('/admin/auth.php')->send();
}
?>
