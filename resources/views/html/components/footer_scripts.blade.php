<script src="/js/app.js"></script>
<script src="/js/legacy/libs/slick.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/ru.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/js/legacy/blocks.js"></script>
<script src="/js/legacy/script.js"></script>
<script src="/js/legacy/libs/jquery.maskedinput.min.js"></script>
<script src="/js/legacy/libs/select2.min.js"></script>
<script src="/js/legacy/libs/jquery.nice-select.min.js"></script>
<script src="/js/legacy/libs/jquery-ui.min.js"></script>


<script>
    $('.cb_phone_country_code').niceSelect();
    $('.cb_phone_input').mask("+38 (099) 999-9999");
    function changeInputMask(item){
        let selectedOption = $(item).find(':selected');
        $('.cb_phone_input').mask($(selectedOption).data('mask'));
        $('.cb_phone_input').attr('placeholder',$(selectedOption).data('placeholder'));
    };
    function popUpForm() {
      document.querySelector('.callback_popUp').classList.toggle('active');
      document.querySelector('.callback_popup_overlay').classList.toggle('active');

      const isVisible = document.querySelector('.callback_popUp').style.display === 'block';
      document.querySelector('.callback_popUp').style.display = isVisible ? 'none' : 'block';
      document.querySelector('.callback_popup_overlay').style.display = isVisible ? 'none' : 'block';
    }

    function sendCallback() {
      alert("Форма отправлена!");
    }

    function exitAccount(){
        $('body').prepend('<div class="loader"></div>');
        $.ajax({
            type:'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: 'https://new.maxtransltd.com/ajax/ru',
            data:{
                'request':'exit'
            },
            success:function(response){
                location.href = 'https://new.maxtransltd.com';
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

    $(document).ready(function() {
        $('select.lang-select').niceSelect();
    });


    const fromSelect = document.getElementById('from_location');
    const toSelect = document.getElementById('to_location');

    const destinations = {
        kyiv: ["Львов", "Одесса", "Харьков"],
        lviv: ["Киев", "Одесса", "Ужгород"],
        odesa: ["Киев", "Львов", "Николаев"]
    };

    fromSelect.addEventListener('change', function () {
       const selectedFrom = this.value;
       toSelect.innerHTML = '<option value="" disabled selected>Куда</option>';

       if (destinations[selectedFrom]) {
          destinations[selectedFrom].forEach(city => {
           const opt = document.createElement('option');
           opt.value = city.toLowerCase();
           opt.textContent = city;
           toSelect.appendChild(opt);
        });
        toSelect.disabled = false;
        } else {
      toSelect.disabled = true;
      }
    });

    function togglePhoneDropdown() {
      const menu = document.getElementById('phoneMenu-header');
      menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }  


    document.addEventListener('click', function(e) {
       const target = e.target;
       const menu = document.getElementById('phoneMenu-header');
       const icon = document.querySelector('.phone-icon-header');
       if (!menu.contains(target) && target !== icon) {
          menu.style.display = 'none';
       }
    });

    function openPopupRegular() {
      const popup = document.getElementById('popup-regular');
      popup.style.display = 'flex';

      document.getElementById('step-country').style.display = 'block';
      document.getElementById('step-country').classList.add('show');
    }    

    function closePopupRegular() {
      document.getElementById('popup-regular').style.display = 'none';
    }

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

    // Добавляем обработчик клика по фону, чтобы закрыть попап
    const overlay = document.getElementById('popup-regular');
    overlay.addEventListener('click', function(event) {
    // Закрыть попап только если клик был на фоне, а не на самом модальном окне
      if (event.target === overlay) {
        closePopupRegular();
      }
    });

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
                url: 'https://new.maxtransltd.com/ajax/ru',
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
                            defaultDate: "",
                            locale: 'ru',
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
                            defaultDate: "",
                            locale: 'ru',
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


    document.addEventListener("DOMContentLoaded", function() {
        const filterInput = document.querySelector(".filter_date_booking");

        if (!filterInput) {
            return;
        }
        let filterDatePicker;
        let isFilterInitialized = false;

        // Функция для отправки AJAX-запроса
        function sendFilterRequest() {
            const departure = "";
            const arrival = "";
            console.log("Отправляем запрос с параметрами departure:", departure, "и arrival:", arrival);
            $.ajax({
                type: 'post',
                url:  'https://new.maxtransltd.com/ajax/ru',
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
                            defaultDate: "",
                            locale: 'ru',
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
                            defaultDate: "",
                            locale: 'ru',
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
                url: 'https://new.maxtransltd.com/ajax/ru', // Путь к PHP-файлу, обрабатывающему запрос
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
    function sendCallback(){
        let departure = $.trim($('#callback_departure').val());
        let arrival = $.trim($('#callback_arrival').val());
        let phone = $.trim($('#callback_phone').val());
        let message = $.trim($('#callback_message').val());

        let allFieldsFilled = true;
        $('.cb_req_input').each(function () {
            if ($.trim($(this).val()) === '') {
                out('Заполните все обязательные поля', 'Обязательные поля отмечены *');
                allFieldsFilled = false; // Устанавливаем флаг в false если хотя бы одно поле не заполнено
                return false; // Прерываем цикл
            }
        });
        if (!allFieldsFilled) { // Если хотя бы одно поле не заполнено
            return false; // Прерываем выполнение функции и не отправляем данные
        }
        $('body').prepend('<div class="loader"></div>');
        $.ajax({
            type:'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: 'https://new.maxtransltd.com/ajax/ru',
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
                    out('Ваше сообщение отправлено', 'Мы свяжемся с Вами в ближайшее время');
                }else{
                    out('Не удалось отправить сообщение', 'Попробуйте позже');
                }
            }
        })
    }

</script>
<script src="/js/legacy/libs/slick.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        $('.advantages_slider').slick({
            dots: true,
            dotsClass: 'advantages_slider_nav slick_slider_nav',
            arrows: false,
        });

        $('.company_docs_slider').slick({
            dots: false,
            arrows: false,
            slidesToScroll: 1,
            slidesToShow: 3,
            responsive: [
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    });
</script>

<script>
    function addTicketPassenger() {
        const passengerList = document.getElementById("ticket-form-passenger-list");
        const currentPassengerCount = passengerList.querySelectorAll(".ticket-form-passenger").length + 1;

        const passengerDiv = document.createElement("div");
        passengerDiv.className = "ticket-form-passenger";

        passengerDiv.innerHTML = `
            <div class="tickets-count-block">         
                <h3 class="ticket-form-subtitle">Контактные данные пассажира №${currentPassengerCount}</h3>
                <button type="button" class="ticket-form-remove-btn">
                    <img src="<?= asset('images/legacy/rmv-btn.png'); ?>" alt="remove-passager">
                </button>
            </div>
            <div class="ticket-form-input-space">
                <input type="text" name="lastname[]" placeholder="Фамилия" required class="ticket-form-input" />
                <input type="text" name="firstname[]" placeholder="Имя" required class="ticket-form-input" />
            </div>
        `;

        passengerList.appendChild(passengerDiv);
        updatePassengerNumbers();
        updatePassengerCountDisplay(); // обновляем кол-во
        }

    const passengerList = document.getElementById("ticket-form-passenger-list");
    passengerList.addEventListener("click", function (event) {
        const btn = event.target.closest(".ticket-form-remove-btn");
        if (btn) {
            const passengerDiv = btn.closest(".ticket-form-passenger");
            if (passengerDiv) {
                passengerDiv.remove();
                updatePassengerNumbers();
                updatePassengerCountDisplay(); // обновляем кол-во
            }
        }
    });

    function updatePassengerNumbers() {
        const passengers = document.querySelectorAll(".ticket-form-passenger");
        passengers.forEach((passenger, index) => {
            const title = passenger.querySelector(".ticket-form-subtitle");
            if (title) {
                title.textContent = `Контактные данные пассажира №${index + 1}`;
            }
        });
    }

    function updatePassengerCountDisplay() {
        const count = document.querySelectorAll(".ticket-form-passenger").length;
        const countDisplay = document.getElementById("passenger-count");
        if (countDisplay) {
            countDisplay.textContent = count;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        let ticketPassengerCount = 1;

        // Переход по шагам
        function goToStep(step) {
            console.log('GO TO STEP:', step);
            document.querySelectorAll('.step-ticketing').forEach(s => s.classList.remove('active'));
            const activeStep = document.querySelector('#step-ticketing' + step);
            if (activeStep) activeStep.classList.add('active');

            document.querySelectorAll('.step-btn-ticketing').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.step-btn-ticketing[data-step="${step}"]`)?.classList.add('active');
        }

        // Назначаем переходы по шагам (включая «перейти к оплате»)
        document.querySelectorAll(".step-btn-ticketing, .trip-price-btn[data-step]").forEach(button => {
            button.addEventListener("click", () => {
                const step = parseInt(button.dataset.step, 10);
                if (!isNaN(step)) {
                    goToStep(step);
                }
            });
        });

        // Назначаем кнопку «добавить пассажира»
        document.getElementById("add-ticket-passenger-btn").addEventListener("click", addTicketPassenger);
    });

</script>

<script>
    const buyOnlineBtn = document.getElementById('ticket-datepicker-button');
    const calendarModal = document.getElementById('calendar-modal');
    const calendarElement = document.getElementById('calendar');
    const saveBtn = document.getElementById('save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const dateInput = document.getElementById('ticket-selected-date'); // <-- input поле даты

    let selectedDate = null;

    // Инициализация Flatpickr
    const calendar = flatpickr(calendarElement, {
        inline: true,
        dateFormat: "Y-m-d",
        position: "auto center",
        disableMobile: true,
        showMonths: 1,
        monthSelectorType: 'static',
        locale: "ru",
        prevArrow: "←",
        nextArrow: "→",
        onChange: function (selectedDates, dateStr) {
            selectedDate = dateStr;
        },
        onDayCreate: function (dObj, dStr, fp, dayElem) {
            let date = new Date(dayElem.dateObj);
            let day = date.getDay();
            if (day === 0 || day === 6) {
                dayElem.style.color = "red";
            }
        }
    });

    // Открытие календаря по кнопке "Купить онлайн"
    buyOnlineBtn.addEventListener('click', () => {
        calendarModal.style.display = 'flex';
    });

    // Сохранение даты
    saveBtn.addEventListener('click', () => {
        if (selectedDate) {
            dateInput.textContent = selectedDate; // вставляем дату в input
            calendarModal.style.display = 'none';
        } else {
            alert("Пожалуйста, выберите дату.");
        }
    });

    // Отмена
    cancelBtn.addEventListener('click', () => {
        calendarModal.style.display = 'none';
    });
</script>
