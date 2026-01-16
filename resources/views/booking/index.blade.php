@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/slick/slick.css') }}">
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/nice_select/nice-select.css') }}">
    <link rel="stylesheet" href="{{ mix('css/legacy/style_table.css') }}">
    <link rel="stylesheet" href="{{ mix('css/responsive.css') }}">

    <style>
    @media (max-width: 1400px) {
    .ticket_order_block {
        margin-top: 0px;
    }
}

        /* =========================
           BOOKING V2 (match screenshot)
           ========================= */
           .booking_v2 #b2_add_row{
    display:flex !important;
    align-items:center !important;
    gap:10px !important;
}
.tabs_links_container{
    box-shadow: 0 2px 44px 0 rgb(0 0 0 / 30%);
    padding:15px;
    max-width: 2700px;
}

.booking_v2 #b2_add_passenger_btn{
    border:0 !important;
    padding:0 !important;
    cursor:pointer !important;
    appearance:none !important;
    -webkit-appearance:none !important;
}

.booking_v2 #b2_add_passenger_text{
    border:0 !important;
    padding:0 !important;
    cursor:pointer !important;
    background:transparent !important;
}

.booking_v2 .b2_add_text_btn{
    border:0;
    background:transparent;
    padding:0;
    margin:0;
    cursor:pointer;
    font-family: Montserrat, system-ui;
    font-weight:700;
    font-size:12px;
    color:#878D8F;
    line-height:1.2;
}

