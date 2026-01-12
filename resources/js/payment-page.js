$(document).ready(function () {
    // Инициализация слайдера шагов покупки
    initPurchaseSteps();
    
    // Маски для полей карты (если понадобятся)
    $('#card_number').mask("9999 9999 9999 9999");
    $('#card_valid_date').mask("99/99");
    $('#card_cvv').mask("999");

    // Обработчик кнопки оплаты
    $('#orderTicket').click(function() {
        processPayment();
    });

    // Переключение методов оплаты
    $('input[name=paymethod]').on('change', function () {
        if ($(this).data('cardpay')) {
            $('.payment_data').show();
        } else {
            $('.payment_data').hide();
        }
    });
});

/**
 * Инициализация слайдера шагов
 */
function initPurchaseSteps() {
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

    // Для мобильных устройств показываем текущий шаг
    if ($(window).width() < 576) {
        $('.purchase_steps').slick('slickGoTo', 2, true);
    }
}

/**
 * Обработка платежа
 */
function processPayment() {
    const paymethod = $('input[name="paymethod"]:checked').val();
    
    initLoader();
    
    // Сначала создаем заказ
    $.ajax({
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': paymentData.csrfToken
        },
        url: paymentData.ajaxUrl,
        data: {
            'request': 'order_route',
            'paymethod': paymethod,
            'ticket_info': paymentData.ticketInfo,
            'order': paymentData.order
        },
        success: function (response) {
            if (response.data === 'ok') {
                // Заказ успешно создан
                handlePaymentMethod(paymethod);
                deleteOrderTourId();
            } else {
                removeLoader();
                showAlert(paymentData.messages.orderError);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            removeLoader();
            console.error('AJAX error: ', textStatus, errorThrown);
            showAlert(paymentData.messages.orderError);
        }
    });
}

/**
 * Обработка метода оплаты
 */
function handlePaymentMethod(paymethod) {
    if (paymethod === 'cash') {
        // Оплата наличными - отправляем письмо
        sendOrderEmail();
        // Перенаправляем на страницу благодарности
        setTimeout(function() {
            window.location.href = paymentData.successUrl;
        }, 500);
    } else {
        // Оплата картой - создаем платеж через LiqPay
        createLiqPayPayment();
    }
}

/**
 * Создание платежа через LiqPay
 */
function createLiqPayPayment() {
    $.ajax({
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': paymentData.csrfToken
        },
        url: paymentData.paymentCreateUrl,
        data: {
            'ticket_info': paymentData.ticketInfo,
            'order': paymentData.order,
            'total_price': paymentData.totalPrice
        },
        success: function(paymentResponse) {
            if (paymentResponse.success) {
                // Создаем форму и отправляем на LiqPay
                const form = $('<form/>', {
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
                showAlert(paymentData.messages.paymentError + ': ' + paymentResponse.error);
            }
        },
        error: function() {
            removeLoader();
            showAlert(paymentData.messages.orderError);
        }
    });
}

/**
 * Отправка письма с подтверждением заказа
 */
function sendOrderEmail() {
    $.ajax({
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': paymentData.csrfToken
        },
        url: paymentData.ajaxUrl,
        data: {
            'request': 'order_mail',
            'ticket_info': paymentData.ticketInfo,
            'order': paymentData.order
        },
        success: function (emailResponse) {
            if (emailResponse === 'ok') {
                console.log('Email sent successfully');
            } else {
                console.error('Email sending failed:', emailResponse);
            }
        },
        error: function(error) {
            console.error('Email sending error:', error);
        }
    });
}

/**
 * Удаление tour_id из сессии
 */
function deleteOrderTourId() {
    $.ajax({
        type: 'post',
        headers: {
            'X-CSRF-TOKEN': paymentData.csrfToken
        },
        url: paymentData.ajaxUrl,
        data: {
            'request': 'delete_order_tour_id'
        }
    });
}

/**
 * Переключение видимости информации о маршруте (мобильная версия)
 */
function toggleRouteInfo(item) {
    $('.route').slideToggle();
    $(item).find('img').toggleClass('rotate');
}

/**
 * Переключение видимости CVV кода
 */
function toggleCvv(item) {
    $(item).toggleClass('active');
    if ($(item).hasClass('active')) {
        $('.cvv_input').attr('type', 'text');
    } else {
        $('.cvv_input').attr('type', 'password');
    }
}

/**
 * Инициализация лоадера
 */
function initLoader() {
    $('body').prepend('<div class="loader"></div>');
}

/**
 * Удаление лоадера
 */
function removeLoader() {
    $('.loader').remove();
}

/**
 * Проверка валидности email
 */
function isEmail(email) {
    if (email.length < 5) {
        return false;
    }

    const parts = email.split('@');
    if (parts.length !== 2) {
        return false;
    }

    const domain = parts[1];
    if (domain.length < 4) {
        return false;
    }

    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Показ уведомления
 */
function showAlert(msg, txt = '') {
    if (msg == undefined || msg == '' || $('.alert').length > 0) {
        return false;
    }

    const alert = document.createElement('div');
    $(alert).addClass('alert');

    const alertContent = document.createElement('div');
    $(alertContent).addClass('alert_content').appendTo(alert);

    const appendOverlay = document.createElement('div');
    $(appendOverlay).addClass('alert_overlay').appendTo(alert);

    const alertTitle = document.createElement('div');
    $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

    if (txt != '') {
        const alertTxt = document.createElement('div');
        $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
    }

    const closeBtn = document.createElement('button');
    $(closeBtn).addClass('alert_ok').text(paymentData.messages.closeBtn || 'Закрыть').appendTo(alertContent);

    $('body').append(alert);
    $(alert).fadeIn();

    $('.alert_ok,.alert_overlay').on('click', function () {
        $('.alert').fadeOut();
        setTimeout(function () {
            $('.alert').remove();
        }, 350);
    });
}
