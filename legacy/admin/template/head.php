<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Max Trans | Admin</title>

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/all.min.css'); ?>">
<!-- Ionicons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Tempusdominus Bootstrap 4 -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/tempusdominus-bootstrap-4.min.css'); ?>">
<!-- iCheck -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/icheck-bootstrap.min.css'); ?>">
<!-- JQVMap -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/jqvmap.min.css'); ?>">
<!-- select2 -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/select2.min.css'); ?>">
<link rel="stylesheet" href="<?= mix('css/admin/plugins/select2-bootstrap4.min.css'); ?>">
<!-- Theme style -->
<link rel="stylesheet" href="<?= mix('css/admin/adminlte.css'); ?>">
<!-- overlayScrollbars -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/OverlayScrollbars.min.css'); ?>">
<!-- Daterange picker -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/daterangepicker.css'); ?>">
<!-- summernote -->
<link rel="stylesheet" href="<?= mix('css/admin/plugins/summernote-bs4.min.css'); ?>">
<link rel="stylesheet" href="<?= mix('css/admin/style.css'); ?>">

<?
$adminTheme = array();
if (isset($Admin->theme) && $Admin->theme == 2){
  $adminTheme['i_class'] = 'fas';
  $adminTheme['body_class'] = 'dark-mode';
  $adminTheme['main_header_class'] = 'navbar-dark';
  $adminTheme['aside_class'] = 'sidebar-dark-info';
  $adminTheme['aside_nav_class'] = 'navbar-dark';
}else{
  $adminTheme['i_class'] = 'far';
  $adminTheme['body_class'] = '';
  $adminTheme['main_header_class'] = 'navbar-light navbar-white';
  $adminTheme['aside_class'] = 'sidebar-light-primary';
  $adminTheme['aside_nav_class'] = '';
}

?>
