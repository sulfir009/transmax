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
            <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
                $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
                $exceptions[] = 'newpass';
                $ar_clean['login'] = trim($ar_clean['login']);

                // проверим, ежели такого логина нет
                $getLogin = mysqli_query(" SELECT id FROM `" .  DB_PREFIX . "_users`WHERE login = '" . $ar_clean['login'] . "' ");
                if (mysqli_num_rows($getLogin) > 0) {
                    ?>
                    <div class="alert alert-danger">
                        Логин <?= $ar_clean['login'] ?> уже существует
                    </div>
                    <?
                } else {
                    // Изображение
                    if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'] != '') {
                        include_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/engine/CImageProcessor.php';
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
                    } else {
                        exit('<div class="alert alert-danger">No image!</div>');
                    }

                    $pass = password_hash($ar_clean['newpass'], PASSWORD_DEFAULT);

                    addElement($_params['table'], array(), $txt?? [], array('image' => $FileName, 'pass' => $pass), $exceptions ?? []);
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
                                <? editElem('name', 'Имя', '1', '', '', 'add', 1, 6); ?>
                                <? editElem('image', 'Аватар', '5', '', '', 'add', 1, 6, '', ''); ?>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3"> Права доступа <b class="red">*</b> </label>
                                <div class="col-sm-6">
                                    <select required name="usergroup" class="form-control input-sm">
                                        <?
                                        $get = mysqli_query($db, " SELECT * FROM `" .  DB_PREFIX . "_user_group`WHERE id != 1 AND id >= '".$Admin->permissions."' ");
                                        while ($langSite = mysqli_fetch_assoc($get)) {
                                            ?>
                                            <option value="<?= $langSite['id'] ?>"><?= $langSite['name'] ?></option>
                                            <?
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <? editElem('login', 'Логин', '1', '', '', 'add', 1, 6); ?>
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
<?php
