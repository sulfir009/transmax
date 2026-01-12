<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';

use App\Repository\Client\ClientRepository;

if ($Admin->CheckPermission($_params['access'])) {
    $clientRepository = new ClientRepository();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Получаем информацию о возврате
    $order = $clientRepository->getReturnDetails($id, $Admin->lang);

    if (!$order) {
        header('Location: index.php');
        exit;
    }

    // Получаем информацию о пассажирах
    $passengers = $clientRepository->getOrderPassengers($order['uniqid']);
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
                            <h1 class="m-0">Детали возврата</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Вернул билет</a></li>
                                <li class="breadcrumb-item active">Детали</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Информация о клиенте</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">ФИО:</dt>
                                        <dd class="col-sm-8"><?=$order['client_name'] . ' ' . $order['client_surname']?></dd>

                                        <dt class="col-sm-4">Телефон:</dt>
                                        <dd class="col-sm-8"><?=$order['client_phone']?></dd>

                                        <dt class="col-sm-4">Email:</dt>
                                        <dd class="col-sm-8"><?=$order['client_email']?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Информация о заказе</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-4">Номер заказа:</dt>
                                        <dd class="col-sm-8"><?=$order['id']?></dd>

                                        <dt class="col-sm-4">Маршрут:</dt>
                                        <dd class="col-sm-8"><?=$order['departure_city'] . ' - ' . $order['arrival_city']?></dd>

                                        <dt class="col-sm-4">Дата поездки:</dt>
                                        <dd class="col-sm-8"><?=date('d.m.Y', strtotime($order['tour_date']))?></dd>

                                        <dt class="col-sm-4">Дата заказа:</dt>
                                        <dd class="col-sm-8"><?=date('d.m.Y H:i', strtotime($order['date']))?></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Информация о возврате</h3>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-2">Дата возврата:</dt>
                                        <dd class="col-sm-10"><?=date('d.m.Y H:i', strtotime($order['return_date']))?></dd>

                                        <dt class="col-sm-2">Причина возврата:</dt>
                                        <dd class="col-sm-10"><?=$order['return_reason_title'] ?: 'Не указана'?></dd>

                                        <dt class="col-sm-2">Способ возврата:</dt>
                                        <dd class="col-sm-10">
                                            <?php
                                            if ($order['payment_status'] == 2) {
                                                echo 'На карту';
                                            } elseif ($order['return_payment_type'] == 1) {
                                                echo '-';
                                            } else {
                                                echo 'Не указан';
                                            }
                                            ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (count($passengers) > 0) { ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Пассажиры</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ФИО</th>
                                                <th>Дата рождения</th>
                                                <th>Статус возврата</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($passengers as $index => $passenger) { ?>
                                            <tr>
                                                <td><?=$index + 1?></td>
                                                <td><?=$passenger->second_name . ' ' . $passenger->name . ' ' . $passenger->patronymic?></td>
                                                <td><?=$passenger->birth_date ? date('d.m.Y', strtotime($passenger->birth_date)) : '-'?></td>
                                                <td>
                                                    <?php if ($passenger->ticket_return == 1 && $order['payment_status'] == 2) { ?>
                                                        <span class="badge badge-danger">Возвращен</span>
                                                    <?php } elseif ($passenger->ticket_return == 1 && $order['payment_status'] == 1) { ?>
                                                        <span class="badge">-</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-success">Активен</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="form-group">
                        <a href="index.php" class="btn btn-default">Назад</a>
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
