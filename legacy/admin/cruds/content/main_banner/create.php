<?php
header("Location: ".$_SERVER['HTTP_REFERER']);
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php' ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <? ?>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?=$_params['title']?></h1>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            /*ini_set('error_reporting', E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);*/

            if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'] != '') {
                include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php');
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if ($extension == 'svg'){
                    $FileName = generateName(10) . '.svg';
                }else{
                    $FileName = generateName(10) . '.webp';
                }
                $inputFile = $_FILES['image'];
                $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/main/'. $FileName;
                $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['image_width'], $_params['image_height']);
                $imageProcessor->processImage();
            }

            addElement($_params['table'], array(), $txt?? [], array('image' => $FileName), $exceptions?? []);
            $id = mysqli_insert_id($db);
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}
        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_1" role="tab" aria-controls="tab_1" aria-selected="true">Общие данные</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <? editElem('image', 'Изображение (' . $_params['image_width'] . 'X' . $_params['image_height'] . 'px)', '5', '', '', 'add', 1, 6, '', ''); ?>
                            <? foreach ($Admin->langs as $key => $lang_index) { ?>
                                <? editElem('title', 'Заголовок ('.$lang_index['code'].')', '1', '', $lang_index['code'], 'add', 1, 7); ?>
                            <? } ?>
                        </div>
                    </div>

                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
                    </div>
                </form>
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
</div>
<!-- ./wrapper -->
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
<script>
    $('.txt_editor').summernote();
</script>
</body>
</html>
