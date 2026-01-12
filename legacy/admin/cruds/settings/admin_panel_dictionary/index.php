<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';

// Проверка, был ли отправлен POST-запрос для обновления данных
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['titles']) && is_array($_POST['titles'])) {
        foreach ($_POST['titles'] as $id => $title) {
            $id = intval($id); // Преобразование id в целое число для безопасности
            $title = mysqli_real_escape_string($db, $title); // Экранирование строки для предотвращения SQL-инъекций

            $updateQuery = "UPDATE `" . DB_PREFIX . "_menu_admin` SET title = '$title' WHERE id = $id";
            mysqli_query($db, $updateQuery);
        }
    }
    // Перенаправление для предотвращения повторной отправки формы
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-KPZPXJNJ');</script>
    <!-- End Google Tag Manager -->

    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPZPXJNJ"
                  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="wrapper">
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Заголовки меню</h1>
                    </div>

                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="table-responsive table-striped table-valign-middle">
                    <form method="POST" action="">
                        <table class="table m-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Новый заголовок</th>
                                <th style="text-align:center;">Просмотреть страницу</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?
                            $getTableElems = mysqli_query($db, "SELECT id, title, link FROM `" . DB_PREFIX . "_menu_admin` WHERE active = 1 ORDER BY id DESC ");
                            while ($Elem = mysqli_fetch_assoc($getTableElems)) {
                                ?>
                                <tr>
                                    <td><?=$Elem['id']?></td>
                                    <td><?=$Elem['title']?></td>
                                    <td>
                                        <textarea class="form-control" name="titles[<?=$Elem['id']?>]"><?=$Elem['title']?></textarea>
                                    </td>
                                    <td align="center" width="210">
                                        <a target="_blank" class="nav-link" href="<?= str_replace('{ADMIN_PANEL}', 'admin', $Elem['link']) ?>"> <p> просмотреть страницу</p></a>
                                    </td>
                                </tr>
                            <? } ?>
                            </tbody>
                        </table>
                        <div class="card-footer" style="text-align: center">
                        <button type="submit" class="btn btn-success btn-lg">Сохранить</button>
                        </div>
                    </form>
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

