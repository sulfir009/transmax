<?php
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
        .payment_v2 .pv2_bonus_meta{
            display:block;
            margin-top: 4px;
            font-weight: 500;
            color:#8A8E90;
        }
        .payment_v2 .pv2_bonus_meta span{
            font-weight: 700;
            color:#303233;
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
                    $totalPrice = $_SESSION['order']['passengers'] * $ticketInfo['price'];
                    $bonusBalanceCents = 0;
                    if (isset($User) && !empty($User->id)) {
                        $bonusRow = $Db->getOne("SELECT bonus_balance_cents FROM `" . DB_PREFIX . "_clients` WHERE id = '" . (int)$User->id . "' ");
                        $bonusBalanceCents = (int)($bonusRow['bonus_balance_cents'] ?? 0);
                    }
                    $bonusesAvailable = $bonusBalanceCents / 100;
                    $bonusUseRequested = (int)($_SESSION['order']['use_bonus'] ?? 0);
                    $bonusRedeemCentsPreview = (int)($_SESSION['order']['bonus_redeem_cents_preview'] ?? 0);
                    $bonusToApply = $bonusUseRequested ? min($totalPrice, ($bonusRedeemCentsPreview / 100)) : 0;
                    $totalPriceWithBonuses = $bonusUseRequested ? max($totalPrice - $bonusToApply, 0) : $totalPrice;
                    $formatBonusValue = function ($value) {
                        $formatted = number_format((float)$value, 2, '.', '');
                        return rtrim(rtrim($formatted, '0'), '.');
                    };
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

                                <span class="pv2_method_price" data-base-price="<?php echo $totalPrice; ?>" data-bonus-price="<?php echo $totalPriceWithBonuses; ?>">
                                    <?php echo $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                </span>
                            </label>

                            <label class="pv2_method pv2_method--mono">
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

                                <span class="pv2_method_price" data-base-price="<?php echo $totalPrice; ?>" data-bonus-price="<?php echo $totalPriceWithBonuses; ?>">
                                    <?php echo $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
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

                                <span class="pv2_method_price" data-base-price="<?php echo $totalPrice; ?>" data-bonus-price="<?php echo $totalPriceWithBonuses; ?>">
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
                                <input type="checkbox" hidden id="use_bonuses" <?php echo $bonusBalanceCents > 0 ? '' : 'disabled'; ?> <?php echo $bonusUseRequested ? 'checked' : ''; ?>>
                                <span class="pv2_box"></span>
                                <span class="pv2_check_text">
                                    <?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_USE_BONUSES'] ?? 'Использовать бонусы'; ?>
                                    <span class="pv2_bonus_meta">
                                        <?php echo ($GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_BONUSES_AVAILABLE'] ?? 'Доступно бонусов') . ': '; ?>
                                        <span id="bonuses_available_value"><?php echo $formatBonusValue($bonusesAvailable); ?></span>
                                    </span>
                                    <span class="pv2_bonus_meta">
                                        <?php echo ($GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_TOTAL_WITH_BONUSES'] ?? 'К оплате с бонусами') . ': '; ?>
                                         <span id="bonuses_total_value"><?php echo $formatBonusValue($totalPriceWithBonuses); ?></span>
                                        <?php echo ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']; ?>
                                    </span>
                                </span>
                            </label>
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
                        <div class="route_block"></div>
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

        var LANG = '<?php echo $Router->lang ?>';
        var AJAX_URL = '/ajax/' + LANG;

        function deleteOrderTourId() {
            $.ajax({
                type: 'post',
                url: AJAX_URL,
                data: {
                    'request': 'delete_order_tour_id'
                }
            });
        }

        var ticketInfo = <?php echo json_encode($ticketInfo); ?>;
        var order = <?php echo json_encode($_SESSION['order']); ?>;
        var totalPriceBase = <?php echo $totalPrice; ?>;
        var bonusesAvailable = <?php echo $bonusesAvailable; ?>;
        var bonusToApply = <?php echo $bonusToApply; ?>;
        var totalPriceWithBonuses = <?php echo $totalPriceWithBonuses; ?>;
        var totalPrice = totalPriceBase;
        var currencyLabel = <?php echo json_encode($GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN']); ?>;
        var payableCents = <?php echo (int)round($totalPrice * 100); ?>;

        var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content') || '';

        function escHtml(s){
            return String(s || '').replace(/[&<>"']/g, function(m){
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
            });
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
                console.log('response (as returned by jQuery):', response);
                console.log('xhr.responseText:', xhr.responseText);
                console.log('xhr.responseJSON:', xhr.responseJSON);
                console.groupEnd();
            }catch(e){}
        }

        function normalizeResponse(response, xhr){
            if (typeof response === 'object' && response !== null) return response;

            var raw = (typeof response === 'string')
                ? response
                : (xhr && xhr.responseText ? xhr.responseText : '');

            try { return JSON.parse(raw); } catch(e){}

            return { _raw: raw };
        }

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

        function handleCardPaySuccess(rawResponse, parsed){
            if (isHtmlLike(rawResponse)) {
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

            var redirectUrl =
                pick(parsed, ['redirect_url','redirectUrl','payment_url','paymentUrl','checkout_url','checkoutUrl','url'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['redirect_url','payment_url','checkout_url','url']) : null);

            if (redirectUrl) {
                location.href = redirectUrl;
                return true;
            }

            var data =
                pick(parsed, ['liqpay_data','data_liqpay','liqpayData','data'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['data','liqpay_data','liqpayData']) : null);

            var signature =
                pick(parsed, ['liqpay_signature','signature_liqpay','liqpaySignature','signature'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['signature','liqpay_signature','liqpaySignature']) : null);

            if (String(data || '') === 'ok') data = null;

            var actionUrl =
                pick(parsed, ['liqpay_action','action','action_url','actionUrl'])
                || (parsed && parsed.liqpay ? pick(parsed.liqpay, ['action','action_url','actionUrl']) : null);

            if (data && signature) {
                var okL = submitLiqpayCheckout(data, signature, actionUrl);
                if (okL) return true;
            }

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

        let paymentFlowInFlight = false;

        function safeJsonParse(raw) {
            try { return JSON.parse(raw); } catch (e) { return null; }
        }

        function unlockPayFlow($btn){
            paymentFlowInFlight = false;
            if ($btn && $btn.length) $btn.prop('disabled', false);
        }

        function startLiqPayCheckout(orderRouteResp, $btn) {
            initLoader();

            var paymethodSelected = $('input[name="paymethod"]:checked').val() || 'cardpay';
            if (paymethodSelected === 'card') paymethodSelected = 'cardpay';

            if (order && typeof order === 'object') {
                order.paymethod = paymethodSelected;
            }

            const payload = {
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
                dataType: 'text',
                timeout: 20000,
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: payload,

                success: function (raw, textStatus, xhr) {
                    dumpAjaxSuccess('/payment/legacy/create', raw, textStatus, xhr, { paymethod: paymethodSelected, totalPrice: totalPrice });

                    var parsed = safeJsonParse(raw) || normalizeResponse(raw, xhr);

                    var started = handleCardPaySuccess(raw, parsed);
                    if (started) {
                        deleteOrderTourId();
                        return;
                    }

                    removeLoader();
                    unlockPayFlow($btn);
                },

                error: function (xhr, textStatus, errorThrown) {
                    removeLoader();
                    unlockPayFlow($btn);
                    dumpAjaxError('/payment/legacy/create', xhr, { textStatus: textStatus, errorThrown: errorThrown });
                }
            });
        }

        var paymentDebugEnabled = (new URLSearchParams(window.location.search)).get('debug') === '1';

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

            if (paymentDebugEnabled && window.console) {
                console.log('[PAYMENT TRACE] Monobank button', {
                    endpoint: '/payment/monobank/start/{order}',
                    order_db_id: orderDbId,
                    uniqid: uniqid || null,
                    redirect_url: url
                });
            }

            window.location.href = url;
        }

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
        
             function formatMoney(value){
            var num = parseFloat(value);
            if (Number.isNaN(num)) return value;
            return num.toFixed(2).replace(/\.?0+$/, '');
        }


        function updateBonusUi(){
            var useBonuses = $('#use_bonuses').is(':checked') && bonusToApply > 0;
            totalPrice = useBonuses ? totalPriceWithBonuses : totalPriceBase;

            $('.pv2_method_price').each(function () {
                var basePrice = $(this).data('base-price');
                var price = useBonuses ? totalPriceWithBonuses : basePrice;
                $(this).text(formatMoney(price) + ' ' + currencyLabel);
            });

            $('#bonuses_total_value').text(formatMoney(totalPriceWithBonuses));
            $('#bonuses_available_value').text(formatMoney(bonusesAvailable));
        }

        updateBonusUi();

        function applyBonusPreview(useBonus) {
            return $.ajax({
                type: 'post',
                url: AJAX_URL,
                dataType: 'json',
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: {
                    request: 'bonus_preview',
                    use_bonus: useBonus ? 1 : 0,
                    payable_cents: payableCents
                }
            });
        }

        $('#use_bonuses').on('change', function () {
            var useBonus = $(this).is(':checked');
            if (!useBonus) {
                bonusToApply = 0;
                totalPriceWithBonuses = totalPriceBase;
                updateBonusUi();
                applyBonusPreview(false);
                return;
            }

            applyBonusPreview(true).done(function (response) {
                var redeemCents = parseInt(response && response.redeem_cents ? response.redeem_cents : 0, 10);
                var balanceCents = parseInt(response && response.balance_cents ? response.balance_cents : 0, 10);
                bonusToApply = redeemCents / 100;
                bonusesAvailable = balanceCents / 100;
                totalPriceWithBonuses = Math.max(totalPriceBase - bonusToApply, 0);
                updateBonusUi();
            }).fail(function () {
                bonusToApply = 0;
                totalPriceWithBonuses = totalPriceBase;
                updateBonusUi();
            });
        });

        $('#orderTicket').off('click.orderTicket').on('click.orderTicket', function (e) {
            e.preventDefault();
            e.stopPropagation();

            if (paymentFlowInFlight) return;
            paymentFlowInFlight = true;

            const $btn = $(this);
            $btn.prop('disabled', true);

            let paymethod = $('input[name="paymethod"]:checked').val();
            const isCardPay = !!$('input[name="paymethod"]:checked').data('cardpay');
            const useBonuses = $('#use_bonuses').is(':checked') && bonusToApply > 0;

            if (order && typeof order === 'object') {
                order.paymethod = paymethod;
                order.use_bonuses = useBonuses ? 1 : 0;
                order.bonus_amount = useBonuses ? bonusToApply : 0;
                order.total_with_bonuses = useBonuses ? totalPriceWithBonuses : totalPriceBase;
            }

            initLoader();

            $.ajax({
                type: 'post',
                url: AJAX_URL,
                dataType: 'text',
                timeout: 20000,
                headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
                data: {
                    request: 'order_route',
                    paymethod: paymethod,

                    card_number: $.trim($('#card_number').val()),
                    card_valid_date: $.trim($('#card_valid_date').val()),
                    card_cvv: $.trim($('#card_cvv').val()),
                    cardholder_name: $.trim($('#cardholder_name').val()),
                    save_card: $('#save_card').is(':checked') ? 1 : 0,

                    ticket_info: JSON.stringify(ticketInfo),
                    order: JSON.stringify(order)
                },

                success: function (raw, textStatus, xhr) {
                    removeLoader();
                    dumpAjaxSuccess(AJAX_URL + ' (order_route)', raw, textStatus, xhr, { paymethod: paymethod, totalPrice: totalPrice });

                    const r = safeJsonParse(raw) || normalizeResponse(raw, xhr);
                    const ok = (r && (r.data === 'ok' || r.status === 'ok' || r.result === 'ok'));

                    if (!ok) {
                        unlockPayFlow($btn);
                        out(
                            'order_route вернул НЕ ok',
                            '<pre style="white-space:pre-wrap;max-height:320px;overflow:auto;">' +
                            escHtml(typeof r === 'object' ? JSON.stringify(r, null, 2) : String(raw)) +
                            '</pre>'
                        );
                        return;
                    }

                    if (paymethod === 'cash' && !isCardPay) {
                        $.ajax({
                            type: 'post',
                            url: AJAX_URL,
                            data: {
                                request: 'order_mail',
                                ticket_info: JSON.stringify(ticketInfo),
                                order: JSON.stringify(order)
                            }
                        });

                        deleteOrderTourId();
                        window.location.href = '<?php echo $Router->writelink(90)?>';
                        return;
                    }

                    if (isCardPay) {
                        startLiqPayCheckout(r, $btn);
                        return;
                    }

                    if (paymethod === 'monobank') {
                        startMonoCheckout(r, $btn);
                        return;
                    }

                    deleteOrderTourId();
                    window.location.href = '<?php echo $Router->writelink(90)?>';
                },

                error: function (xhr, textStatus, errorThrown) {
                    removeLoader();
                    unlockPayFlow($btn);
                    dumpAjaxError(AJAX_URL + ' (order_route)', xhr, { textStatus: textStatus, errorThrown: errorThrown, paymethod: paymethod, totalPrice: totalPrice });
                }
            });
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
            updatePayBtnUi();
        });
        $('#use_bonuses').on('change', updateBonusUi);

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
    });
</script>

</body>
</html>
