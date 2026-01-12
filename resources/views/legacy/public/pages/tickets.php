<!DOCTYPE html>
<html lang="<?php echo  $Router->lang ?>">
<head>
    <link rel="stylesheet" href="<?php echo  mix('css/legacy/libs/jquery_ui_slider/jquery-ui.min.css'); ?>">
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?></div>
    <div class="content">
        <div class="main_filter_wrapper">
            <div class="container">
                <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/filter.php' ?>
            </div>
        </div>
        <div class="purchase_steps_wrapper">
            <div class="tabs_links_container">
                <div class="purchase_steps">
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title active">1. <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_VIBIR_AVTOBUSA'] ?></div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">2. <?php echo  $Router->writetitle(85) ?></div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">3. <?php echo  $Router->writetitle(86) ?></div >
                    </div>
                </div>
            </div>
        </div>
        <div class="page_content_wrapper">
            <?php $filterParams = '';
            if ($filterDeparture > 0) {
                $filterParams .= " AND (t.departure = ".$filterDeparture." OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$filterDeparture."' ) ))";
                $departureCityTitle = $Db->getOne("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $filterDeparture . "' ");

            }
            if ($filterArrival > 0) {
                $filterParams .= " AND (t.arrival = ".$filterArrival." OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE to_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$filterArrival."' ) ))";
                $arrivalCityTitle = $Db->getOne("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $filterArrival . "' ");
            }
            $weekDay = date('N',time());
            if ($filterDate !== "today") {
                $weekDay = date('N',strtotime($filterDate));
                $filterParams .= " AND t.days LIKE '%".$weekDay."%' ";
                //$dateForCheckDateParam = implode('-',array_map('intval',$dateArray));
                $filterMonth = $Db->getOne("SELECT title_".$Router->lang." AS title FROM `" .  DB_PREFIX . "_months`WHERE id = '".(int)explode('-',$filterDate)[1]."' ");
            }
            $dateParam = "";
            /*if (strtotime($dateForCheckDateParam) == strtotime(date('Y-m-d',time()))){
                $dateParam = " AND ts.departure_time > CURRENT_TIME()";
            }*/

            $minTicketsPrice = 0;
            $maxTicketsPrice = 1;
            $priceFilterParam = "";
            if ($filterDeparture > 0){
                $priceFilterParam .= " AND tsp.from_stop IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$filterDeparture."' )";
            }if ($filterArrival > 0){
                $priceFilterParam .= " AND tsp.to_stop IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$filterArrival."' )";
            }
            $getTicketsPrices = $Db->getAll("SELECT MAX(tsp.price) AS price FROM `" .  DB_PREFIX . "_tours_stops_prices`tsp
            LEFT JOIN `" .  DB_PREFIX . "_tours`t ON t.id = tsp.tour_id
            LEFT JOIN `" .  DB_PREFIX . "_tours_stops`ts ON ts.tour_id = tsp.tour_id
            WHERE t.active = 1 $dateParam $priceFilterParam
            GROUP BY tsp.tour_id
            ORDER BY tsp.id ASC");
            $pricesArray = [];
            if ($getTicketsPrices){
                foreach ($getTicketsPrices AS $k=>$ticketsPrice){
                    $pricesArray[] = $ticketsPrice['price'];
                }

                $minTicketsPrice = min($pricesArray);
                $maxTicketsPrice = max($pricesArray);
            }

            $pagination = pagination("SELECT id FROM `" . DB_PREFIX . "_tours` t WHERE active = '1' $dateParam $filterParams ",6);

            $getTickets = $Db->getAll("SELECT DISTINCT(t.id),t.departure,t.arrival,t.days,
            dc.title_".$Router->lang." AS departure_city,
            dc.section_id AS departure_city_section_id,
            ac.title_".$Router->lang." AS arrival_city,
            ac.section_id AS arrival_city_section_id,
            b.title_" . $Router->lang . " AS bus_title
            FROM `" . DB_PREFIX . "_tours` t
            LEFT JOIN `" .  DB_PREFIX . "_cities`dc ON dc.id = t.departure
            LEFT JOIN `" .  DB_PREFIX . "_cities`ac ON ac.id = t.arrival
            LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
            LEFT JOIN `" .  DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
            LEFT JOIN `" .  DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
            WHERE t.active = '1' $dateParam $priceFilterParam $filterParams
            ORDER BY tsp.price DESC LIMIT ".$pagination['from'].",".$pagination['per_page']); ?>

            <?php
                $ticketParams = new \App\Repository\Races\Params\TicketParams(
                    $filterDeparture,
                    $filterArrival,
                    $filterDate,
                    $Router->lang,
                );

                $ticketService = new \App\Service\Tour\TicketService();
                $tickets = $ticketService->get($ticketParams);
            ?>
            <div class="container">
                <?php if (empty($tickets)): ?>
                <div class="ticket_page_title h2_title reccomend_title"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_RECOMMEND_DATES']?></div>
                <div class="recommend_dates">
                    <?php

                        $daysResult = $Db->getAll("SELECT DISTINCT t.days
                        FROM `" . DB_PREFIX . "_tours` t
                        LEFT JOIN `" .  DB_PREFIX . "_cities`dc ON dc.id = t.departure
                        LEFT JOIN `" .  DB_PREFIX . "_cities`ac ON ac.id = t.arrival
                        LEFT JOIN `" .  DB_PREFIX . "_cities`dcountry ON dcountry.id = dc.section_id
                        LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
                        LEFT JOIN `" .  DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
                        LEFT JOIN `" .  DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
                        WHERE t.active = '1' AND (t.departure = " . $filterDeparture . " OR t.id
                        IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop
                        IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '" . $filterDeparture . "' ) ))
                         AND (t.arrival = " . $filterArrival . " OR t.id
                        IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE to_stop
                        IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '" . $filterArrival . "' ) ))
                        ORDER BY dc.section_id ASC,tsp.price DESC");
                        $getMonths = $Db->getAll("SELECT id, title_" . $Router->lang . " AS title FROM " . DB_PREFIX . "_months");
                        // Преобразуем результат запроса в ассоциативный массив
                        $months = [];
                        foreach ($getMonths as $month) {
                            $months[$month['id']] = $month['title'];
                        }
                        $availableDays = [];
                        foreach ($daysResult as $row) {
                            $daysOfWeek = explode(',', $row['days']);
                            $availableDays = array_merge($availableDays, $daysOfWeek);
                        }
                        $availableDays = array_unique($availableDays);
                        foreach ($availableDays as $dayOfWeek) {
                            $currentWeekDay = date('N');
                            $nearestDay = ($currentWeekDay <= $dayOfWeek) ? ($dayOfWeek - $currentWeekDay) : (7 - $currentWeekDay + $dayOfWeek);
                            $nearestDate = date('Y-m-d', strtotime("+$nearestDay days"));
                            $weekday = $daysOfWeek[date('N', strtotime($nearestDate))];
                            $date = date('d', strtotime("+$nearestDay days"));
                            $monthId = date('n', strtotime($nearestDate));
                            $month = $months[$monthId];
                            echo  '<div class="reccomend_date blue_btn"><a class="tour_date_link" href="#" data-date="' . $nearestDate . '">' . $date . ' ' . $month . '</a></div>';
                        }

                    ?>
                </div>
                <?php endif; ?>
                <div class="ticket_page_subtitle par"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_VIZD_TA_PRIBUTTYA_ZA_MISCEVIM_CHASOM'] ?></div>
                <div class="ticket_page_title h2_title">
                    <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_ROZKLAD_AVTOBUSIV'] ?>
                    <?php if ($filterParams){
                      echo  $departureCityTitle['title'].' - '.$arrivalCityTitle['title'].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_NA'].' '.date('d', strtotime($filterDate)).' '.$filterMonth['title'];
                    }?>
                </div>
                <div class="sort_block hidden-xl hidden-lg hidden-md hidden-sm hidden-xs">
                    <div class="sort_block_tile h3_title"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_SORTUVATI'] ?></div>
                    <div class="sort_options flex_ac">
                        <button class="sort_option active h5_title flex_ac desc" data-sort="1" data-sort-direction="1"
                                onclick="changeSort(this)">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CINA'] ?>
                        </button>
                        <button class="sort_option h5_title flex_ac desc" data-sort="2" data-sort-direction="1"
                                onclick="changeSort(this)">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA'] ?>
                        </button>
                        <button class="sort_option h5_title flex_ac desc" data-sort="3" data-sort-direction="1"
                                onclick="changeSort(this)">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CHAS_PRIBUTTYA'] ?>
                        </button>
                        <!--<button class="sort_option h5_title flex_ac desc" data-sort="4" data-sort-direction="1"
                                onclick="changeSort(this)">
                            <?php /*echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_POPULYATNISTI'] */?>
                        </button>-->
                    </div>
                </div>
                <div class="mobile_sort_filter hidden-xxl flex_ac">
                    <select class="sort_select flex_ac">
                        <option value="" hidden selected
                                disabled><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_SORTUVATII_ZA'] ?></option>
                        <option value="1"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CINA'] ?></option>
                        <option value="2"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA'] ?></option>
                        <option value="3"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CHAS_PRIBUTTYA'] ?></option>
                        <option value="4"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_POPULYATNISTI'] ?></option>
                    </select>
                    <button class="mobile_filter_btn" onclick="toggleMobileFilter()">
                        <img src="<?php echo  asset('images/legacy/common/filter.svg'); ?>" alt="filter">
                    </button>
                </div>
            </div>
            <div class="catalog_filter_overlay overlay hidden-xxl" onclick="toggleMobileFilter()"></div>
            <div class="tickets_catalog_wrapper">
                <div class="container">
                    <div class="tickets_catalog">
                        <div class="catalog_elements">
                            <div class="catalog_elements_title h3_title">
                                <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_ZNAJDENO'] . ' ' . count($getTickets) . ' ' . $GLOBALS['dictionary']['MSG_MSG_TICKETS_AVTOBUSIV'] ?></div>
                            <div class="catalog_elements_subtitle par"><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA_TA_PRIBUTTYA_MISCEVIJ'] ?></div>

                            <div class="ticket_cards_wrapper">
                                <?
                                foreach ($tickets as $k => $ticket) {
                                    $getTicketStops = $Db->getAll("SELECT stop_id,arrival_time,departure_time,arrival_day FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$ticket['id']."' ORDER BY id ASC ");

                                    $tourDeparture = $ticket['departure'];
                                    if ($filterDeparture > 0){
                                        $tourDeparture = $filterDeparture;
                                    }
                                    $tourArrival = $ticket['arrival'];
                                    if ($filterArrival > 0){
                                        $tourArrival = $filterArrival;
                                    }
                                    $ticketDepartureDate = $filterDate;
                                    if ($filterDate == 'today'){
                                        $ticketDepartureDate = findNearestDayOfWeek(date('Y-m-d',time()), explode(',',$ticket['days']));
                                    }
                                    $dateArray = explode('-',$ticketDepartureDate);
                                    $month = $Db->getOne("SELECT title_".$Router->lang." AS title FROM `" .  DB_PREFIX . "_months`WHERE id = '".(int)$dateArray[1]."' ");
                                    $departureDate = $dateArray[2] . ' ' . $month['title'] . ' ' . $dateArray[0];

                                    $departureDetails = $Db->getOne("SELECT station.id,station.title_".$Router->lang." AS station,city.title_".$Router->lang." AS city,stop.departure_time FROM `" .  DB_PREFIX . "_cities`station
                                    LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
                                    LEFT JOIN `" .  DB_PREFIX . "_tours_stops`stop ON stop.stop_id = station.id AND stop.tour_id = '".$ticket['id']."'
                                    WHERE station.station = 1 AND station.section_id = '".$tourDeparture."' AND station.id IN(SELECT stop_id FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$ticket['id']."' )");
                                    $arrivalDetails = $Db->getOne("SELECT station.id,station.title_".$Router->lang." AS station,city.title_".$Router->lang." AS city,stop.arrival_time, stop.arrival_day FROM `" .  DB_PREFIX . "_cities`station
                                    LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
                                    LEFT JOIN `" .  DB_PREFIX . "_tours_stops`stop ON stop.stop_id = station.id AND stop.tour_id = '".$ticket['id']."'
                                    WHERE station.station = 1 AND station.section_id = '".$tourArrival."' AND station.id IN(SELECT stop_id FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$ticket['id']."' )");
                                    $rideTime = calculateTotalTravelTime($getTicketStops,$departureDetails['id'],$arrivalDetails['id'],$arrivalDetails['arrival_day']);
                                    $international = ($ticket['departure_city_section_id'] != $ticket['arrival_city_section_id']);
                                    $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop = '".$departureDetails['id']."' AND to_stop = '".$arrivalDetails['id']."' AND tour_id = '".$ticket['id']."' "); ?>
                                    <div class="ticket_card shadow_block">
                                        <div class="d_none">
                                            <?php echo Out($tourArrival)?>
                                            <?php echo Out($ticket['arrival'])?>
                                            <?php echo Out($arrivalDetails)?>

                                        </div>
                                        <div class="flex-row">
                                            <div class="col-lg-9 col-xs-12">
                                                <div class="ticket_info">
                                                    <div class="ticket_info_header flex_ac">
                                                        <div class="ticket_info_date_block flex_ac">
                                                            <img src="<?php echo  asset('images/legacy/common/ticket_calendar.svg'); ?>" alt="calendar">
                                                            <span class="ticket_info_date par">
                                                                <?php echo  $departureDate ?>
                                                            </span>
                                                        </div>
                                                        <div class="ride_description_wrapper flex_ac">
                                                            <div class="ride_description par">
                                                                <span><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_REJS'] ?></span>
                                                                <span><?php echo  $ticket['departure_city'] ?> — <?php echo  $ticket['arrival_city'] ?></span>
                                                            </div>
                                                            <div class="ride_description par">
                                                                <span><?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_AVTOBUS'] ?></span>
                                                                <span><?php echo  $ticket['bus_title'] ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="ticket_ride_info_block flex-row gap-30">
                                                        <div class="col-lg-4 col-sm-6 col-xs-12">
                                                            <div class="ticket_ride_departure ticket_ride_info">
                                                                <div class="ticket_ride_time flex_ac">
                                                                    <img src="<?php echo  asset('images/legacy/common/clock.svg'); ?>" alt="clock">
                                                                    <span class="btn_txt"><?php echo  date("H:i", strtotime($ticket['dep_time'])) ?></span>
                                                                </div>
                                                                <div class="ticket_ride_city btn_txt">
                                                                    <?php echo  $departureDetails['city'] ?>
                                                                </div>
                                                                <div class="ticket_ride_checkpoint manrope">
                                                                    <?php echo  $ticket['dep_station_title'] ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4 hidden-md hidden-sm col-xs-12">
                                                            <div class="ticket_ride_info ride_total_time">
                                                                <div class="ticket_logo_wrapper">
                                                                    <img src="<?php echo  asset('images/legacy/common/ticket_logo_2.svg'); ?>" alt="ticket logo" class="fit_img">
                                                                </div>
                                                                <div class="ticket_ride_total_time_wrapper">
                                                                    <div class="ticket_ride_total_time_info">
                                                                        <img src="<?php echo  asset('images/legacy/common/info.svg'); ?>" alt="info">
                                                                        <div class="ticket_info_tooltip par">
                                                                            <?php echo $GLOBALS['dictionary']['MSG_MSG_TICKETS_VKAZANIJ_CHAS_NE_VRAHOVU_ZATRIMOK_NA_KORDONI']?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ticket_ride_total_time_data">
                                                                        <div class="ticket_ride_total_time par">
                                                                            <?php echo  (int)explode(':',$rideTime)[0].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_GOD'].' '.(int)explode(':',$rideTime)[1].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_HV_V_DOROZI'] ?>
                                                                        </div>
                                                                        <? if ($international) { ?>
                                                                            <div class="ticket_ride_status par">
                                                                                <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_MIZHNARODNIJ'] ?>
                                                                            </div>
                                                                        <? } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4 col-sm-6 col-xs-12">
                                                            <div class="ticket_ride_arrival ticket_ride_info">
                                                                <div class="ticket_ride_time flex_ac">
                                                                    <img src="<?php echo  asset('images/legacy/common/clock.svg'); ?>" alt="clock">
                                                                    <span class="btn_txt"><?php echo  date('H:i', strtotime($ticket['arr_time'])) ?></span>
                                                                </div>
                                                                <div class="ticket_ride_city btn_txt">
                                                                    <?php echo  $arrivalDetails['city'] ?>
                                                                </div>
                                                                <div class="ticket_ride_checkpoint">
                                                                    <?php echo  $ticket['arr_station_title'] ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button class="ticket_details_btn shedule_link flex_ac hidden-md hidden-sm hidden-xs" onclick="toggleRouteDetails('<?php echo  $ticket['id'] ?>','<?php echo $departureDetails['id']?>','<?php echo $arrivalDetails['id']?>')">
                                                    <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_DETALINISHE'] ?>
                                                    <img src="<?php echo  asset('images/legacy/common/arrow_down_2.svg'); ?>" alt="arrow down">
                                                </button>
                                            </div>
                                            <div class="col-lg-3 hidden-md hidden-sm hidden-xs">
                                                <div class="ticket_totals">
                                                    <div class="ticket_price"><?php echo  $ticket['price'] ?> ₴</div>
                                                    <button class="ticket_buy_btn flex_ac h5_title blue_btn" onclick="buyTicket(this,'<?php echo  $ticket['id'] ?>','<?php echo $departureDetails['id']?>','<?php echo $arrivalDetails['id']?>', '<?php echo $filterDeparture?>', '<?php echo $filterArrival?>' )">
                                                        <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_KUPITI_KVITOK'] ?>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="hidden-xxl hidden-xl hidden-lg col-sm-12 hidden-xs">
                                                <div class="ride_total_time">
                                                    <div class="ticket_logo_wrapper">
                                                        <img src="<?php echo  asset('images/legacy/common/ticket_logo_2.svg'); ?>" alt="ticket logo" class="fit_img">
                                                    </div>
                                                    <div class="mobile_ticket_ride_total_time_wrapper flex_ac">
                                                        <div class="ticket_ride_total_time_info flex_ac">
                                                            <img src="<?php echo  asset('images/legacy/common/info.svg'); ?>" alt="info">
                                                            <div class="ticket_ride_total_time par">
                                                                <?php echo  (int)explode(':',$rideTime)[0].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_GOD'].' '.(int)explode(':',$rideTime)[1].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_HV_V_DOROZI'] ?>
                                                            </div>
                                                        </div>
                                                        <? if ($international) { ?>
                                                            <div class="ticket_ride_status par">
                                                                <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_MIZHNARODNIJ'] ?>
                                                            </div>
                                                        <? } ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="hidden-xxl hidden-xl hidden-lg col-xs-12">
                                                <div class="mobile_ticket_totals flex_ac">
                                                    <div class="mobile_ticket_details flex_ac">
                                                        <div class="ticket_price"><?php echo  $ticket['price'] ?> ₴</div>
                                                        <button class="ticket_details_btn shedule_link flex_ac" onclick="toggleRouteDetails('<?php echo  $ticket['id'] ?>','<?php echo $departureDetails['id']?>','<?php echo $arrivalDetails['id']?>')">
                                                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_DETALINISHE'] ?>
                                                            <img src="<?php echo  asset('images/legacy/common/arrow_down_2.svg'); ?>" alt="arrow down">
                                                        </button>
                                                    </div>
                                                    <button class="ticket_buy_btn flex_ac h5_title blue_btn" onclick="buyTicket(this,'<?php echo  $ticket['id'] ?>','<?php echo $departureDetails['id']?>','<?php echo $arrivalDetails['id']?>', '<?php echo $filterDeparture?>', '<?php echo $filterArrival?>' )">
                                                        <?php echo  $GLOBALS['dictionary']['MSG_MSG_TICKETS_KUPITI_KVITOK'] ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <? } ?>
                            </div>
                            <div class="pagination_wrapper">
                                <?php echo paginatePublic($pagination)?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php echo  view('layout.components.footer.footer', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
</div>
<div class="route_details_popup blue_popup">

</div>
<div class="route_details_overlay overlay" onclick="toggleRouteDetails('0')"></div>
<?php

// Инициализация PHP сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Инициализация PHP сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php echo  view('layout.components.footer.footer_scripts', [
    'page_data' => $page_data,
])->render(); ?>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"
        integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
        function buyTicket(item, id,departure,arrival,fromCity, toCity) {
            $('body').prepend('<div class="loader"></div>');
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'remember_ticket',
                    'id': id,
                    <?php if (isset($_SESSION['filter']['date'])){?>
                    'date': '<?php echo $_SESSION['filter']['date']?>',
                    <?php }else{?>
                    'date': '<?php echo date('Y-m-d',time())?>',
                    <?php }?>
                    'passengers': '<?php echo  $_SESSION['filter']['adults'] + $_SESSION['filter']['kids']?>',
                    'departure':departure,
                    'arrival':arrival,
                    'fromCity' : fromCity,
                    'toCity': toCity
                },
                success: function (response) {
                    console.log(response);
                    $('.loader').remove();
                    if ($.trim(response.data) === 'ok') {
                        location.href = '<?php echo  rtrim(url($Router->writelink(85)), '/') ?>';
                    }else if ($.trim(response.data) === 'late'){
                        out('<?php echo  __('dictionary.MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL') ?>');
                    }
                }
            })
        }
        function out(msg, txt) {
            if (msg == undefined || msg == '' || $('.alert').length > 0) {
                return false;
            }

            let alert = document.createElement('div');
            $(alert).addClass('alert');

            let alertContent = document.createElement('div');
            $(alertContent).addClass('alert_content').appendTo(alert);

            let appendOverlay = document.createElement('div');
            $(appendOverlay).addClass('alert_overlay').appendTo(alert);

            let alertTitle = document.createElement('div');
            $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

            if (txt != '') {
                let alertTxt = document.createElement('div');
                $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
            }

            let closeBtn = document.createElement('button');
            $(closeBtn).addClass('alert_ok').text(close_btn).appendTo(alertContent);

            $('body').append(alert);
            $(alert).fadeIn();

            $('.alert_ok,.alert_overlay').on('click', function () {
                $('.alert').fadeOut();
                setTimeout(function () {
                    $('.alert').remove();
                }, 350)
            });

        };

        $('.purchase_steps').slick({
            slidesToShow: 4,
            slidesToScroll: 1,
            dots: false,
            arrows: false,
            infinite: false,
            variableWidth: true,
            responsive: [
                {
                    breakpoint: 576,
                    settings: {
                        infinite: false,
                        slidesToShow: 1
                    }
                },
            ]
        });

        $('.sort_select').niceSelect();
        $(function () {
            $("#price_range").slider({
                range: true,
                min: <?php echo $minTicketsPrice?>,
                max: <?php echo $maxTicketsPrice?>,
                values: [<?php echo $minTicketsPrice?>, <?php echo $maxTicketsPrice?>],
                slide: function (event, ui) {
                    $(".filter_price_min").text(ui.values[0]);
                    $(".filter_price_max").text(ui.values[1]);
                },
                stop: function(event,ui){
                    filterTickets();
                }
            })
        });

        function toggleFilterParams(item) {
            $(item).next().slideToggle();
            setTimeout(function () {
                $(item).toggleClass('active');
            }, 400)
        }

        function toggleRouteDetails(id,departure,arrival) {
            if (parseInt(id) > 0) {
                $('body').prepend('<div class="loader"></div>');
                $.ajax({
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    url: '/ajax/ru',
                    data: {
                        'request': 'route_details',
                        'id': id,
                        'departure':departure,
                        'arrival':arrival
                    },
                    success: function (response) {
                        $('.loader').remove();
                        if ($.trim(response) != 'err') {
                            $('.route_details_popup').html(response.data).toggleClass('active');
                            $('.route_details_overlay').fadeToggle();
                            $('body').toggleClass('overflow');
                        } else {
                            out('Ошибка');
                        }
                    }
                })
            } else {
                $('.route_details_popup').html('').toggleClass('active');
                $('.route_details_overlay').fadeToggle();
                $('body').toggleClass('overflow');
            }
        }

        function toggleInfoBlock(item) {
            $(item).next().slideToggle();
            $(item).find('img').toggleClass('rotate');
        }

        function toggleMobileFilter() {
            $('.catalog_filter').toggleClass('active');
            $('.catalog_filter_overlay').fadeToggle();
            $('body').toggleClass('overflow');
        }

        function changeSort(item) {
            $('.sort_option').not(item).removeClass('active');
            if ($(item).hasClass('active')) {
                $(item).toggleClass('desc').toggleClass('asc');
                if ($(item).hasClass('desc')) {
                    $(item).attr('data-sort-direction', '1');
                } else if ($(item).hasClass('asc')) {
                    $(item).attr('data-sort-direction', '2');
                }
                $('.ticket_cards_wrapper').toggleClass('reverse');
            }
            $(item).addClass('active');
            $('.sort_select').val($(item).attr('data-sort'));
            $('.sort_select').niceSelect('update');
        }

        function filterTickets() {
            let min_price = parseInt($('.filter_price_min').text());
            let max_price = parseInt($('.filter_price_max').text());
            let stops = $('.stops_option:checked').val();
            let departure_time = [];
            if ($('.departure_time_option:checked').length > 0) {
                $('.departure_time_option:checked').each(function () {
                    departure_time.push($(this).val());
                })
            }

            if (departure_time.includes('1') && departure_time.length === 1) {
                departure_time = [];
            }

            let arrival_time = [];
            if ($('.arrival_time_option:checked').length > 0) {
                $('.arrival_time_option:checked').each(function () {
                    arrival_time.push($(this).val());
                })
            }

            if (arrival_time.includes('1') && arrival_time.length === 1) {
                arrival_time = [];
            }

            let departure_station = [];
            if ($('.departure_station_checker:checked').length > 0) {
                $('.departure_station_checker:checked').each(function () {
                    departure_station.push($(this).val());
                })
            }

            let arrival_station = [];
            if ($('.arrival_station_checker:checked').length > 0) {
                $('.arrival_station_checker:checked').each(function () {
                    arrival_station.push($(this).val())
                })
            }

            let comfort = [];
            if ($('.bus_options_checker:checked').length > 0) {
                $('.bus_options_checker:checked').each(function () {
                    comfort.push($(this).val());
                })
            }

            let sort_option = $('.sort_option.active').attr('data-sort');
            let sort_direction = $('.sort_option.active').attr('data-sort-direction');
            $('body').prepend('<div class="loader"></div>');
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'filter',
                    'stops': stops,
                    'departure_time': departure_time,
                    'arrival_time': arrival_time,
                    'departure_station': departure_station,
                    'arrival_station': arrival_station,
                    'comfort': comfort,
                    'sort_option': sort_option,
                    'sort_direction': sort_direction,
                    'arrival_city': '<?php echo $filterArrival?>',
                    'departure_city': '<?php echo $filterDeparture?>',
                    'adults': '<?php echo $adults?>',
                    'kids': '<?php echo $kids?>',
                    <?php if ($filterDate){?>
                    'date': '<?php echo $filterDate?>',
                    <?php }else{?>
                    'date': '<?php echo date('Y-m-d',time())?>',
                    <?php }?>
                    'min_price':min_price,
                    'max_price':max_price
                },
                success: function (response) {
                    $('.loader').remove();
                    if ($.trim(response) != 'err') {
                        $('.catalog_elements').html(response);
                    } else {
                        out('Ошибка');
                    }
                }
            })
        }

        $('.filter_option').on('change', function () {
            filterTickets();

        })
    ;

</script>
</body>
</html>
