<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if( $Admin->CheckPermission($_params['access_delete']) ) {
    if ( !isset($id) ){
        header("Location: ".$_SERVER['HTTP_REFERER']);
    } else {
        $get_elem = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE `id`='" . $id . "'");
        $el = mysqli_fetch_assoc($get_elem);
        unlink(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . $_params['image'] . $el['image']);
        // стандарные блоки удаления
        $del_elem = mysqli_query($db, "DELETE FROM `" . $_params['table'] . "` WHERE `id`='" . $id . "'");
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }
}else{
    header("Location: ".$_SERVER['HTTP_REFERER']);
}
?>
