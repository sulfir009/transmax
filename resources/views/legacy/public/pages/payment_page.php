<?php if (!isset($_SESSION['order']['tour_id'])) {
    header('Location:' . route('main'));
}
Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); //Дата в прошлом
Header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
Header("Pragma: no-cache"); // HTTP/1.1
Header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
?>


<!DOCTYPE html>
<html lang="<?php echo $Router->lang?>">
<head>
    <?php echo  view('layout.components.header.head', [
        'page_data' => $page_data,
    ])->render(); ?></head>
<body>
<div class="wrapper">
    <div class="header">
        <?php echo  view('layout.components.header.header', [
            'page_data' => $page_data,
        ])->render(); ?>
    </div>
    <div class="content">
        <div class="main_filter_wrapper">
            <div class="container">
                <!--?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']).'/public/blocks/filter.php'?-->
            </div>
        </div>
        <div class="purchase_steps_wrapper">
            <div class="tabs_links_container">
                <div class="purchase_steps">
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">1. <?php echo $GLOBALS['dictionary']['MSG_MSG_TICKETS_VIBIR_AVTOBUSA']?></div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">2. <?php echo $Router->writetitle(85)?></div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title active">3. <?php echo $Router->writetitle(86)?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page_content_wrapper">
            <div class="container">
                <?php $ticketInfo = $Db->getOne(" SELECT
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
            ?>
            <div class="flex-row gap-30 booking_blocks">
                <div class="col-xl-7 col-xs-12">
                    <div class="paymethods_block shadow_block">
                        <div
                            class="block_title h2_title"><?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OBERITI_SPOSIB_OPLATI'] ?></div>
                        <div class="paymethods_block_subtitle par">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_DLYA_OFORMLENNYA_ZAMOVLENNYA_OPLATITI_JOGO_DO'] . ' ' . $paymentDateTime ?>
                        </div>
                        <div class="paymethod_rows">
                            <div class="paymethod_row flex_ac flex-row">
                                <div class="col-sm-6 col-xs-9">
                                    <label class="c_checkbox_wrapper flex_ac">
                                        <input type="radio" name="paymethod" class="c_checkbox_checker" hidden
                                               data-cardpay="true" value="cardpay">
                                        <span class="c_checkbox"></span>

                                        <span
                                            class="c_checkbox_title par"><?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA'] ?></span>
                                    </label>
                                </div>
                                <div class="col-md-3 hidden-sm hidden-xs">
                                    <div class="paymethod_logo">
                                        <img src="<?php echo  asset('images/legacy/common/bank_card.svg'); ?>" alt="bank card">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-3">
                                    <div class="total_price pay_total h3_title">
                                        <?php echo  $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN'] ?>
                                    </div>
                                </div>
                            </div>

                            <div class="paymethod_row flex_ac flex-row">
                                <div class="col-sm-6 col-xs-9">
                                    <label class="c_checkbox_wrapper flex_ac">
                                        <input type="radio" name="paymethod" class="c_checkbox_checker" hidden
                                               data-cardpay="false" value="cash" checked>
                                        <span class="c_checkbox"></span>
                                        <span
                                            class="c_checkbox_title par"><?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GOTIVKOYU'] ?></span>
                                    </label>
                                </div>
                                <div class="col-md-3 hidden-sm hidden-xs">
                                    <div class="paymethod_logo">
                                        <img src="<?php echo  asset('images/legacy/common/cash.svg'); ?>" alt="bank card">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-3">
                                    <div class="total_price pay_total h3_title">
                                        <?php echo  $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN'] ?>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Удалена старая форма LiqPay, теперь используем новую интеграцию -->


                    <button class="payment_btn h4_title blue_btn flex_ac" id="orderTicket">
                        <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_OPLATITI'] ?>
                    </button>
                    <div class="payment_clarification par">
                        <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI'] ?>
                    </div>
                </div>
                <div class="col-xxl-1 hidden-xl hidden-lg hidden-md hidden-sm hidden-xs"></div>
                <div class="col-xxl-4 col-xl-5 col-xs-12">
                    <div class="route_block">
                        <div class="route_block_title h3_title hidden-md hidden-sm hidden-xs">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_MARSHRUT'] ?>
                        </div>
                        <div class="mobile_route_block_title flex_ac h3_title hidden-xxl hidden-xl hidden-lg"
                             onclick="toggleRouteInfo(this)">
                            <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_MARSHRUT'] ?>
                            <img src="<?php echo  asset('images/legacy/common/arrow_down_2.svg'); ?>" alt="arrow down">
                        </div>
                        <div class="route">
                            <div class="route_details_info">
                                <div class="route_points">
                                    <div class="route_point_block par">
                                        <div class="route_point active"></div>
                                        <div class="route_time">
                                            <?php echo  date('H:i', strtotime($ticketInfo['departure_time'])) ?>
                                        </div>
                                        <div class="route_point_title">
                                            <?php echo  $ticketInfo['departure_city'] . ' ' . $ticketInfo['departure_station'] ?>
                                        </div>
                                    </div>
                                    <div class="route_point_block par">
                                        <div class="route_point"></div>
                                        <div class="route_time">
                                            <?php echo  date('H:i', strtotime($ticketInfo['arrival_time'])) ?>
                                        </div>
                                        <div class="route_point_title">
                                            <?php echo  $ticketInfo['arrival_city'] . ' ' . $ticketInfo['arrival_station'] ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="route_options flex-row gap-y-20">
                                    <div class="payment_date_wrapper">
                                        <div
                                            class="payment_date_title par"><?php echo  $GLOBALS['dictionary']['MSG_ALL_KOLI'] ?></div>
                                        <div class="payment_date"><?php echo $_SESSION['order']['date'] ?></div>
                                    </div>
                                </div>
                                <div class="route_options flex-row gap-y-20">
                                    <?php $getBusOptions = $Db->getall("SELECT title_" . $Router->lang . " AS title FROM `" . DB_PREFIX . "_buses_options`
                                         WHERE id IN(SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = '" . $ticketInfo['bus'] . "' )");
                                    foreach ($getBusOptions as $k => $busOption) {
                                        ?>
                                        <div class="col-md-<?php if ($k % 2 == 0) {
                                            echo  '5';
                                        } else {
                                            echo  '7';
                                        } ?>">
                                            <div class="bus_option flex_ac par">
                                                <div class="check_imitation"></div>
                                                <?php echo  $busOption['title'] ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="route_passagers h5_title">
                                    <span><?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_PASAZHIRIV'] ?></span>
                                    <span><?php echo $_SESSION['order']['passengers'] ?></span>
                                </div>
                            </div>
                            <div class="route_details_delimiter"></div>
                            <div class="route_details_info">
                                <div class="route_price h4_title flex_ac">
                                    <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_CINA'] ?>
                                    <span class="total_price h3_title">

                                    <?php echo  $ticketInfo['price'] . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN'] ?>
                                        </span>
                                </div>
                                <div class="route_price h4_title flex_ac route_payment_price">
                                    <?php echo  $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_DO_SPLATI'] ?>
                                    <span class="total_price h3_title">
                                        <?php echo  $totalPrice . ' ' . $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_GRN'] ?>
                                        </span>
                                </div>

                                <a href="<?php echo  $Router->writelink(87) ?>"
                                   class="small_link"><?php echo  $Router->writetitle(87) ?></a>
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
    <?php echo  view('layout.components.footer.footer_scripts', [
        'page_data' => $page_data,
    ])->render(); ?>
    <div class="d_none">
    <?php out($ticketInfo)?>
</div>
<script src="<?php echo  mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>
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

        var ticketInfo = <?php echo  json_encode($ticketInfo); ?>;
        var order = <?php echo  json_encode($_SESSION['order']); ?>;
        var totalPrice = <?php echo $totalPrice; ?>;

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
            ;
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
                        console.log("TI:", ticketInfo, "order:", order);

                        // Отправляем запрос только если метод оплаты - "cash"
                        if (paymethod === 'cash') {
                            $.ajax({
                                type: 'post',
                                url: '/ajax/ru',
                                data: {
                                    'request': 'order_mail',
                                    'ticket_info': ticketInfo,
                                    'order': order
                                },
                                success: function (emailResponse) {
                                    if (emailResponse == 'ok') {
                                        console.log(order);
                                    } else {
                                        console.error(emailResponse);
                                    }
                                }
                            });
                        }

                        // Если запись заказа в базу данных успешна, и выбран метод оплаты картой,
                        // создаем платеж через новую систему LiqPay
                        if ($('input[name=paymethod]:checked').data('cardpay')) {
                            // Отправляем запрос на создание платежа
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
                                        // Создаем форму и отправляем на LiqPay
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
                            // Если метод оплаты не карта, перенаправляем пользователя на страницу благодарности
                            location.href = '<?php echo $Router->writelink(90)?>';
                        }
                        deleteOrderTourId();
                    } else {
                        // Если запись не успешна, выводим сообщение об ошибке
                        out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                    }

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error: ', textStatus, errorThrown);
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                }
            })
        });

        function orderTicket() {
            let card_number = $.trim($('#card_number').val());
            let card_valid_date = $.trim($('#card_valid_date').val());
            let card_cvv = $.trim($('#card_cvv').val());
            let cardholder_name = $.trim($('#cardholder_name').val());
            let saveCard = 0;
            let paymethod = $('input[name="paymethod"]:checked').val();
            if ($('#save_card').is(':checked')) {
                saveCard = 1;
            }
            ;
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
                        console.log("TI:", ticketInfo, "order:", order);

                        // Отправляем запрос только если метод оплаты - "cash"
                        if (paymethod === 'cash') {
                            $.ajax({
                                type: 'post',
                                url: '/ajax/ru',
                                data: {
                                    'request': 'order_mail',
                                    'ticket_info': ticketInfo,
                                    'order': order
                                },
                                success: function (emailResponse) {
                                    if (emailResponse == 'ok') {
                                        console.log(order);
                                    } else {
                                        console.error(emailResponse);
                                    }
                                }
                            });
                        }

                        // Если запись заказа в базу данных успешна, и выбран метод оплаты картой,
                        // создаем платеж через новую систему LiqPay
                        if ($('input[name=paymethod]:checked').data('cardpay')) {
                            // Отправляем запрос на создание платежа
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
                                        // Создаем форму и отправляем на LiqPay
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
                            // Если метод оплаты не карта, перенаправляем пользователя на страницу благодарности
                            location.href = '<?php echo $Router->writelink(90)?>';
                        }
                        deleteOrderTourId();
                    } else {
                        // Если запись не успешна, выводим сообщение об ошибке
                        out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                    }

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error: ', textStatus, errorThrown);
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE']?>');
                }
            })
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

        $(document).ready(function () {
            if ($(window).width() < 576) {
                $('.purchase_steps').slick('slickGoTo', 2, true)
            }
        });

        $('.doc_select').niceSelect();

        function toggleRouteInfo(item) {
            $('.route').slideToggle();
            $(item).find('img').toggleClass('rotate');
        };

        $('input[name=paymethod]').on('change', function () {
            if ($(this).data('cardpay')) {
                $('.payment_data').show();
            } else {
                $('.payment_data').hide();
            }
        });

        function toggleCvv(item) {
            $(item).toggleClass('active');
            if ($(item).hasClass('active')) {
                $('.cvv_input').attr('type', 'text');
            } else {
                $('.cvv_input').attr('type', 'password');
            }
        };

        function testMail() {
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'order_mail',
                    'ticket_info': ticketInfo,
                    'order': order
                },

                success: function (emailResponse) {
                    console.log(JSON.stringify(emailResponse));
                    if (emailResponse == 'ok') {
                        console.log('Email sent successfully');
                    } else {
                        console.error(emailResponse);
                    }
                }
            });
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
    });


</script>
</body>
</html>
