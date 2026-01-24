<div class="content">
    <div class="main_filter_wrapper">
        <div class="container">
            <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/filter.php' ?>
        </div>
    </div>
    <div class="page_content_wrapper">
        <div class="shedule_block">
            <? $filterParams = $joinParam = '';
            if (isset($_GET['departure']) && isset($_GET['arrival'])) {
                $filterParams .= " AND t.departure = " . (int)$_GET['departure'] . " AND t.arrival = " . (int)$_GET['arrival'];
            }
            if (isset($_GET['country'])) {
                $filterParams .= " AND (dc.section_id = " . (int)$_GET['country'] . " OR ac.section_id = " . (int)$_GET['country'] . " )";
                $country = $Db->getOne("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_cities`WHERE id = '" . (int)$_GET['country'] . "' ");
            }
            if (isset($_GET['city'])) {
                $city = (int)$_GET['city'];
                $filterParams .= " AND (t.departure = " . (int)$_GET['city'] . " OR t.id
                    IN(SELECT tour_id FROM `" . DB_PREFIX . "_tours_stops_prices`WHERE from_stop
                    IN(SELECT id FROM `" . DB_PREFIX . "_cities`WHERE section_id = '" . (int)$_GET['city'] . "' ) ))
                    OR (t.arrival = " . (int)$_GET['city'] . " OR t.id
                    IN(SELECT tour_id FROM `" . DB_PREFIX . "_tours_stops_prices`WHERE to_stop
                    IN(SELECT id FROM `" . DB_PREFIX . "_cities`WHERE section_id = '" . (int)$_GET['city'] . "' ) ))
                    AND (tsp.from_stop IN(SELECT id FROM `" . DB_PREFIX . "_cities`WHERE section_id = '" . $city . "' ) OR tsp.to_stop IN(SELECT id FROM `" . DB_PREFIX . "_cities`WHERE section_id = '" . $city . "' ))";
            }
            $pagination = pagination("SELECT COUNT(DISTINCT(t.id)) AS qty
                FROM `" . DB_PREFIX . "_tours` t
                LEFT JOIN `" . DB_PREFIX . "_cities`dc ON dc.id = t.departure
                LEFT JOIN `" . DB_PREFIX . "_cities`ac ON ac.id = t.arrival
                LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
                WHERE t.active = '1' $filterParams", 16);

            $getRoutes = $Db->getAll("SELECT DISTINCT(t.id),t.departure,t.arrival,t.days,
                dc.title_" . $Router->lang . " AS departure_city,
                dc.section_id AS departure_city_section_id,
                dcountry.title_" . $Router->lang . " AS departure_country,
                ac.title_" . $Router->lang . " AS arrival_city,
                ac.section_id AS arrival_city_section_id,
                b.title_" . $Router->lang . " AS bus_title
                FROM `" . DB_PREFIX . "_tours` t
                LEFT JOIN `" . DB_PREFIX . "_cities`dc ON dc.id = t.departure
                LEFT JOIN `" . DB_PREFIX . "_cities`ac ON ac.id = t.arrival
                LEFT JOIN `" . DB_PREFIX . "_cities`dcountry ON dcountry.id = dc.section_id
                LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
                WHERE t.active = '1' $filterParams
                ORDER BY
    CASE
    WHEN dc.section_id = 13 THEN ac.section_id
    ELSE dc.section_id
    END ASC,tsp.price DESC LIMIT " . $pagination['from'] . "," . $pagination['per_page']);
            ?>
            <div class="shedule_table_container">
                <div class="shedule_title h2_title">
                    <? if (!$filterParams) {
                        echo route('schedule');
                    } elseif ($filterParams && isset($_GET['country'])) {
                        echo __('dictionary.MSG_MSG_SCHEDULE_ROZKLAD_NAPRAVLENNYA') . ' ' . $country['title'];
                    } elseif ($filterParams && isset($_GET['departure']) && isset($_GET['arrival'])) {
                        echo __('dictionary.MSG_MSG_SCHEDULE_ROZKLAD_NAPRAVLENNYA') . ' ' . $getRoutes[0]['departure_city_title'] . ' - ' . $getRoutes[0]['arrival_city_title'];
                    } ?>
                </div>
                <div class="shedule_table_wrapper">
                    <table class="shedule_table">
                        <thead class="shedule_th">
                        <tr>
                            <th>
                                <?= __('dictionary.MSG_MSG_SCHEDULE_KRANA') ?>
                            </th>
                            <th><?= __('dictionary.MSG_MSG_SCHEDULE_REJS') ?></th>
                            <th><?= __('dictionary.MSG_MSG_SCHEDULE_MARSHRUT')?></th>
                            <th><?= __('dictionary.MSG_MSG_SCHEDULE_VARTISTI') ?></th>
                            <th><?= __('dictionary.MSG_MSG_SCHEDULE_POSILANNYA_NA_BRONYUVANNYA') ?></th>
                        </tr>
                        </thead>
                        <tbody class="shedule_tbody">
                        <? $routesArray = [];
                        $ukraineRoutes = [];

                        foreach ($getRoutes as $route) {
                            if ($route['departure_city_section_id'] == 13) {
                                // Маршруты из Украины
                                $ukraineRoutes[$route['arrival_city_section_id']][] = $route;
                            } else {
                                // Маршруты из других стран
                                $routesArray[$route['departure_city_section_id']][] = $route;
                            }
                        }

                        // Добавляем маршруты из Украины к соответствующим секциям
                        foreach ($ukraineRoutes as $arrivalCitySectionId => $routes) {
                            if (isset($routesArray[$arrivalCitySectionId])) {
                                foreach ($routes as $route) {
                                    $routesArray[$arrivalCitySectionId][] = $route;
                                }
                            } else {
                                // Если нет маршрутов для этой секции, создаем новый массив
                                $routesArray[$arrivalCitySectionId] = $routes;
                            }
                        }

                        foreach ($routesArray as $departureCitySectionId => $routes) {
                            foreach ($routes as $k => $route) {
                                $routeArrival = $route['arrival'];
                                $routeDeparture = $route['departure'];

                                $departureDetails = $Db->getOne("SELECT station.id, station.title_" . $Router->lang . " AS station, city.title_" . $Router->lang . " AS city, stop.departure_time
                FROM `" . DB_PREFIX . "_cities`station
                LEFT JOIN `" . DB_PREFIX . "_cities`city ON city.id = station.section_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops`stop ON stop.stop_id = station.id AND stop.tour_id = '" . $route['id'] . "'
                WHERE station.station = 1 AND station.section_id = '" . $route['departure'] . "' AND station.id IN (SELECT stop_id FROM `" . DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $route['id'] . "')");

                                $arrivalDetails = $Db->getOne("SELECT station.id, station.title_" . $Router->lang . " AS station, city.title_" . $Router->lang . " AS city, stop.arrival_time
                FROM `" . DB_PREFIX . "_cities`station
                LEFT JOIN `" . DB_PREFIX . "_cities`city ON city.id = station.section_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops`stop ON stop.stop_id = station.id AND stop.tour_id = '" . $route['id'] . "'
                WHERE station.station = 1 AND station.section_id = '" . $route['arrival'] . "' AND station.id IN (SELECT stop_id FROM `" . DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $route['id'] . "')");

                                $getTicketStops = $Db->getAll("SELECT stop_id, arrival_time, departure_time, arrival_day FROM `" . DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $route['id'] . "' ORDER BY id ASC ");
                                $lastStop = end($getTicketStops);
                                $arrival_day = $lastStop['arrival_day'];
                                $rideTime = calculateTotalTravelTime($getTicketStops, $departureDetails['id'], $arrivalDetails['id'], $arrival_day);
                                $international = ($route['departure_city_section_id'] != $route['arrival_city_section_id']);
                                $ticketPrice = $Db->getOne("SELECT price FROM `" . DB_PREFIX . "_tours_stops_prices`WHERE from_stop = '" . $departureDetails['id'] . "' AND to_stop = '" . $arrivalDetails['id'] . "' AND tour_id = '" . $route['id'] . "' ");
                                $nearestDepartureDate = findNearestDayOfWeek(date('Y-m-d', time()), explode(',', $route['days']));
                                $departureDateTime = date('Y-m-d H:i:s', strtotime($nearestDepartureDate . ' ' . $departureDetails['departure_time']));
                                $hours = explode(':', $rideTime)[0];
                                $minutes = explode(':', $rideTime)[1];
                                $arrivalDateTime = calculateArrivalDateTime($departureDateTime, $hours, $minutes);

                                ?>
                                <tr class="shedule_tr">
                                    <?php if ($k == 0) { ?>
                                        <td class="shedule_td manrope" rowspan="<?= count($routes) ?>">
                                            <?= $route['departure_country'] ?>
                                        </td>
                                    <?php } ?>
                                    <td class="shedule_td manrope"><?= $route['departure_city'] . ' - ' . $route['arrival_city'] ?></td>
                                    <td class="shedule_td">
                                        <button class="schedule_details_btn"
                                                onclick="toggleRouteDetailsSchedule('<?= $route['id'] ?>', '<?= $departureDetails['id'] ?>', '<?= $arrivalDetails['id'] ?>')">
                                            <?= __('dictionary.MSG_MSG_SCHEDULE_GRAFIK_I_RASPISANIE_REJSA') ?>
                                        </button>
                                    </td>
                                    <td class="shedule_td h4_title">
                                        <button class="info_btn">
                                            <img src="<?= asset('images/legacy/common/info.svg'); ?>" alt="info">
                                        </button>
                                        <button class="schedule_details_btn"
                                                onclick="toggleRoutePricesSchedule('<?= $route['id'] ?>', '<?= $departureDetails['id'] ?>', '<?= $arrivalDetails['id'] ?>', '<?= $nearestDepartureDate ?>')">
                                            <?=__('dictionary.MSG_MSG_SCHEDULE_PRICE_TABLE') ?>
                                        </button>
                                    </td>
                                    <td class="shedule_td">
                                        <button class="buy_btn h5_title"
                                                onclick="buyTicketFromSchedule(this, '<?= $route['id'] ?>', '<?= $departureDetails['id'] ?>', '<?= $arrivalDetails['id'] ?>', '<?= $nearestDepartureDate ?>')">
                                            <?= __('dictionary.MSG_MSG_SCHEDULE_KUPITI_KVITOK') ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="shedule_table_pagination_wrapper">
                    <?= paginatePublic($pagination) ?>
                </div>
            </div>
        </div>
        <div class="routes_block">
            <div class="container">
                <div class="routes_title h2_title">
                    <?= __('dictionary.MSG__NASHI_NAPRAVLENNYA') ?>
                </div>
                <div class="routes_subtitle par">
                    <?= __('dictionary.MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI') ?>
                </div>
                <div class="routes_lists_wrapper">
                    <div class="route_list_block">
                        <div class="route_list_title h3_title"><?= __('dictionary.MSG_ALL_KRANI') ?></div>
                        <div class="route_list">
                            <? $getCountries = $Db->getall("SELECT id,title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_cities` WHERE active = '1' AND section_id = '0' AND show_home = '1' ORDER BY sort DESC");
                            foreach ($getCountries as $k => $country) {
                                ?>
                                <div>
                                    <a href="<?= route('schedule') ?>?country=<?= $country['id'] ?>"
                                       class="shedule_link"><?= $country['title'] ?></a>
                                </div>
                            <? } ?>
                        </div>
                    </div>
                    <div class="route_list_block">
                        <div class="route_list_title h3_title"><?= $GLOBALS['dictionary']['MSG_ALL_ROZKLAD'] ?></div>
                        <div class="route_list">
                            <? $getCities = $Db->getAll("SELECT id,title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_cities` WHERE active = '1' AND section_id != 0 AND section_id != '175' AND station = '0' ORDER BY sort DESC LIMIT 10");
                            foreach ($getCities as $k => $city) {
                                ?>
                                <div>
                                    <a href="<?= route('schedule') ?>?city=<?= $city['id'] ?>"
                                       class="shedule_link"><?= $city['title'] ?></a>
                                </div>
                            <? } ?>
                        </div>
                    </div>
                    <div class="route_list_block">
                        <div
                            class="route_list_title h3_title"><?= $GLOBALS['dictionary']['MSG_ALL_MIZHNARODNI'] ?></div>
                        <div class="route_list">
                            <? $getInternationalTours = $Db->getAll("SELECT t.id,t.departure,t.arrival,departure_city.title_" . $Router->lang . " AS departure_city, arrival_city.title_" . $Router->lang . " AS arrival_city,
                            departure_city.id AS departure_city_id,arrival_city.id AS arrival_city_id
                                FROM `" . DB_PREFIX . "_tours` t
                                 JOIN `" . DB_PREFIX . "_cities` departure_city ON t.departure = departure_city.id
                                 JOIN `" . DB_PREFIX . "_cities` arrival_city ON t.arrival = arrival_city.id
                                 WHERE departure_city.section_id != arrival_city.section_id");
                            $printedRoutes = array();
                            foreach ($getInternationalTours as $k => $internationalTour) {
                                $routeString = $internationalTour['departure_city_id'] . "_" . $internationalTour['arrival_city_id'];

                                if (!in_array($routeString, $printedRoutes)) {
                                    ?>
                                    <div>
                                        <a href="<?= route('schedule') ?>?departure=<?= $internationalTour['departure_city_id'] ?>&arrival=<?= $internationalTour['arrival_city_id'] ?>"
                                           class="shedule_link"><?= $internationalTour['departure_city'] ?>
                                            → <?= $internationalTour['arrival_city'] ?></a>
                                    </div>
                                    <?php $printedRoutes[] = $routeString;
                                }
                            } ?>
                        </div>
                    </div>
                    <div class="route_list_block">
                        <div class="route_list_title h3_title"><?= __('dictionary.MSG_ALL_VNUTRISHNI') ?></div>
                        <div class="route_list">
                            <?php $getHomeTours = $Db->getAll("SELECT t.id,t.departure,t.arrival,departure_city.title_" . $Router->lang . " AS departure_city, arrival_city.title_" . $Router->lang . " AS arrival_city,
                            departure_city.id AS departure_city_id,arrival_city.id AS arrival_city_id
                                FROM `" . DB_PREFIX . "_tours` t
                                 JOIN `" . DB_PREFIX . "_cities` departure_city ON t.departure = departure_city.id
                                 JOIN `" . DB_PREFIX . "_cities` arrival_city ON t.arrival = arrival_city.id
                                 WHERE departure_city.section_id = '13' AND arrival_city.section_id = '13' ");
                            $printedRoutes = array();

                            foreach ($getHomeTours as $k => $homeTour) {
                                $routeString = $homeTour['departure_city_id'] . "_" . $homeTour['arrival_city_id'];
                                if (!in_array($routeString, $printedRoutes)) {
                                    ?>
                                    <div>
                                        <a href="<?= route('schedule') ?>?departure=<?= $homeTour['departure_city_id'] ?>&arrival=<?= $homeTour['arrival_city_id'] ?>"
                                           class="shedule_link"><?= $homeTour['departure_city'] ?>
                                            → <?= $homeTour['arrival_city'] ?></a>
                                    </div>
                                    <?php $printedRoutes[] = $routeString;
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="schedule_route_details_popup">

</div>
<div class="schedule_route_details_overlay overlay" onclick="toggleRouteDetailsSchedule('0')"></div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/footer_scripts.php' ?>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script>

    function toggleDetailsServices(item) {
        $(item).toggleClass('active');
        $('.sr_bus_options').slideToggle();
    }

    $(".shedule_table_wrapper").mCustomScrollbar({
        axis: "x",
        theme: 'maxtrans_theme'
    });

    function buyTicketFromSchedule(item, id, departure, arrival, date) {
        initLoader();
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '<?= rtrim(url($Router->writelink(3)), '/') ?>',
            data: {
                'request': 'remember_ticket_without_date',
                'id': id,
                'passengers': '1',
                'departure': departure,
                'date': date,
                'arrival': arrival
            },
            success: function (response) {
                removeLoader();
                if ($.trim(response) == 'ok') {
                    location.href = '<?=$Router->writelink(85)?>';
                } else if ($.trim(response) === 'late') {
                    out('<?=$GLOBALS['dictionary']['MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL']?>');
                }
            }
        })
    };

    function toggleRouteDetailsSchedule(id, departure, arrival) {
        if (parseInt(id) > 0) {
            initLoader();
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '<?= rtrim(url($Router->writelink(3)), '/') ?>',
                data: {
                    'request': 'route_details_schedule',
                    'id': id,
                    'departure': departure,
                    'arrival': arrival
                },
                success: function (response) {
                    removeLoader();
                    if ($.trim(response) != 'err') {
                        $('.schedule_route_details_popup').html(response).toggleClass('active');
                        $('.schedule_route_details_overlay').fadeToggle();
                        $('body').toggleClass('overflow');
                    } else {
                        out('Ошибка');
                    }
                }
            })
        } else {
            $('.schedule_route_details_popup').html('').toggleClass('active');
            $('.schedule_route_details_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }
    }

    function toggleRoutePricesSchedule(id, departure, arrival) {
        if (parseInt(id) > 0) {
            initLoader();
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '<?= rtrim(url($Router->writelink(3)), '/') ?>',
                data: {
                    'request': 'route_price_details',
                    'id': id,
                    'departure': departure,
                    'arrival': arrival
                },
                success: function (response) {
                    removeLoader();
                    if ($.trim(response) != 'err') {
                        $('.schedule_route_details_popup').html(response).toggleClass('active');
                        $('.schedule_route_details_overlay').fadeToggle();
                        $('body').toggleClass('overflow');
                    } else {
                        out('Ошибка');
                    }
                }
            })
        } else {
            $('.schedule_route_details_popup').html('').toggleClass('active');
            $('.schedule_route_details_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }
    }
</script>
