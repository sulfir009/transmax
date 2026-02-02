<?php
// -----------------------------------------------------------------------------
// payment_page.php (АККУРАТНОЕ ИСПРАВЛЕНИЕ)
// Цель: не “ломать” существующую логику, но защититься от ситуации,
// когда $_SESSION['order']['passengers'] остался = 2, хотя фактически доп.пассажир удалён.
// Мы НЕ трогаем workflow order_route / order_mail / payment/legacy/create,
// а только “нормализуем” passengers по данным сессии (если они есть) и пересчитываем totalPrice.
// -----------------------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['order']['tour_id'])) {
    header('Location:' . route('main'));
    exit;
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
?>

<!DOCTYPE html>
<html lang="<?php echo $Router->lang ?>">
<head>
    <?php echo view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?>

    <style>
        /* ==========================================================
           PAYMENT V2 (match screenshot) — scoped only to .payment_v2
           ========================================================== */

        .payment_v2{
            position: relative;
            background:#fff;
            overflow:hidden;
            padding-bottom:140px; /* запас под автобус снизу */
        }

        /* На макете контент узкий и по центру */
        .payment_v2 .container{
            max-width: 940px;  /* чтобы карточка 872px реально влезла */
        }

        /* Фильтр на макете не нужен */
        .payment_v2 .main_filter_wrapper{
            display:none !important;
        }

        .payment_v2 .purchase_steps{
            display:flex !important;
            gap:60px;
            align-items:center;
            flex-wrap:nowrap;
        }
        .payment_v2 .purchase_step_wrapper{
            position:relative;
        }
        .payment_v2 .purchase_step{
            height:40px;
            width: 180px;
            padding: 0 10px;
            border-radius:999px;
            border:1px solid #40A6FF;
            color:#40A6FF;
            font-family: Montserrat,system-ui;
            font-weight:700;
            font-size:10px;
            display:flex;
            align-items:center;
            white-space:nowrap;
            justify-content:center;
        }
        .payment_v2 .purchase_step.active{
            background:#40A6FF;
            color:#fff;
        }

        /* Карточка как на макете */
        .payment_v2 .payment_v2__card.shadow_block{
            box-sizing: border-box;
            width: 100%;
            max-width: 872px;        /* Figma width */
            margin: 0 auto;
            background: #fff;
            border: 3px solid #A3E8F9;   /* Figma border */
            border-radius: 15px;        /* Figma radius */
            box-shadow: inset 0px 2px 25px rgba(53, 186, 240, 0.15) !important;
            padding: 40px; /* Figma: отступы внутри по 40px */
        }

        .payment_v2 .payment_v2__title{
            font-family: Montserrat,system-ui;
            font-weight: 800;
            font-size: 18px;
            line-height: 1.2;
            color:#303233;
            margin:0 0 6px;
        }

        .payment_v2 .payment_v2__sub{
            font-family: Montserrat,system-ui;
            font-weight: 500;
            font-size: 11px;
            line-height: 1.35;
            color:#6E7172;
            margin:0 0 12px;
        }

        /* Методы оплаты — строки как на макете */
        .payment_v2 .payment_v2__methods{
            display:flex;
            flex-direction:column;
            gap:10px;
            margin-top: 8px;
        }

        .payment_v2 .pv2_method{
            display:grid;
            grid-template-columns: 18px 1fr 34px auto;
            gap:10px;
            align-items:center;
            padding: 10px 12px;
            border:1px solid #A3E8F9;
            border-radius:10px;
            background:#fff;
            cursor:pointer;
            user-select:none;
        }

        /* Радио — круг как на скрине (красный выбранный) */
        .payment_v2 .pv2_radio{
            width:14px;
            height:14px;
            border-radius:999px;
            border:2px solid #BFC6C8;
            position:relative;
            display:inline-block;
            box-sizing:border-box;
        }
        .payment_v2 .pv2_method input:checked + .pv2_radio{
            border-color:#EB5757;
        }
        .payment_v2 .pv2_method input:checked + .pv2_radio::after{
            content:"";
            position:absolute;
            left:50%;
            top:50%;
            width:6px;
            height:6px;
            border-radius:999px;
            background:#EB5757;
            transform: translate(-50%,-50%);
        }

        .payment_v2 .pv2_method_name{
            font-family: Montserrat,system-ui;
            font-weight: 600;
            font-size: 12px;
            color:#6E7172;
        }

        .payment_v2 .pv2_method_logo img{
            width: 26px;
            height: 18px;
            display:block;
            object-fit:contain;
            opacity:.95;
        }
        .payment_v2 .pv2_method_logo--mono{
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .payment_v2 .mono_badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:44px;
            height:28px;
            padding:0 10px;
            border-radius:999px;
            background:#000;
            color:#fff;
            font-weight:600;
            font-size:12px;
            letter-spacing:.5px;
        }

        .payment_v2 .pv2_method_price{
            font-family: Montserrat,system-ui;
            font-weight: 800;
            font-size: 14px;
            color:#303233;
            white-space:nowrap;
        }

        /* Логотипы карт как на макете */
        .payment_v2 .payment_v2__logos{
            display:flex;
            gap:22px;
            align-items:center;
            justify-content:flex-start;
            margin: 12px 0 10px;
        }
        .payment_v2 .payment_v2__logos img{
            height: 41px;
            width:auto;
            display:block;
            object-fit:contain;
        }

        /* Чекбоксы */
        .payment_v2 .payment_v2__checks{
            display:flex;
            flex-direction:column;
            gap:10px;
            margin-top: 10px;
        }
        .payment_v2 .payment_v2__bonus{
            display:flex;
            flex-direction:column;
            gap:8px;
            padding:12px 14px;
            border:1px solid #E6EEF4;
            border-radius:10px;
            background:#F8FBFF;
            margin: 12px 0 16px;
        }
        .payment_v2 .payment_v2__bonus_row{
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:14px;
        }
        .payment_v2 .payment_v2__bonus_check{
            margin:0;
        }

        .payment_v2 .pv2_check{
            display:flex;
            align-items:flex-start;
            gap:10px;
            cursor:pointer;
            user-select:none;
        }
        .payment_v2 .pv2_box{
            width:16px;
            height:16px;
            border-radius:2px;
            border:2px solid #35BAF0;
            background:#fff;
            flex:0 0 auto;
            margin-top: 1px;
            position:relative;
        }
        .payment_v2 .pv2_check input:checked + .pv2_box{
            background:#35BAF0;
        }
        .payment_v2 .pv2_check input:checked + .pv2_box::after{
            content:"";
            position:absolute;
            left:4px;
            top:1px;
            width:5px;
            height:9px;
            border:2px solid #fff;
            border-top:0;
            border-left:0;
            transform: rotate(45deg);
        }

        .payment_v2 .pv2_check_text{
            font-family: Montserrat,system-ui;
            font-weight: 600;
            font-size: 11px;
            line-height: 1.25;
            color:#6E7172;
        }
        .payment_v2 .pv2_check_text a{
            color:#40A6FF;
            text-decoration:none;
        }
        .payment_v2 .pv2_check_text a:hover{
            text-decoration:underline;
        }

        /* Кнопка "Оплатить" */
        .payment_v2 .payment_v2__btn{
            width:60%;
            margin-left: 21%;
            height:38px;
            border:0;
            border-radius:999px;
            background: linear-gradient(180deg,#63D5F8,#34B9F0);
            color:#fff;
            font-family: Montserrat,system-ui;
            font-weight:800;
            font-size:12px;
            margin-top: 14px;
            box-shadow: 0 10px 18px rgba(52,185,240,.22);
        }

        /* ==========================================================
           Background decor (2 dashed paths + pins + bus)
           ========================================================== */
        .payment_v2__decor{
            position:absolute;
            inset:0;
            pointer-events:none;
            z-index:0;
        }
        .payment_v2__content{
            position:relative;
            z-index:1;
        }

        .payment_v2__dash{
            fill:none;
            stroke:#35BAF0;
            stroke-width:4;
            stroke-linecap:round;
            stroke-dasharray:10 14;
        }

        .payment_v2__path{
            position:absolute;
            pointer-events:none;
            z-index:0;
            width:auto;
            height:auto;
            opacity:1;
        }

        .payment_v2__path.path1{
            left:-170px;
            top:260px;
            width:520px;
            height:800px;
            transform: scaleX(-1);
        }
        .payment_v2__path.path2{
            right:-130px;
            top:52px;
            width:520px;
            height:800px;
        }

        .payment_v2__pin{
            position:absolute;
            width:34px;
            height:34px;
            border-radius:999px;
            background:#fff;
            border:2px solid #35BAF0;
            box-shadow:0 0 0 6px rgba(53,186,240,.12);
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .payment_v2__pin_icon{
            width:18px;
            height:18px;
            display:block;
            object-fit:contain;
        }

        .payment_v2__pin.pin_left{ left: 85px; top: 320px; }
        .payment_v2__pin.pin_right{ right: 110px; top: 430px; }

        .payment_v2{
            --bus-w: clamp(260px, 34vw, 524px);
            --bus-offset: 60px;
            --bus-duration: 14s;
        }

        .payment_v2__bus_wrap{
            position: absolute;
            bottom: 0px;
            left: 0;
            width: var(--bus-w);
            height: auto;
            z-index: 2;
            pointer-events: none;
            animation: bus-drive var(--bus-duration) linear infinite;
            will-change: transform;
        }
        .payment_v2__bus{
            width: 100%;
            height: auto;
            display: block;
            animation: bus-bounce 1.15s ease-in-out infinite;
            transform-origin: 50% 100%;
        }

        .payment_v2__bus_wrap::before,
        .payment_v2__bus_wrap::after{
            content:"";
            position:absolute;
            left: 10%;
            top: 72%;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(180,180,180,.55) 0%, rgba(180,180,180,0) 70%);
            opacity: 0;
            filter: blur(0.4px);
            pointer-events:none;
        }
        .payment_v2__bus_wrap::before{ animation: smoke 1.2s ease-out infinite; }
        .payment_v2__bus_wrap::after{
            width: 34px;
            height: 34px;
            left: 6%;
            top: 70%;
            animation: smoke 1.2s ease-out infinite;
            animation-delay: .35s;
        }

        @keyframes bus-drive{
            0%{ transform: translateX(calc(-1 * (var(--bus-w) + var(--bus-offset)))); }
            100%{ transform: translateX(calc(100vw + var(--bus-offset))); }
        }
        @keyframes bus-bounce{
            0%,100%{ transform: translateY(0) rotate(0deg); }
            50%{ transform: translateY(-2px) rotate(-0.15deg); }
        }
        @keyframes smoke{
            0%{ transform: translate(0, 0) scale(0.6); opacity: 0; }
            15%{ opacity: .65; }
            100%{ transform: translate(-38px, -16px) scale(1.6); opacity: 0; }
        }

        @media (max-width: 480px){
            .payment_v2{
                --bus-w: clamp(210px, 62vw, 360px);
                --bus-duration: 12s;
            }
            .payment_v2__bus_wrap{ bottom: -6px; }
        }

        @media (prefers-reduced-motion: reduce){
            .payment_v2__bus_wrap,
            .payment_v2__bus{
                animation: none !important;
            }
            .payment_v2__bus_wrap::before,
            .payment_v2__bus_wrap::after{
                display:none !important;
            }
        }

        @media (max-width: 520px){
            .payment_v2{ background:#F3FAFF !important; }
            .payment_v2 .container{
                max-width: 420px !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
            }
            .payment_v2 .purchase_steps{ gap: 10px !important; }
            .payment_v2 .purchase_step{
                height: 24px !important;
                width: auto !important;
                padding: 0 10px !important;
                border-radius: 999px !important;
                border: 2px dashed #40A6FF !important;
                font-size: 9px !important;
            }
            .payment_v2 .purchase_step.active{ border-style: solid !important; }
            .payment_v2 .purchase_step_wrapper:not(:last-child)::after{
                right: -12px !important;
                width: 12px !important;
                background: repeating-linear-gradient(
                    to right,
                    #40A6FF 0 4px,
                    transparent 4px 8px
                ) !important;
                height: 2px !important;
            }
            .payment_v2 .payment_v2__card{ max-width: 100% !important; }
            .payment_v2 .pv2_method{
                grid-template-columns: 18px 1fr 28px auto;
                padding: 10px 10px;
            }
        }

        .payment_v2 .payment_v2_hide{ display:none !important; }
        .header{ padding: 0px 0; }

        @media (max-width: 920px){
            .payment_v2 .payment_v2__card.shadow_block{
                max-width: 100%;
                min-height: auto;
                padding: 18px 16px;
                border-width: 2px;
                border-radius: 14px;
            }
        }

        .payment_v2 .tabs_links_container{
            box-shadow: none !important;
            background: transparent !important;
        }

        .payment_v2 .purchase_step_wrapper:nth-child(1) .purchase_step{ width: 273px; }
        .payment_v2 .purchase_step_wrapper:nth-child(2) .purchase_step{ width: 327px; }
        .payment_v2 .purchase_step_wrapper:nth-child(3) .purchase_step{ width: 169px; }

        @media (max-width: 520px){
            .payment_v2 .purchase_steps{ justify-content: flex-start; }
            .payment_v2 .purchase_step{
                height: 44px;
                border-radius: 44px;
                border-width: 2px;
                font-size: 14px;
                line-height: 18px;
                padding: 0 14px;
            }
            .payment_v2 .purchase_step_wrapper:nth-child(1) .purchase_step{ width: 210px; }
            .payment_v2 .purchase_step_wrapper:nth-child(2) .purchase_step{ width: 250px; }
            .payment_v2 .purchase_step_wrapper:nth-child(3) .purchase_step{ width: 140px; }
        }

        .payment_v2 .purchase_steps_wrapper{
            margin: 0 0 38px;
            background:#fff;
            padding: 26px 0 30px;
            box-shadow: 0 10px 24px rgba(53,186,240,.12);
        }
        .payment_v2 .tabs_links_container{
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 40px;
        }
        .payment_v2 .purchase_steps{
            display:flex !important;
            align-items:center;
            gap: 43px;
            flex-wrap:nowrap;
        }
        .payment_v2 .purchase_step_wrapper{
            position: relative;
            flex: 0 0 auto;
        }
        .payment_v2 .purchase_step_wrapper:nth-child(1){ width: 273px; }
        .payment_v2 .purchase_step_wrapper:nth-child(2){ width: 327px; }
        .payment_v2 .purchase_step_wrapper:nth-child(3){ width: 169px; }
        .payment_v2 .purchase_step{
            width: 100%;
            height: 37px;
            border-radius: 60.5px;
            border: 3px solid #40A6FF;
            background: #fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family: Montserrat,system-ui;
            font-weight:600;
            font-size:11px;
            line-height:24.72px;
            color:#40A6FF;
            white-space:nowrap;
        }
        .payment_v2 .purchase_step.active{
            background:#40A6FF;
            border-color:#40A6FF;
            color:#fff;
        }
        .payment_v2 .purchase_step_wrapper:not(:last-child)::after{
            content:"";
            position:absolute;
            left: calc(100% + 0px);
            top: 50%;
            transform: translateY(-50%);
            width: 122px;
            height: 0;
            border-top: 3px dashed #40A6FF;
            opacity: 1;
        }
        @media (max-width: 768px){
            .payment_v2 .tabs_links_container{ padding: 0 14px; }
            .payment_v2 .purchase_steps{
                justify-content:flex-start;
                gap: 12px;
                overflow-x:auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 6px;
            }
            .payment_v2 .purchase_step_wrapper{ width:auto !important; }
            .payment_v2 .purchase_step{
                height: 44px;
                font-size: 14px;
                padding: 0 18px;
                width: auto;
            }
            .payment_v2 .purchase_step_wrapper::after{ display:none !important; }
        }
    </style>
</head>

<body>
<div class="wrapper">
    <div class="header">
        <?php echo view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>

    <div class="content payment_v2">

        <div class="payment_v2__decor" aria-hidden="true">
            <svg class="payment_v2__path path1" viewBox="0 0 572 1829" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path class="payment_v2__dash"
                      d="M444.209 1.98722C444.209 1.98722 219.722 27.4398 135.756 142.505C-39.6686 382.902 599.912 507.562 540.994 818.843C486.312 1107.74 45.4024 936.525 4.07039 1228.8C-44.1303 1569.64 570.887 1826.66 570.887 1826.66"/>
            </svg>

            <svg class="payment_v2__path path2" viewBox="0 0 572 1829" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g transform="translate(572 0) scale(-1 1)">
                    <path class="payment_v2__dash"
                          d="M444.209 1.98722C444.209 1.98722 219.722 27.4398 135.756 142.505C-39.6686 382.902 599.912 507.562 540.994 818.843C486.312 1107.74 45.4024 936.525 4.07039 1228.8C-44.1303 1569.64 570.887 1826.66 570.887 1826.66"/>
                </g>
            </svg>

            <div class="payment_v2__pin pin_left">
                <img class="payment_v2__pin_icon" src="<?php echo asset('images/booking/pin.png'); ?>" alt="">
            </div>

            <div class="payment_v2__pin pin_right">
                <img class="payment_v2__pin_icon" src="<?php echo asset('images/booking/pin.png'); ?>" alt="">
            </div>

            <div class="payment_v2__bus_wrap" aria-hidden="true">
                <img class="payment_v2__bus" src="<?php echo asset('images/booking/bus.png'); ?>" alt="">
            </div>
        </div>

        <div class="payment_v2__content">
            <div class="main_filter_wrapper">
                <div class="container"></div>
            </div>

            <div class="purchase_steps_wrapper">
                <div class="tabs_links_container">
                    <div class="purchase_steps">
                        <div class="purchase_step_wrapper">
                            <div class="purchase_step">1. <?php echo $GLOBALS['dictionary']['MSG_MSG_TICKETS_VIBIR_AVTOBUSA']; ?></div>
                        </div>

                        <div class="purchase_step_wrapper">
                            <div class="purchase_step">2. <?php echo $Router->writetitle(85); ?></div>
                        </div>

                        <div class="purchase_step_wrapper">
                            <div class="purchase_step active">3. <?php echo $Router->writetitle(86); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page_content_wrapper">
                <div class="container">
                    <?php
                    // -----------------------------
                    // Ticket info (как было)
                    // -----------------------------
                    $ticketInfo = $Db->getOne(" SELECT
                        from_stop.departure_time AS departure_time,
                        from_city.title_" . $Router->lang . " AS departure_station,
                        departure_city.title_" . $Router->lang . " AS departure_city,
                        to_stop.arrival_time AS arrival_time,
                        to_city.title_" . $Router->lang . " AS arrival_station,
                        arrival_city.title_" . $Router->lang . " AS arrival_city,
                        bus.title_" . $Router->lang . " AS bus,
                        prices.price AS price
                    FROM `" . DB_PREFIX . "_tours_stops`AS from_stop
                        JOIN `" . DB_PREFIX . "_cities`AS from_city ON from_stop.stop_id = from_city.id
                        JOIN `" . DB_PREFIX . "_tours`AS tours ON from_stop.tour_id = tours.id
                        JOIN `" . DB_PREFIX . "_cities`AS departure_city ON departure_city.id = tours.departure
                        JOIN `" . DB_PREFIX . "_tours_stops`AS to_stop ON from_stop.tour_id = to_stop.tour_id
                        JOIN `" . DB_PREFIX . "_cities`AS to_city ON to_stop.stop_id = to_city.id
                        JOIN `" . DB_PREFIX . "_cities`AS arrival_city ON arrival_city.id = tours.arrival
                        JOIN `" . DB_PREFIX . "_buses`AS bus ON tours.bus = bus.id
                        JOIN `" . DB_PREFIX . "_tours_stops_prices`AS prices ON
                                prices.tour_id = from_stop.tour_id AND
                                prices.from_stop = from_stop.stop_id AND
                                prices.to_stop = to_stop.stop_id
                        WHERE from_stop.tour_id = '" . (int)($_SESSION['order']['tour_id'] ?? 0) . "'
                        AND from_stop.stop_id = '" . (int)($_SESSION['order']['from'] ?? 0) . "'
                        AND to_stop.stop_id = '" . (int)($_SESSION['order']['to'] ?? 0) . "'
                    ");

                    // -----------------------------
                    // Аккуратная нормализация passengers в сессии (самоисправление)
                    // Логика: если в сессии есть массивы доп.пассажиров — используем их как "факт",
                    // и ТОЛЬКО УМЕНЬШАЕМ order['passengers'] (никогда не увеличиваем), чтобы не сломать резерв.
                    // -----------------------------
                    $countFilledRows = function ($arr) {
                        if (!is_array($arr)) return 0;
                        $cnt = 0;
                        foreach ($arr as $row) {
                            if (!is_array($row)) continue;
                            $hasAny = false;
                            foreach ($row as $v) {
                                if (is_array($v)) continue;
                                $s = trim((string)$v);
                                if ($s !== '') { $hasAny = true; break; }
                            }
                            if ($hasAny) $cnt++;
                        }
                        return $cnt;
                    };

                    $orderPassengers = (int)($_SESSION['order']['passengers'] ?? 1);
                    if ($orderPassengers < 1) $orderPassengers = 1;

                    $derivedPassengers = null;

                    // 1) если есть явные extra в order — это самый “логичный” источник
                    if (isset($_SESSION['order']['passengers_extra']) && is_array($_SESSION['order']['passengers_extra'])) {
                        $extraFilled = $countFilledRows($_SESSION['order']['passengers_extra']);
                        if ($extraFilled > 0) {
                            $derivedPassengers = 1 + $extraFilled;
                        }
                    }

                    // 2) иначе — если remember_private_data писал passenger_data пасс-ров
                    if ($derivedPassengers === null && isset($_SESSION['passenger_data']['passengers']) && is_array($_SESSION['passenger_data']['passengers'])) {
                        $extraFilled = $countFilledRows($_SESSION['passenger_data']['passengers']);
                        if ($extraFilled > 0) {
                            $derivedPassengers = 1 + $extraFilled;
                        }
                    }

                    // Клэмп и правило “только уменьшаем”
                    $finalPassengers = max(1, min(10, $orderPassengers));
                    if ($derivedPassengers !== null) {
                        $derivedPassengers = max(1, min(10, (int)$derivedPassengers));
                        $finalPassengers = min($finalPassengers, $derivedPassengers);
                    }

                    $_SESSION['order']['passengers'] = $finalPassengers;

                    // -----------------------------
                    // Date / Month (как было, но без notice)
                    // -----------------------------
                    $paymentDateTime = '';
                    $month = null;

                    $rawDate = (string)($_SESSION['order']['date'] ?? '');
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
                        $monthId = (int)substr($rawDate, 5, 2);
                        $dayNum  = (int)substr($rawDate, 8, 2);

                        $month = $Db->getOne("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_months` WHERE id = '" . $monthId . "' ");
                        $monthTitle = $month['title'] ?? '';

                        $paymentDateTime = $dayNum . ' ' . $monthTitle . ' ' . date('H:i', strtotime($ticketInfo['departure_time'] ?? '00:00'));
                    }

                    // -----------------------------
                    // Итоговая цена (ВАЖНО: берём уже нормализованное passengers)
                    // -----------------------------
                    $unitPrice  = (int)($ticketInfo['price'] ?? 0);
                    $totalPrice = (int)($_SESSION['order']['passengers'] ?? 1) * $unitPrice;

                    $bonusEligible = false;
                    $bonusBalanceCents = 0;
                    $bonusUseRequested = (int)($_SESSION['order']['use_bonus'] ?? 0);
                    $bonusRedeemCents = 0;

                    if (isset($User->id) && (int)$User->id > 0) {
                        $bonusEligible = true;
                        $bonusRow = $Db->getOne("SELECT bonus_balance_cents FROM `" . DB_PREFIX . "_clients` WHERE id = '" . (int)$User->id . "'");
                        $bonusBalanceCents = (int)($bonusRow['bonus_balance_cents'] ?? 0);
                    }

                    $payableCents = $totalPrice * 100;
                    if ($bonusEligible && $bonusUseRequested && $payableCents > 0) {
                        $bonusService = app(\App\Services\BonusService::class);
                        $bonusRedeemCents = $bonusService->calculateMaxRedeemCents($bonusBalanceCents, $payableCents);
                    }
                    $payableAfterBonusCents = max(0, $payableCents - $bonusRedeemCents);
                    $displayTotalPrice = $bonusUseRequested ? ($payableAfterBonusCents / 100) : $totalPrice;
                    ?>

                    <div class="payment_v2__card shadow_block">
                        <div class="payment_v2__title">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OPLATA'] ?? 'Оплата'; ?>
                        </div>

                        <div class="payment_v2__sub">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI']; ?>
                        </div>

                        <div class="payment_v2__methods">

                            <label class="pv2_method">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="true"
                                       value="cardpay"
                                       checked>
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    <?php echo $GLOBALS['dictionary']['PAYMENT_LIQPAY'] ?? __('dictionary.PAYMENT_LIQPAY'); ?>
                                </span>

                                <span class="pv2_method_logo">
                                    <img src="<?php echo asset('images/legacy/common/bank_card.svg'); ?>" alt="bank card">
                                </span>

                                <span class="pv2_method_price" data-role="payable-price">
                                    <?php echo $displayTotalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                            <label class="pv2_method">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="false"
                                       value="monobank">
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    <?php echo $GLOBALS['dictionary']['PAYMENT_MONOPAY'] ?? __('dictionary.PAYMENT_MONOPAY'); ?>
                                </span>

                                <span class="pv2_method_logo pv2_method_logo--mono" aria-hidden="true">
                                    <span class="mono_badge">mono</span>
                                </span>

                                <span class="pv2_method_price" data-role="payable-price">
                                    <?php echo $displayTotalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                            <label class="pv2_method">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="false"
                                       value="cash">
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    <?php echo $GLOBALS['dictionary']['PAYMENT_CASH'] ?? __('dictionary.PAYMENT_CASH'); ?>
                                </span>

                                <span class="pv2_method_logo">
                                    <img src="<?php echo asset('images/legacy/common/cash.svg'); ?>" alt="cash">
                                </span>

                                <span class="pv2_method_price" data-role="payable-price">
                                    <?php echo $displayTotalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                        </div>

                        <div class="payment_v2__logos">
                            <img src="<?php echo asset('images/legacy/common/mastercard.svg'); ?>" alt="mastercard">
                            <img src="<?php echo asset('images/legacy/common/maestro.svg'); ?>" alt="maestro">
                            <img src="<?php echo asset('images/legacy/common/visa.svg'); ?>" alt="visa">
                        </div>

                        <?php if ($bonusEligible) { ?>
                            <div class="payment_v2__bonus" data-bonus-balance-cents="<?php echo $bonusBalanceCents; ?>">
                                <div class="payment_v2__bonus_row">
                                    <span>Бонусный баланс:</span>
                                    <strong><?php echo number_format($bonusBalanceCents / 100, 2, '.', ''); ?> грн</strong>
                                </div>
                                <label class="pv2_check payment_v2__bonus_check">
                                    <input type="checkbox" hidden id="use_bonus" <?php echo $bonusUseRequested ? 'checked' : ''; ?>>
                                    <span class="pv2_box"></span>
                                    <span class="pv2_check_text">Рассчитаться бонусами (до 20% от оплаты)</span>
                                </label>
                                <div class="payment_v2__bonus_row">
                                    <span>Списано бонусами:</span>
                                    <strong><span id="bonus_redeem_amount"><?php echo number_format($bonusRedeemCents / 100, 2, '.', ''); ?></span> грн</strong>
                                </div>
                                <div class="payment_v2__bonus_row">
                                    <span>К оплате с бонусами:</span>
                                    <strong><span id="bonus_payable_amount"><?php echo number_format($payableAfterBonusCents / 100, 2, '.', ''); ?></span> грн</strong>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="payment_v2__checks">
                            <label class="pv2_check">
                                <input type="checkbox" hidden id="terms_accept">
                                <span class="pv2_box"></span>
                                <span class="pv2_check_text">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_YA_PRIJMAYU_UMOVI']; ?>
                                    <a href="<?php echo $Router->writelink(84); ?>" class="small_link"><?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_PUBLICHNO_OFERTI']; ?></a>,
                                    <a href="<?php echo $Router->writelink(83); ?>" class="small_link"><?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_POLITIKI_KONFIDENCIJNOSTI']; ?></a>
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_I']; ?>
                                    <a href="<?php echo $Router->writelink(87); ?>" class="small_link"><?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_POVERNENNYA']; ?></a>
                                </span>
                            </label>

                            <label class="pv2_check">
                                <input type="checkbox" hidden id="personal_data_process">
                                <span class="pv2_box"></span>
                                <span class="pv2_check_text">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_YA_DAYU_ZGODU_NA_OBROBKU_PERSONALINIH_DANIH']; ?>
                                </span>
                            </label>
                        </div>

                        <button class="payment_v2__btn" id="orderTicket">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OPLATITI']; ?>
                        </button>
                    </div>

                    <div class="payment_v2_hide">
                        <div class="route_block">
                            <!-- старый route_block если нужно -->
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="footer">
        <?php echo view('layout.components.footer.footer', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
</div>

<?php echo view('layout.components.footer.footer_scripts', [
    'page_data' => $page_data,
])->render(); ?>

<script src="<?php echo mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>

<script>
    $(document).ready(function () {

        $('#card_number').mask("9999 9999 9999 9999");
        $('#card_valid_date').mask("99/99");
        $('#card_cvv').mask("999");

        function deleteOrderTourId() {
            $.ajax({
                type: 'post',
                url: '/ajax/ru',
                data: {
                    'request': 'delete_order_tour_id'
                }
            });
        }

        var ticketInfo = <?php echo json_encode($ticketInfo); ?>;
        // ВАЖНО: order сериализуем ПОСЛЕ нормализации passengers в PHP выше
        var order = <?php echo json_encode($_SESSION['order']); ?>;
        var payableCents = <?php echo (int)$payableCents; ?>;
        var bonusBalanceCents = <?php echo (int)$bonusBalanceCents; ?>;
        var bonusRedeemCents = <?php echo (int)$bonusRedeemCents; ?>;
        var bonusUseRequested = <?php echo $bonusUseRequested ? 'true' : 'false'; ?>;
        var totalPrice = <?php echo (float)$displayTotalPrice; ?>;

        function calculateMaxRedeemCents(balance, payable) {
            return Math.min(balance, payable, Math.floor(payable * 0.2));
        }

        function formatUah(cents) {
            var value = (cents / 100);
            var formatted = value.toFixed(2);
            return formatted.replace(/\.00$/, '');
        }

        function updatePayableUi(redeemCents) {
            var payableAfterCents = Math.max(payableCents - redeemCents, 0);
            totalPrice = payableAfterCents / 100;

            $('.pv2_method_price[data-role="payable-price"]').text(totalPrice + ' <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>');
            $('#bonus_redeem_amount').text(formatUah(redeemCents));
            $('#bonus_payable_amount').text(formatUah(payableAfterCents));
        }

        function syncBonusSession(useBonus) {
            return $.ajax({
                type: 'post',
                url: '/ajax/ru',
                data: {
                    'request': 'bonus_preview',
                    'use_bonus': useBonus ? 1 : 0,
                    'payable_cents': payableCents
                }
            });
        }

        if (bonusUseRequested && bonusBalanceCents > 0) {
            updatePayableUi(bonusRedeemCents);
        }

        $('#use_bonus').on('change', function () {
            var useBonus = $(this).is(':checked');
            if (!useBonus) {
                updatePayableUi(0);
                syncBonusSession(false);
                return;
            }

            var calculated = calculateMaxRedeemCents(bonusBalanceCents, payableCents);
            syncBonusSession(true).done(function (response) {
                var redeemCents = parseInt(response && response.redeem_cents ? response.redeem_cents : calculated, 10);
                updatePayableUi(redeemCents);
            }).fail(function () {
                updatePayableUi(0);
            });
        });

        $('#orderTicket').click(function (){
            let card_number = $.trim($('#card_number').val());
            let card_valid_date = $.trim($('#card_valid_date').val());
            let card_cvv = $.trim($('#card_cvv').val());
            let cardholder_name = $.trim($('#cardholder_name').val());
            let saveCard = 0;
            let paymethod = $('input[name="paymethod"]:checked').val();

            if ($('#save_card').is(':checked')) {
                saveCard = 1;
            }

            initLoader();

            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'order_route',
                    'paymethod': paymethod,
                    'card_number': card_number,
                    'card_valid_date': card_valid_date,
                    'card_cvv': card_cvv,
                    'cardholder_name': cardholder_name,
                    'save_card': saveCard,
                    'ticket_info': ticketInfo,
                    'order': order
                },
                success: function (response) {
                    removeLoader();

                    if ($.trim(response.data) == 'ok') {

                        if (paymethod === 'cash') {
                            $.ajax({
                                type: 'post',
                                url: '/ajax/ru',
                                data: {
                                    'request': 'order_mail',
                                    'ticket_info': ticketInfo,
                                    'order': order
                                }
                            });
                        }

                        if ($('input[name=paymethod]:checked').data('cardpay')) {
                            $.ajax({
                                type: 'post',
                                url: '/payment/legacy/create',
                                data: {
                                    'ticket_info': ticketInfo,
                                    'order': order,
                                    'total_price': totalPrice
                                },
                                success: function(paymentResponse) {
                                    if (paymentResponse.success) {
                                        var form = $('<form/>', {
                                            'method': 'POST',
                                            'action': paymentResponse.payment_url,
                                            'style': 'display:none'
                                        });

                                        form.append($('<input/>', {
                                            'type': 'hidden',
                                            'name': 'data',
                                            'value': paymentResponse.data
                                        }));

                                        form.append($('<input/>', {
                                            'type': 'hidden',
                                            'name': 'signature',
                                            'value': paymentResponse.signature
                                        }));

                                        $('body').append(form);
                                        form.submit();
                                    } else {
                                        removeLoader();
                                        out('Ошибка создания платежа: ' + paymentResponse.error);
                                    }
                                },
                                error: function() {
                                    removeLoader();
                                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                                }
                            });
                        } else {
                            location.href = '<?php echo $Router->writelink(90)?>';
                        }

                        deleteOrderTourId();

                    } else {
                        out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                    }
                },
                error: function () {
                    removeLoader();
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE')?>');
                }
            })
        });

        function initStepsSlick(){
            var isMobile = $(window).width() < 576;

            if (isMobile && !$('.purchase_steps').hasClass('slick-initialized')) {
                $('.purchase_steps').slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    dots: false,
                    arrows: false,
                    infinite: false,
                    variableWidth: true
                });
                $('.purchase_steps').slick('slickGoTo', 2, true);
            }

            if (!isMobile && $('.purchase_steps').hasClass('slick-initialized')) {
                $('.purchase_steps').slick('unslick');
            }
        }

        initStepsSlick();
        $(window).on('resize', initStepsSlick);

        $('input[name=paymethod]').on('change', function () {
            if ($(this).data('cardpay')) {
                $('.payment_data').show();
            } else {
                $('.payment_data').hide();
            }
        });

        function initLoader() {
            $('body').prepend('<div class="loader"></div>');
        }

        function removeLoader() {
            $('.loader').remove();
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

        }
    });
</script>

</body>
</html>
