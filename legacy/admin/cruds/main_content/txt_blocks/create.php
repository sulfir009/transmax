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

            foreach ($Admin->langs as $key => $value) {
                $txt[]='text_'.$value['code'];
            }

            $code = str_replace(" ", "_", $ar_clean['code']);
            $code = $Router->translitURL($code);
            $code = strtoupper($code);

            $is_el = mysqli_query($db, "SELECT id FROM `".$_params['table']."` WHERE `code`='".$code."'");
            $el_count = mysqli_num_rows($is_el);

            if( $el_count > 0 ){
                ?>
                <div class="alert alert-danger"> Код <?=$code?> уже существует </div>

                <?
                if( $Admin->checkPermission( 1 ) ){
                    $Elem['code'] = $code;
                    ?>
                    <div class="codeBlock">
                        <div id="<?=uniqid()?>" class="php_code" style="font-family: Consolas, Courier">
                            <?echo"$";echo"Main->GetPageIncData('".$Elem['code']."' , ";echo "$";echo "CCpu->lang)";echo"<br>";?>
                        </div>
                        <div id="<?=uniqid()?>" class="html_code" style="font-family: Consolas, Courier">
                            <?echo"&lt;?=$";echo"Main->GetPageIncData('".$Elem['code']."' , ";echo "$";echo "CCpu->lang)?&gt;<br>";?>
                        </div>
                        <br>
                        <div style="position: absolute;bottom: 3px;" class="notific"> </div>
                    </div>
                    <?
                }
                ?>

                <?
            }else{
                addElement($_params['table'], array('code'=>$code),$txt);
            }

            // addElement($_params['table'], array(), $txt?? [], array());
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
                            <? editElem('code', 'Code (пример - TEXT_NEWSLETTER )', '1', '',  '', 'add', 1, 7 );
                            $c_tab = 2;
                            foreach( $Admin->langs as $lang_index ){
                                editElem('title',  "Заголовок (".$lang_index['code'].")", '1', '',  $lang_index['code'], 'add', 1, 7 );
                                editElem('text', "Текст (".$lang_index['code'].")", '4', '', $lang_index['code'], 'add');
                                ?>
                                <hr>
                                <?
                            }
                            ?>
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
