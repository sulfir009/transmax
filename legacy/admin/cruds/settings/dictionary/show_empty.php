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
                            <?if (isset($_GET['id'])){?>
                                <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                                <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                            <?}?>
                        </div>
                        <div class="col-sm-6">
                            <? if( $Admin->checkPermission( 1 ) ) {
                                if($id>0) {
                                    $getEL = mysqli_query($db, "SELECT * FROM `".$_params['table']."` WHERE id = ".$id);
                                    $Elparent = mysqli_fetch_assoc($getEL);

                                    ?>
                                    <a class="btn btn-success float-right" role="button" href="<?= $_SERVER['REQUEST_URI'] . '/create.php' ?>?id=<?=$id?>">
                                        <i class="fas fa-plus"></i>
                                        Добавить слово
                                    </a>
                                <? } else { ?>
                                    <a class="btn btn-success float-right" role="button" href="create_section.php?id=<?=$id?>">
                                        <i class="fas fa-plus"></i>
                                        Добавить группу
                                    </a>
                                <? }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->
            <?php $translationEmpty = (new \App\Repository\Site\TranslationRepository())->getWithEmptyTranslation() ?>
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="table-responsive table-striped table-valign-middle">
                        <table class="table m-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Перевод Рус</th>
                                <th>Перевод Укр</th>
                                <th>Перевод Анг</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php /*dd($translationEmpty) */?>
                            <?php foreach ($translationEmpty as $translation): ?>
                                <tr>
                                    <td><?= $translation->id ?></td>
                                    <td><?= $translation->code ?></td>
                                    <td><?= $translation->title_ru ?></td>
                                    <td><?= $translation->title_uk ?></td>
                                    <td><?= $translation->title_en ?></td>
                                    <td style="text-align:right;"><a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) . '/edit.php' ?>?id=<?= $translation->id ?>" class="btn btn-default"
                                                                     title="Редактировать">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a></td>
                                </tr>
                            <?php endforeach; ?>
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
