<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
$racesDate = "today";
if ($Admin->CheckPermission($_params['access'])) {
    if (!isset($salesDate)) {
        $salesDate = date('Y-m-d');
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
                <button class="btn btn-success" title="Активировать" type="button" onclick="continue_date() ">
                    <i class="fas fa-check"></i> Продлить поездки
                </button>
            </br>
                Продлить согласно глубины продаж из параметров маршрутов
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?=$_params['title']?></h1>
                        </div>
                    </div>
                    <div class="col-lg-20 col-sm-6 col-xs-12">
                        <div class="filter_block_wrapper">
                            <div class="filter_date_wrapper">
                                <div class="filter_date_title par">На дату:</div>
                                <input type="text" class="filter_date" name="date">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="table-responsive table-striped table-valign-middle races_shdeule">
                        <table class="table m-0">
                            <thead>
                            <tr>
                                <!--th>ID маршрута</th-->
                                <th>Маршрут</th>
                                <th>Автобус</th>
                                <th>Куплено билетов</th>
                                <th>Забронировано билетов</th>
                                <th>Свободных мест</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $getTableElems = $Db->getAll("SELECT * FROM ".$_params['table']." WHERE tour_date = '" .$salesDate. "'  GROUP BY tour_id,tour_date ORDER BY tour_date DESC");
                            foreach ($getTableElems AS $k=>$Elem) {

                                $mainInfo = $Db->getOne("SELECT
                                    departure_city.title_" . $Admin->lang . " AS departure_city,
                                    arrival_city.title_" . $Admin->lang . " AS arrival_city,
                                    b.title_".$Admin->lang." AS bus
                                    FROM `" . DB_PREFIX . "_tours` t
                                    LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = t.departure
                                    LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = t.arrival
                                    LEFT JOIN `".DB_PREFIX."_buses` b ON b.id = t.bus
                                    WHERE t.id = '".$Elem['tour_id']."'");
                                $ticketsBuy = $Db->getOne("SELECT tickets_buy FROM ".$_params['table']." WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."' ");
                                $ticketsOrder = $Db->getOne("SELECT tickets_order FROM ".$_params['table']." WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."' ");
                                $free_tickets = $Db->getOne("SELECT free_tickets FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."'");
                                $departureTimeQuery = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$Elem['tour_id']."' ORDER BY id ASC");
                                $departureTime = substr($departureTimeQuery['departure_time'], 0, 5);

                                ?>
                                <tr <?if ($Elem['active'] == '0'){?>
                                  class="disabled"
                                <?}?>>
                                    <!--td><?=$Elem['tour_id']?></td-->
                                    <td>
                                        <b><?=date('Y.m.d',strtotime($Elem['tour_date']))?></b>
                                        <div>
                                            <?=$mainInfo['departure_city'].' - '.$mainInfo['arrival_city']?>
                                        </div>
                                        <div><?=$departureTime ?></div>
                                    </td>
                                    <td><?=$mainInfo['bus']?></td>
                                    <td><?=$ticketsBuy['tickets_buy']?></td>
                                    <td><?=$ticketsOrder['tickets_order']?></td>
                                    <td class="free_tickets_td"><?=$free_tickets['free_tickets']?>
                                        <button class="btn btn-default" title="Редактировать" type="button" onclick="editFreeTickets(this,'<?=$Elem['tour_id']?> ','<?=$Elem['tour_date']?>') ">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <? if ($ticketsBuy['tickets_buy'] > 0 || $ticketsOrder['tickets_order'] > 0) { ?>
                                                <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/pdf.php'?> ?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="скачать ведомость">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            <? } ?>
                                            <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/edit.php' ?>?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="Посмотреть детали">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                            <? if ($Elem['active'] == '0') {?>
                                                <button class="btn btn-success" title="Активировать" type="button" onclick="setActive(this,'<?=$Elem['id']?> ') ">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?} else { ?>
                                                <button class="btn btn-danger" title="Дезактивировать" type="button" onclick="setInactive(this,'<?=$Elem['id']?> ') ">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <? } ?>

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/<?=$Admin->lang?>.js"></script>

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
                removeLoader();
                if ($.trim(response) !== 'err') {
                    $(item).closest('td').html(response);
                } else {
                    alert('Ошибка!');
                }
            }
        });
    }

    function setActive(item, id) {
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'setActive_race',
                'id': id,
            },
            success: function(response) {
                console.log(response)
                removeLoader();
                if ($.trim(response) !== 'err') {
                    $(item).closest('tr').removeClass('disabled');
                    $(item).closest('div.btn-group').find('button').replaceWith(response);
                } else {
                    alert('Ошибка!');
                }
            }
        });
    }
    function setInactive(item, id) {
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'setInactive_race',
                'id': id,
            },
            success: function(response) {
                console.log(response)
                removeLoader();
                if ($.trim(response) !== 'err') {
                    $(item).closest('tr').addClass('disabled');
                    $(item).closest('div.btn-group').find('button').replaceWith(response);
                } else {
                    alert('Ошибка!');
                }
            }
        });
    }

    function toggleFilterCalendar() {
        const filterInput = document.querySelector(".filter_date");
        let filterDatePicker;
        filterDatePicker = flatpickr(filterInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            defaultDate: "<?=$racesDate?>",
            locale: '<?=$Router->lang?>',
            static: true,
            onChange: function filterRaces(selectedDates, dateStr, instance) {
                initLoader();
                    $.ajax({
                        type:'post',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
                        data: {
                            'request': 'filterRaces',
                            'date' : dateStr
                        },
                        success: function (response) {
                            console.log(dateStr)
                            document.querySelector(".races_shdeule").innerHTML = response;
                        }
                    });
                removeLoader();
            }
        });
    }

    function continue_date() {
        initLoader();
        $.ajax({
            type:'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data: {
                'request': 'continue',
            },
            success: function(response) {
                removeLoader();
            }
        });
    }


    document.addEventListener('DOMContentLoaded',toggleFilterCalendar);
</script>
