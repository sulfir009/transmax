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
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <? if( isset( $_POST['ok'] ) && $Admin->CheckPermission($_params['access_edit']) ){
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            foreach ($Admin->langs as $lang_index) {
                $txt[]='text_'.$lang_index['code'];
            }
            updateElement($id, $_params['table'], array(), $txt);
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
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <?
                        $cur_tab = 1;
                        foreach( $Admin->langs as $lang_index =>$Language ){
                            ?>
                            <li class="nav-item <?if ($cur_tab == '1'){echo 'active';}?>">
                                <a class="nav-link <?if ($cur_tab == '1'){echo 'active';}?>" href="#tab_<?=$cur_tab?>" role="tab" data-toggle="pill" aria-selected="false" aria-controls="tab_<?=$cur_tab?>"><?=$Language['title']?></a>
                            </li>
                            <?
                            $cur_tab++;
                        }
                        ?>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <?
                        $c_tab = 1;
                        foreach( $Admin->langs as $key => $lang_index ){?>
                            <div class="tab-pane fade <?if ($c_tab == '1'){echo 'show active';}?>" id="tab_<?=$c_tab?>" role="tabpanel" aria-labelledby="tab_<?=$c_tab?>">
                                <? editElem('title',  'Заголовок', '1', $Elem,  $lang_index['code'], 'edit', 1, 7 ); ?>
                                <? editElem('text', 'Текст', '4', $Elem,  $lang_index['code'], 'edit'); ?>
                            </div>
                            <?
                            $c_tab++;
                        }
                        ?>
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
