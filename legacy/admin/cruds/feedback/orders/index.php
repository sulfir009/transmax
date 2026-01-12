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
                                <th>ID маршрута</th>
                                <th>Маршрут</th>
                                <th>Автобус</th>
                                <th>Куплено билетов</th>
                                <th>Свободных мест</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $getTableElems = $Db->getAll("SELECT * FROM ".$_params['table']." GROUP BY tour_id,tour_date ORDER BY tour_date DESC");
                            foreach ($getTableElems AS $k=>$Elem) {
                                $mainInfo = $Db->getOne("SELECT
                                    departure_city.title_" . $Admin->lang . " AS departure_city,
                                    arrival_city.title_" . $Admin->lang . " AS arrival_city,
                                    b.title_".$Admin->lang." AS bus,
                                    b.seats_qty AS bus_seats
                                    FROM `" . DB_PREFIX . "_tours` t
                                    LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = t.departure
                                    LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = t.arrival
                                    LEFT JOIN `".DB_PREFIX."_buses` b ON b.id = t.bus
                                    WHERE t.id = '".$Elem['tour_id']."' ");
                                $ticketsBuy = $Db->getOne("SELECT COUNT(id) AS qty FROM ".$_params['table']." WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."' ");
                                $free_tickets = $Db->getOne("SELECT free_tickets FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."'");
                                $departureTimeQuery = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$Elem['tour_id']."' ORDER BY id ASC");
                                $departureTime = substr($departureTimeQuery['departure_time'], 0, 5);
                            ?>
                                <tr <?if ($Elem['active'] == '0'){?>
                                    class="disabled"
                                <?}?>>
                                    <td><?=$Elem['tour_id']?></td>
                                    <td>
                                        <b><?=date('Y.m.d',strtotime($Elem['tour_date']))?></b>
                                        <div>
                                            <?=$mainInfo['departure_city'].' - '.$mainInfo['arrival_city']?>

                                        </div>
                                        <div><?=$departureTime ?></div>
                                    </td>
                                    <td><?=$mainInfo['bus']?></td>
                                    <td><?=$ticketsBuy['qty']?></td>
                                    <td class="free_tickets_td"><?=$free_tickets['free_tickets']?>
                                        <button class="btn btn-default" title="Редактировать" type="button" onclick="editFreeTickets(this,'<?=$Elem['tour_id']?> ','<?=$Elem['tour_date']?>') ">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <a href="orders/pdf.php?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="Посмотреть детали">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <a href="<?= $_SERVER['REQUEST_URI'] . '/edit.php' ?>?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="Посмотреть детали">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
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
<script>
    function editFreeTickets(item,id,date){
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'edit_sales',
                'id': id,
                'date': date
            },
            success: function(response) {
                console.log(response)
                removeLoader();
                if ($.trim(response) !== 'err') {
                    $(item).closest('td').html(response);
                } else {
                    alert('Ошибка!');
                }
            }
        });
    }
    function acceptFreeTickets(item,id,date){
        let free_tickets_value =  $(item).closest('td').find('.edit_tickets_num').val();
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'accept_sales_changes',
                'id': id,
                'date': date,
                'tickets' : free_tickets_value
            },
            success: function(response) {
                console.log(response)
                removeLoader();
                if ($.trim(response) !== 'err') {
                    $(item).closest('td').html(response);
                } else {
                    alert('Ошибка!');
                }
            }
        });
    }
</script>
