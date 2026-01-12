<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if( $Admin->CheckPermission($_params['access_delete']) )
{

    $old_page = $_SERVER['HTTP_REFERER'];
    if ( !isset($id) ):
        header("Location: ".$old_page);
        exit;
    else:

        // стандарные блоки удаления
        $del_elem = mysqli_query($db, "DELETE FROM `" . DB_PREFIX . "_regular_race_alias` WHERE `id`='".$id."'");
        $delete = mysqli_query($db, "DELETE FROM `".DB_PREFIX."_regular_races` WHERE `regular_race_alias_id` ='".$id."'");

        header("Location: ".$old_page);

    endif;
}
?>
