@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/slick/slick.css') }}">
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/nice_select/nice-select.css') }}">
    <link rel="stylesheet" href="{{ mix('css/legacy/style_table.css') }}">
    <link rel="stylesheet" href="{{ mix('css/responsive.css') }}">
@endsection
<?php
    $Router = new \App\Service\DbRouter\Router();
?>

@section('content')
<div class="content">
    <div class="main_filter_wrapper">
        <div class="container">
            {{-- Фильтр если нужен --}}
        </div>
    </div>

    {{-- Шаги покупки --}}
    <div class="purchase_steps_wrapper">
        <div class="tabs_links_container">
            <div class="purchase_steps flex_ac">
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title">1. @lang('dictionary.MSG_MSG_TICKETS_VIBIR_AVTOBUSA')</div>
                </div>
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title active">2. @lang('data_ticket_page')</div>
                </div>
                <div class="purchase_step_wrapper">
                    <div class="purchase_step h4_title">3. @lang('payment_ticket_page')</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page_content_wrapper">
        <div class="container">
            <div class="flex-row gap-30 booking_blocks">
                {{-- Левая колонка с формой --}}
                <div class="col-xxl-7 col-xs-12">
                    @include('booking.partials.order-form')
                    @include('booking.partials.contact-data')
                    @include('booking.partials.promocode')
                    @include('booking.partials.payment-block')

                    <button class="payment_btn h4_title flex_ac blue_btn" onclick="goPayment()">
                        @lang('dictionary.MSG_MSG_BOOKING_PEREJTI_DO_OPLATI')
                    </button>
                    <div class="payment_clarification par">
                        @lang('dictionary.MSG_MSG_BOOKING_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI')
                    </div>
                </div>

                <div class="col-xxl-1 hidden-xxl hidden-lg hidden-md hidden-sm hidden-xs"></div>

                {{-- Правая колонка с информацией о маршруте --}}
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
            <?/*let doc_type = $('#doc_select').val();*/?>
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
                    allFieldsFilled = false; // Устанавливаем флаг в false если хотя бы одно поле не заполнено
                    return false; // Прерываем цикл
                }
            });

            if (!allFieldsFilled) { // Если хотя бы одно поле не заполнено
                return false; // Прерываем выполнение функции и не отправляем данные
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

        $('.purchase_steps').slick({
            slidesToShow:4,
            slidesToScroll:1,
            dots:false,
            arrows:false,
            infinite:false,
            variableWidth: true,
            responsive:[
                {
                    breakpoint: 576,
                    settings: {
                        infinite:false,
                        slidesToShow: 1
                    }
                },
            ],
        });

        $(document).ready(function(){
            if ($(window).width() < 576){
                $('.purchase_steps').slick('slickGoTo',1 , true)
            }
        });

        <?/*$('.doc_select').niceSelect();*/?>
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
            document.querySelector(".filter_date_booking").click();
        });

    </script>
@endsection
