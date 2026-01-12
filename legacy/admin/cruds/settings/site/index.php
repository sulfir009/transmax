<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if ($Admin->CheckPermission($_params['access'])) {
    ?>
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
                            <h1 class="m-0"><?= $_params['title'] ?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <?php
            if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
                $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
                foreach ($ar_clean as $code => $ar_value) {
                    if (is_array($ar_value[1])) {
                        $ar_value[1] = implode(",", $ar_value[1]);
                    }
                    $set_settings = mysqli_query($db, "UPDATE `" . DB_PREFIX . "_settings`
                        	SET `description`='" . $ar_value[0] . "', `value`='" . $ar_value[1] . "' WHERE `code`='" . $code . "'");

                }
                ?>
                <div class="col-xs-3">
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        Настройки изменены
                    </div>
                </div>

                <?
            }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
                <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
            <?}
            ?>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <form method="post" action="">
                        <? $getSettings = mysqli_query($db, " SELECT * FROM " . $_params['table'] . " WHERE active = 1 ORDER BY sort DESC  ");
                        while ($Setting = mysqli_fetch_assoc($getSettings)) { ?>
                            <div class="col-xs-12">
                                <div class="card">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><?= $Setting['title'] ?></h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12">
                                                <p class="text-light-blue"> Комментарий </p>
                                                <textarea name="<?= $Setting['code'] ?>[]"
                                                          class="form-control"><?= $Setting['description'] ?></textarea>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <p class="text-light-blue"> Значение </p>
                                                <textarea name="<?= $Setting['code'] ?>[]"
                                                          class="form-control"><?= $Setting['value'] ?></textarea>
                                            </div>
                                        </div>

                                        <? if ($Admin->checkPermission(1)) { ?>
                                            <div class="codeBlock">
                                                <div id="<?= uniqid() ?>" class="php_code"
                                                     style="font-family: Consolas, Courier;margin-top: 10px;">
                                                    <? echo "$";
                                                    echo "GLOBALS['site_settings']['" . $Setting['code'] . "']<br>"; ?>
                                                </div>
                                                <div id="<?= uniqid() ?>" class="html_code"
                                                     style="font-family: Consolas, Courier">
                                                    <? echo "&lt;?=$";
                                                    echo "GLOBALS['site_settings']['" . $Setting['code'] . "']?&gt;<br>"; ?>
                                                </div>
                                                <br>
                                                <div style="position: absolute;bottom: 3px;" class="notific"></div>
                                            </div>
                                        <? } ?>
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <div style="text-align: center">
                            <input type="submit" class="btn btn-primary btn-lg" value="Сохранить" name="ok"/>
                        </div>
                    </form>
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
    </div>
    <!-- ./wrapper -->
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
    </body>
    </html>
<?php } ?>
