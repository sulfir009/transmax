<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if ($Admin->CheckPermission($_params['access'])) {?>
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
                            <a href="../index.php?parent=<?=$parent?>" class="btn btn-info mt-2">Назад</a>
                        </div>
                        <div class="col-sm-6">
                            <a class="btn btn-success float-right" role="button" href="<?= $_SERVER['REQUEST_URI'] . '/create.php' ?>?parent=<?=$parent?>&city=<?=(int)$_GET['city']?>">
                                <i class="fas fa-plus"></i>
                                Добавить запись
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="table-responsive table-striped table-valign-middle">
                        <table class="table m-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $getTableElems = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE section_id = '".(int)$_GET['city']."' ORDER BY id DESC ");
                            while ($Elem = mysqli_fetch_assoc($getTableElems)) {
                                ?>
                                <tr <?if ($Elem['active'] == '0'){?>
                                  class="disabled"
                                <?}?>>
                                    <td><?=$Elem['id']?></td>
                                    <td><?=$Elem['title_'.$Admin->lang]?></td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <a onclick="refresh_elem(<?= $Elem['id'] ?>, '<?= $_params['table'] ?>')"
                                               class="btn btn-success" title="Активировать/дезактивировать">
                                                <i <?if ($Elem['active'] == '0'){?>
                                  class="fas fa-toggle-off"
                                <?} else { ?> class="fas fa-toggle-on" <?} ?>></i>
                                            </a>
                                            <a href="<?= dirname(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . '/edit.php' ?>?id=<?= $Elem['id'] ?>&parent=<?=$parent?>&city=<?=(int)$_GET['city']?>" class="btn btn-default"
                                               title="Редактировать">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a onclick="return confirm('<?= $GLOBALS['CPLANG']['SURE_TO_DELETE'] ?>')"
                                               href="<?= $_SERVER['REQUEST_URI'] . '/delete.php' ?>?id=<?= $Elem['id'] ?>" class="btn btn-danger"
                                               title="Удалить">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <? } ?>
                            </tbody>
                        </table>
                    </div>
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
