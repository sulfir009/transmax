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
                        <div class="col-sm-6">
                            <a class="btn btn-success float-right" role="button" href="<?= $_SERVER['REQUEST_URI'] . '/create.php' ?>">
                                <i class="fas fa-plus"></i>
                                Создать "Регулярный рейс"
                            </a>
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
                                <th>ID</th>
                                <th>Название RU</th>
                                <th>Название UA</th>
                                <th>Название EN</th>
                                <th>Моб баннер</th>
                                <th>Деск баннер</th>
                                <th style="text-align:center;">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $getTableElems = mysqli_query($db, "
                                SELECT rra.id as id,
                                       rra.image_mob as image_mob,
                                       rra.image_desc as image_desc,
                                       rra.title_ru as title_ru,
                                       rra.title_ua as title_ua,
                                       rra.title_en as title_en,
                                       cityDeparte.title_ru as departure,
                                       cityArrive.title_ru as arrive,
                                       ts.departure_time as departure_time,
                                       rra.active as active
                                FROM `mt_regular_race_alias` as rra
                                left join `mt_regular_races` as rr on rr.regular_race_alias_id = rra.id
                                left join `mt_tours` as tours on tours.id = rr.tour_id
                                left join `mt_tours_stops` as ts on rr.tours_stop_id = ts.id
                                left join `mt_cities` as cityDeparte on cityDeparte.id = tours.departure
                                left join `mt_cities` as cityArrive on cityArrive.id = tours.arrival
                                GROUP BY rra.id DESC");
                            while ($Elem = mysqli_fetch_assoc($getTableElems)) {

                                ?>
                                <tr>
                                    <td><?=$Elem['id']?></td>
                                    <td><?=$Elem['title_ru']?></td>
                                    <td><?=$Elem['title_ua']?></td>
                                    <td><?=$Elem['title_en']?></td>
                                    <td>
                                        <?php if(!empty($Elem['image_mob'])): ?>
                                            <img src="<?= asset('images/pages/regular_races/' . $Elem['image_mob']) ?>" style="max-width: 50px;">
                                        <?php endif;?>
                                    </td>
                                    <td>
                                        <?php if(!empty($Elem['image_desc'])): ?>
                                            <img src="<?= asset('images/pages/regular_races/' . $Elem['image_desc']) ?>" style="max-width: 50px;">
                                        <?php endif;?>
                                    </td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <a data-active="<?= $Elem['active']?>" onclick="refresh_elem(<?= $Elem['id'] ?>, '<?= "mt_regular_race_alias" ?>')"
                                               class="btn btn-success" title="Активировать/дезактивировать">
                                                <i <?if ($Elem['active'] == '0'){?>
                                                    class="fas fa-toggle-off"
                                                <?} else { ?> class="fas fa-toggle-on" <?} ?>></i>
                                            </a>
                                            <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/edit.php' ?>?id=<?= $Elem['id'] ?>"
                                               class="btn btn-default"
                                               title="Редактировать"><i class="fas fa-pencil-alt"></i></a>
                                            <a onclick="return confirm('<?= "Вы уверены что хотите удалить ?" ?>')"
                                               href="<?= $_SERVER['REQUEST_URI'] . '/delete.php' ?>?id=<?= $Elem['id'] ?>"
                                               class="btn btn-danger"
                                               title="Удалить"><i class="fas fa-times"></i></a>
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
