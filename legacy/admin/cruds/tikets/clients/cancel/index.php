<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';

use App\Repository\Client\ClientRepository;

if ($Admin->CheckPermission($_params['access'])) {
    $clientRepository = new ClientRepository();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
        <style>
            .client-info {
                margin-bottom: 10px;
            }
            .client-info span {
                font-weight: bold;
            }
        </style>
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
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-valign-middle">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ФИО</th>
                                        <th>Номер телефона</th>
                                        <th>Email</th>
                                        <th>Способ оплаты</th>
                                        <th>Дата возврата</th>
                                        <th>Дата билета</th>
                                        <th>Причина возврата</th>
                                        <th>Маршрут</th>
                                        <th style="text-align:center;">Действия</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($page - 1) * $_params['num_page'];

                                    // Получаем возвращенные билеты через репозиторий
                                    $getOrders = $clientRepository->getClientsWithReturnedTickets($Admin->lang, $offset, $_params['num_page']);

                                    // Получаем общее количество для пагинации
                                    $total = $clientRepository->countReturnedTickets();
                                    $totalPages = ceil($total / $_params['num_page']);

                                    foreach ($getOrders AS $order) {
                                        $fullName = $order->client_name . ' ' . $order->client_surname;

                                        // Определяем способ оплаты
                                        $paymentType = '';
                                        $paymentBadge = '';
                                        if ($order->payment_status == 1) {
                                            $paymentType = 'Наличными';
                                            $paymentBadge = 'badge-warning';
                                        } elseif ($order->payment_status == 2) {
                                            $paymentType = 'Онлайн';
                                            $paymentBadge = 'badge-success';
                                        }
                                    ?>
                                        <tr>
                                            <td><?=$order->id?></td>
                                            <td><?=$fullName?></td>
                                            <td><?=$order->client_phone?></td>
                                            <td><?=$order->client_email?></td>
                                            <td><span class="badge <?=$paymentBadge?>"><?=$paymentType?></span></td>
                                            <td><?=date('d.m.Y H:i', strtotime($order->return_date))?></td>
                                            <td><?=date('d.m.Y', strtotime($order->tour_date))?></td>
                                            <td><?=$order->return_reason_title ?: 'Не указана'?></td>
                                            <td><?=$order->departure_city . ' - ' . $order->arrival_city?></td>
                                            <td align="center">
                                                <div class="btn-group">
                                                    <!--<button type="button" class="btn btn-sm btn-warning" onclick="sendReturnNotification(<?php /*=$order->id*/?>)" title="Отправить уведомление о возврате">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>-->
                                                    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') . '/edit.php' ?>?id=<?=$order->id?>" class="btn btn-sm btn-default" title="Редактировать">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') . '/view.php' ?>?id=<?=$order->id?>" class="btn btn-sm btn-info" title="Подробности">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPages > 1) { ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                            <a class="page-link" href="?page=<?=$i?>"><?=$i?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </nav>
                            <?php } ?>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>
            <!-- /.content -->
        </div>
    </div>
    <!-- ./wrapper -->
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>

    <script>
    function sendReturnNotification(orderId) {
        if (!confirm('Отправить уведомление о возврате клиенту?')) {
            return;
        }

        initLoader();
        $.ajax({
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'process_return',
                'order_id': orderId,
                'reason': 'Отправка уведомления о возврате'
            },
            dataType: 'json',
            success: function(response) {
                removeLoader();
                if (response.success) {
                    alert('Уведомление о возврате отправлено!');
                } else {
                    alert('Ошибка при отправке уведомления: ' + (response.message || 'Неизвестная ошибка'));
                }
            },
            error: function() {
                removeLoader();
                alert('Произошла ошибка при отправке запроса');
            }
        });
    }
    </script>
    </body>
    </html>
<?php } ?>
