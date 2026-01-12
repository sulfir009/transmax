<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';

use App\Repository\Client\ClientRepository;

if ($Admin->CheckPermission($_params['access_edit'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Обновление данных клиента
        $name = mysqli_real_escape_string($db, $_POST['client_name']);
        $second_name = mysqli_real_escape_string($db, $_POST['client_surname']);
        $email = mysqli_real_escape_string($db, $_POST['client_email']);
        $phone = mysqli_real_escape_string($db, $_POST['client_phone']);

        $updateQuery = "UPDATE `" . DB_PREFIX . "_orders` SET
                       client_name = '$name',
                       client_surname = '$second_name',
                       client_email = '$email',
                       client_phone = '$phone'
                       WHERE id = $id";

        if (mysqli_query($db, $updateQuery)) {
            header('Location: index.php?success=1');
            exit;
        } else {
            $error = 'Ошибка при обновлении данных';
        }
    }
    $clientRepository = new ClientRepository();
    // Получаем данные клиента
    $client = $clientRepository->getOrderInfoById($id);
    $client = $client[0] ?? [];

    if (!$client) {
        header('Location: index.php');
        exit;
    }

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
                            <h1 class="m-0">Редактирование клиента</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Купил билет наличкой</a></li>
                                <li class="breadcrumb-item active">Редактирование</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <?php if (isset($error)) { ?>
                                <div class="alert alert-danger"><?=$error?></div>
                            <?php } ?>

                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Имя</label>
                                            <input type="text" class="form-control" name="client_name" value="<?=$client->client_name ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Фамилия</label>
                                            <input type="text" class="form-control" name="client_surname" value="<?=$client->client_surname?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" class="form-control" name="client_email" value="<?=$client->client_email ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Телефон</label>
                                            <input type="text" class="form-control" name="client_phone" value="<?=$client-> client_phone ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Сохранить</button>
                                    <a href="index.php" class="btn btn-default">Отмена</a>
                                </div>
                            </form>
                        </div>
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
