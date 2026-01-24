<?php
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php'; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class']; ?>">
<div class="wrapper">
    <?php ?>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php'; ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $_params['title']; ?></h1>
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <?php if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            $exceptions[] = 'additional_params';

            /*ini_set('error_reporting', E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);*/

            if (isset($_FILES['images']['tmp_name']['0']) && $_FILES['image']['tmp_name']['0'] != '') {
                include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php');
                $extension = pathinfo($_FILES['images']['0']['name'], PATHINFO_EXTENSION);
                if ($extension == 'svg'){
                    $FileName = generateName(10) . '.svg';
                }else{
                    $FileName = generateName(10) . '.webp';
                }
                $inputFile = $_FILES['images']['0'];
                $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/buses/'. $FileName;
                $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['image_width'], $_params['image_height']);
                $imageProcessor->processImage();
            }

            addElement($_params['table'], array(), $txt?? [], array('image' => $FileName), $exceptions?? []);
            $id = mysqli_insert_id($db);
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($tmp_name != '') {
                        $extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $FileName = generateName(10) . '.' . $extension; // Генерируем уникальное имя для изображения

                        // Перемещаем загруженный файл в указанную папку
                        $uploadPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/buses/'. $FileName;
                        if (move_uploaded_file($tmp_name, $uploadPath)) {
                            echo "<p>Файл успешно загружен: " . basename($uploadPath) . "</p>";
                            // Сохраняем имя файла в базу данных
                            $query = "INSERT INTO `" .  DB_PREFIX . "_buses_images`(bus_id, bus_img) VALUES ('$id', '$FileName')";
                            mysqli_query($db, $query);
                        } else {
                            echo "<p>Ошибка при загрузке файла.</p>";
                        }
                    }
                }

            }
            $_FILES = array();


            foreach ($_POST['additional_params'] as $k => $additional_param) {
                mysqli_query($db, "INSERT INTO `" .  DB_PREFIX . "_buses_options_connector`(`bus_id`,`option_id`) VALUES ('" . $id . "','" . $additional_param . "') ");
            }
        } elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])) { ?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?php } ?>

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
                            <div class="form-group">
                                <label for="images">Загрузить изображения</label>
                                <input type="file" name="images[]" multiple class="form-control">
                            </div>

                            <?php editElem('seats_qty', 'Количество посадочных мест', '1', '', '', 'add', 1, 2); ?>
                            <?php foreach ($Admin->langs as $key => $lang_index) { ?>
                                <?php editElem('title', 'Название (' . $lang_index['code'] . ')', '1', '', $lang_index['code'], 'add', 1, 7); ?>
                            <?php } ?>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="col-sm-12">Дополнительные опции</label>
                                <div class="row">
                                    <?php
                                    $getAdditionalOptions = mysqli_query($db, "SELECT id,title_" . $Admin->lang . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE active = 1 ORDER BY sort DESC");
                                    while ($additionalOption = mysqli_fetch_assoc($getAdditionalOptions)) {
                                        ?>
                                        <div class="col-sm-3">
                                            <div class="custom-control custom-checkbox col-sm-10">
                                                <input class="custom-control-input" type="checkbox"
                                                       name="additional_params[]" value="<?= $additionalOption['id']; ?>"
                                                       id="<?= $additionalOption['id']; ?>">
                                                <label for="<?= $additionalOption['id']; ?>"
                                                       class="custom-control-label"> <?= $additionalOption['title']; ?> </label>
                                            </div>
                                        </div>

                                    <?php } ?>
                                </div>
                            </div>
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
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php'; ?>
<script>
    $('.txt_editor').summernote();
</script>
</body>
</html>
