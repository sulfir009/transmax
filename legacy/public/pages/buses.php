<!DOCTYPE html>
<html lang="<?=$Router->lang?>">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/head.php' ?>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/header.php' ?>
    </div>
    <div class="content">
        <div class="page_content_wrapper">
            <div class="container">
                <div class="our_buses_title h2_title">
                    <?$busesTitle = $Db->getOne("SELECT title_".$Router->lang." AS title FROM `" .  DB_PREFIX . "_txt_blocks`WHERE id = 10 ")?>
                    <?=$busesTitle['title']?>
                </div>
                <div class="our_buses_subtitle par">
                    <?$busesTxt = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `" .  DB_PREFIX . "_txt_blocks`WHERE id = 10 ")?>
                    <?=$busesTxt['text']?>
                </div>
                <div class="our_buses_container">
                    <? $busesCount = $Db->getOne("SELECT COUNT(id) FROM `" . DB_PREFIX . "_buses` WHERE active = '1' ");
                    $getBuses = $Db->getAll("SELECT id,image,seats_qty,title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_buses` WHERE active = '1' ORDER BY sort DESC LIMIT 6");

                    foreach ($getBuses as $k => $bus) {
                        $busImages = $Db->getAll("SELECT bus_img FROM `" .  DB_PREFIX . "_buses_images`WHERE bus_id = '".$bus['id']."'");
                        ?>
                        <div class="bus flex-row gap-30">
                            <div class="col-lg-6">
                                <div class="bus_img">
                                    <? foreach ($busImages as $busimg){ ?>
                                        <img src="<?= asset('images/legacy/upload/buses/' . $busimg['bus_img']); ?>" alt="bus" class="fit_img">
                                    <? }?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="bus_info">
                                    <div class="bus_title h2_title">
                                        <?= $bus['title'] ?>
                                    </div>
                                    <div class="bus_seats flex_ac h4_title">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_KILIKISTI_MISCI']?>
                                        <span class="total_seats h2_title">
                                        <?= $bus['seats_qty'] ?>
                                    </span>
                                    </div>
                                    <div class="bus_info_delimiter"></div>
                                    <div class="bus_options">
                                        <div class="flex-row gap-30">
                                            <? $getBusAdditionalOptions = $Db->getAll("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE active = '1'
                                        AND id IN(SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = '" . $bus['id'] . "' ) ORDER BY sort DESC");
                                            foreach ($getBusAdditionalOptions as $key => $additionalOption) {?>
                                                <div class="col-sm-4 col-xs-6">
                                                    <div class="bus_option flex_ac par">
                                                        <div class="check_imitation"></div>
                                                        <?=$additionalOption['title']?>
                                                    </div>
                                                </div>
                                            <? } ?>
                                        </div>
                                    </div>
                                    <button class="order_bus_link h4_title flex_ac blue_btn" onclick="toggleOrderBus('<?=$bus['id']?>', <?= $bus['seats_qty'] ?>)">
                                        <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_ZAMOVITI_AVTOBUS']?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <? } ?>
                </div>
                <?if ($busesCount['COUNT(id)'] > 6){?>
                    <button class="more_buses_btn h4_title" onclick="moreBuses()">
                        <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_ZAVANTAZHITI_SCHE']?>
                    </button>
                <?}?>
            </div>
        </div>
    </div>
    <div class="footer">
        <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/footer.php' ?>
    </div>
</div>
<div class="order_bus_popup blue_popup">
    <div class="order_bus_popup_content_wrapper">
        <div class="close_order_bus_wrapper">
            <button class="close_menu" onclick="toggleOrderBus()">
                <img src="<?= asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
        </div>
        <div class="order_bus_popup_content">
            <div class="order_bus_row">
                <div class="order_bus_row_title" ><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_ZVIDKI']?></div>
                <input type="text" class="c_input order_bus_input par req_input" placeholder="<?=$GLOBALS['dictionary']['MSG_MSG_BOOKING_IMYA_']?>" id="name"
                >
            </div>
            <div class="order_bus_row">
                <div class="order_bus_row_title" ><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_KUDI']?></div>
                <div class="flex_ac">
                    <select class="phone_country_code flex_ac" onchange="changeInputMask(this)" id="phone_code">
                        <?$getPhoneCodes = $Db->getall("SELECT * FROM `".DB_PREFIX."_phone_codes` WHERE active = '1' ORDER BY sort DESC");
                        foreach ($getPhoneCodes AS $k=>$phoneCode){
                            if ($k == 0){
                                $firstPhoneExample = $phoneCode['phone_example'];
                                $firstPhoneMask = $phoneCode['phone_mask'];
                            }?>
                            <option value="<?=$phoneCode['id']?>" data-mask="<?=$phoneCode['phone_mask']?>" data-placeholder="<?=$phoneCode['phone_example']?>" <?if ($k == 0){echo 'selected';}?>><?=$phoneCode['phone_country']?></option>
                        <?}?>
                    </select>
                    <input type="text" class="c_input order_bus_phone order_bus_input inter req_input" placeholder="<?=$firstPhoneExample?>" id="phone"
                        <?if (trim($clientInfo['phone']) != ''){?>
                            value="<?=$clientInfo['phone']?>"
                        <?}?>
                    >
                </div>
            </div>
            <div class="order_bus_row order_bus_date">
                <div class="order_bus_row_title"><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_KOLI']?></div>
                <div class="order_bus_date_wrapper">
                    <input type="text" class="order_bus_date_input filter_date">
                    <button class="filter_calendar_btn order_bus_calendar_btn" onclick="toggleDateCalendar()">
                        <img src="<?= asset('images/legacy/common/filter_calendar.svg'); ?>" alt="calendar" class="fit_img">
                    </button>
                </div>
            </div>
            <div class="order_bus_row">
                <div class="order_bus_passengers_wrapper">
                    <div class="order_bus_row_title" ><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_PASAZHIRI']?></div>
                    <? $adults = 1;
                    $kids = 0;
                    if (isset($_GET['adults'])) {
                        $adults = (int)$_GET['adults'];
                    }
                    if (isset($_GET['kids'])) {
                        $kids = (int)$_GET['kids'];
                    } ?>
                    <div class="order_bus_row_value flex_ac" onclick="toggleOrderBusSubmenu(this)">
                        <div>
                            <span class="adults_total"><?= $adults ?></span> <?= $GLOBALS['dictionary']['MSG_ALL_DOROSLIH'] ?>
                        </div>
                        <div>
                            <span class="kids_total"><?= $kids ?></span> <?= $GLOBALS['dictionary']['MSG_MSG_BUSES_DITEJ'] ?>
                        </div>
                    </div>

                    <div class="order_bus_row_submenu">
                        <div class="passengers_counter_block flex_ac adult_passagers">
                            <div class="passengers_counter_title h5_title"><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_DOROSLIH']?></div>
                            <div class="passengers_counter flex_ac">
                                <button class="counter_btn minus" onclick="countPassagers(this,'minus','adults')">
                                    <img src="<?= asset('images/legacy/common/minus.svg'); ?>" alt="minus">
                                </button>
                                <div class="p_counter_value par"><?=$adults?></div>
                                <button class="counter_btn plus" onclick="countPassagers(this,'plus','adults', $seats)">
                                    <img src="<?= asset('images/legacy/common/plus.svg'); ?>" alt="plus">
                                </button>
                            </div>
                        </div>
                        <div class="passengers_counter_block flex_ac">
                            <div class="passengers_counter_title h5_title">
                                <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_DITEJ']?>
                                <span><?=$GLOBALS['dictionary']['MSG_MSG_BUSES_DO_3_ROKIV_-_BEZKOSHTOVNO']?></span>
                            </div>
                            <div class="passengers_counter flex_ac">
                                <button class="counter_btn minus" onclick="countPassagers(this,'minus','kids')">
                                    <img src="<?= asset('images/legacy/common/minus.svg'); ?>" alt="minus">
                                </button>
                                <div class="p_counter_value par"><?=$kids?></div>
                                <button class="counter_btn plus" onclick="countPassagers(this,'plus','kids', $seats)">
                                    <img src="<?= asset('images/legacy/common/plus.svg'); ?>" alt="plus">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="blue_btn flex_ac h4_title order_bus_btn" onclick="orderBus()">
                <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_ZABRONYUVATI_AVTOBUS']?>
            </button>
        </div>
        <div class="order_bus_rules">
            <div class="order_bus_rules_title h3_title flex_ac"
                 onclick="$(this).next().slideToggle();$(this).find('img').toggleClass('rotate')">
                <?=$GLOBALS['dictionary']['MSG_MSG_BUSES_PRAVILA_BRONYUVANNYA_AVTOBUSA']?>
                <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow">
            </div>
            <div class="order_bus_rules_txt par">
                <?$bookingTxt = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '8' ")?>
                <?=$bookingTxt['text']?>
                <div class="order_bus_rules_txt_warning">
                    <div class="warning_img">
                        <img src="<?= asset('images/legacy/common/warning.svg'); ?>" alt="warning">
                    </div>
                    <div class="warning_txt par">
                        <?$bookingWarning = $Db->getOne("SELECT text_".$Router->lang." AS text FROM `".DB_PREFIX."_txt_blocks` WHERE id = '9' ")?>
                        <?=$bookingWarning['text']?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="order_bus_overlay overlay" onclick="toggleOrderBus()"></div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/public/blocks/footer_scripts.php' ?>
<script>

    window.addEventListener('load', function() {
        let input = document.getElementById('name');

        // Принудительно задаем стили, когда поле будет автозаполнено
        input.addEventListener('input', function() {
            if (input.matches(':-webkit-autofill')) {
                input.style.backgroundColor = 'transparent';
                input.style.color = '#fff';
                input.style.height = '49px';
                input.style.border = 'none';
            }
        });
    });

    // Удаляем красную рамку при вводе данных
    $(document).on('input', '.req_input', function() {
        $(this).removeClass('error-border');
    });

    $('.phone_country_code').niceSelect();

    $('.order_bus_phone').mask("<?=$firstPhoneMask?>");
    function changeInputMask(item){
        let selectedOption = $(item).find(':selected');
        $('.order_bus_phone').mask($(selectedOption).data('mask'));
        $('.order_bus_phone').attr('placeholder',$(selectedOption).data('placeholder'));
    };

    function searchTicketsByBus(){
        let departure = $('#filter_departure').val();
        let arrival = $('#filter_arrival').val();
        let date = $('.filter_date.flatpickr-input').val();
        let adults = +$('.adults_total').text();
        let kids = +$('.kids_total').text();
        let url = '<?=$Router->writelink(76)?>';
        url = url + '?departure=' + departure + '&arrival=' + arrival + '&date=' + date + '&adults=' + adults + '&kids=' + kids;
        location.href = url;
    };



    const orderBusDatePicker = flatpickr(".order_bus_date_input", {
        minDate: "today",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        <?if (isset($_GET['date'])){?>
        defaultDate:"<?=$_GET['date']?>",
        <?}else{?>
        defaultDate:"today",
        <?}?>
        locale:'<?=$Router->lang?>',
        static:true
    });

    function toggleDateCalendar(){
        orderBusDatePicker.open()
    }


    function toggleOrderBus(item, seatsQty) {
        $('.order_bus_popup').toggleClass('active');
        $('.order_bus_overlay').fadeToggle();
        $('body').toggleClass('overflow');
        $('.adults_total').text(1);
        $('.kids_total').text(0);

        // Обновляем значения счетчиков в попапе
        $('.adult_passagers .p_counter_value').text(1);
        $('.kids_passagers .p_counter_value').text(0);
        $seats = seatsQty
        countPassagers($seats);
    };

    function toggleInfoBlock(item) {
        $(item).next().slideToggle();
        $(item).find('img').toggleClass('rotate');
    };

    function toggleOrderBusSubmenu(item){
        if ($(item).next().hasClass('active')){
            $(item).next().removeClass('active');
            return false;
        }else{
            $(item).next().addClass('active').slideDown();
            listenPageToCloseBusSubmenu();
        }
    };

    function listenPageToCloseBusSubmenu(){
        $(document).mouseup( function(e){
            let busSubmenu = $( ".order_bus_row_submenu" );
            if ( !busSubmenu.is(e.target) && busSubmenu.has(e.target).length === 0) {
                busSubmenu.slideUp();
            }if (!e.target.offsetParent.classList.contains('order_bus_row')){
                busSubmenu.removeClass('active');
            }
        });
    }



    function moreBuses(){
        let currentBuses = $('.bus').length;
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?=$Router->writelink(3)?>',
            data:{
                'request':'more_buses',
                'current':currentBuses
            },
            success:function(response){
                if ($.trim(response) != 'err'){
                    $('.our_buses_container').append(response);
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

    function orderBus() {
        let allFieldsFilled = true;
        let name = $('#name').val();
        let phone = $('#phone').val();
        let date = $('.filter_date.flatpickr-input').val();
        let adults = +$('.adults_total').text();
        let kids = +$('.kids_total').text();

        // Удаляем предыдущие ошибки
        $('.req_input').removeClass('error-border');

        $('.req_input').each(function () {
            if ($.trim($(this).val()) === '') {
                $(this).addClass('error-border');
                allFieldsFilled = false; // Устанавливаем флаг в false если хотя бы одно поле не заполнено
            }
        });

        if (!allFieldsFilled) { // Если хотя бы одно поле не заполнено
            out('<?=$GLOBALS['dictionary']['MSG_MSG_BOOKING_ZAPOLNITE_VSE_OBYAZATELINYE_POLYA']?>', '<?=$GLOBALS['dictionary']['MSG_MSG_BOOKING_OBYAZATELINYE_POLYA_OTMECHENY_']?>');
            return false; // Прерываем выполнение функции и не отправляем данные
        }
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?=$Router->writelink(3)?>',
            data:{
                'request':'orderBus',
                'name':name,
                'phone': phone,
                'message': `Заказ автобуса на ${date}. Пассажиры: взрослых - ${adults}, детей - ${kids}.`

            },
            success:function(response){
                if ($.trim(response) === 'ok'){
                    location.href = '<?=$Router->writelink(90)?>'
                }
                else if ($.trim(response) != 'err'){
                    $('.our_buses_container').append(response);
                }
            }
        })
    }
</script>
</body>
</html>
