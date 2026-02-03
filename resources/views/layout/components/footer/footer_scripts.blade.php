<!-- Flatpickr стили загружаем в начале -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Фиксированные стили для календаря Flatpickr для правильного отображения на всех страницах -->
<style>
/* Гарантируем правильное отображение календаря независимо от конфликтующих стилей */
.flatpickr-calendar {
    background: #fff !important;
    box-shadow: 1px 0 0 #e6e6e6, -1px 0 0 #e6e6e6, 0 1px 0 #e6e6e6, 0 -1px 0 #e6e6e6, 0 3px 13px rgba(0,0,0,0.08) !important;
    width: 307.875px !important;
    max-width: 307.875px !important;
    min-width: 307.875px !important;
    font-size: 14px !important;
    border-radius: 5px !important;
    border: 0 !important;
    padding: 0 !important;
    box-sizing: border-box !important;
}

/* Стабилизируем размеры после анимации открытия */
.flatpickr-calendar.animate,
.flatpickr-calendar:not(.open) {
    width: 307.875px !important;
    max-width: 307.875px !important;
    min-width: 307.875px !important;
}

.flatpickr-calendar.open {
    z-index: 99999 !important;
    display: inline-block !important;
}

.flatpickr-months .flatpickr-month {
    height: 34px !important;
    background: transparent !important;
}

.flatpickr-current-month {
    font-size: 135% !important;
    height: 34px !important;
    padding: 7.48px 0 0 0 !important;
    line-height: 1 !important;
}

.flatpickr-weekdays {
    height: 28px !important;
    background: transparent !important;
}

span.flatpickr-weekday {
    font-size: 90% !important;
    font-weight: bolder !important;
    background: transparent !important;
    color: rgba(0,0,0,0.54) !important;
}

.flatpickr-days {
    width: 307.875px !important;
}

.dayContainer {
    width: 307.875px !important;
    min-width: 307.875px !important;
    max-width: 307.875px !important;
    padding: 0 !important;
}

.flatpickr-day {
    max-width: 39px !important;
    height: 39px !important;
    line-height: 39px !important;
    border-radius: 150px !important;
    width: 14.2857143% !important;
    flex-basis: 14.2857143% !important;
    background: none !important;
    border: 1px solid transparent !important;
    color: #393939 !important;
    font-weight: 400 !important;
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange,
.flatpickr-day.selected:hover,
.flatpickr-day.selected:focus {
    background: #569ff7 !important;
    border-color: #569ff7 !important;
    color: #fff !important;
}

.flatpickr-day.today {
    border-color: #959ea9 !important;
}

.flatpickr-day.today:hover,
.flatpickr-day.today:focus {
    border-color: #959ea9 !important;
    background: #959ea9 !important;
    color: #fff !important;
}

.flatpickr-day:hover {
    background: #e6e6e6 !important;
    border-color: #e6e6e6 !important;
}

.flatpickr-day.highlight-day {
    background: #e3f2fd !important;
    border-color: #2196f3 !important;
}

.flatpickr-day.highlight-day:hover {
    background: #2196f3 !important;
    border-color: #2196f3 !important;
    color: #fff !important;
}

.flatpickr-day.flatpickr-disabled,
.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
    color: rgba(57,57,57,0.3) !important;
    background: transparent !important;
    border-color: transparent !important;
}
</style>

<script src="<?php echo  mix('js/app.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/slick.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/<?php echo $lang?>.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?php echo  mix('js/legacy/blocks.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/script.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/select2.min.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/jquery.nice-select.min.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.17/js/intlTelInput.min.js"></script>

