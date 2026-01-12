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
                            <?php $translationEmpty = (new \App\Repository\Site\TranslationRepository())->getCountWithEmptyTranslation() ?>
                            <a href="<?= $_SERVER['REQUEST_URI'] . '/show_empty.php' ?>" class="btn btn-info mt-2">Без переводов <span style="border-radius:100px; min-width: 5px; max-width: 25px; background-color: <?= ($translationEmpty > 0) ? 'red' : 'green'?>;"> (<?= $translationEmpty ?>) </span></a>
                            <?if (isset($_GET['id'])){?>
                                <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                            <?} else { ?>

                            <?php } ?>
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
                            <? $getTableElems = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE section_id = ".$id." AND edit_by_user = 1 ORDER BY id DESC ");
                            while ($Elem = mysqli_fetch_assoc($getTableElems)) {
                                ?>
                                <tr <?if ($Elem['active'] == '0'){?>
                                  class="disabled"
                                <?}?>>
                                    <td><?=$Elem['id']?></td>
                                    <td><?=$Elem['title_'.$Admin->lang]?>
                                        <?
                                        if( $Admin->checkPermission( 1 ) ){
                                            ?>
                                            <div class="codeBlock">
                                                <div id="<?=uniqid()?>" class="php_code" style="font-family: Consolas, Courier">
                                                    <?echo"$";echo"GLOBALS['dictionary']['".$Elem['code']."']<br>";?>
                                                </div>
                                                <div id="<?=uniqid()?>" class="html_code" style="font-family: Consolas, Courier">
                                                    <?echo"&lt;?=$";echo"GLOBALS['dictionary']['".$Elem['code']."']?&gt;<br>";?>
                                                </div>
                                                <br>
                                                <div style="position: absolute;bottom: 3px;" class="notific"> </div>
                                            </div>
                                            <?
                                        }else{
                                            echo $Elem['comment'];
                                        }
                                        ?>
                                    </td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <?if($id==0){?>
                                                <a href="?id=<?=$Elem['id']?>" class="btn btn-default" title="Слова">
                                                    <i class="fas fa-folder-open"></i>
                                                </a>
                                            <?}?>
                                            <?if ($id > 0){?>
                                                <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/edit.php' ?>?id=<?= $Elem['id'] ?>&parent=<?=$id?>" class="btn btn-default"
                                                   title="Редактировать">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            <?}else{?>
                                                <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/edit.php' ?>?id=<?= $Elem['id'] ?>" class="btn btn-default"
                                                   title="Редактировать">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            <?}?>
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
