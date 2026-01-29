<?php
/**
 * payment_page.php (legacy view)
 * — FIX: стабильный старт сессии
 * — FIX: order_route / order_events / delete_order_tour_id / order_mail -> через PaymentPageController@ajax (/ajax/ru)
 * — FIX: /payment/legacy/create -> PaymentPageController@createLegacyPayment (JSON)
 * — ADD: mt_order_events tail (request=order_events) + автотрейс по order_db_id
 * — FIX: cash: ждём ответ order_mail + успеваем забрать события, потом редирект
 * — FIX: единая защита от дабл-клика / корректный unlock
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['order']['tour_id'])) {
    header('Location:' . route('main'));
    exit;
}

Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
Header("Cache-Control: no-cache, must-revalidate");
Header("Pragma: no-cache");
Header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
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

            /* Figma: box-shadow: 0px 2px 25px rgba(53, 186, 240, 0.15) inset */
            box-shadow: inset 0px 2px 25px rgba(53, 186, 240, 0.15) !important;

            padding: 40px; /* Figma: отступы внутри по 40px (лево/верх видно в макете) */
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
            border:2px solid #BFC6C8; /* по умолчанию серый контур */
            position:relative;
            display:inline-block;
            box-sizing:border-box;
        }
        .payment_v2 .pv2_method input:checked + .pv2_radio{
            border-color:#EB5757; /* красный контур */
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

        .payment_v2 .pv2_method_price{
            font-family: Montserrat,system-ui;
            font-weight: 800;
            font-size: 14px;
            color:#303233;
            white-space:nowrap;
        }

        /* ✅ MONOBANK: "logo" в виде чёрного бейджа mono (самодостаточно, без файла) */
        .payment_v2 .pv2_method_logo--mono{
            width: 26px;
            height: 18px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .payment_v2 .mono_badge{
            width: 26px;
            height: 18px;
            border-radius: 4px;
            background:#000;
            color:#fff;
            font-family: Montserrat,system-ui;
            font-weight: 900;
            font-size: 9px;
            letter-spacing: .2px;
            display:flex;
            align-items:center;
            justify-content:center;
            line-height: 1;
            text-transform: lowercase;
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

        /* Чекбоксы (квадраты с голубой рамкой как на скрине) */
        .payment_v2 .payment_v2__checks{
            display:flex;
            flex-direction:column;
            gap:10px;
            margin-top: 10px;
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

        /* Кнопка "Оплатить" как на макете */
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

            /* ✅ чтобы можно было красиво переключать на mono */
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
            transition: background .18s ease, box-shadow .18s ease, opacity .18s ease;
        }

        /* ✅ MONO режим кнопки: черная как в бренд-стиле mono */
        .payment_v2 .payment_v2__btn.pv2_btn_mono_mode{
            background:#000;
            box-shadow: 0 10px 18px rgba(0,0,0,.18);
        }
        .payment_v2 .payment_v2__btn.pv2_btn_mono_mode:hover{
            opacity:.92;
        }
        .payment_v2 .payment_v2__btn .pv2_btn_mono_badge{
            display:none;
            width: 34px;
            height: 20px;
            border-radius: 6px;
            background:#000;
            color:#fff;
            border: 1px solid rgba(255,255,255,.18);
            font-family: Montserrat,system-ui;
            font-weight: 900;
            font-size: 10px;
            letter-spacing:.2px;
            align-items:center;
            justify-content:center;
            line-height:1;
            text-transform: lowercase;
        }
        .payment_v2 .payment_v2__btn.pv2_btn_mono_mode .pv2_btn_mono_badge{
            display:inline-flex;
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

        /* подгонка как на макете */
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

        /* позиции пинов (можешь подкрутить 2 числа) */
        .payment_v2__pin.pin_left{
            left: 85px;
            top: 320px;
        }
        .payment_v2__pin.pin_right{
            right: 110px;
            top: 430px;
        }

        /* BUS (как на бронировании) */
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

            /* Если хочешь СТАТИЧНЫЙ автобус как на скрине — раскомментируй следующую строку и закомментируй animation ниже */
            /* animation: none !important; */

            animation: bus-drive var(--bus-duration) linear infinite;
            will-change: transform;
        }
        .payment_v2__bus{
            width: 100%;
            height: auto;
            display: block;

            /* статично — можно убрать */
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

        /* MOBILE: сделать шаги компактнее как на мобиле в бронировании */
        @media (max-width: 520px){
            .payment_v2{
                background:#F3FAFF !important;
            }
            .payment_v2 .container{
                max-width: 420px !important;
                padding-left: 14px !important;
                padding-right: 14px !important;
            }
            .payment_v2 .purchase_steps{
                gap: 10px !important;
            }
            .payment_v2 .purchase_step{
                height: 24px !important;
                width: auto !important;
                padding: 0 10px !important;
                border-radius: 999px !important;
                border: 2px dashed #40A6FF !important;
                font-size: 9px !important;
            }
            .payment_v2 .purchase_step.active{
                border-style: solid !important;
            }
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

            .payment_v2 .payment_v2__card{
                max-width: 100% !important;
            }

            .payment_v2 .pv2_method{
                grid-template-columns: 18px 1fr 28px auto;
                padding: 10px 10px;
            }
        }

        /* Вспомогательное: скрыть старые блоки, если оставим в DOM */
        .payment_v2 .payment_v2_hide{
            display:none !important;
        }
        .header{
            padding: 0px 0;
        }
        @media (max-width: 920px){
            .payment_v2 .payment_v2__card.shadow_block{
                max-width: 100%;
                min-height: auto;   /* на мобилке высота по контенту */
                padding: 18px 16px;
                border-width: 2px;  /* визуально аккуратнее на маленьких экранах */
                border-radius: 14px;
            }
        }
        /* ==========================================================
           PURCHASE STEPS (match Figma)
           ========================================================== */

        .payment_v2 .tabs_links_container{
            box-shadow: none !important; /* чтобы не мешал внешний стиль темы */
            background: transparent !important;
        }

        /* Ширины как в Figma:
           1) 273px
           2) 327px
           3) 169px */
        .payment_v2 .purchase_step_wrapper:nth-child(1) .purchase_step{
            width: 273px;
        }
        .payment_v2 .purchase_step_wrapper:nth-child(2) .purchase_step{
            width: 327px;
        }
        .payment_v2 .purchase_step_wrapper:nth-child(3) .purchase_step{
            width: 169px;
        }

        /* MOBILE steps: компактно, но в стиле макета */
        @media (max-width: 520px){
            .payment_v2 .purchase_steps{
                justify-content: flex-start; /* slick сам даст горизонтальный скролл */
            }

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

        /* =========================
           PURCHASE STEPS — как на фото (белая плашка + пунктир)
           ========================= */

        /* Белая плашка с отступами */
        .payment_v2 .purchase_steps_wrapper{
            margin: 0 0 38px;
            background:#fff;
            padding: 26px 0 30px;               /* отступы внутри белой плашки */
            box-shadow: 0 10px 24px rgba(53,186,240,.12); /* лёгкая тень вниз как на скрине */
        }

        /* Убираем возможные стили темы у контейнера вокруг */
        .payment_v2 .tabs_links_container{
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 40px;                    /* боковые отступы внутри белой плашки */
        }

        /* Ровная центровка трёх “пилюль” */
        .payment_v2 .purchase_steps{
            display:flex !important;
            align-items:center;
            gap: 43px;                         /* расстояние под пунктир */
            flex-wrap:nowrap;
        }

        /* Каждый шаг — позиционирование для псевдоэлемента-пунктира */
        .payment_v2 .purchase_step_wrapper{
            position: relative;
            flex: 0 0 auto;
        }

        /* Ширины как в дизайне (1:273, 2:327, 3:169) */
        .payment_v2 .purchase_step_wrapper:nth-child(1){ width: 273px; }
        .payment_v2 .purchase_step_wrapper:nth-child(2){ width: 327px; }
        .payment_v2 .purchase_step_wrapper:nth-child(3){ width: 169px; }

        /* Пилюля */
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

        /* Активная (синяя) */
        .payment_v2 .purchase_step.active{
            background:#40A6FF;
            border-color:#40A6FF;
            color:#fff;
        }

        /* Пунктир между шагами (как на фото) */
        .payment_v2 .purchase_step_wrapper:not(:last-child)::after{
            content:"";
            position:absolute;
            left: calc(100% + 0px);
            top: 50%;
            transform: translateY(-50%);
            width: 122px;
            height: 0;
            border-top: 3px dashed #40A6FF;   /* пунктир */
            opacity: 1;
        }

        /* Мобилка: делаем компактно и убираем пунктир (иначе ломает) */
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

            .payment_v2 .purchase_step_wrapper::after{
                display:none !important;
            }
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
                    // ticketInfo / price
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
                        WHERE from_stop.tour_id = '" . (int)$_SESSION['order']['tour_id'] . "'
                        AND from_stop.stop_id = '" . (int)$_SESSION['order']['from'] . "'
                        AND to_stop.stop_id = '" . (int)$_SESSION['order']['to'] . "'
                    ");

                    $month = $Db->getOne("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_months`WHERE id = '" . (int)explode('-', $_SESSION['order']['date'])[1] . "' ");
                    $paymentDateTime = (int)explode('-', $_SESSION['order']['date'])[2] . ' ' . $month['title'] . ' ' . date('H:i', strtotime($ticketInfo['departure_time']));
                    $totalPrice = (int)($_SESSION['order']['passengers'] ?? 1) * (float)($ticketInfo['price'] ?? 0);
                    $totalPrice = (int)round($totalPrice);
                    ?>

                    <div class="payment_v2__card shadow_block">
                        <div class="payment_v2__title">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OPLATA'] ?? 'Оплата'; ?>
                        </div>

                        <div class="payment_v2__sub">
                            <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI']; ?>
                        </div>

                        <div class="payment_v2__methods">

                            <!-- CARD (LiqPay) -->
                            <label class="pv2_method">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="true"
                                       value="cardpay">
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA']; ?>
                                </span>

                                <span class="pv2_method_logo">
                                    <img src="<?php echo asset('images/legacy/common/bank_card.svg'); ?>" alt="bank card">
                                </span>

                                <span class="pv2_method_price">
                                    <?php echo $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                            <!-- CASH -->
                            <label class="pv2_method">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="false"
                                       value="cash"
                                       checked>
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GOTIVKOYU']; ?>
                                </span>

                                <span class="pv2_method_logo">
                                    <img src="<?php echo asset('images/legacy/common/cash.svg'); ?>" alt="cash">
                                </span>

                                <span class="pv2_method_price">
                                    <?php echo $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                            <!-- MONOBANK -->
                            <label class="pv2_method pv2_method--mono">
                                <input type="radio"
                                       name="paymethod"
                                       hidden
                                       data-cardpay="false"
                                       value="monobank">
                                <span class="pv2_radio"></span>

                                <span class="pv2_method_name">
                                    monobank (mono)
                                </span>

                                <span class="pv2_method_logo pv2_method_logo--mono" aria-hidden="true">
                                    <span class="mono_badge">mono</span>
                                </span>

                                <span class="pv2_method_price">
                                    <?php echo $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                        </div>

                        <div class="payment_v2__logos">
                            <img src="<?php echo asset('images/legacy/common/mastercard.svg'); ?>" alt="mastercard">
                            <img src="<?php echo asset('images/legacy/common/maestro.svg'); ?>" alt="maestro">
                            <img src="<?php echo asset('images/legacy/common/visa.svg'); ?>" alt="visa">
                        </div>

                        <div class="payment_v2__checks">
                            <label class="pv2_check">
                                <input type="checkbox" hidden id="terms_accept" checked>
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
                                <input type="checkbox" hidden id="personal_data_process" checked>
                                <span class="pv2_box"></span>
                                <span class="pv2_check_text">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_BOOKING_YA_DAYU_ZGODU_NA_OBROBKU_PERSONALINIH_DANIH']; ?>
                                </span>
                            </label>
                        </div>

                        <button type="button" class="payment_v2__btn" id="orderTicket">
                            <span class="pv2_btn_label"><?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OPLATITI']; ?></span>
                            <span class="pv2_btn_mono_badge" aria-hidden="true">mono</span>
                        </button>

                        <div id="liqpay_checkout_holder" style="display:none;"></div>
                    </div>

                    <div class="payment_v2_hide">
                        <div class="route_block">
                            <!-- legacy hidden -->
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

        // =========================
        // Masks (если такие поля реально есть в DOM)
        // =========================
        if ($('#card_number').length) $('#card_number').mask("9999 9999 9999 9999");
        if ($('#card_valid_date').length) $('#card_valid_date').mask("99/99");
        if ($('#card_cvv').length) $('#card_cvv').mask("999");

        // =========================
        // Globals from PHP
        // =========================
        var ticketInfo  = <?php echo json_encode($ticketInfo); ?>;
        var order       = <?php echo json_encode($_SESSION['order']); ?>;
        var totalPrice  = <?php echo (int)$totalPrice; ?>;

        // ✅ CSRF (если meta нет — не падаем)
        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content') || '';
        window.CSRF_TOKEN = CSRF_TOKEN; // важно: чтобы ORDER EVENTS tail мог брать CSRF

        // =========================
        // Helpers
        // =========================
        function escHtml(s){
            return String(s || '').replace(/[&<>"']/g, function(m){
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
            });
        }

        function safeJsonParse(raw) {
            if (raw === null || raw === undefined) return null;
            if (typeof raw === 'object') return raw;
            try { return JSON.parse(String(raw)); } catch (e) { return null; }
        }

        function dumpAjaxError(where, xhr, extra){
            try {
                console.group('[PAYMENT] ' + where + ' ERROR');
                console.log('HTTP:', xhr.status, xhr.statusText);
                if (extra) console.log('Extra:', extra);
                console.log('ResponseText:', xhr.responseText);
                console.groupEnd();
            } catch(e){}

            var body = xhr && xhr.responseText ? xhr.responseText : '';
            if (body.length > 12000) body = body.substring(0, 12000) + '\n...\n[truncated]';

            out(
                where + ' — HTTP ' + (xhr && xhr.status ? xhr.status : '???'),
                '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(body) + '</pre>'
            );
        }

        function dumpAjaxSuccess(where, response, textStatus, xhr, extra){
            try{
                console.group('[PAYMENT] ' + where + ' SUCCESS');
                console.log('textStatus:', textStatus);
                console.log('HTTP:', xhr.status, xhr.statusText);
                console.log('Content-Type:', xhr.getResponseHeader('content-type'));
                if (extra) console.log('Extra:', extra);
                console.log('typeof response:', typeof response);
                console.log('response:', response);
                console.log('xhr.responseText:', xhr.responseText);
                console.log('xhr.responseJSON:', xhr.responseJSON);
                console.groupEnd();
            }catch(e){}
        }

        function initLoader() {
            if (!$('.loader').length) $('body').prepend('<div class="loader"></div>');
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
            $(closeBtn).addClass('alert_ok').text((typeof window.close_btn !== 'undefined' && window.close_btn) ? window.close_btn : 'OK').appendTo(alertContent);

            $('body').append(alert);
            $(alert).fadeIn();

            $('.alert_ok,.alert_overlay').on('click', function () {
                $('.alert').fadeOut();
                setTimeout(function () {
                    $('.alert').remove();
                }, 350)
            });
        }

        function unlockPayFlow($btn){
            window.__paymentFlowInFlight = false;
            if ($btn && $btn.length) $btn.prop('disabled', false);
        }

        function deleteOrderTourId() {
            $.ajax({
                type: 'post',
                url: '/ajax/ru',
                dataType: 'json',
                timeout: 15000,
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: {
                    request: 'delete_order_tour_id'
                }
            });
        }

        // ================================
        // DEBUG: Full network trace (AJAX)
        // ================================
        (function(){
            window.PAY_DEBUG = true;

            function nowStr(){
                try { return new Date().toISOString(); } catch(e){ return ''+Date.now(); }
            }

            function trunc(s, n){
                s = (s === undefined || s === null) ? '' : String(s);
                if (s.length > n) return s.substring(0, n) + ' ...[truncated '+(s.length-n)+' chars]';
                return s;
            }

            function safeJsonTry(text){
                try { return JSON.parse(text); } catch(e){ return null; }
            }

            function normalizeAjaxData(data){
                try {
                    if (data instanceof FormData) {
                        var o = {};
                        data.forEach(function(v,k){ o[k]=v; });
                        return o;
                    }
                } catch(e){}

                if (typeof data === 'string') {
                    var obj = {};
                    try{
                        data.split('&').forEach(function(pair){
                            var p = pair.split('=');
                            var k = decodeURIComponent(p[0] || '');
                            var v = decodeURIComponent((p[1] || '').replace(/\+/g,' '));
                            if (!k) return;
                            obj[k] = v;
                        });
                        if (Object.keys(obj).length) return obj;
                    }catch(e){}
                    return data;
                }

                if (typeof data === 'object' && data !== null) {
                    try { return JSON.parse(JSON.stringify(data)); } catch(e){}
                    return data;
                }
                return data;
            }

            $(document).ajaxSend(function(event, jqXHR, settings){
                if (!window.PAY_DEBUG) return;

                var payload = normalizeAjaxData(settings.data);

                jqXHR.__paydbg = {
                    t0: Date.now(),
                    url: settings.url,
                    type: settings.type,
                    dataType: settings.dataType,
                    payload: payload
                };

                console.groupCollapsed('%c[AJAX SEND] ' + settings.type + ' ' + settings.url, 'color:#0b74de');
                console.log('time:', nowStr());
                console.log('dataType:', settings.dataType);
                console.log('payload:', payload);
                console.groupEnd();
            });

            $(document).ajaxSuccess(function(event, jqXHR, settings, response){
                if (!window.PAY_DEBUG) return;

                var meta = jqXHR.__paydbg || {};
                var ms = meta.t0 ? (Date.now()-meta.t0) : null;

                var ctype = '';
                try { ctype = jqXHR.getResponseHeader('content-type') || ''; } catch(e){}

                var raw = '';
                try { raw = jqXHR.responseText || ''; } catch(e){ raw=''; }

                var parsed = safeJsonTry(raw);

                console.groupCollapsed('%c[AJAX OK] ' + (settings.type||'') + ' ' + (settings.url||''), 'color:#17a34a');
                console.log('time:', nowStr(), 'duration_ms:', ms);
                console.log('status:', jqXHR.status, jqXHR.statusText);
                console.log('content-type:', ctype);
                console.log('response (jQuery arg):', response);
                console.log('responseText:', trunc(raw, 6000));
                if (parsed) console.log('parsed JSON:', parsed);
                console.groupEnd();
            });

            $(document).ajaxError(function(event, jqXHR, settings, thrownError){
                if (!window.PAY_DEBUG) return;

                var meta = jqXHR.__paydbg || {};
                var ms = meta.t0 ? (Date.now()-meta.t0) : null;

                var ctype = '';
                try { ctype = jqXHR.getResponseHeader('content-type') || ''; } catch(e){}

                var raw = '';
                try { raw = jqXHR.responseText || ''; } catch(e){ raw=''; }

                console.groupCollapsed('%c[AJAX ERR] ' + (settings.type||'') + ' ' + (settings.url||''), 'color:#dc2626');
                console.log('time:', nowStr(), 'duration_ms:', ms);
                console.log('status:', jqXHR.status, jqXHR.statusText);
                console.log('content-type:', ctype);
                console.log('thrownError:', thrownError);
                console.log('payload:', meta.payload);
                console.log('responseText:', trunc(raw, 12000));
                console.groupEnd();
            });

            window.addEventListener('error', function(e){
                if (!window.PAY_DEBUG) return;
                console.groupCollapsed('%c[JS ERROR] ' + (e.message || 'error'), 'color:#dc2626');
                console.log('time:', nowStr());
                console.log('message:', e.message);
                console.log('file:', e.filename);
                console.log('line:', e.lineno, 'col:', e.colno);
                console.log('error:', e.error);
                console.groupEnd();
            });

            window.addEventListener('unhandledrejection', function(e){
                if (!window.PAY_DEBUG) return;
                console.groupCollapsed('%c[PROMISE REJECTION]', 'color:#dc2626');
                console.log('time:', nowStr());
                console.log('reason:', e.reason);
                console.groupEnd();
            });
        })();

        // =========================================
        // ORDER EVENTS TAIL (mt_order_events -> console)
        // =========================================
        (function(){
            window.PAY_EVENTS_DEBUG = true;

            var trace = {
                orderId: 0,
                afterId: 0,
                timer: null,
                startedAt: 0
            };

            function nowStr(){
                try { return new Date().toISOString(); } catch(e){ return ''+Date.now(); }
            }

            function safeJsonParseAny(v){
                if (v === null || v === undefined) return null;
                if (typeof v === 'object') return v;
                var s = String(v);
                try { return JSON.parse(s); } catch(e){ return s; }
            }

            function colorByType(type){
                type = String(type || '');
                if (type.indexOf('failed') !== -1) return 'color:#dc2626';
                if (type.indexOf('success') !== -1 || type.indexOf('sent') !== -1) return 'color:#17a34a';
                if (type.indexOf('try') !== -1 || type.indexOf('received') !== -1) return 'color:#0b74de';
                if (type.indexOf('updated') !== -1) return 'color:#9333ea';
                return 'color:#111827';
            }

            function logEventRow(ev){
                var t = String(ev.type || '');
                var msg = String(ev.message || '');
                var payload = safeJsonParseAny(ev.payload);

                console.groupCollapsed('%c[ORDER EVENT] #' + ev.id + ' ' + t, colorByType(t));
                console.log('time:', nowStr());
                console.log('created_at:', ev.created_at);
                console.log('order_id:', ev.order_id);
                console.log('type:', t);
                console.log('message:', msg);
                console.log('payload:', payload);
                console.groupEnd();
            }

            function fetchEventsOnce(){
                if (!window.PAY_EVENTS_DEBUG) return;
                if (!trace.orderId) return;

                $.ajax({
                    type: 'post',
                    url: '/ajax/ru',
                    dataType: 'json',
                    timeout: 15000,
                    headers: window.CSRF_TOKEN ? { 'X-CSRF-TOKEN': window.CSRF_TOKEN } : {},
                    data: {
                        request: 'order_events',
                        order_id: trace.orderId,
                        after_id: trace.afterId
                    },
                    success: function(resp){
                        if (!resp || resp.ok !== true) {
                            console.groupCollapsed('%c[ORDER EVENTS] bad response', 'color:#dc2626');
                            console.log('time:', nowStr());
                            console.log('resp:', resp);
                            console.groupEnd();
                            return;
                        }

                        var events = resp.events || [];
                        if (!events.length) return;

                        for (var i=0; i<events.length; i++){
                            var ev = events[i];
                            if (ev && ev.payload && typeof ev.payload === 'string') {
                                ev.payload = safeJsonParseAny(ev.payload);
                            }
                            logEventRow(ev);
                            trace.afterId = Math.max(trace.afterId, parseInt(ev.id || 0, 10) || trace.afterId);
                        }
                    },
                    error: function(xhr, textStatus, errorThrown){
                        console.groupCollapsed('%c[ORDER EVENTS] ajax error', 'color:#dc2626');
                        console.log('time:', nowStr());
                        console.log('status:', xhr.status, xhr.statusText);
                        console.log('textStatus:', textStatus);
                        console.log('errorThrown:', errorThrown);
                        console.log('responseText:', (xhr && xhr.responseText) ? xhr.responseText : '');
                        console.groupEnd();
                    }
                });
            }

            function startTrace(orderId){
                if (!window.PAY_EVENTS_DEBUG) return;

                orderId = parseInt(orderId || 0, 10) || 0;
                if (!orderId) return;

                if (trace.orderId === orderId && trace.timer) return;

                stopTrace();

                trace.orderId = orderId;
                trace.afterId = 0;
                trace.startedAt = Date.now();

                console.groupCollapsed('%c[ORDER TRACE START] order_id=' + trace.orderId, 'color:#0b74de');
                console.log('time:', nowStr());
                console.groupEnd();

                fetchEventsOnce();
                trace.timer = setInterval(fetchEventsOnce, 1500);
            }

            function stopTrace(){
                if (trace.timer) {
                    clearInterval(trace.timer);
                    trace.timer = null;

                    console.groupCollapsed('%c[ORDER TRACE STOP]', 'color:#6b7280');
                    console.log('time:', nowStr());
                    console.log('orderId:', trace.orderId);
                    console.log('afterId:', trace.afterId);
                    console.groupEnd();
                }
            }

            window.PAY_TRACE = {
                start: startTrace,
                stop: stopTrace,
                fetchOnce: fetchEventsOnce,
                getState: function(){ return JSON.parse(JSON.stringify(trace)); }
            };

            window.addEventListener('beforeunload', function(){
                stopTrace();
            });
        })();

        // =========================
        // UI: mono button style
        // =========================
        var $payBtn = $('#orderTicket');
        var $payBtnLabel = $('#orderTicket .pv2_btn_label');
        var payBtnBaseText = ($payBtnLabel.text() || '').trim();

        function updatePayBtnUi(){
            var v = $('input[name="paymethod"]:checked').val();
            if (v === 'monobank') {
                $payBtn.addClass('pv2_btn_mono_mode');
                if (payBtnBaseText) $payBtnLabel.text(payBtnBaseText + ' (mono)');
            } else {
                $payBtn.removeClass('pv2_btn_mono_mode');
                if (payBtnBaseText) $payBtnLabel.text(payBtnBaseText);
            }
        }
        updatePayBtnUi();

        // =========================
        // LiqPay helpers
        // =========================
        function isHtmlLike(s){
            s = String(s || '').trim();
            if (!s) return false;
            return /<\s*html|<\s*form|<\s*input|<\s*script/i.test(s);
        }

        function extractFirstFormFromHtml(html){
            var $holder = $('#liqpay_checkout_holder');
            $holder.empty().html(html);
            var $form = $holder.find('form').first();
            return $form.length ? $form : null;
        }

        function submitForm($form){
            try {
                var action = ($form.attr('action') || '').trim();
                if (!action) return false;
                $form.attr('target', '_self');
                $form.css({display:'none'});
                $('body').append($form);
                $form.trigger('submit');
                return true;
            } catch(e){
                return false;
            }
        }

        function submitLiqpayCheckout(data, signature, actionUrl){
            actionUrl = actionUrl || 'https://www.liqpay.ua/api/3/checkout';
            if (!data || !signature) return false;

            var $form = $('<form>', {
                method: 'POST',
                action: actionUrl,
                acceptCharset: 'utf-8'
            });

            $form.append($('<input>', { type:'hidden', name:'data', value:String(data) }));
            $form.append($('<input>', { type:'hidden', name:'signature', value:String(signature) }));

            $('body').append($form);
            $form.submit();
            return true;
        }

        function pick(obj, keys){
            for (var i=0; i<keys.length; i++){
                var k = keys[i];
                if (obj && typeof obj === 'object' && obj[k] !== undefined && obj[k] !== null && String(obj[k]).trim() !== ''){
                    return obj[k];
                }
            }
            return null;
        }

        // Универсальная обработка ответа createLegacyPayment (JSON/HTML)
        function handleCardPaySuccess(rawResponse, parsed){
            // 1) HTML (если когда-то вернут формой)
            if (typeof rawResponse === 'string' && isHtmlLike(rawResponse)) {
                var $form = extractFirstFormFromHtml(rawResponse);
                if ($form) {
                    var okSubmit = submitForm($form);
                    if (okSubmit) return true;

                    out(
                        'LiqPay: форма найдена, но не удалось отправить',
                        '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(rawResponse) + '</pre>'
                    );
                    return false;
                }

                out(
                    'LiqPay: сервер вернул HTML, но форма не найдена',
                    '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(rawResponse) + '</pre>'
                );
                return false;
            }

            // 2) redirect_url
            var redirectUrl =
                pick(parsed, ['redirect_url','redirectUrl','payment_url','paymentUrl','checkout_url','checkoutUrl','url'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['redirect_url','payment_url','checkout_url','url']) : null);

            if (redirectUrl) {
                location.href = redirectUrl;
                return true;
            }

            // 3) data + signature
            var data =
                pick(parsed, ['liqpay_data','data_liqpay','liqpayData','data'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['data','liqpay_data','liqpayData']) : null);

            var signature =
                pick(parsed, ['liqpay_signature','signature_liqpay','liqpaySignature','signature'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['signature','liqpay_signature','liqpaySignature']) : null);

            // у createLegacyPayment: data/signature — это правильные поля
            var actionUrl =
                pick(parsed, ['liqpay_action','action','action_url','actionUrl','payment_url'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['action','action_url','actionUrl','payment_url']) : null);

            if (data && signature) {
                var okL = submitLiqpayCheckout(data, signature, actionUrl);
                if (okL) return true;
            }

            // 4) form_html/html
            var htmlForm =
                pick(parsed, ['form','form_html','payment_form','paymentForm','html'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['form','form_html','payment_form','html']) : null);

            if (htmlForm && isHtmlLike(htmlForm)) {
                var $f2 = extractFirstFormFromHtml(htmlForm);
                if ($f2) {
                    var okSubmit2 = submitForm($f2);
                    if (okSubmit2) return true;
                }
            }

            out(
                'Не удалось запустить LiqPay',
                '<div style="margin-bottom:8px;">Сервер не вернул ни redirect_url, ни data+signature, ни HTML-форму.</div>' +
                '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(JSON.stringify(parsed, null, 2)) + '</pre>'
            );
            return false;
        }

        function startLiqPayCheckout(orderRouteResp, $btn) {
            initLoader();

            var paymethodSelected = $('input[name="paymethod"]:checked').val() || 'cardpay';
            if (paymethodSelected === 'card') paymethodSelected = 'cardpay';

            if (order && typeof order === 'object') {
                order.paymethod = paymethodSelected;
            }

            var payload = {
                ticket_info: ticketInfo,
                order: order,
                total_price: totalPrice,
                paymethod: paymethodSelected,
                order_db_id: (orderRouteResp && (orderRouteResp.order_db_id || orderRouteResp.order_id)) ? (orderRouteResp.order_db_id || orderRouteResp.order_id) : '',
                uniqid: orderRouteResp && (orderRouteResp.uniqid || orderRouteResp.uniqId) ? (orderRouteResp.uniqid || orderRouteResp.uniqId) : ''
            };

            $.ajax({
                type: 'post',
                url: '/payment/legacy/create',
                dataType: 'json',          // ✅ контроллер возвращает JSON
                timeout: 20000,
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: payload,
                success: function (resp, textStatus, xhr) {
                    removeLoader();
                    dumpAjaxSuccess('/payment/legacy/create', resp, textStatus, xhr, { paymethod: paymethodSelected, totalPrice: totalPrice });

                    // resp уже объект
                    var pr = resp && typeof resp === 'object' ? resp : safeJsonParse(resp);

                    // если контроллер вернул success=false
                    if (pr && pr.success === false) {
                        unlockPayFlow($btn);
                        out(
                            'LiqPay: ошибка создания платежа',
                            '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(JSON.stringify(pr, null, 2)) + '</pre>'
                        );
                        return;
                    }

                    // запускаем liqpay
                    var ok = handleCardPaySuccess('', pr || {});
                    if (!ok) {
                        unlockPayFlow($btn);
                    } else {
                        // чтобы не плодились повторные заказы
                        deleteOrderTourId();
                    }
                },
                error: function (xhr, textStatus, errorThrown) {
                    removeLoader();
                    unlockPayFlow($btn);
                    dumpAjaxError('/payment/legacy/create', xhr, { textStatus: textStatus, errorThrown: errorThrown });
                }
            });
        }

        // =========================
        // Monobank redirect (как у тебя было)
        // =========================
        function startMonoCheckout(orderRouteResp, $btn){
            initLoader();

            var orderDbId = null;
            if (orderRouteResp && typeof orderRouteResp === 'object') {
                orderDbId = orderRouteResp.order_db_id || orderRouteResp.order_id || orderRouteResp.orderId || null;
            }

            if (!orderDbId) {
                removeLoader();
                unlockPayFlow($btn);
                out(
                    'Monobank: нет order_id',
                    'order_route вернул ok, но не вернул order_db_id / order_id.<br><br>' +
                    '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' + escHtml(JSON.stringify(orderRouteResp, null, 2)) + '</pre>'
                );
                return;
            }

            deleteOrderTourId();

            var url = '/payment/monobank/start/' + encodeURIComponent(orderDbId);

            var uniqid = orderRouteResp.uniqid || orderRouteResp.uniqId || '';
            if (uniqid) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + 'uniqid=' + encodeURIComponent(uniqid);
            }

            window.location.href = url;
        }

        // =========================
        // purchase_steps slick
        // =========================
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

        // =========================
        // show/hide payment_data if exists + update mono button
        // =========================
        $('input[name="paymethod"]').on('change', function () {
            if ($(this).data('cardpay')) {
                $('.payment_data').show();
            } else {
                $('.payment_data').hide();
            }
            updatePayBtnUi();
        });

        // =========================
        // Click handler (MAIN)
        // =========================
        window.__paymentFlowInFlight = false;

        $('#orderTicket').off('click.orderTicket').on('click.orderTicket', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (window.__paymentFlowInFlight) return;
            window.__paymentFlowInFlight = true;

            var $btn = $(this);
            $btn.prop('disabled', true);

            var $checked = $('input[name="paymethod"]:checked');
            var paymethod = $checked.val() || 'cash';
            var isCardPay = !!$checked.data('cardpay');

            if (order && typeof order === 'object') {
                order.paymethod = paymethod;
            }

            initLoader();

            $.ajax({
                type: 'post',
                url: '/ajax/ru',
                dataType: 'json',         // ✅ контроллер возвращает JSON
                timeout: 20000,
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: {
                    request: 'order_route',
                    paymethod: paymethod,

                    // если вдруг есть поля карты
                    card_number: $.trim($('#card_number').val()),
                    card_valid_date: $.trim($('#card_valid_date').val()),
                    card_cvv: $.trim($('#card_cvv').val()),
                    cardholder_name: $.trim($('#cardholder_name').val()),
                    save_card: $('#save_card').is(':checked') ? 1 : 0,

                    // оставим как было (контроллер игнорит — не мешает)
                    ticket_info: JSON.stringify(ticketInfo),
                    order: JSON.stringify(order)
                },

                success: function (resp, textStatus, xhr) {
                    removeLoader();
                    dumpAjaxSuccess('/ajax/ru (order_route)', resp, textStatus, xhr, { paymethod: paymethod, totalPrice: totalPrice });

                    var r = (resp && typeof resp === 'object') ? resp : safeJsonParse(resp);

                    console.groupCollapsed('%c[ORDER_ROUTE PARSED]', 'color:#f59e0b');
                    console.log('parsed r:', r);
                    console.log('ok condition:', (r && (r.data === 'ok' || r.status === 'ok' || r.result === 'ok')));
                    console.groupEnd();

                    var ok = (r && (r.data === 'ok' || r.status === 'ok' || r.result === 'ok'));

                    if (!ok) {
                        unlockPayFlow($btn);
                        out(
                            'order_route вернул НЕ ok',
                            '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' +
                            escHtml(JSON.stringify(r, null, 2)) +
                            '</pre>'
                        );
                        return;
                    }

                    // ✅ сразу стартуем tailer событий (чтобы поймать order_created/email_send_try/...)
                    var orderDbId = (r && (r.order_db_id || r.order_id)) ? (r.order_db_id || r.order_id) : 0;
                    if (orderDbId && window.PAY_TRACE) {
                        window.PAY_TRACE.start(orderDbId);
                    }

                    // ✅ CASH: ждём order_mail, потом редирект
                    if (paymethod === 'cash' && !isCardPay) {

                        $.ajax({
                            type: 'post',
                            url: '/ajax/ru',
                            dataType: 'json',
                            timeout: 20000,
                            headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                            data: {
                                request: 'order_mail',
                                ticket_info: JSON.stringify(ticketInfo),
                                order: JSON.stringify(order)
                            },
                            success: function(mailResp, tsMail, xhrMail){
                                dumpAjaxSuccess('/ajax/ru (order_mail)', mailResp, tsMail, xhrMail, { order_id: orderDbId });

                                // принудительно дернем ещё раз события, чтобы увидеть email_sent/email_failed
                                if (window.PAY_TRACE) {
                                    window.PAY_TRACE.fetchOnce();
                                }

                                setTimeout(function(){
                                    if (window.PAY_TRACE) window.PAY_TRACE.fetchOnce();

                                    deleteOrderTourId();
                                    window.location.href = '<?php echo $Router->writelink(90)?>';
                                }, 700);
                            },
                            error: function(xhrMail, textStatusMail, errorThrownMail){
                                removeLoader();
                                unlockPayFlow($btn);
                                dumpAjaxError('/ajax/ru (order_mail)', xhrMail, { textStatus: textStatusMail, errorThrown: errorThrownMail, order_id: orderDbId });
                            }
                        });

                        return;
                    }

                    // ✅ CARD (LiqPay)
                    if (isCardPay) {
                        startLiqPayCheckout(r, $btn);
                        return;
                    }

                    // ✅ MONOBANK
                    if (paymethod === 'monobank') {
                        startMonoCheckout(r, $btn);
                        return;
                    }

                    // fallback
                    deleteOrderTourId();
                    window.location.href = '<?php echo $Router->writelink(90)?>';
                },

                error: function (xhr, textStatus, errorThrown) {
                    removeLoader();
                    unlockPayFlow($btn);
                    dumpAjaxError('/ajax/ru (order_route)', xhr, { textStatus: textStatus, errorThrown: errorThrown, paymethod: paymethod, totalPrice: totalPrice });
                }
            });
        });

    });
</script>

</body>
</html>