<script>
    $('.cb_phone_country_code').niceSelect();
    $('.cb_phone_input').mask("<?php echo $firstPhoneMask?>");
    function changeInputMask(item){
        let selectedOption = $(item).find(':selected');
        $('.cb_phone_input').mask($(selectedOption).data('mask'));
        $('.cb_phone_input').attr('placeholder',$(selectedOption).data('placeholder'));
    };

    // Удаляем красную рамку при вводе данных в попапе
    $('.callback_popUp').on('input change', '#callback_phone, select[name="from_location"], select[name="to_location"]', function() {
        $(this).removeClass('error-border');
    });

    // Также удаляем ошибку при изменении селектора кода страны
    $('.callback_popUp').on('change', '.call_select_pop', function() {
        $('#callback_phone').removeClass('error-border');
    });
    function popUpForm() {
        document.querySelector('.callback_popUp').classList.toggle('active');
        document.querySelector('.callback_popup_overlay').classList.toggle('active');

        const isVisible = document.querySelector('.callback_popUp').style.display === 'block';
        document.querySelector('.callback_popUp').style.display = isVisible ? 'none' : 'block';
        document.querySelector('.callback_popup_overlay').style.display = isVisible ? 'none' : 'block';
    }

    function exitAccount(){
        $('body').prepend('<div class="loader"></div>');
        $.ajax({
            type:'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '/ajax/ru',
            data:{
                'request':'exit'
            },
            success:function(response){
                location.href = '<?php echo route('main')?>';
            }
        })
    }

    function toggleSupport(item){
        if ($(item).next().hasClass('active')){
            $(item).next().removeClass('active');
            return false;
        }else{
            $(item).next().addClass('active').slideDown();
            listenPageToCloseSupport();
        }
    };

    $(".regular_tours_wrapper").hover(function (e){
        let item = $(this).children().next();
        if (item.hasClass('active')){
            item.removeClass('active');
            return false;
        }else{
            $('.support_phones').removeClass('active').slideUp();
            item.addClass('active').slideDown();
            listenPageToCloseSupport2();
        }
    });

    function toggleSupport2(item){
        if ($(item).next().hasClass('active')){
            $(item).next().removeClass('active');
            return false;
        }else{
            $(item).next().addClass('active').slideDown();
            listenPageToCloseSupport2();
        }
    };

    function listenPageToCloseSupport(){
        $(document).mouseup( function(e){
            let support = $( ".support_phones" );
            if ( !support.is(e.target) && support.has(e.target).length === 0) {
                support.slideUp();
            }if (!e.target.offsetParent.classList.contains('support_wrapper')){
                support.removeClass('active');
            }
        });
    }

    function listenPageToCloseSupport2(){
        $(document).mouseup( function(e){
            let support = $( ".regular_tours" );
            if ( !support.is(e.target) && support.has(e.target).length === 0) {
                support.slideUp();
            }if (!e.target.offsetParent.classList.contains('regular_tours_wrapper')){
                support.removeClass('active');
            }
        });
    }





    $('.filter_city_select').select2({
        sorter: function(data) {
            return data.sort(function(a, b) {
                var term = $('.select2-search__field').val().toUpperCase();

                var aIndex = a.text.toUpperCase().indexOf(term);
                var bIndex = b.text.toUpperCase().indexOf(term);

                // Если одна из опций не содержит введенного символа, она идет ниже
                if (aIndex === -1 && bIndex !== -1) {
                    return 1;
                }
                if (aIndex !== -1 && bIndex === -1) {
                    return -1;
                }

                // Сортируем опции в соответствии с индексом первого введенного символа
                if (aIndex !== bIndex) {
                    return aIndex - bIndex;
                } else {
                    // Если индексы совпадают, используем сортировку с учетом украинского алфавита
                    var collator = new Intl.Collator('uk');
                    return collator.compare(a.text, b.text);
                }
            });
        }});

    $('.order_bus_select').select2({
        selectionCssClass: 'order_bus_select2'
    });

    $('.langs_select').niceSelect();

    function toggleSubmenu(item){
        if ($(item).next().hasClass('active')){
            $(item).removeClass('active');
            $(item).next().removeClass('active');
            return false;
        }else{
            $(item).next().addClass('active').slideDown();
            $(item).addClass('active');
            listenPageToCloseSubmenu();
        }
    };

    function listenPageToCloseSubmenu(){
        $(document).mouseup( function(e){
            let submenu = $( ".passagers_counter_wrapper" );
            if ( !submenu.is(e.target) && submenu.has(e.target).length === 0) {
                submenu.slideUp();
            }if (!e.target.offsetParent.classList.contains('passagers')){
                submenu.removeClass('active');
                submenu.prev().removeClass('active');
            }
        });
    }

    function countPassagers(item, act, type, maxSeats) {
        let adultsQty = +$('.adults_total').text(); // Получаем количество взрослых
        let kidsQty = +$('.kids_total').text(); // Получаем количество детей
        let currentQty = (type === 'adults') ? adultsQty : kidsQty; // Определяем текущее количество в зависимости от типа

        let newQty = 0;

        if (act === 'plus' && (adultsQty + kidsQty) < maxSeats) { // Проверяем, что общее количество пассажиров не превышает количество мест в автобусе
            newQty = currentQty + 1;
        } else if (act === 'minus' && currentQty >= 1) {
            newQty = currentQty - 1;
        } else {
            return; // Если действие не "plus" и текущее количество равно или превышает максимальное количество, просто выходим из функции
        }

        // Обновляем отображаемое количество пассажиров
        $(item).closest('.passengers_counter').find('.p_counter_value').text(newQty);

        // Обновляем общее количество пассажиров взрослых или детей в зависимости от типа
        if (type === 'kids') {
            $('.kids_total').text(newQty);
            $('.kids_passengers').val(newQty); // Предполагается, что здесь будет установка значения для какого-то элемента формы, например, скрытого поля
        } else if (type === 'adults') {
            $('.adults_total').text(newQty);
            $('.adults_passengers').val(newQty); // Предполагается, что здесь будет установка значения для какого-то элемента формы, например, скрытого поля
        }
    }

    function toggleMobileMenu(){
        $('.mobile_menu').toggleClass('active');
        $('.mobile_menu_overlay').fadeToggle();
        $('body').toggleClass('overflow');
    };

    function selectCity(item){
        $(item).closest('.filter_block_wrapper').find('.filter_block_value').text($(item).text());
        toggleSubmenu(item);
        $(item).closest('.filter_block_wrapper').find('.filter_block').attr('data-id',$(item).attr('data-id'));
    };

    function switchDirections(){
        let currentDeparture = $('#filter_departure').val();
        let currentArrival = $('#filter_arrival').val();
        $('#filter_arrival').val(currentDeparture).trigger('change');
        $('#filter_departure').val(currentArrival).trigger('change');
    }

    document.querySelectorAll('.tour_date_link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var date = this.getAttribute('data-date');
            // Устанавливаем значение в поле ввода
            document.querySelector('.filter_date').value = date;

            // Сабмитим форму после выбора новой даты
            document.querySelector('.main_filter').submit(); // Сабмитим форму
        });
    });

    const currentDate = new Date();
    const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());


    let filterDatePicker;
    let isFilterInitialized = false;

    document.addEventListener("DOMContentLoaded", function() {
        const filterInput = document.querySelector(".filter_date");

        if (!filterInput) {
            return;
        }
        
        // Проверяем, что flatpickr еще не инициализирован
        if (filterInput._flatpickr) {
            return; // Уже инициализирован
        }
        
        let filterDatePicker;
        let isFilterInitialized = false;

        // Функция для отправки AJAX-запроса
        function sendFilterRequest() {
            const departure = $('#filter_departure').val();
            const arrival = $('#filter_arrival').val();
            console.log("Отправляем запрос с параметрами departure:", departure, "и arrival:", arrival);
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    request: 'filter_date',
                    departure: departure,
                    arrival: arrival
                },
                success: function(response) {
                    console.log("Получен ответ от сервера:", response);

                    let highlightedDaysString = response.trim();

                    if (highlightedDaysString) {
                        highlightedDaysArray = highlightedDaysString.split('\n').map(line => line.trim().split(/\D+/).map(Number)).flat().filter(day => day > 0);
                        // Очищаем от повторяющихся и лишних чисел
                        let uniqueDays = {};
                        highlightedDaysArray.forEach(day => {
                            uniqueDays[day] = true;
                        });
                        highlightedDaysArray = Object.keys(uniqueDays).map(Number);
                        console.log(highlightedDaysArray)
                        if (filterDatePicker) {
                            filterDatePicker.destroy();
                        }

                        filterDatePicker = flatpickr(filterInput, {
                            minDate: "today",
                            dateFormat: "Y-m-d",
                            altInput: true,
                            altFormat: "F j, Y",
                            defaultDate: "<?php echo isset($filterDate) ? $filterDate : date('Y-m-d')?>",
                            locale: '<?php echo $lang?>',
                            static: true,
                            disableMobile: true,
                            onDayCreate: function(dObj, dStr, fp, dayElem) {
                                let dayOfWeek = dayElem.dateObj.getDay();
                                if (dayOfWeek === 0) {
                                    dayOfWeek = 7;
                                }
                                if (highlightedDaysArray.includes(dayOfWeek)) {
                                    dayElem.classList.add("highlight-day");
                                }
                            },
                            onChange: function(selectedDates, dateStr, instance) {
                                const currentDate = new Date();
                                const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());
                                if (selectedDates.length && selectedDates[0] < threeYearsAgo) {
                                    instance.setDate(threeYearsAgo);
                                }
                            }
                        });

                        isFilterInitialized = true;
                    } else {
                        console.log("Нет доступных дней для выбранных параметров.");
                        filterDatePicker = flatpickr(filterInput, {
                            minDate: "today",
                            dateFormat: "Y-m-d",
                            altInput: true,
                            altFormat: "F j, Y",
                            defaultDate: "<?php echo isset($filterDate) ? $filterDate : date('Y-m-d')?>",
                            locale: '<?php echo isset($lang) ? $lang : 'uk'?>',
                            static: true,
                            disableMobile: true,
                            onChange: function(selectedDates, dateStr, instance) {
                                const currentDate = new Date();
                                const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());
                                if (selectedDates.length && selectedDates[0] < threeYearsAgo) {
                                    instance.setDate(threeYearsAgo);
                                }
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка при выполнении запроса:", error);
                }
            });
        }

        sendFilterRequest();

        // Отправляем запрос при изменении значений инпутов
        $('#filter_departure, #filter_arrival').on("change", sendFilterRequest);
    });

    <?php
    $departure = isset($_SESSION['order']['fromCityId']) ? $_SESSION['order']['fromCityId'] : '';
    $arrival = isset($_SESSION['order']['toCityId']) ? $_SESSION['order']['toCityId'] : '';
    $orderDate = isset($_SESSION['order']['date']) ? $_SESSION['order']['date'] : '';
    ?>

    document.addEventListener("DOMContentLoaded", function() {
        const filterInput = document.querySelector(".filter_date_booking");

        if (!filterInput) {
            return;
        }
        let filterDatePicker;
        let isFilterInitialized = false;

        // Функция для отправки AJAX-запроса
        function sendFilterRequest() {
            const departure = "<?php echo  $departure ?>";
            const arrival = "<?php echo  $arrival ?>";
            console.log("Отправляем запрос с параметрами departure:", departure, "и arrival:", arrival);
            $.ajax({
                type: 'post',
                url:  '/ajax/ru',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                data: {
                    request: 'filter_date',
                    departure: departure,
                    arrival: arrival
                },
                success: function(response) {
                    console.log("Получен ответ от сервера:", response);

                    let highlightedDaysString = response.trim();

                    if (highlightedDaysString) {
                        highlightedDaysArray = highlightedDaysString.split('\n').map(line => line.trim().split(/\D+/).map(Number)).flat().filter(day => day > 0);
                        // Очищаем от повторяющихся и лишних чисел
                        let uniqueDays = {};
                        highlightedDaysArray.forEach(day => {
                            uniqueDays[day] = true;
                        });
                        highlightedDaysArray = Object.keys(uniqueDays).map(Number);
                        console.log(highlightedDaysArray)
                        if (filterDatePicker) {
                            filterDatePicker.destroy();
                        }

                        filterDatePicker = flatpickr(filterInput, {
                            minDate: "today",
                            dateFormat: "Y-m-d",
                            altInput: true,
                            altFormat: "F j, Y",
                            defaultDate: "<?php echo $orderDate?>",
                            locale: '<?php echo $lang?>',
                            static: true,
                            disableMobile: true,
                            onDayCreate: function(dObj, dStr, fp, dayElem) {
                                let dayOfWeek = dayElem.dateObj.getDay();
                                if (dayOfWeek === 0) {
                                    dayOfWeek = 7;
                                }
                                if (highlightedDaysArray.includes(dayOfWeek)) {
                                    dayElem.classList.add("highlight-day");
                                }
                            },
                            onChange: function(selectedDates, dateStr, instance) {
                                const currentDate = new Date();
                                const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());
                                if (selectedDates.length && selectedDates[0] < threeYearsAgo) {
                                    instance.setDate(threeYearsAgo);
                                }
                                updateSessionDate(dateStr);
                            }
                        });

                        isFilterInitialized = true;

                    } else {
                        console.log("Нет доступных дней для выбранных параметров.");
                        filterDatePicker = flatpickr(filterInput, {
                            minDate: "today",
                            dateFormat: "Y-m-d",
                            altInput: true,
                            altFormat: "F j, Y",
                            defaultDate: "<?php echo $orderDate?>",
                            locale: '<?php echo $lang?>',
                            static: true,
                            disableMobile: true,
                            onChange: function(selectedDates, dateStr, instance) {
                                const currentDate = new Date();
                                const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());
                                if (selectedDates.length && selectedDates[0] < threeYearsAgo) {
                                    instance.setDate(threeYearsAgo);
                                }
                                updateSessionDate(dateStr);
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка при выполнении запроса:", error);
                }
            });

        }

        // Функция для обновления даты в сессии
        function updateSessionDate(date) {
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru', // Путь к PHP-файлу, обрабатывающему запрос
                data: {
                    request: 'booking_date',
                    date: date
                },
                success: function(response) {
                    console.log("Дата успешно обновлена в сессии:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка при обновлении даты в сессии:", error);
                }
            });
        }

        sendFilterRequest();

    });
    function togglePhoneDropdown() {
        const menu = document.getElementById('phoneMenu-header');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    function sendOrderRequest() {
        // Удаляем предыдущие ошибки
        $('.callback_popUp').find('.error-border').removeClass('error-border');

        let params = {
            phone: $.trim($('#callback_phone').val()),
            arrival: $.trim($('.callback_popUp select[name="to_location"]').val()),
            departure: $.trim($('.callback_popUp select[name="from_location"]').val()),
            comment: $.trim($('#callback_message').val()),
        };

        // Валидация обязательных полей
        let hasErrors = false;

        if (!params.phone || params.phone.trim() === '') {
            $('#callback_phone').addClass('error-border');
            hasErrors = true;
        }

        if (!params.departure || params.departure === '') {
            $('.callback_popUp select[name="from_location"]').addClass('error-border');
            hasErrors = true;
        }

        if (!params.arrival || params.arrival === '') {
            $('.callback_popUp select[name="to_location"]').addClass('error-border');
            hasErrors = true;
        }

        if (hasErrors) {
            return false;
        }

        sendRequestOrder(params);
    }

        function sendRequestOrder(params)
        {
            $.ajax({
                type: "POST",
                url: '/ajax/callback',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(params),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    document.getElementById('successModal').style.display = 'flex';
                    document.querySelector('.callback_popUp').style.display = 'none';
                },
                error: function (xhr) {
                    alert('Request din`t send');
                }
            });
        }

    function sendCallback(){
        // Удаляем предыдущие ошибки
        $('.callback_form').find('.error-border').removeClass('error-border');

        let departure = $.trim($('#callback_departure').val());
        let arrival = $.trim($('#callback_arrival').val());
        let phone = $.trim($('#callback_phone').val());
        let message = $.trim($('#callback_message').val());

        let hasErrors = false;

        // Валидация обязательных полей
        if (!phone || phone.trim() === '') {
            $('#callback_phone').addClass('error-border');
            hasErrors = true;
        }

        if (!departure || departure === '') {
            $('#callback_departure').addClass('error-border');
            hasErrors = true;
        }

        if (!arrival || arrival === '') {
            $('#callback_arrival').addClass('error-border');
            hasErrors = true;
        }

        if (hasErrors) {
            return false;
        }
        $('body').prepend('<div class="loader"></div>');
        $.ajax({
            type:'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '/ajax/ru',
            data:{
                'request':'callback',
                'departure':departure,
                'arrival':arrival,
                'phone':phone,
                'message':message
            },
            success:function(request){
                removeLoader();
                $('.callback_form').find('input,textarea').val('');
                if ($.trim(request) == 'ok'){
                    out('@lang('dictionary.MSG_MSG_CONTACTS_VASHE_SOOBSCHENIE_OTPRAVLENO')', '@lang('dictionary.MSG_MSG_CONTACTS_MY_SVYAZHEMSYA_S_VAMI_V_BLIZHAJSHEE_VREMYA')');
                }else{
                    out('@lang('dictionary.MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE')', '@lang('dictionary.MSG_MSG_CONTACTS_POPROBUJTE_POZZHE')');
                }
            }
        })
    }

    function removeLoader() {
        document.querySelector(".loader").remove();
    };

    document.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            document.getElementById('successModal').style.display = 'none';
            window.location.reload();
        });
    });

</script>
