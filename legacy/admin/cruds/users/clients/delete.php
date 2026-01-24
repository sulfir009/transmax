<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
    $id = (int)$_GET['id'];
    if( $Admin->checkPermission($_params['access_delete']) ){

    $getUserImg = mysqli_query($db, "SELECT image FROM `".$_params['table']."` WHERE id = ".$id);
    $USER = mysqli_fetch_assoc($getUserImg);
    $del = mysqli_query($db, "DELETE FROM `".$_params['table']."` WHERE `id`='".$id."'");
    unlink(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).$_params['image'].$USER['image']);
    header("Location: ".$_SERVER['HTTP_REFERER']);
    }else{
    ?>
    <div class="alert alert-danger">ERROR. NO PERMISSIONS</div>
<?
}
