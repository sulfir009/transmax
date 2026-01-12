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
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->
        <?php $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS); ?>
        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $active = checkboxParam('active');
dd(request()->file('image'));
            $travelHours = (int)$ar_clean['hours'];
            $travelMinutes = (int)$ar_clean['minutes'];
            $departureTimestamp = strtotime($ar_clean['departure_time']);
            $travelTimeSeconds = ($travelHours * 3600) + ($travelMinutes * 60);
            $arrivalTimestamp = $departureTimestamp + $travelTimeSeconds;
            $arrivalTime = date("H:i", $arrivalTimestamp);

            $exceptions[] = 'days';
            $exceptions[] = 'stops';
            $exceptions[] = 'departure_closed';
            $exceptions[] = 'stops_closed';

            $departureClose = date("H:i:s", strtotime($ar_clean['departure_closed']));
            $stopsClose = date("H:i:s", strtotime($ar_clean['stops_closed']));
            $racesFutureDate = (int)$ar_clean['races_future_date'];

            $days = implode(',', $ar_clean['days']);

            /* Обновим данные об остановках и их стоимости */
            $del = mysqli_query($db, "DELETE FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE tour_id = '" . $id . "' ");
            foreach ($ar_clean['stops'] as $stopKey => $stopValues) {
                if ((int)$stopValues['price'] > 0) {
                    $stopFrom = explode('-', $stopKey)[1];
                    $stopTo = explode('-', $stopKey)[2];
                    $addInfo = mysqli_query($db, "INSERT INTO `" .  DB_PREFIX . "_tours_stops_prices`(`tour_id`,`from_stop`,`to_stop`,`price`,`distance`)
                VALUES ('" . $id . "','" . $stopFrom . "','" . $stopTo . "','" . (int)$stopValues['price'] . "','" . (int)$stopValues['distance'] . "')");
                }
            }

            updateElement($id, $_params['table'], array('active' => $active, 'departure_closed' => $departureClose, 'stops_closed' => $stopsClose, 'races_future_date' => $racesFutureDate), $txt?? [], array('days' => $days), $exceptions ?? []);
        } elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])) { ?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
            <?
        }
        $tours = mysqli_query($db, "
            SELECT tours.id as id,
                cityDeparte.title_ru as departe,
                cityArrive.title_ru as arrive,
                    (SELECT tsid.id
                    FROM `mt_tours_stops` as tsid
                    WHERE tsid.tour_id = tours.id
                    ORDER BY stop_num ASC limit 1) as toursStopsId,
                (SELECT ts.departure_time
                    FROM `mt_tours_stops` as ts
                    WHERE ts.tour_id = tours.id
                    ORDER BY ts.stop_num ASC limit 1) as depTime
            FROM `mt_tours` as tours
            left join `mt_cities` as cityDeparte on cityDeparte.id = tours.departure
            left join `mt_cities` as cityArrive on cityArrive.id = tours.arrival
        ");
        $regularTourAlias = mysqli_query($db, "
                                SELECT rra.id as id,
                                       rra.image_mob as image_mob,
                                       rra.image_desc as image_desc,
                                       rra.title_ru as title_ru,
                                       rra.title_ua as title_ua,
                                       rra.title_en as title_en
                                FROM `mt_regular_race_alias` as rra
                                WHERE rra.id = '" . $id . "'
        ");

        $tourIds = mysqli_query($db, "
                                SELECT rr.tour_id
                                FROM `mt_regular_races` as rr
                                WHERE rr.regular_race_alias_id = '" . $id . "'
        ");

        $regularTour = mysqli_query($db, "
                                SELECT rra.id as id,
                                       rr.tour_id as tourId,
                                       cityDeparte.title_ru as departure,
                                       cityArrive.title_ru as arrive,
                                       ts.departure_time as departure_time
                                FROM `mt_regular_race_alias` as rra
                                left join `mt_regular_races` as rr on rr.regular_race_alias_id = rra.id
                                left join `mt_tours` as tours on tours.id = rr.tour_id
                                left join `mt_tours_stops` as ts on rr.tours_stop_id = ts.id
                                left join `mt_cities` as cityDeparte on cityDeparte.id = tours.departure
                                left join `mt_cities` as cityArrive on cityArrive.id = tours.arrival
        ");

        $db_element = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id='" . $id . "'");
        $Elem = mysqli_fetch_array($db_element);
        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_1" role="tab" aria-controls="tab_1" aria-selected="true">Редактирование "Регулярного рейса"</a>
                        </li>
                    </ul>
                    <?php
                    foreach ($regularTourAlias as $regularTour) {
                        $regularRaceId = $regularTour['id'];
                        $titleRu = $regularTour['title_ru'];
                        $titleUa = $regularTour['title_ua'];
                        $titleEn = $regularTour['title_en'];
                        $imageDesc = $regularTour['image_desc'];
                        $imageMob = $regularTour['image_mob'];
                    }
                    ?>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <div class="form-group">
                                <label for="images_mob">Баннер для моб</label>
                                <input type="file" id="images_mob" name="images_mob" multiple class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <img src="<?= asset('images/pages/regular_races/' . $imageMob) ?>" style="max-width: 150px">
                            </div>
                            <div class="form-group">
                                <label for="images_desc">Баннер для комп</label>
                                <input type="file" id="images_desc" name="images_desc" multiple class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <img src="<?= asset('images/pages/regular_races/' . $imageDesc) ?>" style="max-width: 150px">
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название Ru
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input data-title-ru type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="<?= $titleRu; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название Ua
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input data-title-ua type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="<?= $titleUa; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название En
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input data-title-en type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="<?= $titleEn; ?>">
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Направления
                                </label>
                                <div class="col-sm-3">
                                    <?php
                                    $clearData = [];
                                    foreach ($tourIds as $t) {
                                        $arrayTourIds[] = $t['tour_id'];
                                    }

                                    ?>
                                    <select class="custom-select js-multiple-tour" name="tours[]" multiple="multiple"
                                            required data-tour-selected="<?=implode(',', $arrayTourIds); ?>">
                                        <?php
                                        foreach ($tours as $tour) {
                                            ?>
                                            <option data-tour-id="<?= $tour['id'] ?>" data-tours-stops-id="<?= $tour["toursStopsId"]; ?>" value="<?= $tour['id'] ?>">
                                                <?= $tour['departe'] . "-" . $tour["arrive"] . "[" . $tour["depTime"] . "]" ?>
                                            </option>
                                        <? } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" style="text-align: center">
                        <input type="submit" data-regular-race-id="<?= $regularRaceId ?>" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
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

    $(document).ready(function () {
        let values = JSON.parse("[" + $('.js-multiple-tour').data('tour-selected') + "]");
        $('.js-multiple-tour').val(values);
        $('.js-multiple-tour').select2({
            placeholder: "Выберите рейс",
            allowClear: true
        });
    });



    $(document).ready(function() {
        $('[data-regular-race-id]').click(function (e) {
            e.preventDefault();
            let tours =  $('.js-multiple-tour').val();
            let regularTourId = $(this).data('regular-race-id');
            let titleRu = $('[data-title-ru]').val();
            let titleUa = $('[data-title-ua]').val();
            let titleEn = $('[data-title-en]').val();
            let image_mob = $('#images_mob')[0].files[0];
            let image_desc = $('#images_desc')[0].files[0];

            let formData = new FormData();
            formData.append('request', 'edit_regular_race');
            formData.append('tours', tours);
            formData.append('regularTourId', regularTourId);
            formData.append('titleRu', titleRu);
            formData.append('titleUa', titleUa);
            formData.append('titleEn', titleEn);
            formData.append('image_mob', image_mob);
            formData.append('image_desc', image_desc);


            $.ajax({
                type:'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
                data:formData,
                processData: false,
                contentType: false,
                success:function(responce){
                    alert("Изминения успешно сохранены");
                    window.location.reload();
                },
                error: function(xhr) {
                    alert("Произошла ошибка");
                }
            })

        });
    });



</script>
</body>
</html>
