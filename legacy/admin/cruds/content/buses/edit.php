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
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <?php
        if (isset($_POST['delete_image'])) {
            $imageId = (int)$_POST['delete_image'];
            $result = mysqli_query($db, "SELECT bus_img FROM `" .  DB_PREFIX . "_buses_images`WHERE id='$imageId'");
            $image = mysqli_fetch_array($result)['bus_img'];
            $filePath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . $_params['image'] . $image;

            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                } else {
                }
            } else {
            }

            if (mysqli_query($db, "DELETE FROM `" .  DB_PREFIX . "_buses_images`WHERE id='$imageId'")) {
            } else {
                echo "<p>Failed to delete database entry.</p>";
            }
        }

        // Проверяем, был ли отправлен основной запрос формы
        if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {

            // Очистка и фильтрация данных POST
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            // Обработка активности
            $active = checkboxParam('active');

            // Массив исключений
            $exceptions = ['oldimg', 'additional_params'];

            // Обработка одиночного изображения
            if (isset($_FILES['images']['name'][0]) && $_FILES['image']['tmp_name'] != '') {
                $extension = pathinfo($_FILES['images']['name'][0], PATHINFO_EXTENSION);
                $FileName = generateName(10) . '.' . $extension; // Генерируем уникальное имя для изображения

                // Перемещаем загруженный файл в указанную папку
                $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/buses/'. $FileName;
                if (move_uploaded_file($_FILES['images']['name'][0], $uploadPath)) {
                    echo "<p>Файл успешно загружен: " . basename($uploadPath) . "</p>";
                } else {
                    echo "<p>Ошибка при загрузке файла.</p>";
                }
            }

            // Обработка нескольких изображений
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($tmp_name != '') {
                        $extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $FileName = generateName(10) . '.' . $extension; // Генерируем уникальное имя для изображения

                        // Перемещаем загруженный файл в указанную папку
                        $uploadPath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . $_params['image'] . $FileName;
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
            // Удаление текущих связей
            $delCurrentRelation = mysqli_query($db, "DELETE FROM `" .  DB_PREFIX . "_buses_options_connector`WHERE bus_id = '$id'");
            // Добавление новых связей
            foreach ($_POST['additional_params'] as $additional_param) {
                mysqli_query($db, "INSERT INTO `" .  DB_PREFIX . "_buses_options_connector`(bus_id, option_id) VALUES ('$id', '$additional_param')");
            }

            $seats_qty = isset($ar_clean['seats_qty']) ? $ar_clean['seats_qty'] : 0;
            mysqli_query($db, "UPDATE `" .  DB_PREFIX . "_buses`SET seats_qty='$seats_qty' WHERE id='$id'");
            header('Location: ' . $_SERVER['REQUEST_URI']); // Перенаправляем на текущую страницу

            // Для надежности завершаем выполнение скрипта после отправки заголовка

        } elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])) {
            echo "<div class='alert alert-danger'>У Вас нет прав доступа на редактирование данного раздела</div>";
        }

        $db_element = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id='$id'");
        $Elem = mysqli_fetch_array($db_element);
        ?>
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card" action="<?= $_SERVER['REQUEST_URI'] ?>">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_1" role="tab" aria-controls="tab_1" aria-selected="true">Общие данные</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <? editElem('active', 'Активный', '3', $Elem, '', 'edit'); ?>
                            <!--div class="form-group">
                                <label for="exampleInputEmail1" class="col-sm-3">Основное изображение</label>
                                <div class="col-sm-10">
                                    <img style="max-width: 300px;" src="<?= $_params['image'] . $Elem['image'] ?>"/>
                                    <input type="hidden" name="oldimg" value="<?= $Elem['image'] ?>"/>
                                </div>
                            </div-->
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="col-sm-3">Текущее изображение</label>
                                <div class="col-sm-10">
                                    <?php
                                    $images = mysqli_query($db, "SELECT id, bus_img FROM `" .  DB_PREFIX . "_buses_images`WHERE bus_id='" . $id . "'");
                                    $imgPath = asset('images/legacy/upload/buses/');
                                    while ($image = mysqli_fetch_array($images)) {
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<img style="max-width: 300px;" src="' . $imgPath . '/' . $image['bus_img'] . '"/>';
                                        echo '<button type="submit" name="delete_image" value="' . $image['id'] . '" class="btn btn-danger">Удалить</button>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="images">Загрузить новые изображения</label>
                                <input type="file" name="images[]" multiple class="form-control">
                            </div>
                            <? editElem('seats_qty', 'Количество посадочных мест', '1', $Elem, $lang_index['code'], 'edit', 1, 7); ?>
                            <? foreach ($Admin->langs as $key => $lang_index) { ?>
                                <? editElem('title', 'Заголовок (' . $lang_index['code'] . ')', '1', $Elem, $lang_index['code'], 'edit', 1, 7); ?>
                            <? } ?>
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="col-sm-12">Дополнительные опции</label>
                                <div class="row">
                                    <? $getCurrentAdditionalOptions = mysqli_query($db,"SELECT option_id FROM `" .  DB_PREFIX . "_buses_options_connector`WHERE bus_id = '".$id."' ");
                                    while ($currentAdditionalOption = mysqli_fetch_assoc($getCurrentAdditionalOptions)){
                                        $currentOptions[] = $currentAdditionalOption['option_id'];
                                    }
                                    $getAdditionalOptions = mysqli_query($db, "SELECT id,title_" . $Admin->lang . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE active = 1 ORDER BY sort DESC");
                                    while ($additionalOption = mysqli_fetch_assoc($getAdditionalOptions)) {
                                        ?>
                                        <div class="col-sm-3">
                                            <div class="custom-control custom-checkbox col-sm-10">
                                                <input class="custom-control-input" type="checkbox" name="additional_params[]" value="<?= $additionalOption['id'] ?>" id="<?= $additionalOption['id'] ?>" <?if (in_array($additionalOption['id'],$currentOptions)){ echo 'checked';}?> >
                                                <label for="<?= $additionalOption['id'] ?>" class="custom-control-label"> <?= $additionalOption['title'] ?> </label>
                                            </div>
                                        </div>

                                    <? } ?>
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
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
<script>
    $('.txt_editor').summernote();
</script>
</body>
</html>
<?php  exit;?>
