<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
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
            include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php');
            $arrImages = [];
            foreach ($Admin->langs as $key => $value) {
                $exceptions[] = 'oldwhitelogo_'.$value['code'];
                $exceptions[] = 'oldblacklogo_'.$value['code'];
                if (isset($_FILES['white_logo_'.$value['code']]['tmp_name']) && $_FILES['white_logo_'.$value['code']]['tmp_name'] != '') {
                    $extension = pathinfo($_FILES['white_logo_'.$value['code']]['name'], PATHINFO_EXTENSION);
                    if ($extension == 'svg'){
                        $FileNameWhite = generateName(10) . '.svg';
                    }else{
                        $FileNameWhite = generateName(10) . '.webp';
                    }
                    $inputFile = $_FILES['white_logo_'.$value['code']];
                    $outputPath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/'.$_params['white_logo'].$FileNameWhite;
                    $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['white_logo_width'], $_params['white_logo_height']);
                    $imageProcessor->processImage();
                }else{
                    $FileNameWhite = $ar_clean['oldwhitelogo_'.$value['code']];
                }
                $arrImages['white_logo_'.$value['code']] = $FileNameWhite;
                if (isset($_FILES['black_logo_'.$value['code']]['tmp_name']) && $_FILES['black_logo_'.$value['code']]['tmp_name'] != '') {
                    $extension = pathinfo($_FILES['black_logo_'.$value['code']]['name'], PATHINFO_EXTENSION);
                    if ($extension == 'svg'){
                        $FileName = generateName(10) . '.svg';
                    }else{
                        $FileName = generateName(10) . '.webp';
                    }
                    $inputFile = $_FILES['black_logo_'.$value['code']];
                    $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/logos/'. $FileName;
                    $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['black_logo_width'], $_params['black_logo_height']);
                    $imageProcessor->processImage();
                }else{
                    $FileName = $ar_clean['oldblacklogo_'.$value['code']];
                }
                $arrImages['black_logo_'.$value['code']] = $FileName;
            }
            out($arrImages);

            updateElement($id, $_params['table'], $arrImages, $txt?? [], array(), $exceptions ?? []);
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}

        $db_element = mysqli_query($db, "SELECT * FROM `".$_params['table']."` WHERE id='".$id."'");
        $Elem = mysqli_fetch_array($db_element);
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
                            <?foreach ($Admin->langs as $key => $value) {?>
                                <div class="form-group">
                                    <label for="exampleInputEmail1" class="col-sm-3">Логотип белый</label>
                                    <div class="col-sm-10">
                                        <img style="max-width: 300px;" src="<?= asset('images/legacy/upload/logos/' . $Elem['white_logo_'.$value['code']]); ?>" />
                                        <input type="hidden" name="oldwhitelogo_<?=$value['code']?>" value="<?=$Elem['white_logo_'.$value['code']]?>" />
                                    </div>
                                </div>
                                <? editElem('white_logo', 'Изменить изображение ('.$value['code'].') (' .$_params['white_logo_width'].' X '.$_params['white_logo_height'].')', '5', $Elem,  $value['code'], 'edit', 0, 6, '', ''); ?>
                                <div class="form-group">
                                    <label for="exampleInputEmail1" class="col-sm-3">Логотип черный</label>
                                    <div class="col-sm-10">
                                        <img style="max-width: 300px;" src="<?= asset('images/legacy/upload/logos/' . $Elem['black_logo_'.$value['code']]); ?>" />
                                        <input type="hidden" name="oldblacklogo_<?=$value['code']?>" value="<?=$Elem['black_logo_'.$value['code']]?>" />
                                    </div>
                                </div>
                                <? editElem('black_logo', 'Изменить изображение ('.$value['code'].') (' .$_params['black_logo_width'].' X '.$_params['black_logo_height'].')', '5', $Elem,  $value['code'], 'edit', 0, 6, '', ''); ?>
                            <?}?>
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
