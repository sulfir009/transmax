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
    <? if ($Admin->CheckPermission($_params['access'])){
    $getElemInfo = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id = '" . $id . "' ");
    $Elem = mysqli_fetch_assoc($getElemInfo); ?>
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
            $active = checkboxParam('active');
            updateElement($id, $_params['table'], array('active' => $active));
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}
        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <?out("SELECT * FROM `" . $_params['table'] . "` WHERE id = '" . $id . "' ")?>
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
                            <div class="form-group">
                                <label class="col-md-3">Имя</label>
                                <div class="col-md-9"><?=$Elem['name']?></div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Фамилия</label>
                                <div class="col-md-9"><?=$Elem['second_name']?></div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Отчество</label>
                                <div class="col-md-9"><?=$Elem['patronymic']?></div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Email</label>
                                <div class="col-md-9"><?=$Elem['email']?></div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Телефон</label>
                                <div class="col-md-9"><?=$Elem['phone']?></div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Дата рождения</label>
                                <div class="col-md-9">
                                    <?if ($Elem['birth_date'] != '0000-00-00'){
                                        echo date('d.m.Y',strtotime($Elem['birth_date']));
                                    }else{
                                        echo 'Не указана';
                                    }?>
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

