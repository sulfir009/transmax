<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <?
    if ($Admin->CheckPermission($_params['access']) || $Admin->id == $id){
    $getElemInfo = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id = '" . $id . "' ");
    $Elem = mysqli_fetch_assoc($getElemInfo) ?>
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
        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $exceptions[] = 'oldimg';
            $exceptions[] = 'newpass';
            $exceptions[] = 'newpassrepeat';
            $ar_clean['login'] = trim($ar_clean['login']);
            $active = checkboxParam('active');

            // проверим, ежели такого логина нет
            $getLogin = mysqli_query(" SELECT id FROM `" .  DB_PREFIX . "_users`WHERE login = '" . $ar_clean['login'] . "' ");
            if (mysqli_num_rows($getLogin) > 0) {
                ?>
                <div class="alert alert-danger">
                    Логин <?= $ar_clean['login'] ?> уже существует
                </div>
                <?
            } else {
                if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'] != '') {
                    include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php');
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    if ($extension == 'svg'){
                        $FileName = generateName(10) . '.svg';
                    }else{
                        $FileName = generateName(10) . '.webp';
                    }
                    $FileName = generateName(10) . '.webp';
                    $inputFile = $_FILES['image'];
                    $outputPath = $_SERVER['DOCUMENT_ROOT'] . 'images/legacy/upload/main/'. $FileName;
                    $imageProcessor = new ImageProcessor($inputFile, $outputPath, $_params['image_width'], $_params['image_height']);
                    $imageProcessor->processImage();
                } else {
                    $FileName = $ar_clean['oldimg'];
                }
                $pass = password_hash($ar_clean['newpass'], PASSWORD_DEFAULT);
                updateElement($id, $_params['table'], array('active' => $active, 'image' => $FileName), array(), array(), $exceptions ?? []);

                $db_element = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id='" . $id . "'");
                $Elem = mysqli_fetch_array($db_element);

                if (isset($ar_clean['newpass']) && !empty(trim($ar_clean['newpass']))) {

                    $pass = password_hash($ar_clean['newpass'], PASSWORD_DEFAULT);

                    $update = mysqli_query($db, "UPDATE `" .  DB_PREFIX . "_users`SET pass = '" . $pass . "' WHERE id = " . $id);
                    if ($update) { ?>
                        <div class="alert alert-info">
                            <? echo "Пароль успешно изменен"; ?>
                        </div>
                    <? }
                }
            }
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
                            <? editElem('active', 'Активный', '3', $Elem, '', 'edit'); ?>
                            <? editElem('login', 'Логин', '1', $Elem, '', 'edit', 1, 6); ?>
                            <? editElem('name', 'Имя', '1', $Elem, '', 'edit', 1, 6); ?>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3"> Права доступа <b class="red">*</b> </label>
                            <div class="col-sm-6">
                                <? if ($Admin->CheckPermission(array(1, 2))) { ?>
                                    <select required name="usergroup" class="form-control input-sm">
                                        <?if ($Admin->permissions == '1'){?>
                                            <option value="1">Разработчик</option>
                                        <?}?>
                                        <? $getPermissions = mysqli_query($db, " SELECT * FROM `" .  DB_PREFIX . "_user_group`WHERE id != 1 AND id >= '" . $Admin->permissions . "' ");
                                        while ($permission = mysqli_fetch_assoc($getPermissions)) { ?>
                                            <option value="<?= $permission['id'] ?>" <?if ($Elem['usergroup'] == $permission['id']){echo 'selected';}?>><?= $permission['name'] ?></option>
                                        <? } ?>
                                    </select>
                                <? } else {
                                    $getUserPermissions = mysqli_query($db, "SELECT name FROM `" . DB_PREFIX . "_user_group` WHERE id = '" . $Admin->permissions . "' ");
                                    $userPermissions = mysqli_fetch_assoc($getUserPermissions); ?>
                                    <label class="col-md-12"><?= $userPermissions['name'] ?></label>
                                <? } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1" class="col-sm-3">Текущее изображение</label>
                            <div class="col-sm-10">
                                <img style="max-width: 300px;" src="<?= $_params['image'] . $Elem['image'] ?>"/>
                                <input type="hidden" name="oldimg" value="<?= $Elem['image'] ?>"/>
                            </div>
                        </div>
                        <? editElem('image', 'Изображение ('.$_params['image_width'].' X '.$_params['image_height'].')', '5', $Elem, '', 'edit', 0, 6, '', ''); ?>
                        <div class="form-group">
                            <label class="col-sm-3"> Пароль <b class="red">*</b></label>
                            <div class="col-sm-4">
                                <div class="input-group">
                                    <div class="btn btn-default" style="cursor: pointer"
                                         onclick="gen_password(10,'newpass')" title="Сгенерировать пароль">
                                        <i <?if ($Elem['active'] == '0'){?>
                                  class="fas fa-toggle-off"
                                <?} else { ?> class="fas fa-toggle-on" <?} ?>></i>
                                    </div>
                                    <input type="text" class="form-control" name="newpass" id="newpass">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3"> </label>
                            <div class="col-sm-4">
                                <div><span class="stronger"></span></div>
                                <div> Сложность пароля: <span id="indicator"> Очень легкий </span></div>
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
<? } ?>
<!-- ./wrapper -->
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
<script>
    function gen_password(len, inptID) {
        var password = "";
        var symbols = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!№;%:?*()_+=";
        for (var i = 0; i < len; i++) {
            password += symbols.charAt(Math.floor(Math.random() * symbols.length));
        }
        // return password;
        $('#' + inptID).val(password);
        checkPassword($('#' + inptID));
    }
</script>
</body>
</html>

