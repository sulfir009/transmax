{{-- /resources/views/regular-races/components/regular-races.blade.php --}}

@php
    $lang = \App\Service\Site::lang();

    // Безопасный “двойной/тройной” decode, чтобы убрать &#34; и подобное.
    $rr3Decode = function($s){
        $s = (string)($s ?? '');
        for ($i=0; $i<3; $i++) {
            $n = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($n === $s) break;
            $s = $n;
        }
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return $s;
    };

    // Фото автобуса (баннер сверху)
    $busImgDesk = asset('images/legacy/bus.png');
    $busImgMob  = asset('images/legacy/bus.png');

    /*
      ИКОНКИ (SVG):
      1) resources/views/regular-races/components/icons/*.svg
      2) public/images/regular-races/icons/*.svg

      Имена:
      rr3-clock.svg
      rr3-bus.svg   (НО ЭТО У ТЕБЯ БОЛЬШОЙ SVG С ЛИНИЯМИ, МЫ ЕГО НЕ ИСПОЛЬЗУЕМ В ЦЕНТРЕ)
      rr3-duration.svg
      rr3-wifi.svg
      rr3-video.svg
      rr3-plug.svg
      rr3-snow.svg
      rr3-calendar.svg
    */
    $icoPublicPath = 'images/regular-races/icons';

    $busCenterIcon = asset('images/regular-races/icons/rr3-bus.png');

    if (!is_file(public_path('images/regular-races/icons/rr3-bus.png'))) {
        $busCenterIcon = asset('images/regular-races/icons/rr3-bus.svg');
    }

    // Хелпер: отдаёт INLINE SVG (если файл найден), иначе <img src="...">
    $ico = function(string $file, string $class = '', string $alt = '') use ($icoPublicPath) {
        $file = basename($file);

        $try = [
            resource_path('views/regular-races/components/icons/' . $file),
            resource_path('views/regular-races/components/' . $file),
            public_path($icoPublicPath . '/' . $file),
            public_path('images/regular-races/icons/' . $file),
        ];

        $svg = null;
        foreach ($try as $p) {
            if (is_file($p)) {
                $c = @file_get_contents($p);
                if ($c && stripos($c, '<svg') !== false) {
                    $svg = $c;
                    break;
                }
            }
        }

        if ($svg) {
            $svg = preg_replace('~<\?xml.*?\?>~i', '', $svg);
            $svg = preg_replace('~<!DOCTYPE.*?>~i', '', $svg);
            $svg = preg_replace('/\s(width|height)="[^"]*"/i', '', $svg);

            if ($class) {
                if (preg_match('/<svg\b[^>]*\bclass="/i', $svg)) {
                    $svg = preg_replace('/(<svg\b[^>]*\bclass=")([^"]*)(")/i', '$1$2 ' . e($class) . '$3', $svg, 1);
                } else {
                    $svg = preg_replace('/<svg\b/i', '<svg class="' . e($class) . '"', $svg, 1);
                }
            }

            if ($alt) {
                if (!preg_match('/\baria-label="/i', $svg)) {
                    $svg = preg_replace('/<svg\b/i', '<svg role="img" aria-label="' . e($alt) . '"', $svg, 1);
                }
            } else {
                if (!preg_match('/\baria-hidden="/i', $svg)) {
                    $svg = preg_replace('/<svg\b/i', '<svg aria-hidden="true"', $svg, 1);
                }
            }

            return $svg;
        }

        $src = asset($icoPublicPath . '/' . $file);
        return '<img class="'.e($class).'" src="'.e($src).'" alt="'.e($alt).'" loading="lazy">';
    };
@endphp

@once
<style>
/* =========================================================
   RR3 — MOBILE FIX (чтобы НЕ ОБРЕЗАЛОСЬ и было одинаково)
   ========================================================= */

.rr3_scope, .rr3_scope * { box-sizing: border-box; }
.rr3_scope{
    width:100%;
    max-width:100%;
    font-family: Montserrat, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    position:relative;
    z-index:50;
}

