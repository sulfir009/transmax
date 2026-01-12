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
                            <h1 class="m-0"><?=$_params['title']?></h1>
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
                                <th>Заголовок</th>
                                <th>Изображение</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $getTableElems = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` ORDER BY id DESC ");
                            while ($Elem = mysqli_fetch_assoc($getTableElems)) {
                                ?>
                                <tr <?if ($Elem['active'] == '0'){?>
                                    class="disabled"
                                <?}?>>
                                    <td><?=$Elem['id']?></td>
                                    <td><?=$Elem['title_ru']?></td>
                                    <td><!--/Users/duda/work/new/legacy/public/upload/advantage/pt1_7c2cdo.jpg
                                        /Users/duda/work/new/legacy/public/upload/advantage/sdfhibb119.webp-->
                                        <img src="<?= asset('images/legacy/upload/advantage/' . $Elem['image']); ?>" style="max-width: 150px">
                                    </td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <a onclick="refresh_elem(<?= $Elem['id'] ?>, '<?= $_params['table'] ?>')"
                                               class="btn btn-success" title="Активировать/дезактивировать">
                                                <i <?if ($Elem['active'] == '0'){?>
                                  class="fa-regular fa-toggle-off"
                                <?} else { ?> class="fa-regular fa-toggle-off" <?} ?>></i>
                                            </a>
                                            <a href="<?= str_replace('/index,php', '', $_SERVER['REQUEST_URI']) . '/edit.php' ?>?id=<?= $Elem['id'] ?>" class="btn btn-default"
                                               title="Редактировать">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a onclick="return confirm('<?= $GLOBALS['CPLANG']['SURE_TO_DELETE'] ?>')"
                                               href="<?= str_replace('/index,php', '', $_SERVER['REQUEST_URI']) . '/delete.php' ?>?id=<?= $Elem['id'] ?>" class="btn btn-danger"
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