.booking_v2 .b2_passenger_wrap.is_hidden{
    display:none !important;
}

        .booking_v2{
            position: relative;
            background:#fff;
            overflow:hidden;
            padding-bottom:140px; /* чтобы автобус снизу не перекрывал контент */
        }
        
        .ticket_order_block{
            margin-bottom: 60px;
        }
        .customer_contact_data{
            margin-bottom: 10px;
        }

        /* На фото внутри контент узкий и по центру */
        .booking_v2 .container{
            max-width: 672px;
        }

        /* Скрываем пустой блок фильтра (на фото его нет) */
        .booking_v2 .main_filter_wrapper{
            display:none !important;
        }

        /* Одна колонка всегда (как на фото) */
        .booking_v2 .booking_blocks{
            display:flex !important;
            flex-direction:column !important;
            gap:18px !important;
        }
        .booking_v2 .booking_blocks > [class*="col-"]{
            width:100% !important;
            max-width:100% !important;
        }

        /* Карточки (вместо старого вида shadow_block) */
        .booking_v2 .shadow_block{
            background:#fff;
            border:1px solid #A3E8F9;
            border-radius:14px;
            box-shadow: 0 0 0 rgba(0,0,0,0);
            padding:14px 14px 16px;
        }

        /* Заголовки в карточках */
        .booking_v2 .block_title{
            font-family: Montserrat, system-ui;
            font-weight: 700;
            font-size: 16px;
            line-height: 1.2;
            color:#303233;
            margin:0 0 6px;
        }
        .booking_v2 .par{
            font-family: Montserrat, system-ui;
            font-weight: 500;
            font-size: 12px;
            line-height: 1.35;
            color:#6E7172;
        }

        /* Сетка 2 колонки как на фото (даже на мобиле) */
        .booking_v2 .b2_grid{
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:12px;
        }
        @media (max-width: 360px){
            .booking_v2 .b2_grid{ grid-template-columns: 1fr; }
        }
        .booking_v2 .c_input{
            width: 90% !important;
        }
        .phone_input_wrapper{
            border: none;
            padding: 0;
            align-items: baseline;
        }

        /* Поля ввода под макет */
        .booking_v2 .c_input,
        .booking_v2 .customer_phone_input,
        .booking_v2 .nice-select{
            height:34px !important;
            min-height:34px !important;
            border-radius:8px !important;
            border:1px solid #A3E8F9 !important;
            background:#fff !important;
            padding: 0 12px !important;
            margin-left: 10px;
            font-family: Montserrat, system-ui !important;
            font-weight: 600 !important;
            font-size: 12px !important;
            color:#6E7172 !important;
            box-shadow: inset 0 0 10px rgba(163,216,249,.35) !important;
        }
        .booking_v2 .c_input::placeholder,
        .booking_v2 .customer_phone_input::placeholder{
            color:#9AA2A4;
            font-weight:600;
        }

        .booking_v2 .phone_input_wrapper{
            gap:10px;
        }
        .booking_v2 .nice-select{
            display:flex !important;
            align-items:center !important;
            padding-right:28px !important;
        }
        .booking_v2 .nice-select:after{
            right:10px !important;
        }

        /* Пассажирные заголовки */
        .booking_v2 .b2_passenger_title{
            font-family: Montserrat, system-ui;
            font-weight: 700;
            font-size: 12px;
            color:#303233;
            margin:10px 0 8px;
            display:flex;
            align-items:center;
            justify-content:space-between;
        }

        /* Красная точка справа (как на фото у пассажира №2) */
        .booking_v2 .b2_remove_dot{
            width:12px;
            height:12px;
            border-radius:999px;
            background:#EB5757;
            flex:0 0 auto;
        }

        /* “Добавить пассажира” строка */
        .booking_v2 .b2_add_row{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:10px;
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:12px;
            color:#878D8F;
        }
        .booking_v2 .b2_add_btn{
            width:26px;
            height:26px;
            border-radius:999px;
            background: linear-gradient(180deg,#63D5F8,#34B9F0);
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:16px;
            line-height:1;
            flex:0 0 auto;
        }
        .booking_v2 .b2_req{
            color:#EB5757;
            margin-left:2px;
        }

        /* “Свободная рассадка” */
        .booking_v2 .b2_free_seat{
            margin-top:10px;
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:12px;
            color:#6E7172;
        }

        /* Шаги (пилюли как на фото) */
        .booking_v2 .purchase_steps_wrapper{
            margin: 0px 0 14px;
        }
        .booking_v2 .purchase_steps{
            display:flex !important;
            gap:10px;
            align-items:center;
            flex-wrap:nowrap;
        }
        .booking_v2 .purchase_step_wrapper{
            position:relative;
        }

        .booking_v2 .purchase_step{
            height:40px;
            width: 180px;
            padding: 0 10px;
            border-radius:999px;
            border:1px solid #40A6FF;
            color:#40A6FF;
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:10px;
            display:flex;
            align-items:center;
            white-space:nowrap;
        }
        .booking_v2 .purchase_step.active{
            background:#40A6FF;
            color:#fff;
        }
        .booking_v2 .purchase_step_wrapper:not(:last-child)::after{
            content:"";
            position:absolute;
            top:50%;
            right:-10px;
            width:10px;
            height:1px;
            background:#40A6FF;
            transform: translateY(-50%);
        }
        /* Больше расстояние между плашками */
.booking_v2 .purchase_steps{
    gap:60px; /* было 10px */
}

/* Делаем линию равной gap */
.booking_v2 .purchase_step_wrapper:not(:last-child)::after{
    right:-60px; /* было -10px */
    width:60px;  /* было 10px */
    height:2px;  /* можно 1px, но 2px визуально ближе к макету */
    background: repeating-linear-gradient(
        to right,
        #40A6FF 0 6px,      /* штрих 6px */
        transparent 6px 12px /* пробел 6px */
    );
}


        /* Route card */
        .booking_v2 .route_block{
            margin-top:0;
        }
        .booking_v2 .b2_route_top{
            display:grid;
            grid-template-columns: 1fr auto 1fr;
            align-items:start;
            gap:10px;
            margin-top:8px;
        }
        .booking_v2 .b2_route_col{
            text-align:left;
        }
        .booking_v2 .b2_route_col.right{
            text-align:right;
        }
        .booking_v2 .b2_route_time{
            font-family: Montserrat, system-ui;
            font-weight:800;
            font-size:14px;
            color:#303233;
        }
        .booking_v2 .b2_route_city{
            margin-top:6px;
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:10px;
            color:#6E7172;
        }
        .booking_v2 .b2_route_mid{
            text-align:center;
            padding-top:18px;
        }
        .booking_v2 .b2_route_duration{
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:10px;
            color:#303233;
        }
        .booking_v2 .b2_route_line{
            margin:8px auto 0;
            width:150px;
            height:2px;
            background: linear-gradient(180deg,#63D5F8,#34B9F0);
            border-radius:999px;
        }

        /* Строки “Когда/Пассажиров” */
        .booking_v2 .b2_row{
            display:flex;
            justify-content:space-between;
            gap:10px;
            margin-top:10px;
            font-family: Montserrat, system-ui;
            font-weight:700;
            font-size:10px;
            color:#6E7172;
        }
        .booking_v2 .b2_row strong{
            color:#303233;
            font-weight:800;
        }
        .booking_v2 .b2_divider{
            height:1px;
            background:#DFE0E0;
            margin:12px 0;
        }

        /* Цена / К оплате */
        .booking_v2 .b2_price_row{
            display:flex;
            justify-content:space-between;
            margin-top:8px;
            font-family: Montserrat, system-ui;
            font-weight:800;
            font-size:12px;
            color:#6E7172;
        }
        .booking_v2 .b2_price_row .val{
            color:#303233;
        }

        /* Кнопка оплаты внутри route-card */
        .booking_v2 .b2_pay_btn{
            width:100%;
            height:34px;
            border:0;
            border-radius:999px;
            background: linear-gradient(180deg,#63D5F8,#34B9F0);
            color:#fff;
            font-family: Montserrat, system-ui;
            font-weight:800;
            font-size:12px;
            margin-top:14px;
        }

        /* Скрываем блоки, которые на фото отсутствуют, но оставляем в DOM */
        .booking_v2 .booking_v2_hide{
            display:none !important;
        }

        /* =========================
           Background decor (2 dashed paths + pins + bus + call)
           ========================= */
        .booking_v2__decor{
            position:absolute;
            inset:0;
            pointer-events:none;
            z-index:0;
        }
        .booking_v2__content{
            position:relative;
            z-index:1;
        }

        .booking_v2__path{
            position:absolute;
            width:520px;
            height:1100px;
            opacity:1;
        }
        .booking_v2__path.path1{ left:-120px; top:120px; }
        .booking_v2__path.path2{ right:-160px; top:0px; transform: scaleX(-1); }

        .booking_v2__pin{
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
        .booking_v2__pin.pin_left {
    left: 103px;
    top: 953px;
}
        .booking_v2__pin.pin_right {
    right: 158px;
    top: 460px;
}

/* ===== BUS DRIVE (responsive) ===== */

/* Настройки через переменные — удобно подгонять */
.booking_v2{
    --bus-w: clamp(260px, 34vw, 524px);   /* ширина автобуса: авто-адаптация */
    --bus-offset: 60px;                  /* запас "за экран" */
    --bus-duration: 14s;                 /* скорость (меньше = быстрее) */
}

/* Обёртка едет по экрану */
.booking_v2__bus_wrap{
    position: absolute;
    bottom: 0px;
    left: 0;                 /* базовая точка */
    width: var(--bus-w);
    height: auto;
    z-index: 200;

    pointer-events: none;    /* чтобы не блокировал клики по контенту */

    /* сама поездка */
    animation: bus-drive var(--bus-duration) linear infinite;
    will-change: transform;
}

/* Сам img автобуса */
.booking_v2__bus{
    width: 100%;
    height: auto;            /* лучше, чем фикс height:150px */
    display: block;

    /* лёгкая "тряска/пружина", чтобы казалось что едет */
    animation: bus-bounce 1.15s ease-in-out infinite;
    transform-origin: 50% 100%;
}

/* Дымок (2 "пуха" для живости) */
.booking_v2__bus_wrap::before,
.booking_v2__bus_wrap::after{
    content:"";
    position:absolute;

    /* ПРИМЕРНАЯ точка выхлопа:
       если дым не совпадёт с трубой — просто подвигай left/top */
    left: 10%;
    top: 72%;

    width: 26px;
    height: 26px;
    border-radius: 50%;

    /* дым полупрозрачный */
    background: radial-gradient(circle, rgba(180,180,180,.55) 0%, rgba(180,180,180,0) 70%);
    opacity: 0;

    filter: blur(0.4px);
    pointer-events:none;
}

/* Первый клуб — чаще и меньше */
.booking_v2__bus_wrap::before{
    animation: smoke 1.2s ease-out infinite;
}

/* Второй клуб — с задержкой, больше */
.booking_v2__bus_wrap::after{
    width: 34px;
    height: 34px;
    left: 6%;
    top: 70%;
    animation: smoke 1.2s ease-out infinite;
    animation-delay: .35s;
}

/* Траектория движения:
   от - (ширина автобуса + запас) до (100vw + запас) */
@keyframes bus-drive{
    0%{
        transform: translateX(calc(-1 * (var(--bus-w) + var(--bus-offset))));
    }
    100%{
        transform: translateX(calc(100vw + var(--bus-offset)));
    }
}

/* Лёгкая тряска/покачивание */
@keyframes bus-bounce{
    0%,100%{ transform: translateY(0) rotate(0deg); }
    50%{ transform: translateY(-2px) rotate(-0.15deg); }
}

/* Дымок улетает назад-вверх (влево), расширяется и исчезает */
@keyframes smoke{
    0%{
        transform: translate(0, 0) scale(0.6);
        opacity: 0;
    }
    15%{
        opacity: .65;
    }
    100%{
        transform: translate(-38px, -16px) scale(1.6);
        opacity: 0;
    }
}

/* На очень маленьких экранах — чуть ниже/меньше */
@media (max-width: 480px){
    
    .booking_v2{
        --bus-w: clamp(210px, 62vw, 360px);
        --bus-duration: 12s;
    }
    .booking_v2__bus_wrap{ bottom: -6px; }
}

/* Если у пользователя включено "уменьшить движение" — выключаем анимации */
@media (prefers-reduced-motion: reduce){
    .booking_v2__bus_wrap,
    .booking_v2__bus{
        animation: none !important;
    }
    .booking_v2__bus_wrap::before,
    .booking_v2__bus_wrap::after{
        display:none !important;
    }
}


        .booking_v2__call{
            position:fixed;
            right:18px;
            bottom:110px;
            width:56px;
            height:56px;
            border-radius:999px;
            background:#fff;
            border:2px solid #35BAF0;
            box-shadow:0 0 0 8px rgba(53,186,240,.12);
            display:flex;
            align-items:center;
            justify-content:center;
            z-index:50;
        }
        .booking_v2__call svg{ width:24px; height:24px; }
        
        /* общий стиль для линий */
.booking_v2__dash{
    fill:none;
    stroke:#35BAF0;
    stroke-width:4;
    stroke-linecap:round;
    stroke-dasharray:10 14; /* штрих / пробел */
}

/* позиционирование каждого SVG как декора */
.booking_v2__path{
    position:absolute;
    pointer-events:none;
    z-index:0;
    width:auto;
    height:auto;
}

/* левая линия */
.booking_v2__path.path1{
    left:-170px;
    top:550px;
            width:520px;     /* меняй под макет */
    height:800px;
        transform: scaleX(-1);
}

/* правая линия (зеркалим по горизонтали) */
.booking_v2__path.path2{
    right:-130px;
    top:52px;

        width:520px;     /* меняй под макет */
    height:800px;   /* меняй под макет */
}
.purchase_steps_wrapper{
   
}
.booking_v2__pin_icon{
    width:18px;
    height:18px;
    display:block;
    object-fit:contain;
}


/* ================================
   MOBILE (ONE-TO-ONE like screenshot)
   вставить В КОНЕЦ <style>
================================== */
@media (max-width: 520px){
    @media (max-width: 575px) {
    .booking_v2__bus {
        bottom: 5px;
    }
}
    .purchase_step{
                margin-right: 0px;
                margin-left: 0px;
    }
    .purchase_step1{
        margin-left: 25px;
    }
    .booking_v2 .b2_route_top{
            display:grid;
            grid-template-columns: 1fr auto 1fr;
            align-items:start;
            gap:0px;
            margin-top:8px;
        }

  /* фон страницы как на фото (светло-голубой), карточки остаются белыми */
  .booking_v2{
    background:#F3FAFF !important;
  }

  /* контейнер узкий, по центру, с боковыми отступами как на скрине */
  .booking_v2 .container{
    max-width: 420px !important;
    padding-left: 14px !important;
    padding-right: 14px !important;
  }

  /* карточки: более “воздушные” и с лёгкой тенью как на фото */
  .booking_v2 .shadow_block{
    border:2px solid #A3E8F9 !important;
    border-radius:16px !important;
    padding:16px 16px 18px !important;
    box-shadow: 0 6px 18px rgba(53,186,240,.10) !important;
  }

  /* заголовки/текст чуть компактнее под мобайл */
  .booking_v2 .block_title{
    font-size: 16px !important;
    margin-bottom: 6px !important;
  }
  .booking_v2 .par{
    font-size: 12px !important;
    line-height: 1.35 !important;
  }

  /* ===== ШАГИ: убрать “большую карточку” вокруг и сделать как в макете ===== */
  .tabs_links_container{
    box-shadow:none !important;
    padding:0 !important;
    background:transparent !important;
  }
  .booking_v2 .purchase_steps_wrapper{
    padding: 5px;
  }
  .booking_v2 .purchase_steps{
    gap: 10px !important;
    
  }
  .booking_v2 .purchase_step{
    height: 24px !important;
    width: auto !important;
    padding: 0 10px !important;
    border-radius: 999px !important;
    border: 2px dashed #40A6FF !important;   /* как “пунктир” на фото */
    font-size: 9px !important;
    line-height: 1 !important;
    white-space: nowrap !important;
    justify-content:center !important;
  }
  .booking_v2 .purchase_step.active{
    background:#40A6FF !important;
    color:#fff !important;
    border-style: solid !important;
  }
  .booking_v2 .purchase_step_wrapper:not(:last-child)::after{
    right: -12px !important;
    width: 12px !important;
    background: repeating-linear-gradient(
      to right,
      #40A6FF 0 4px,
      transparent 4px 8px
    ) !important;
  }

  /* ===== ПОЛЯ: всегда 1 колонка как на фото ===== */
  .booking_v2 .b2_grid{
    grid-template-columns: 1fr !important;
    gap: 10px !important;
  }

  /* убрать поломку ширины/сдвигов */
  .booking_v2 .c_input,
  .booking_v2 .customer_phone_input{
    width: 100% !important;
    margin-left: 0 !important;
    height: 36px !important;
    border-radius: 10px !important;
    border: 2px solid #A3E8F9 !important;
    box-shadow: none !important;                 /* на фото почти нет внутренней тени */
    padding: 0 12px !important;
  }

  /* Bootstrap .row внутри grid часто даёт лишние margin */
  .booking_v2 .b2_grid .row{
    margin: 0 !important;
    padding: 0 !important;
  }

  /* ===== “удалить пассажира” — сделать красный минус в кружке как на фото ===== */
  .booking_v2 .b2_remove_dot{
    width: 18px !important;
    height: 18px !important;
    background: #EB5757 !important;
    position: relative;
  }
  .booking_v2 .b2_remove_dot::before{
    content:"";
    position:absolute;
    left:50%;
    top:50%;
    width: 10px;
    height: 2px;
    background:#fff;
    transform: translate(-50%,-50%);
    border-radius: 2px;
  }

  /* ===== “Добавить пассажира” — размеры как на фото ===== */
  .booking_v2 .b2_add_row{
    margin-top: 12px !important;
    gap: 10px !important;
  }
  .booking_v2 .b2_add_btn{
    width: 24px !important;
    height: 24px !important;
    font-size: 16px !important;
  }

  /* ===== ТЕЛЕФОН: сделать единое поле как на фото ===== */
  .booking_v2 .phone_input_wrapper{
    padding: 0 !important;
    border: 2px solid #A3E8F9 !important;
    border-radius: 10px !important;
    height: 36px !important;
    display:flex !important;
    align-items:center !important;
    gap: 8px !important;
    box-shadow: none !important;
  }
  /* убираем рамки у внутренних элементов, чтобы была одна общая рамка */
  .booking_v2 .phone_input_wrapper .nice-select{
    border: 0 !important;
    height: 32px !important;
    min-height: 32px !important;
    box-shadow: none !important;
    background: transparent !important;
    padding-left: 10px !important;
    padding-right: 22px !important;
    margin-left: 0 !important;
  }
  .booking_v2 .phone_input_wrapper .customer_phone_input{
    border: 0 !important;
    height: 32px !important;
    box-shadow: none !important;
    padding-left: 0 !important;
    padding-right: 10px !important;
    background: transparent !important;
  }
}


@media (max-width: 992px) {
    .booking_v2 .booking_blocks{
        gap: 0px !important;
    }
    .page_content_wrapper {
        padding: 0px 0;
    }
    .ticket_order_block {
    margin-bottom: 30px;
}
}


    </style>
@endsection

<?php
    $Router = new \App\Service\DbRouter\Router();
?>

@section('content')
    <div class="booking_v2">
        {{-- Декор фона (пунктир + остановки + автобус) --}}
        <div class="booking_v2__decor" aria-hidden="true">
{{-- dashed path 1 (левая) --}}
<svg class="booking_v2__path path1" viewBox="0 0 572 1829" fill="none" xmlns="http://www.w3.org/2000/svg">
    
          <path class="booking_v2__dash"
          d="M444.209 1.98722C444.209 1.98722 219.722 27.4398 135.756 142.505C-39.6686 382.902 599.912 507.562 540.994 818.843C486.312 1107.74 45.4024 936.525 4.07039 1228.8C-44.1303 1569.64 570.887 1826.66 570.887 1826.66" />
</svg>

{{-- dashed path 2 (правая, зеркально) --}}
<svg class="booking_v2__path path2" viewBox="0 0 572 1829" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g transform="translate(572 0) scale(-1 1)">
        <path class="booking_v2__dash"
              d="M444.209 1.98722C444.209 1.98722 219.722 27.4398 135.756 142.505C-39.6686 382.902 599.912 507.562 540.994 818.843C486.312 1107.74 45.4024 936.525 4.07039 1228.8C-44.1303 1569.64 570.887 1826.66 570.887 1826.66" />
    </g>
</svg>



            {{-- pins --}}
            <div class="booking_v2__pin pin_left">
    <img class="booking_v2__pin_icon" src="{{ asset('images/booking/pin.png') }}" alt="">
</div>

<div class="booking_v2__pin pin_right">
    <img class="booking_v2__pin_icon" src="{{ asset('images/booking/pin.png') }}" alt="">
</div>



            <div class="booking_v2__bus_wrap" aria-hidden="true">
    <img class="booking_v2__bus" src="{{ asset('images/booking/bus.png') }}" alt="">
</div>


        </div>

        <div class="booking_v2__content">
            <div class="content">
                <div class="main_filter_wrapper">
                    <div class="container">
                        {{-- Фильтр если нужен --}}
                    </div>
                </div>

                {{-- Шаги покупки (как на фото: пилюли) --}}
                <div class="purchase_steps_wrapper">
                    <div class="tabs_links_container">
                        <div class="purchase_steps">
                            <div class="purchase_step_wrapper">
                                <div class="purchase_step purchase_step1">
                                    1. @lang('dictionary.MSG_MSG_TICKETS_VIBIR_AVTOBUSA')
                                </div>
                            </div>

                            <div class="purchase_step_wrapper">
                                <div class="purchase_step active">
                                    2. @lang('data_ticket_page')
                                </div>
                            </div>

                            <div class="purchase_step_wrapper">
                                <div class="purchase_step">
                                    3. @lang('payment_ticket_page')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page_content_wrapper">
                    <div class="container">
                        <div class="flex-row gap-30 booking_blocks">
                            {{-- Левая колонка (по CSS станет первой карточкой и второй) --}}
                            <div class="col-xxl-7 col-xs-12">
                                @include('booking.partials.order-form')
                                @include('booking.partials.contact-data')

                                {{-- Эти блоки нужны логике goPayment (чекбоксы), но на фото их нет.
                                     Мы оставляем их в DOM и скрываем. --}}
                                <div class="booking_v2_hide">
                                    @include('booking.partials.promocode')
                                    @include('booking.partials.payment-block')
                                </div>
                            </div>

                            {{-- Правая колонка с маршрутом (по CSS станет третьей карточкой) --}}
                            <div class="col-xxl-4 col-xs-12">
                                @include('booking.partials.route-info', [
                                    'ticketInfo' => $ticketInfo,
                                    'busOptions' => $busOptions,
                                    'passengers' => $passengers,
                                    'totalPrice' => $totalPrice,
                                    'order' => $order,
                                    'tourDate' => $tourDate,
                                    'formattedDate' => $formattedDate,
                                    'Router' => $Router
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ mix('js/legacy/libs/jquery.maskedinput.min.js') }}"></script>
    <script>
        // Данные для JavaScript
        const bookingData = {
            lang: '{{ $lang }}',
            passengers: {{ $passengers }},
            phoneMask: '{{ $firstPhoneMask }}',
            csrfToken: '{{ csrf_token() }}',
            ajaxUrl: '/ajax/{{ $lang }}',
            nextStepUrl: '{{ rtrim(url($Router->writelink(86)), '/') }}',
            messages: {
                fillRequiredFields: '@lang("dictionary.MSG_MSG_BOOKING_ZAPOLNITE_VSE_OBYAZATELINYE_POLYA")',
                requiredFieldsMarked: '@lang("dictionary.MSG_MSG_BOOKING_OBYAZATELINYE_POLYA_OTMECHENY_")',
                invalidEmail: '@lang("dictionary.MSG_MSG_BOOKING_EMAIL_UKAZAN_NEVERNO")',
                acceptTerms: '@lang("dictionary.MSG_MSG_BOOKING_DLYA_OFORMLENIYA_ZAKAZA_VY_DOLZHNY_PRINYATI_USLOVIYA")',
                acceptPersonalData: '@lang("dictionary.MSG_MSG_BOOKING_DLYA_OFORMLENIYA_ZAKAZA_VY_DOLZHNY_DATI_SOGLASIE_NA_OBRABOTKU_LICHNYH_DANNYH")',
                noSeatsAvailable: '@lang("dictionary.MSG_MSG_TICKETS_NET_SVOBODNYH_MEST")',
                ticketExpired: '@lang("dictionary.MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL")',
                closeBtn: '@lang("dictionary.MSG_CLOSE")'
            }
        };
    </script>

    <script>
        function validateEmail(input) {
            let email = input.value;
            let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            let isValid = emailRegex.test(email);
            let errorSpan = document.getElementById("email-error");

            if (!isValid && email.length > 0) {
                errorSpan.style.display = "inline";
                input.setCustomValidity("Invalid email");
            } else {
                errorSpan.style.display = "none";
                input.setCustomValidity("");
            }
        }

        function toggleBirthDateCalendar(){
            clientBirthDatePicker.open()
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

        function goPayment(){
            let allFieldsFilled = true;
            let family_name = $.trim($('#family_name').val());
            let name = $.trim($('#name').val());
            let patronymic = $.trim($('#patronymic').val());
            let birth_date = $('#birthdate').val();
            let email = $.trim($('#email').val());
            let phone = $.trim($('#phone').val());
            let saveMyData = 0;
            let phone_code = $('.phone_country_code').val();

            let passengers = [];
            let totalPassengers = document.querySelectorAll('[data-passengers-family-name]').length;

            for (let i = 1; i < totalPassengers; i++) {
                let family_name = $.trim($('input[name="passengers[' + i + '][family_name]"]').val());
                let name = $.trim($('input[name="passengers[' + i + '][name]"]').val());
                let patronymic = $.trim($('input[name="passengers[' + i + '][patronymic]"]').val());
                let birth_date = $('input[name="passengers[' + i + '][birthdate]"]').val();

                passengers.push({
                    family_name: family_name,
                    name: name,
                    patronymic: patronymic,
                    birth_date: birth_date,
                });
            }

            if ($('#save_my_data').is(':checked')){
                saveMyData = 1;
            }

            $('.req_input').each(function () {
                if ($.trim($(this).val()) === '') {
                    $(this).addClass('required_error');
                } else {
                    $(this).removeClass('required_error');
                }
            });

            $('.req_input').each(function () {
                if ($.trim($(this).val()) === '') {
                    out('@lang("dictionary.MSG_MSG_BOOKING_ZAPOLNITE_VSE_OBYAZATELINYE_POLYA")', '@lang("dictionary.MSG_MSG_BOOKING_OBYAZATELINYE_POLYA_OTMECHENY_")');
                    allFieldsFilled = false;
                    return false;
                }
            });

            if (!allFieldsFilled) {
                return false;
            }

            if (!isEmail(email)){
                out('@lang("dictionary.MSG_MSG_BOOKING_EMAIL_UKAZAN_NEVERNO")');
                return false;
            }

            if (!$('#terms_accept').is(':checked')){
                out('@lang("dictionary.MSG_MSG_BOOKING_DLYA_OFORMLENIYA_ZAKAZA_VY_DOLZHNY_PRINYATI_USLOVIYA")');
                return false;
            }

            if (!$('#personal_data_process').is(':checked')){
                out('@lang("dictionary.MSG_MSG_BOOKING_DLYA_OFORMLENIYA_ZAKAZA_VY_DOLZHNY_DATI_SOGLASIE_NA_OBRABOTKU_LICHNYH_DANNYH")');
                return false;
            }

            initLoader();
            console.log('start');

            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'check_OrderTicket'
                },
                success: function(response) {
                    removeLoader();
                    console.log('response ', response);

                    if ($.trim(response) === 'ok') {
                        initLoader();
                        console.log('initLoader');

                        $.ajax({
                            type: 'post',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            url: '/ajax/ru',
                            data: {
                                'request': 'remember_private_data',
                                'family_name': family_name,
                                'name': name,
                                'patronymic': patronymic,
                                'birthDate': birth_date,
                                'email': email,
                                'phone': phone,
                                'save_data': saveMyData,
                                'phone_code': phone_code,
                                'passengers': passengers
                            },
                            success: function (response) {
                                removeLoader();
                                console.log('removeLoader');
                                console.log('response ', response);

                                if ($.trim(response.data) === 'ok') {
                                    location.href = '<?php echo  rtrim(url($Router->writelink(86)), '/') ?>';
                                } else {
                                    out('Ошибка');
                                }
                            }
                        });
                    } else if ($.trim(response) === 'soldout') {
                        out('@lang("dictionary.MSG_MSG_TICKETS_NET_SVOBODNYH_MEST")');
                    } else if ($.trim(response) === 'late') {
                        console.log(response);
                        out('@lang("dictionary.MSG_MSG_TICKETS_ETOT_BILET_BOLISHE_KUPITI_NELIZYA_TK_ETOT_REJS_UZHE_UEHAL")');
                    }
                }
            });
        }

        $('.phone_country_code').niceSelect();
        $('.customer_phone_input').mask("<?php echo $firstPhoneMask?>");

        function changeInputMask(item){
            let selectedOption = $(item).find(':selected');
            $('.customer_phone_input').mask($(selectedOption).data('mask'));
            $('.customer_phone_input').attr('placeholder',$(selectedOption).data('placeholder'));
        };

        function toggleRouteInfo(item){
            $('.route').slideToggle();
            $(item).find('img').toggleClass('rotate');
        };

        function togglePromocodeBlock(){
            $('.customer_promocode').slideToggle();
        };

        function initLoader() {
            $('body').prepend('<div class="loader"></div>');
        };

        function removeLoader() {
            $('.loader').remove();
        };

        function isEmail(email) {
            if (email.length < 5) {
                return false;
            }

            var parts = email.split('@');
            if (parts.length !== 2) {
                return false;
            }

            var domain = parts[1];
            if (domain.length < 4) {
                return false;
            }

            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        };

        document.addEventListener("DOMContentLoaded", function() {
            const el = document.querySelector(".filter_date_booking");
            if (el) {
                el.click();
            }
        });
    </script>
    <script>
(function () {

    function syncAddRowVisibility() {
        var row = document.getElementById('b2_add_row');
        if (!row) { return; }

        var hiddenBlock = document.querySelector('.js_passenger_block.is_hidden');
        if (hiddenBlock) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }

    function showNextPassenger() {
        var hiddenBlock = document.querySelector('.js_passenger_block.is_hidden');
        if (!hiddenBlock) {
            syncAddRowVisibility();
            return;
        }

        hiddenBlock.classList.remove('is_hidden');
        hiddenBlock.style.display = '';

        syncAddRowVisibility();
    }

    document.addEventListener('click', function (e) {
        var btn1 = e.target.closest('#b2_add_passenger_btn');
        var btn2 = e.target.closest('#b2_add_passenger_text');

        if (btn1 || btn2) {
            e.preventDefault();
            showNextPassenger();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        syncAddRowVisibility();
    });

})();
</script>

@endsection
