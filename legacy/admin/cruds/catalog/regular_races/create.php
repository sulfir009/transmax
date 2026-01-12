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

        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            $travelHours = (int)$ar_clean['hours'];
            $travelMinutes = (int)$ar_clean['minutes'];
            $departureTimestamp = strtotime($ar_clean['departure_time']);
            $travelTimeSeconds = ($travelHours * 3600) + ($travelMinutes * 60);
            $arrivalTimestamp = $departureTimestamp + $travelTimeSeconds;
            $arrivalTime = date("H:i", $arrivalTimestamp);

            $days = implode(',',$ar_clean['days']);

            addElement($_params['table'], array(),$txt?? [],array('days'=>$days),$exceptions?? []);
            $id = mysqli_insert_id($db);
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}
        ?>

        <?php
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
        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_1" role="tab" aria-controls="tab_1" aria-selected="true">Создание "Регулярного рейса"</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <div class="form-group">
                                <label for="images_mob">Баннер для моб</label>
                                <input type="file" id="images_mob" name="images_mob" multiple class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <label for="images_desc">Баннер для комп</label>
                                <input type="file" id="images_desc" name="images_desc" multiple class="form-control" accept="image/*" required>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название Ru
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input minlength="3" data-title-ru placeholder="Введите название на русском" type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название Ua
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input minlength="3" data-title-ua placeholder="Введите название на украинском" type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Название En
                                </label>
                                <div class="col-sm-3">
                                    <div style="border: #fff solid 1px; text-align: center">
                                        <input minlength="3" data-title-en placeholder="Введите название на английском" type="text" class="filter_date" name="title_ru" style="width: 100%; background-color:#343a40; color: white;" value="">
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
                                            required data-tour-selected="">
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
                        <input type="submit" data-regular-race-id class="btn btn-success btn-lg" value="Создать" name="ok"/>
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
        $('.js-multiple-tour').select2({
            placeholder: "Выберите рейс",
            allowClear: true
        });
    });

    $('[data-regular-race-id]').click(function (e) {
        e.preventDefault();
        let tours =  $('.js-multiple-tour').val();
        let titleRu = $('[data-title-ru]').val();
        let titleUa = $('[data-title-ua]').val();
        let titleEn = $('[data-title-en]').val();
        let regularTourId = $(this).data('regular-race-id');
        let image_mob = $('#images_mob')[0].files[0];
        let image_desc = $('#images_desc')[0].files[0];

        let formData = new FormData();
        formData.append('request', 'create_regular_race');
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
                alert("Успешно создан Регуляный рейс");
                window.location.href = "/<?php echo ADMIN_PANEL?>/cruds/catalog/regular_races";
            }
        })

    });

    $('.txt_editor').summernote();
</script>
</body>
</html>
