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
                        <h1 class="m-0"><?=$_params['title']?></h1>
                        <a href="index.php?id=<?=(int)$_GET['parent']?>" class="btn btn-info mt-2">Назад</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->
<?php

?>
        <? if( isset( $_POST['ok'] ) && $Admin->CheckPermission($_params['access_edit']) ){
            (new \App\Repository\Site\TranslationRepository())->updateTranslation($id, $_POST);
            $db_element = mysqli_query($db, "SELECT * FROM `".$_params['table']."` WHERE id='".$id."'");
            $Elem = mysqli_fetch_array($db_element);
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}

        // получаем значение элемента
        $getElement = mysqli_query($db, "SELECT * FROM `".$_params['table']."` WHERE id = ".$id);
        $Elem = mysqli_fetch_assoc($getElement);

        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">

                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <?
                        $translationEmpty = (new \App\Repository\Site\TranslationRepository())->getById($id)->first();
                        $titleMap = [
                            'ru' => $translationEmpty->title_ru ?? '',
                            'uk' => $translationEmpty->title_uk ?? '',
                            'en' => $translationEmpty->title_en ?? '',
                        ];
                        foreach ($Admin->langs as $keyLang => $LangInfo) {
                            ?>
                            <div class="row form-group">
                                <div class="col-lg-2 col-xs-12">
                                    <b><?=$LangInfo['title']?></b>
                                </div>
                                <div class="col-lg-8 col-xs-12">
                                    <textarea class="form-control" name="title_<?=$keyLang?>"><?=$titleMap[$keyLang]; ?></textarea>
                                </div>
                            </div>
                            <?
                        }
                        ?>
                        <div class="row form-group">
                            <div class="col-lg-2 col-xs-12">
                                <b> Комментарий </b>
                            </div>
                            <div class="col-lg-8 col-xs-12">
                                <textarea class="form-control" name="comments"><?=$translationEmpty->comments;?></textarea>
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
