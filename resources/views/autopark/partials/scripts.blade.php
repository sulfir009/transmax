<script>
    // Переменные
    let $seats = 0;
    const defaultPhoneMask = "{{ $phoneCodes['default']['mask'] ?? '(999) 999-9999' }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Инициализация при загрузке страницы
    window.addEventListener('load', function() {
        // Инициализация nice select
        if (typeof $.fn.niceSelect !== 'undefined') {
            $('.phone_country_code').niceSelect();
        }

        // Инициализация маски телефона
        $('.order_bus_phone').mask(defaultPhoneMask);

        // Обработка автозаполнения для поля имени
        let input = document.getElementById('name');
        if (input) {
            input.addEventListener('input', function() {
                if (input.matches(':-webkit-autofill')) {
                    input.style.backgroundColor = 'transparent';
                    input.style.color = '#fff';
                    input.style.height = '49px';
                    input.style.border = 'none';
                }
            });
        }
    });

    // Инициализация календаря
    const orderBusDatePicker = flatpickr(".order_bus_date_input", {
        minDate: "today",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        defaultDate: "{{ $filterData['date'] }}",
        locale: '{{ $lang }}',
        static: true
    });

    // Функция открытия календаря
    function toggleDateCalendar() {
        orderBusDatePicker.open();
    }

    // Функция изменения маски телефона
    function changeInputMask(item) {
        let selectedOption = $(item).find(':selected');
        $('.order_bus_phone').mask($(selectedOption).data('mask'));
        $('.order_bus_phone').attr('placeholder', $(selectedOption).data('placeholder'));
    }

    // Функция переключения попапа заказа автобуса
    function toggleOrderBus(busId, seatsQty) {
        $('.order_bus_popup').toggleClass('active');
        $('.order_bus_overlay').fadeToggle();
        $('body').toggleClass('overflow');
        
        if (busId) {
            $('#selected_bus_id').val(busId);
            $seats = seatsQty;
            
            // Сброс счетчиков пассажиров
            $('.adults_total').text(1);
            $('.kids_total').text(0);
            $('.adult_passagers .p_counter_value').text(1);
            $('.passengers_counter_block:not(.adult_passagers) .p_counter_value').text(0);
        }
    }

    // Функция переключения подменю пассажиров
    function toggleOrderBusSubmenu(item) {
        if ($(item).next().hasClass('active')) {
            $(item).next().removeClass('active');
            return false;
        } else {
            $(item).next().addClass('active').slideDown();
            listenPageToCloseBusSubmenu();
        }
    }

    // Слушатель для закрытия подменю при клике вне его
    function listenPageToCloseBusSubmenu() {
        $(document).mouseup(function(e) {
            let busSubmenu = $(".order_bus_row_submenu");
            if (!busSubmenu.is(e.target) && busSubmenu.has(e.target).length === 0) {
                busSubmenu.slideUp();
                if (e.target.offsetParent && !e.target.offsetParent.classList.contains('order_bus_row')) {
                    busSubmenu.removeClass('active');
                }
            }
        });
    }

    // Функция подсчета пассажиров
    function countPassagers(element, action, type, maxSeats) {
        const counterBlock = $(element).closest('.passengers_counter_block');
        const counterValue = counterBlock.find('.p_counter_value');
        let currentValue = parseInt(counterValue.text());
        
        if (action === 'plus') {
            if (type === 'adults' && currentValue < maxSeats) {
                currentValue++;
            } else if (type === 'kids' && currentValue < 10) { // Ограничение для детей
                currentValue++;
            }
        } else if (action === 'minus') {
            if (type === 'adults' && currentValue > 1) { // Минимум 1 взрослый
                currentValue--;
            } else if (type === 'kids' && currentValue > 0) {
                currentValue--;
            }
        }
        
        counterValue.text(currentValue);
        
        // Обновляем общие счетчики
        if (type === 'adults') {
            $('.adults_total').text(currentValue);
        } else if (type === 'kids') {
            $('.kids_total').text(currentValue);
        }
    }

    // Функция загрузки дополнительных автобусов
    function moreBuses() {
        let currentBuses = $('.bus').length;
        
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            url: '{{ route("autopark.load-more") }}',
            data: {
                'current': currentBuses
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('.our_buses_container').append(response.html);
                    
                    // Скрываем кнопку если больше нет автобусов
                    let newBusesCount = $('.bus').length;
                    if (newBusesCount >= {{ $totalBusesCount }}) {
                        $('.more_buses_btn').hide();
                    }
                }
            },
            error: function() {
                console.error('Ошибка при загрузке автобусов');
            }
        });
    }

    // Функция заказа автобуса
    function orderBus() {
        let allFieldsFilled = true;
        
        // Проверка обязательных полей
        $('.req_input').each(function() {
            if ($.trim($(this).val()) === '') {
                out(
                    '@lang("MSG_MSG_BOOKING_ZAPOLNITE_VSE_OBYAZATELINYE_POLYA")', 
                    '@lang("MSG_MSG_BOOKING_OBYAZATELINYE_POLYA_OTMECHENY_")'
                );
                allFieldsFilled = false;
                return false;
            }
        });

        if (!allFieldsFilled) {
            return false;
        }

        // Собираем данные
        let name = $('#name').val();
        let phone = $('#phone').val();
        let phoneCode = $('#phone_code').val();
        let date = $('.filter_date.flatpickr-input').val();
        let adults = parseInt($('.adults_total').text());
        let kids = parseInt($('.kids_total').text());
        let busId = $('#selected_bus_id').val();

        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            url: '{{ route("autopark.order-bus") }}',
            data: {
                'name': name,
                'phone': phone,
                'phone_code': phoneCode,
                'date': date,
                'adults': adults,
                'kids': kids,
                'bus_id': busId
            },
            success: function(response) {
                if (response.status === 'ok') {
                    location.href = response.redirect;
                } else {
                    out('Ошибка', response.message || 'Произошла ошибка при обработке заказа');
                }
            },
            error: function() {
                out('Ошибка', 'Произошла ошибка при отправке заказа');
            }
        });
    }

    // Функция поиска билетов по автобусу (если используется)
    function searchTicketsByBus() {
        let departure = $('#filter_departure').val();
        let arrival = $('#filter_arrival').val();
        let date = $('.filter_date.flatpickr-input').val();
        let adults = parseInt($('.adults_total').text());
        let kids = parseInt($('.kids_total').text());
        let url = '{{ route("booking.index") }}';
        url = url + '?departure=' + departure + '&arrival=' + arrival + '&date=' + date + '&adults=' + adults + '&kids=' + kids;
        location.href = url;
    }
</script>
