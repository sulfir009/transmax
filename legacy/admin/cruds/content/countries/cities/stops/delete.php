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
        $get_elem = mysqli_query($db,"SELECT * FROM `".$_params['table']."` WHERE `id`='".$id."'");
        $el = mysqli_fetch_assoc($get_elem);
        unlink(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).$_params['image'].$el['image']);
        // стандарные блоки удаления
        $del_elem = mysqli_query($db, "DELETE FROM `".$_params['table']."` WHERE `id`='".$id."'");


        $get_elem = mysqli_query($db,"SELECT * FROM `" .  DB_PREFIX . "_photogallery`WHERE page_id = ".$_params['page_id']." AND `elem_id`='".$id."'");
        while( $elem = mysqli_fetch_assoc($get_elem) ){
            unlink(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/upload/gallery/'.$elem['image']);
            unlink(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/upload/gallery/thumb/'.$elem['image']);
        }

        header("Location: ".$old_page);

    endif;
}
?>
