@extends('layout.app')
{{--@section('styles')
    <link rel="stylesheet" href="{{ asset('css/oplata.css') }}">
@endsection--}}
@section('content')
<div class="wrapper">
    @include('oplata.components.content')
    @include('oplata.components.footbanner')
</div>
@endsection











{{--
@extends('layout.app')

@section('title', $translations['MSG_MSG_PAYMENT_PAGE_OPLATA'] ?? 'Оплата')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/oplata.css') }}">
@endsection

@section('content')
    <div class="purchase_steps_wrapper">
        <div class="tabs_links_container">
            <div class="purchase_steps">
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title">
                        1. {{ $translations['MSG_MSG_TICKETS_VIBIR_AVTOBUSA'] ?? 'Выбор автобуса' }}</div>
                </div>
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title">2. {{ $translations['BOOKING'] ?? 'Бронирование' }}</div>
                </div>
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title active">3. {{ $translations['PAYMENT'] ?? 'Оплата' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page_content_wrapper">
        <div class="container">
            <div class="flex-row gap-30 booking_blocks">
                <div class="col-xl-7 col-xs-12">
                    <div class="paymethods_block shadow_block">
                        <div class="block_title h2_title">
                            {{ $translations['MSG_MSG_PAYMENT_PAGE_OBERITI_SPOSIB_OPLATI'] ?? 'Выберите способ оплаты' }}
                        </div>
                        <div class="paymethods_block_subtitle par">
                            {{ $translations['MSG_MSG_PAYMENT_PAGE_DLYA_OFORMLENNYA_ZAMOVLENNYA_OPLATITI_JOGO_DO'] ?? 'Для оформления заказа оплатите его до' }} {{ $paymentDateTime }}
                        </div>

                        <div class="paymethod_rows">
                            <div class="paymethod_row flex_ac flex-row">
                                <div class="col-sm-6 col-xs-9">
                                    <label class="c_checkbox_wrapper flex_ac">
                                        <input type="radio" name="paymethod" class="c_checkbox_checker" hidden
                                               data-cardpay="true" value="cardpay">
                                        <span class="c_checkbox"></span>
                                        <span class="c_checkbox_title par">
                                        {{ $translations['MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA'] ?? 'Банковской картой' }}
                                    </span>
                                    </label>
                                </div>
                                <div class="col-md-3 hidden-sm hidden-xs">
                                    <div class="paymethod_logo">
                                        <img src="{{ asset('images/legacy/common/bank_card.svg') }}" alt="bank card">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-3">
                                    <div class="total_price pay_total h3_title">
                                        {{ $totalPrice }} {{ $translations['MSG_MSG_PAYMENT_PAGE_GRN'] ?? 'грн' }}
                                    </div>
                                </div>
                            </div>

                            <div class="paymethod_row flex_ac flex-row">
                                <div class="col-sm-6 col-xs-9">
                                    <label class="c_checkbox_wrapper flex_ac">
                                        <input type="radio" name="paymethod" class="c_checkbox_checker" hidden
                                               data-cardpay="false" value="cash" checked>
                                        <span class="c_checkbox"></span>
                                        <span class="c_checkbox_title par">
                                        {{ $translations['MSG_MSG_PAYMENT_PAGE_GOTIVKOYU'] ?? 'Наличными' }}
                                    </span>
                                    </label>
                                </div>
                                <div class="col-md-3 hidden-sm hidden-xs">
                                    <div class="paymethod_logo">
                                        <img src="{{ asset('images/legacy/common/cash.svg') }}" alt="cash">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6 col-xs-3">
                                    <div class="total_price pay_total h3_title">
                                        {{ $totalPrice }} {{ $translations['MSG_MSG_PAYMENT_PAGE_GRN'] ?? 'грн' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="payment_btn h4_title blue_btn flex_ac" id="orderTicket">
                        {{ $translations['MSG_MSG_PAYMENT_PAGE_OPLATITI'] ?? 'Оплатить' }}
                    </button>

                    <div class="payment_clarification par">
                        {{ $translations['MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI'] ?? 'Ваши платежные и личные данные надежно защищены' }}
                    </div>
                </div>

                <div class="col-xxl-1 hidden-xl hidden-lg hidden-md hidden-sm hidden-xs"></div>

                <div class="col-xxl-4 col-xl-5 col-xs-12">
                    <div class="route_block">
                        <div class="route_block_title h3_title hidden-md hidden-sm hidden-xs">
                            {{ $translations['MSG_MSG_PAYMENT_PAGE_MARSHRUT'] ?? 'Маршрут' }}
                        </div>
                        <div class="mobile_route_block_title flex_ac h3_title hidden-xxl hidden-xl hidden-lg"
                             onclick="toggleRouteInfo(this)">
                            {{ $translations['MSG_MSG_PAYMENT_PAGE_MARSHRUT'] ?? 'Маршрут' }}
                            <img src="{{ asset('images/legacy/common/arrow_down_2.svg') }}" alt="arrow down">
                        </div>

                        <div class="route">
                            <div class="route_details_info">
                                <div class="route_points">
                                    <div class="route_point_block par">
                                        <div class="route_point active"></div>
                                        <div class="route_time">
                                            {{ date('H:i', strtotime($ticketInfo['departure_time'])) }}
                                        </div>
                                        <div class="route_point_title">
                                            {{ $ticketInfo['departure_city'] }} {{ $ticketInfo['departure_station'] }}
                                        </div>
                                    </div>
                                    <div class="route_point_block par">
                                        <div class="route_point"></div>
                                        <div class="route_time">
                                            {{ date('H:i', strtotime($ticketInfo['arrival_time'])) }}
                                        </div>
                                        <div class="route_point_title">
                                            {{ $ticketInfo['arrival_city'] }} {{ $ticketInfo['arrival_station'] }}
                                        </div>
                                    </div>
                                </div>

                                <div class="route_options flex-row gap-y-20">
                                    <div class="payment_date_wrapper">
                                        <div class="payment_date_title par">
                                            {{ $translations['MSG_ALL_KOLI'] ?? 'Когда' }}
                                        </div>
                                        <div class="payment_date">{{ $orderData['date'] }}</div>
                                    </div>
                                </div>

                                @if(!empty($busOptions) && is_array($busOptions))
                                    <div class="route_options flex-row gap-y-20">
                                        @foreach($busOptions as $k => $busOption)
                                            @if(is_array($busOption) && isset($busOption['title']))
                                                <div class="col-md-{{ $k % 2 == 0 ? '5' : '7' }}">
                                                    <div class="bus_option flex_ac par">
                                                        <div class="check_imitation"></div>
                                                        {{ $busOption['title'] }}
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <div class="route_passagers h5_title">
                                    <span>{{ $translations['MSG_MSG_PAYMENT_PAGE_PASAZHIRIV'] ?? 'Пассажиров' }}</span>
                                    <span>{{ $orderData['passengers'] }}</span>
                                </div>
                            </div>

                            <div class="route_details_delimiter"></div>

                            <div class="route_details_info">
                                <div class="route_price h4_title flex_ac">
                                    {{ $translations['MSG_MSG_PAYMENT_PAGE_CINA'] ?? 'Цена' }}
                                    <span class="total_price h3_title">
                                    {{ $ticketInfo['price'] }} {{ $translations['MSG_MSG_PAYMENT_PAGE_GRN'] ?? 'грн' }}
                                </span>
                                </div>

                                <div class="route_price h4_title flex_ac route_payment_price">
                                    {{ $translations['MSG_MSG_PAYMENT_PAGE_DO_SPLATI'] ?? 'К оплате' }}
                                    <span class="total_price h3_title">
                                    {{ $totalPrice }} {{ $translations['MSG_MSG_PAYMENT_PAGE_GRN'] ?? 'грн' }}
                                </span>
                                </div>

                                <a href="#" class="small_link">
                                    {{ $translations['RETURN_CONDITIONS'] ?? 'Условия возврата' }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        console.log('🚀 Oplata page scripts loaded');

        $(document).ready(function () {
            console.log('✅ jQuery ready');
            console.log('📊 Initial data:', {
                ticketInfo: @json($ticketInfo),
                orderData: @json($orderData),
                totalPrice: {{ $totalPrice }},
                translations: @json($translations)
            });

            var ticketInfo = @json($ticketInfo);
            var orderData = @json($orderData);
            var totalPrice = {{ $totalPrice }};
            var translations = @json($translations);

            // Проверяем CSRF токен
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                console.log('✅ CSRF token found:', csrfToken.content.substring(0, 10) + '...');
            } else {
                console.error('❌ CSRF token not found!');
            }

            function deleteOrderTourId() {
                console.log('🗑️ Deleting order tour ID...');
                $.ajax({
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    url: '/oplata/delete-order',
                    data: {
                        'request': 'delete_order_tour_id'
                    },
                    success: function (response) {
                        console.log('✅ Order tour ID deleted:', response);
                    },
                    error: function (xhr, status, error) {
                        console.error('❌ Error deleting order tour ID:', error);
                    }
                });
            }

            // Обработчик кнопки оплаты
            $('#orderTicket').click(function (e) {
                e.preventDefault();
                console.log('💳 Order button clicked!');

                let paymethod = $('input[name="paymethod"]:checked').val();
                console.log('💰 Payment method:', paymethod);

                if (!paymethod) {
                    console.error('❌ No payment method selected!');
                    out('Выберите способ оплаты');
                    return;
                }

                console.log('📤 Sending order request...');
                initLoader();

                $.ajax({
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    url: '/oplata/create-order',
                    data: {
                        'paymethod': paymethod,
                        'ticket_info': ticketInfo,
                        'order': orderData
                    },
                    success: function (response) {
                        console.log('✅ Order response received:', response);
                        removeLoader();

                        if (response.success && response.data == 'ok') {
                            console.log('🎉 Order created successfully!');

                            if (paymethod === 'cash') {
                                console.log('💵 Cash payment - redirecting to thanks page...');
                                // Перенаправляем на страницу благодарности
                                window.location.href = '/dyakuyu-za-bronyuvannya-biletu';
                            } else if (paymethod === 'cardpay') {
                                console.log('💳 Card payment - creating LiqPay payment...');
                                // Создаем платеж через LiqPay
                                $.ajax({
                                    type: 'post',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    url: '/oplata/create-payment',
                                    data: {
                                        'ticket_info': ticketInfo,
                                        'order': orderData,
                                        'total_price': totalPrice
                                    },
                                    success: function (paymentResponse) {
                                        console.log('💳 Payment response:', paymentResponse);

                                        if (paymentResponse.success) {
                                            console.log('🏦 Redirecting to LiqPay...');
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
                                            console.error('❌ Payment creation failed:', paymentResponse.error);
                                            out(translations['PAYMENT_ERROR'] || 'Ошибка создания платежа: ' + paymentResponse.error);
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        removeLoader();
                                        console.error('❌ Payment AJAX error:', {
                                            status: status,
                                            error: error,
                                            responseText: xhr.responseText
                                        });
                                        out(translations['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE'] || 'Не удалось оформить заказ, попробуйте позже');
                                    }
                                });
                            }

                            deleteOrderTourId();
                        } else {
                            console.error('❌ Order creation failed:', response);
                            out(translations['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE'] || 'Не удалось оформить заказ, попробуйте позже');
                        }
                    },
                    error: function (xhr, status, error) {
                        removeLoader();
                        console.error('❌ Order AJAX error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            statusCode: xhr.status
                        });
                        out(translations['MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE'] || 'Не удалось оформить заказ, попробуйте позже');
                    }
                });
            });

            // Проверяем что кнопка найдена
            if ($('#orderTicket').length > 0) {
                console.log('✅ Order button found');
            } else {
                console.error('❌ Order button NOT found!');
            }

            // Slick slider initialization
            if (typeof $.fn.slick !== 'undefined') {
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

                if ($(window).width() < 576) {
                    $('.purchase_steps').slick('slickGoTo', 2, true);
                }
            } else {
                console.warn('⚠️ Slick slider not available');
            }

            // Utility functions
            function toggleRouteInfo(item) {
                $('.route').slideToggle();
                $(item).find('img').toggleClass('rotate');
            }

            function initLoader() {
                console.log('⏳ Showing loader...');
                $('body').prepend('<div class="loader"></div>');
            }

            function removeLoader() {
                console.log('✅ Removing loader...');
                $('.loader').remove();
            }

            function out(msg, txt) {
                console.log('💬 Showing alert:', msg);

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
                $(closeBtn).addClass('alert_ok').text(translations['CLOSE'] || 'Закрыть').appendTo(alertContent);

                $('body').append(alert);
                $(alert).fadeIn();

                $('.alert_ok,.alert_overlay').on('click', function () {
                    $('.alert').fadeOut();
                    setTimeout(function () {
                        $('.alert').remove();
                    }, 350);
                });
            }

            // Make toggleRouteInfo available globally
            window.toggleRouteInfo = toggleRouteInfo;

            console.log('🎯 All scripts initialized successfully!');
        });
    </script>
@endsection
--}}
