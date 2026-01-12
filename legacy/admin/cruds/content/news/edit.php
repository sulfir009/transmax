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
            $active = checkboxParam('active');
            foreach ($Admin->langs as $key => $value) {
                $txt[] = 'text_' . $value['code'];
                $exceptions[] = 'route_' . $value['code'];
            }

            $exceptions[] = 'oldimg';


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
                $outputPath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/'.$_params['image'].$FileName;
                $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['image_width'], $_params['image_height']);
                $imageProcessor->processImage();
            }else{
                $FileName = $ar_clean['oldimg'];
            }

            updateElement($id, $_params['table'], array('active'=>$active,'image'=>$FileName), $txt?? [], array(), $exceptions ?? []);

            if ($id > 0 && $_params['page_id'] > 0) {
                foreach ($Admin->langs as $lang_index) {
                    $arrURL[$lang_index] = $_POST['route_' . $lang_index['code']];
                    if ($arrURL[$lang_index['code']] == '') {
                        $arrURL[$lang_index['code']] = $_POST['title_' . $lang_index['code']];
                    }
                }

                $arrURL = $Router->controlURL($arrURL);
                $arrURL = $Router->regionURL($arrURL);
                $addCpu = $Router->updateElementCpu($arrURL, $_params['page_id'], $id);

                // если добавляли фото в галлерею !!!переделать
                /*if (isset($_SESSION['photo_gallery_add_page']) && !empty($_SESSION['photo_gallery_add_page'])) {
                    mysqli_query($db, " UPDATE `".DB_PREFIX."_photogallery` SET `elem_id` = '" . $id . "' WHERE page_id = '" . $_params['page_id'] . "' AND id IN (" . implode(',', $_SESSION['photo_gallery_add_page']) . ")  ");
                }*/
            }
        } elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}

        $db_element = mysqli_query($db, "SELECT * FROM `".$_params['table']."` WHERE id='".$id."'");
        $Elem = mysqli_fetch_array($db_element);

        $CPUs = array();
        $getCPUs = mysqli_query($db," SELECT * FROM `".DB_PREFIX."_routes` WHERE page_id = ".$_params['page_id']." AND elem_id = ".$id);
        while($Cp = mysqli_fetch_assoc($getCPUs)){
            $CPUs[$Cp['lang']] = $Cp['route'];
        }
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
                        <?
                        $cur_tab = 2;
                        foreach( $Admin->langs as $lang_index =>$Language ){
                            ?>
                            <li class="nav-item">
                                <a class="nav-link" href="#tab_<?=$cur_tab?>" role="tab" data-toggle="pill" aria-selected="false" aria-controls="tab_<?=$cur_tab?>"><?=$Language['title']?></a>
                            </li>
                            <?
                            $cur_tab++;
                        }
                        ?>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <? editElem('active', 'Активный', '3', $Elem, '', 'edit' ); ?>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="col-sm-3">Текущее изображение</label>
                                <div class="col-sm-10">
                                    <img style="max-width: 300px;" src="<?= asset('images/legacy/upload/main/' . $Elem['image']); ?>" />
                                    <input type="hidden" name="oldimg" value="<?=$Elem['image']?>" />
                                </div>
                            </div>
                            <? editElem('image', 'Изменить изображение (' .$_params['image_width'].' X '.$_params['image_height'].')', '5', $Elem,  '', 'edit', 0, 6, '', ''); ?>
                        </div>
                        <?
                        $c_tab = 2;
                        foreach( $Admin->langs as $key => $lang_index ){?>
                            <div class="tab-pane fade" id="tab_<?=$c_tab?>" role="tabpanel" aria-labelledby="tab_<?=$c_tab?>">
                                <? editElem('title',  'Заголовок', '1', $Elem,  $lang_index['code'], 'edit', 1, 7 ); ?>
                                <? editElem('page_title', 'Заголовок страницы', '1', $Elem,  $lang_index['code'], 'edit', 1, 7 ); ?>
                                <div class="form-group">
                                    <label for="exampleInputEmail1" class="col-sm-3">
                                        URL (<span style="text-transform: uppercase"><?=$lang_index['code']?></span>):</font>
                                    </label>
                                    <div class="col-sm-7">
                                        <input  type="text" name="route_<?=$lang_index['code']?>" value="<?=$CPUs[$lang_index['code']]?>" class="form-control">
                                    </div>
                                </div>
                                <? editElem('meta_description', 'Meta d', '1', $Elem,  $lang_index['code'], 'edit', 0, 7 ); ?>
                                <? editElem('meta_keywords', 'Meta k', '1', $Elem,  $lang_index['code'], 'edit', 0, 7 ); ?>
                                <? editElem('preview', 'Preview', '8', $Elem,  $lang_index['code'], 'edit', 1); ?>
                                <? editElem('text', 'Text', '4', $Elem,  $lang_index['code'], 'edit'); ?>
                            </div>
                            <?
                            $c_tab++;
                        }
                        ?>
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
