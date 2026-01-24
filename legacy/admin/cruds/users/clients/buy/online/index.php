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
                                        <th>Дата заказа</th>
                                        <th>Маршрут</th>
                                        <th style="text-align:center;">Действия</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                    $offset = ($page - 1) * $_params['num_page'];

                                    // Получаем заказы с онлайн оплатой через репозиторий
                                    $getOrders = $clientRepository->getClientsWithOnlinePayment($Admin->lang, $offset, $_params['num_page']);
                                    // Получаем общее количество для пагинации
                                    $total = $clientRepository->countOrdersByPaymentType(2, false);
                                    $totalPages = ceil($total / $_params['num_page']);
                                    foreach ($getOrders AS $order) {
                                        $fullName = $order->second_name . ' ' . $order->name . ' ' . $order->patronymic;
                                    ?>
                                        <tr>
                                            <td><?=$order->id?></td>
                                            <td><?=$fullName?></td>
                                            <td><?=$order->phone?></td>
                                            <td><?=$order->email?></td>
                                            <td><span class="badge badge-success">Онлайн</span></td>
                                            <td><?=date('d.m.Y H:i', strtotime($order->date))?></td>
                                            <td><?=$order->departure_city . ' - ' . $order->arrival_city?></td>
                                            <td align="center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="sendTicket(<?=$order->id?>)">
                                                        <i class="fas fa-envelope"></i> Отправить билет
                                                    </button>
                                                    <a href="edit.php?id=<?=$order->client_id?>" class="btn btn-sm btn-default" title="Редактировать">
                                                        <i class="fas fa-pencil-alt"></i>
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
    function sendTicket(orderId) {
        if (!confirm('Отправить билет клиенту?')) {
            return;
        }

        initLoader();
        $.ajax({
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'send_ticket',
                'order_id': orderId
            },
            success: function(response) {
                removeLoader();
                if (response.success) {
                    alert('Билет успешно отправлен!');
                } else {
                    alert('Ошибка при отправке билета: ' + (response.message || 'Неизвестная ошибка'));
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
