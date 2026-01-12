<?php
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/'.ADMIN_PANEL.'/engine/db.php';

// session_start() - handled by Laravel
if ($_POST['login'] == 'God' && $_POST['password'] == 'mod') {
    session()->put('isAvalibleTest', true);
}
header('Location:/');
?>