@media (max-width: 768px){
    .section_blocks{ overflow-x:hidden !important; }
    .section_blocks .right_blocks_line,
    .section_blocks .mob_right_blocks_line,
    .section_blocks .mob_left_blocks_line,
    .section_blocks .mob_pin_bus_block,
    .section_blocks .mob_pin_bus_block_m{
        z-index:-1 !important;
        pointer-events:none !important;
    }
    .section_blocks .container{ position:relative; z-index:2; }
}

.rr3_list{ display:flex; flex-direction:column; gap:18px; margin-top:81px}

.rr3_group_title{ display:flex; align-items:center; gap:10px; margin:10px 0 6px; }
.rr3_group_title img{ width:32px; height:auto; display:block; }
.rr3_group_title h2{ margin:0; font-weight:700; font-size:18px; color:#303233; }

/* CARD */
.rr3_card{
    position:relative;
    width:100%;
    max-width:100%;
    background:#fff;
    border-radius:15px;
    box-shadow:0px 4px 50px rgba(0,0,0,.10);
    overflow:hidden;
    z-index:5;
}

/* Badge */
.rr3_badge{
    position:absolute; left:0; top:0; z-index:3;
    background:#35BAF0; color:#fff;
    padding:10px 14px;
    border-top-left-radius:15px;
    border-bottom-right-radius:15px;
    font-weight:600; font-size:14px; line-height:16.8px;
}

/* Media */
.rr3_media{ width:100%; height:251px; overflow:hidden; }
.rr3_media img{ width:100%; height:100%; object-fit:cover; display:block; }

/* Body */
.rr3_body{
    padding:14px 16px 0;
    display:flex;
    flex-direction:column;
    gap:12px;
    min-width:0;
}

/* TOP */
.rr3_toprow{
    display:flex;
    flex-direction:column;
    gap:10px;
    align-items:stretch;
    min-width:0;
}

.rr3_side{
    display:inline-flex;
    align-items:flex-start;
    gap:10px;
    max-width:100%;
    min-width:0;
}
.rr3_side--dep{ align-self:flex-start; }
.rr3_side--arr{ align-self:flex-end; text-align:right; }

.rr3_clock_img{
    width:18px; height:18px;
    flex:0 0 auto;
    margin-top:2px;
}
.rr3_clock_img, .rr3_clock_img svg, .rr3_clock_img img{ width:18px; height:18px; display:block; }

.rr3_time{
    font-weight:600;
    font-size:18px;
    line-height:21.6px;
    color:#303233;
    white-space:nowrap;
    flex:0 0 auto;
}

.rr3_place{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width:0;
    max-width:240px;
}
.rr3_side--arr .rr3_place{ align-items:flex-start; } /* оставил как у тебя */

.rr3_cityline{
    display:flex;
    align-items:center;
    gap:8px;
    min-width:0;
}
.rr3_side--arr .rr3_cityline{ justify-content:flex-end; }

.rr3_city{
    font-weight:600;
    font-size:14px;
    line-height:16.8px;
    color:#303233;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    min-width:0;
    max-width:190px;
}

.rr3_station{
    font-weight:400;
    font-size:14px;
    line-height:16.8px;
    color:#878D8F;
    text-decoration:underline;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    min-width:0;
    max-width:240px;
}

/* Flags */
.rr3_flag{ width:19px; height:14px; border-radius:2px; overflow:hidden; flex:0 0 auto; }
.rr3_flag.ua{ background:linear-gradient(#0057B8 0 50%, #FFD700 50% 100%); }
.rr3_flag.gr{
    background:
      linear-gradient(#0D5EAF 0 11%, #fff 11% 22%, #0D5EAF 22% 33%, #fff 33% 44%, #0D5EAF 44% 55%, #fff 55% 66%, #0D5EAF 66% 77%, #fff 77% 88%, #0D5EAF 88% 100%);
}

/* MID */
.rr3_mid{
    width:100%;
    max-width:340px;
    margin:0 auto;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:8px;
    min-width:0;
    padding:0 6px;
}

.rr3_dash{
    width:100%;
    display:block;
    min-width:0;
}

/* ОТКЛЮЧАЕМ CSS-пунктир, потому что он уже есть внутри твоего изображения */
.rr3_dash::before,
.rr3_dash::after{
    content:none !important;
    display:none !important;
}

/* ТВОЁ ГОТОВОЕ ИЗОБРАЖЕНИЕ (линии+круг+автобус) */
.rr3_dash_img{
    width:100%;
    height:42px;          /* высота как у круга в макете */
    display:block;
    object-fit:contain;   /* не режем, просто вписываем */
}


.rr3_bus_circle{
    width:42px; height:42px;
    border-radius:999px;
    border:1px solid #40A6FF;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    flex:0 0 auto;

    overflow:hidden; /* важно: если вдруг попадется большой svg, он не “вылезет” */
}

/* === ключевой фикс: именно IMG внутри круга === */
.rr3_bus_circle img.rr3_bus_img{
    width:20px;
    height:20px;
    display:block;
    object-fit:contain;
}

/* если вдруг fallback на svg — тоже пусть не ломает */
.rr3_bus_circle svg.rr3_bus_img{
    width:20px;
    height:20px;
    display:block;
}

.rr3_duration{
    display:flex;
    align-items:center;
    gap:8px;
    color:#878D8F;
    font-weight:400;
    font-size:14px;
    line-height:19.6px;
    text-align:center;
    min-width:0;
}
.rr3_duration_img{ width:16px; height:16px; display:block; flex:0 0 auto; }
.rr3_duration_img svg, .rr3_duration_img img{ width:16px; height:16px; display:block; }

/* Icons row */
.rr3_icons{
    display:flex;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
}
.rr3_chip{
    width:40px; height:40px;
    border-radius:25px;
    background:#F5F5F5;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
}
.rr3_chip .rr3_chip_icon,
.rr3_chip svg,
.rr3_chip img{
    width:20px; height:20px; display:block;
}

/* Schedule */
.rr3_schedule{
    width:100%;
    border-radius:25px;
    display:flex;
    align-items:center;
    gap:5px;
    min-width:0;
}
.rr3_schedule_label{
    color:#878D8F;
    font-weight:400;
    font-size:11px;
    line-height:16.8px;
    white-space:nowrap;
    flex:0 0 auto;
}
.rr3_schedule_value{
    margin-left:auto;
    color:#303233;
    font-weight:600;
    font-size:9px;
    line-height:16.8px;
    text-align:right;

    min-width:0;
    max-width:60%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

/* Details button */
.rr3_details_btn{
    all:unset;
    box-sizing:border-box;
    cursor:pointer;
    width:100%;
    background:#6E7172;
    color:#fff;
    border-radius:100px;
    padding:20px 20px;
    box-shadow:0px 4px 12px rgba(162,162,162,.25);
    font-size:14px;
    font-weight:500;
    line-height:16.8px;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:12px;
}

.rr3_chev{
    width:10px; height:10px;
    display:inline-block;
    border-right:3px solid #fff;
    border-bottom:3px solid #fff;
    transform: rotate(225deg);
    margin-top:2px;
}
.rr3_card:not(.is-open) .rr3_chev{ transform: rotate(45deg); margin-top:-2px; }

.rr3_details_body{ padding: 0; }

/* Stops */
.rr3_stops_wrap{
    display:flex;
    flex-direction:column;
    gap:16px;
    padding-top:6px;
    min-width:0;
}

.rr3_stops{
    position:relative;
    display:flex;
    flex-direction:column;
    gap:16px;
    padding-left:12px;
    min-width:0;
}
.rr3_line{
    position:absolute;
    left: 19px;
    top: 8px;
    bottom: 8px;
    width:2px;
    background:#A3E8F9;
    border-radius:2px;
}

.rr3_stop{
    display:grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    column-gap:12px;
    align-items:start;
    min-width:0;
}
.rr3_stop_left{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
}
.rr3_dot{
    width:14px; height:14px;
    background:#A3E8F9;
    border-radius:999px;
    flex:0 0 auto;
    margin-top:2px;
}
.rr3_stop_txt{
    font-weight:600;
    font-size:9px;
    line-height:16.8px;
    color:#303233;
    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}
.rr3_stop_station{
    font-weight:400;
    font-size:9px;
    line-height:16.8px;
    color:#878D8F;
    text-decoration:underline;
    text-align:left;

    min-width:0;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.rr3_map{
    width:100%;
    height:142px;
    background:#D9D9D9;
    border-radius:20px;
}

/* Actions */
.rr3_actions{
    padding:12px 0 18px;
    display:flex;
    flex-direction:column;
    gap:16px;
}
.rr3_btn{
    all:unset;
    box-sizing:border-box;
    cursor:pointer;
    width:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:100px;
    padding:20px 20px;
    font-size:14px;
    line-height:16.8px;
    max-width:100%;
}
.rr3_btn.buy{
    background:#FF9900;
    color:#fff;
    font-weight:700;
    box-shadow:0px 4px 12px rgba(92, 8, 8, .25);
}
.rr3_btn.reserve{
    background:linear-gradient(0deg, #63D5F8 0%, #34B9F0 100%);
    color:#fff;
    font-weight:400;
}

/* EXTRA NARROW */
@media (max-width: 380px){
    .rr3_place{ max-width:200px; }
    .rr3_station{ max-width:250px; }
    .rr3_city{ max-width:150px; }
    .rr3_mid{ max-width:300px; }
    .rr3_schedule_value{ max-width:55%; }
}

/* ===== FIX: город + адрес под временем (CSS-only) ===== */
@media (max-width: 768px){
  .rr3_scope .rr3_side{
    display:flex !important;
    flex-wrap:wrap !important;
    align-items:flex-start;
    gap:10px;
    min-width:0;
  }

  .rr3_scope .rr3_clock_img,
  .rr3_scope .rr3_time{
    flex:0 0 auto;
  }

  .rr3_scope .rr3_side .rr3_place{
    flex:0 0 100% !important;
    width:100% !important;
    max-width:100% !important;
    min-width:0;
    margin-top:-2px;
  }

  .rr3_scope .rr3_side--arr{
    align-items:flex-end;
    text-align:right;
  }
  .rr3_scope .rr3_side--arr .rr3_cityline{
    justify-content:flex-end;
  }
  .rr3_scope .rr3_side--arr .rr3_place{
    padding-left:0;
    padding-right:28px;
  }
}

/* =========================================================
   RR3 — DESKTOP LAYOUT (>= 769px) — Мобилку НЕ ТРОГАЕМ
   ========================================================= */
@media (min-width: 769px){

  /* размеры, чтобы дальше не писать магические числа */
  .rr3_scope{
    --rr3-desk-media-w: 290px;  /* ширина превью слева */
    --rr3-desk-gap: 16px;       /* зазор между превью и правой частью */
    --rr3-desk-pad: 14px;       /* внутренние отступы карточки */
  }

  /* карточка становится "2-этажной": верх = превью+контент, низ = детали на всю ширину */
  .rr3_card{
    padding: var(--rr3-desk-pad);
    display:flex;
    flex-wrap:wrap;
    align-items:flex-start;
    gap:0;
  }

  /* бейдж как на фото — не в край, а с отступом */
  .rr3_badge{
    left: calc(var(--rr3-desk-pad) + 8px);
    top:  calc(var(--rr3-desk-pad) + 8px);
    border-radius: 10px;
    padding: 6px 10px;
    font-size: 12px;
    line-height: 14px;
  }

  /* превью слева — не во всю ширину как на мобилке */
  .rr3_media{
    flex: 0 0 var(--rr3-desk-media-w);
    width: var(--rr3-desk-media-w);
    height: 170px;
    border-radius: 14px;
    overflow:hidden;
  }
  .rr3_media img{
    width:100%;
    height:100%;
    object-fit:cover;
  }

  /* правая часть */
  .rr3_body{
    flex: 1 1 calc(100% - var(--rr3-desk-media-w) - var(--rr3-desk-gap));
    margin-left: var(--rr3-desk-gap);
    padding:0;

    /* переставляем блоки под desktop как в макете */
    display:grid;
    grid-template-columns: 1fr auto;
    grid-template-areas:
      "top     top"
      "icons   actions"
      "schedule actions"
      "detailsbtn detailsbtn"
      "details  details";
    column-gap: 16px;
    row-gap: 10px;
    align-items:center;
  }

  .rr3_toprow{ grid-area: top; }

  /* TOPROW на desktop = в одну линию (dep — mid — arr) */
  .rr3_toprow{
    display:flex;
    flex-direction:row;
    align-items:flex-start;
    justify-content:space-between;
    gap: 14px;
    margin-top: 2px;
  }

  /* dep/arr растягиваются, mid фиксированный */
  .rr3_side--dep{ flex:1 1 0; }
  .rr3_side--arr{ flex:1 1 0; justify-content:flex-end; }

  .rr3_mid{
    flex:0 0 240px;
    max-width:240px;
    margin:0;
    padding:0;
    gap:6px;
  }

  /* шрифты как на десктопе (меньше, чем на мобилке) */
  .rr3_time{ font-size:14px; line-height:16px; }
  .rr3_city{ font-size:12px; line-height:14px; max-width:none; }
  .rr3_station{ font-size:11px; line-height:13px; max-width:none; }

  /* КЛЮЧ: город в одной строке со временем, станция ниже */
  .rr3_place{
    max-width:none;
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap: 6px 8px;
  }
  .rr3_cityline{ flex:0 0 auto; }
  .rr3_station{
    flex: 0 0 100%;
    margin-top: 2px;
  }

  /* справа выравниваем как на фото */
  .rr3_side--arr{ text-align:right; }
  .rr3_side--arr .rr3_place{ justify-content:flex-end; }
  .rr3_side--arr .rr3_station{ text-align:right; }

  /* центральная картинка с линиями */
  .rr3_dash_img{
    width:100%;
    height: 28px;
    object-fit:contain;
  }

  .rr3_duration{
    font-size:11px;
    line-height:14px;
    gap:6px;
  }
  .rr3_duration_img{ width:14px; height:14px; }

  /* иконки удобств — компактнее */
  .rr3_icons{ grid-area: icons; justify-content:flex-start; }
  .rr3_chip{
    width:26px;
    height:26px;
    border-radius:999px;
  }
  .rr3_chip .rr3_chip_icon,
  .rr3_chip svg,
  .rr3_chip img{
    width:14px;
    height:14px;
  }

  /* schedule как маленькая строка (не "пилюля") */
  .rr3_schedule{
    grid-area: schedule;
    background: transparent;
    padding:0;
    gap:8px;
    align-items:center;
  }
  .rr3_schedule .rr3_chip{
    width:18px;
    height:18px;
    background: transparent;
  }
  .rr3_schedule_label{
    font-size:12px;
    line-height:14px;
  }
  .rr3_schedule_value{
    font-size:12px;
    line-height:14px;
    max-width:none;
    margin-left:0;
    text-align:left;
  }

  /* кнопки справа, в одну линию */
  .rr3_actions{
    grid-area: actions;
    padding:0;
    flex-direction:row;
    justify-content:flex-end;
    gap:12px;
  }
  .rr3_btn{
    width:auto;
    padding:10px 18px;
    font-size:12px;
    line-height:14px;
    border-radius: 999px;
  }

  /* КЛЮЧ: "Детали" должны быть на всю ширину карточки (включая под превью)
     поэтому расширяем их через calc и отрицательный margin */
  .rr3_details_btn{
    grid-area: detailsbtn;
    margin-top: 50px;
    width: calc(100% + var(--rr3-desk-media-w) + var(--rr3-desk-gap));
    margin-left: calc(-1 * (var(--rr3-desk-media-w) + var(--rr3-desk-gap)));
    padding: 12px 16px;
    border-radius: 10px;
  }

  .rr3_details_body{
    grid-area: details;
    width: calc(100% + var(--rr3-desk-media-w) + var(--rr3-desk-gap));
    margin-left: calc(-1 * (var(--rr3-desk-media-w) + var(--rr3-desk-gap)));
  }

  /* серый контейнер деталей + две колонки (стопы / карта) */
  .rr3_details_body .rr3_stops_wrap{
    background:#F5F5F5;
    border-radius: 14px;
    padding: 14px;
    display:grid;
    
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items:stretch;
  }

  .rr3_stops_wrap{ padding-top:0; } /* убираем мобильный отступ */

  /* стопы крупнее на десктопе */
  .rr3_stop{
    grid-template-columns: 190px 1fr;
    column-gap: 14px;
  }
  .rr3_stop_txt,
  .rr3_stop_station{
    font-size:12px;
    line-height:16px;
  }

  .rr3_map{
    height: 230px;
    border-radius: 14px;
  }
}

</style>
@endonce

@once
<script>
document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-rr3-details-btn]');
    if(!btn) return;

    const card = btn.closest('.rr3_card');
    const body = card.querySelector('[data-rr3-details-body]');
    const isOpen = card.classList.contains('is-open');

    card.classList.toggle('is-open', !isOpen);

    if(body){
        body.hidden = isOpen;
        btn.setAttribute('aria-expanded', String(!isOpen));
    }
});
</script>
@endonce

<div class="rr3_scope">
    <div class="rr3_list">

        @foreach($regularRaces as $alias => $races)
            @if ($races->isEmpty() || $stations[$alias]->isEmpty())
                @continue
            @endif

            <div class="rr3_group_title">
                <img src="{{ asset('images/legacy/' . $alias . '.png') }}" alt="light">
                <h2>@lang('reqular_race_' . $alias)</h2>
            </div>

            @foreach($races as $race)
                @if($stopId > 0 && $race->stops->first()->stop_id != $stopId)
                    @continue
                @endif

                @php
                    $firstStop = $race->stops->first();
                    $lastStop  = $race->stops->last();

                    $depTime = $firstStop ? date('H:i', strtotime($firstStop->arrival_time)) : '';
                    $arrTime = $lastStop  ? date('H:i', strtotime($lastStop->arrival_time))  : '';

                    $depStation = $rr3Decode($firstStop->stopTitle ?? '');
                    $arrStation = $rr3Decode($lastStop->stopTitle ?? '');

                    if ($lang === 'en') {
                        $daysText = collect($race)->get('days_en', '');
                    } elseif ($lang === 'ua') {
                        $daysText = collect($race)->get('days_ua', '');
                    } else {
                        $daysText = collect($race)->get('days_ru', '');
                    }

                    $routeTitle = trim(($race->departure ?? '').' — '.($race->arrive ?? ''));
                    $durationText = '11 год. 30 хв в дорозі';
                @endphp

                <article class="rr3_card is-open">

                    <div class="rr3_badge">{{ $routeTitle }}</div>

                    <div class="rr3_media">
                        <img src="{{ $busImgMob }}" alt="bus" loading="lazy">
                    </div>

                    <div class="rr3_body">

                        <div class="rr3_toprow">
                            {{-- Departure --}}
                            <div class="rr3_side rr3_side--dep">
                                {!! $ico('rr3-clock.svg','rr3_clock_img','clock') !!}
                                <div class="rr3_time">{{ $depTime }}</div>

                                <div class="rr3_place">
                                    <div class="rr3_cityline">
                                        <div class="rr3_city">м. {{ $race->departure }}</div>
                                        <span class="rr3_flag ua" aria-hidden="true"></span>
                                    </div>
                                    <div class="rr3_station">{{ $depStation }}</div>
                                </div>
                            </div>

                            {{-- Mid --}}
                            <div class="rr3_mid">
<div class="rr3_dash" aria-hidden="true">
    <img class="rr3_dash_img" src="{{ $busCenterIcon }}" alt="" loading="lazy">
</div>


                                <div class="rr3_duration">
                                    {!! $ico('rr3-duration.svg','rr3_duration_img','duration') !!}
                                    <span>{{ $durationText }}</span>
                                </div>
                            </div>

                            {{-- Arrival --}}
                            <div class="rr3_side rr3_side--arr">
                                {!! $ico('rr3-clock.svg','rr3_clock_img','clock') !!}
                                <div class="rr3_time">{{ $arrTime }}</div>

                                <div class="rr3_place">
                                    <div class="rr3_cityline">
                                        <div class="rr3_city">м. {{ $race->arrive }}</div>
                                        <span class="rr3_flag gr" aria-hidden="true"></span>
                                    </div>
                                    <div class="rr3_station">{{ $arrStation }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Icons --}}
                        <div class="rr3_icons">
                            <div class="rr3_chip" title="Wi-Fi">{!! $ico('rr3-wifi.svg','rr3_chip_icon','wifi') !!}</div>
                            <div class="rr3_chip" title="Видео">{!! $ico('rr3-video.svg','rr3_chip_icon','video') !!}</div>
                            <div class="rr3_chip" title="Розетка">{!! $ico('rr3-plug.svg','rr3_chip_icon','plug') !!}</div>
                            <div class="rr3_chip" title="Кондиционер">{!! $ico('rr3-snow.svg','rr3_chip_icon','snow') !!}</div>
                        </div>

                        {{-- Schedule --}}
                        <div class="rr3_schedule">
                            <div class="rr3_chip" aria-hidden="true">{!! $ico('rr3-calendar.svg','rr3_chip_icon','calendar') !!}</div>
                            <span class="rr3_schedule_label">График поездок:</span>
                            <div class="rr3_schedule_value" title="{{ $daysText ?: '—' }}">
                                {{ $daysText ?: '—' }}
                            </div>
                        </div>

                        {{-- Details button --}}
                        <button class="rr3_details_btn" type="button" data-rr3-details-btn aria-expanded="true">
                            Детали маршрута <span class="rr3_chev" aria-hidden="true"></span>
                        </button>

                        {{-- Details body --}}
                        <div class="rr3_details_body" data-rr3-details-body>
                            <div class="rr3_stops_wrap">

                                <div class="rr3_stops">
                                    <div class="rr3_line" aria-hidden="true"></div>

                                    @foreach($race->stops as $stop)
                                        @php $stopTitle = $rr3Decode($stop->stopTitle ?? ''); @endphp

                                        <div class="rr3_stop">
                                            <div class="rr3_stop_left">
                                                <span class="rr3_dot" aria-hidden="true"></span>
                                                <div class="rr3_stop_txt" title="{{ date('H:i',strtotime($stop->arrival_time)) }} - м. {{ $stop->stopCity }}">
                                                    {{ date('H:i',strtotime($stop->arrival_time)) }} - м. {{ $stop->stopCity }}
                                                </div>
                                            </div>
                                            <div class="rr3_stop_station" title="{{ $stopTitle }}">
                                                {{ $stopTitle }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="rr3_map" aria-hidden="true"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="rr3_actions">
                            <button
                                class="rr3_btn buy buy-online-btn"
                                data-days="{{ $race->days }}"
                                data-arrival="{{ $race->arrivalId }}"
                                data-departure="{{ $race->departureId }}"
                                data-redirect="{{ route('tickets.index') }}"
                                type="button"
                            >
                                @lang('buy_online')
                            </button>

                            <a href="#form-callback-reserve" class="rr3_btn reserve book-btn">
                                @lang('reserve')
                            </a>
                        </div>

                    </div>
                </article>
            @endforeach
        @endforeach

    </div>
</div>
