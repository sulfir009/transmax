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
    
    $countryCodeMap = [
        'ukraine' => 'ua',
        'romania' => 'ro',
        'greece' => 'gr',
        'moldova' => 'md',
        'bulgaria' => 'bg',
    ];

    $flagCode = function(?string $countryName) use ($countryCodeMap) {
        $key = strtolower(trim((string) $countryName));
        return $countryCodeMap[$key] ?? '';
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
.rr3_side--arr .rr3_place{ align-items:flex-start; } /* оставляю как у тебя для мобилки */

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
.rr3_flag.ro{ background:linear-gradient(90deg, #002B7F 0 33.33%, #FCD116 33.33% 66.66%, #CE1126 66.66% 100%); }
.rr3_flag.md{ background:linear-gradient(90deg, #0033A0 0 33.33%, #FFD100 33.33% 66.66%, #D52B1E 66.66% 100%); }
.rr3_flag.bg{ background:linear-gradient(#FFFFFF 0 33.33%, #00966E 33.33% 66.66%, #D62612 66.66% 100%); }


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
    height:42px;
    display:block;
    object-fit:contain;
}

/* duration: по умолчанию (мобилка) скрываем, desktop включим ниже */
.rr3_duration{
    display:none;
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
    width:50px; height:50px; display:block;
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
    background:rgba(110,113,114,0.55);
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
.rr3_card.is-open .rr3_details_btn{ background:#6E7172; }

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
   RR3 — DESKTOP LAYOUT (>= 769px) — match right.png
   ========================================================= */
@media (min-width: 769px){

  .rr3_scope{
    --rr3-desk-media-w: 290px;  /* ширина превью слева */
    --rr3-desk-gap: 16px;       /* зазор между превью и правой частью */
    --rr3-desk-pad: 14px;       /* внутренние отступы карточки */

    --rr3-chip: 44px;           /* белые кружки */
    --rr3-chip-ico: 41px;       /* размер иконки внутри */
    --rr3-actions-gap: 18px;    /* расстояние между кнопками */
  }

  .rr3_card{
    padding: var(--rr3-desk-pad);
    display:flex;
    flex-wrap:wrap;
    align-items:flex-start;
    gap:0;
  }

  .rr3_badge{
    left: calc(var(--rr3-desk-pad) + 8px);
    top:  calc(var(--rr3-desk-pad) + 8px);
    border-radius: 10px;
    padding: 6px 10px;
    font-size: 12px;
    line-height: 14px;
  }

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
    display:block;
  }

  .rr3_body{
    flex: 1 1 calc(100% - var(--rr3-desk-media-w) - var(--rr3-desk-gap));
    margin-left: var(--rr3-desk-gap);
    padding:0;

    display:grid;
    grid-template-columns: 1fr auto;
    grid-template-areas:
      "top      top"
      "icons    schedule"
      "actions  actions"
      "detailsbtn detailsbtn"
      "details  details";
    column-gap: 18px;
    row-gap: 18px;
    align-items:center;
  }

  /* ===== TOP ROW ===== */
  .rr3_toprow{
    grid-area: top;

    display:flex;
    flex-direction:row;
    align-items:flex-start;
    justify-content:space-between;
    gap: 28px;

    padding-bottom: 18px;
    border-bottom: 1px solid rgba(0,0,0,.10);
  }

  .rr3_side--dep{ flex:1 1 0; }
  .rr3_side--arr{
    flex:1 1 0;
    justify-content:flex-end;
    text-align:right;
  }

  /* на референсе справа нет иконки часов */
  .rr3_side--arr .rr3_clock_img{
    display:none !important;
  }

  /* типографика как на фото */
  .rr3_time{
    font-size: 22px;
    line-height: 26px;
    margin-top: 12px;
            margin-right: 32px;
  }
  .rr3_city{
    font-size: 18px;
    line-height: 22px;
    max-width:none;
  }
  .rr3_station{
    font-size: 18px;
    line-height: 22px;
    max-width:none;
  }

  /* правый блок выравнивание */
  .rr3_side--arr .rr3_place{ align-items:flex-end; }
  .rr3_side--arr .rr3_cityline{ justify-content:flex-end; }
  .rr3_side--arr .rr3_station{ text-align:right; }

  /* MID + duration */
  .rr3_mid{
    flex:0 0 260px;
    max-width:260px;
    margin:0;
    padding:0;
    gap:10px;
  }

  .rr3_dash_img{
    height: 42px;
    object-fit:contain;
  }

  .rr3_duration{
    display:flex; /* desktop показываем */
    align-items:center;
    justify-content:center;
    gap:10px;
    color:#878D8F;
    font-weight:400;
    font-size:16px;
    line-height:19px;
  }
  .rr3_duration_img{
    width:18px;
    height:18px;
    flex:0 0 auto;
  }
  .rr3_duration_img svg,
  .rr3_duration_img img{
    width:18px;
    height:18px;
    display:block;
  }

  /* ===== chips: белые кружки с тенью как на фото ===== */
  .rr3_chip{
    width: var(--rr3-chip);
    height: var(--rr3-chip);
    border-radius:999px;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
    box-shadow: 0px 6px 18px rgba(0,0,0,.12);
  }

  .rr3_chip .rr3_chip_icon,
  .rr3_chip svg,
  .rr3_chip img{
    width: var(--rr3-chip-ico);
    height: var(--rr3-chip-ico);
    display:block;
  }

  /* icons слева (не переносим) */
  .rr3_icons{
    grid-area: icons;
    display:flex;
    align-items:center;
    gap: 14px;
    flex-wrap:nowrap;
  }

  /* schedule справа */
  .rr3_schedule{
    grid-area: schedule;
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap: 14px;
    background: transparent;
    padding:0;
    border-radius:0;
  }

  .rr3_schedule_label{
    font-size:16px;
    line-height:19px;
    color:#878D8F;
    font-weight:400;
    white-space:nowrap;
  }

  .rr3_schedule_value{
    font-size:16px;
    line-height:19px;
    color:#878D8F;
    font-weight:700;
    margin-left:0;
    max-width:none;
    overflow:visible;
    text-overflow:clip;
    white-space:nowrap;
    text-align:left;
  }

  /* ===== actions: две большие кнопки ===== */
  .rr3_actions{
    grid-area: actions;
    padding:0;
    display:flex;
    flex-direction:row;
    align-items:center;
    justify-content:flex-end;
    gap: var(--rr3-actions-gap);
  }

  .rr3_btn{
    width:auto;
    border-radius:999px;
    min-height: 68px;
    padding: 0 46px;
    font-size:18px;
    line-height: 1;
  }

  .rr3_btn.buy{
    min-width: clamp(240px, 32vw, 340px);
    font-weight:700;
    box-shadow: 0px 10px 25px rgba(255,153,0,.35);
  }

  .rr3_btn.reserve{
    min-width: clamp(200px, 26vw, 280px);
    font-weight:600;
    box-shadow: 0px 10px 25px rgba(52,185,240,.30);
  }

  /* ===== детали — растягиваем на всю ширину как было, но без лишнего отступа сверху ===== */
  .rr3_details_btn{
    grid-area: detailsbtn;
    margin-top: 0;

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

  .rr3_details_body .rr3_stops_wrap{
    background:#F5F5F5;
    border-radius: 14px;
    padding: 14px;
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    align-items:stretch;
  }

  .rr3_stops_wrap{ padding-top:0; }

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
                    $depCountryCode = $flagCode($firstStop->stopCountryEn ?? null);
                    $arrCountryCode = $flagCode($lastStop->stopCountryEn ?? null);

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

                <article class="rr3_card">

                    <div class="rr3_badge">{{ $routeTitle }}</div>

                    <div class="rr3_media">
                        <img src="{{ $busImgMob }}" alt="bus" loading="lazy">
                    </div>

                    <div class="rr3_body">

                        <div class="rr3_toprow">
                            {{-- Departure --}}
                            <div class="rr3_side rr3_side--dep">
                                
                                <div class="rr3_time">{{ $depTime }}</div>

                                <div class="rr3_place">
                                    <div class="rr3_cityline">
                                        <div class="rr3_city">м. {{ $race->departure }}</div>
                                        <span class="rr3_flag {{ $depCountryCode }}" aria-hidden="true"></span>
                                    </div>
                                    <div class="rr3_station">{{ $depStation }}</div>
                                </div>
                            </div>

                            {{-- Mid --}}
                            <div class="rr3_mid">
<div class="rr3_dash" aria-hidden="true">
    <img class="rr3_dash_img" src="{{ $busCenterIcon }}" alt="" loading="lazy">
</div>


                                <div class="rr3_duration" hidden>
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
                                        <span class="rr3_flag {{ $arrCountryCode }}" aria-hidden="true"></span>
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
                        <button class="rr3_details_btn" type="button" data-rr3-details-btn aria-expanded="false">
                            Детали маршрута <span class="rr3_chev" aria-hidden="true"></span>
                        </button>

                        {{-- Details body --}}
                        <div class="rr3_details_body" data-rr3-details-body hidden>
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
