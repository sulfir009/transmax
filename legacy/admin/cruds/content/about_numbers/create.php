<?php
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($Admin->CheckPermission($_params['access_edit'])) {
        $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($_FILES['image']['tmp_name'])) {
            require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php';

            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $FileName = generateName(10) . ($extension === 'svg' ? '.svg' : '.webp');

            $inputFile = $_FILES['image'];
            $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/wellcome/'. $FileName;
            $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['image_width'], $_params['image_height']);
            $imageProcessor->processImage();
        }

        addElement($_params['table'], array(), $txt?? [], array('image' => $FileName ?? ''), $exceptions ?? []);
        $id = mysqli_insert_id($db);
    } else {
        $errorMessage = "У Вас нет прав доступа на редактирование данного раздела";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php'; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?= $adminTheme['body_class'] ?>">
<div class="wrapper">
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php'; ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>

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
                            <?php
                            editElem('image', 'Изменить изображение (' . $_params['image_width'] . ' X ' . $_params['image_height'] . ')', '5', $Elem, '', 'add', 0, 6, '', '');

                            foreach ($Admin->langs as $lang_index) {
                                $lang_code = $lang_index['code'];
                                editElem('title', 'Заголовок (' . $lang_code . ')', '1', $Elem, $lang_code, 'add', 1, 7);
                                editElem('text1', 'Текст (' . $lang_code . ')', '1', $Elem, $lang_code, 'add', 1, 7);
                                editElem('text2', 'Текст 2 (' . $lang_code . ')', '1', $Elem, $lang_code, 'add', 1, 7);
                                editElem('text3', 'Текст 3 (' . $lang_code . ')', '1', $Elem, $lang_code, 'add', 1, 7);
                                editElem('text4', 'Текст 4 (' . $lang_code . ')', '1', $Elem, $lang_code, 'add', 1, 7);
                            }

                            editElem('number1', 'Цифра 1', '1', $Elem, '', 'add', 1, 7);
                            editElem('number2', 'Цифра 2', '1', $Elem, '', 'add', 1, 7);
                            editElem('number3', 'Цифра 3', '1', $Elem, '', 'add', 1, 7);
                            editElem('number4', 'Цифра 4', '1', $Elem, '', 'add', 1, 7);
                            ?>
                        </div>
                    </div>

                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php'; ?>
<script>
    $('.txt_editor').summernote();
</script>
</body>
</html>
