<!--<script type="module" src="<?php /*= mix('js/legacy/libs/jquery.3.7.0.js') */?>"></script>-->
<script src="<?php echo  mix('js/app.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/slick.min.js') ?>"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/<?php echo $Router->lang?>.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="<?php echo  mix('js/legacy/blocks.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/script.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/jquery.maskedinput.min.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/select2.min.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/jquery.nice-select.min.js') ?>"></script>
<script src="<?php echo  mix('js/legacy/libs/jquery-ui.min.js') ?>"></script>


<script>
    $('.cb_phone_country_code').niceSelect();
    $('.cb_phone_input').mask("<?php echo $firstPhoneMask?>");
    function changeInputMask(item){
        let selectedOption = $(item).find(':selected');
        $('.cb_phone_input').mask($(selectedOption).data('mask'));
        $('.cb_phone_input').attr('placeholder',$(selectedOption).data('placeholder'));
    };
    function popUpForm(){
        $('.callback_popUp').toggleClass('active');
        $('.callback_popup_overlay').fadeToggle();
        $('body').toggleClass('overflow');
    }

    function exitAccount(){
        $('body').prepend('<div class="loader"></div>');
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '<?php echo  rtrim(url($Router->writelink(3)), '/') ?>',
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

    function togglePhoneDropdown() {
        const menu = document.getElementById('phoneMenu-header');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
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
            url: '<?php echo  rtrim(url($Router->writelink(3)), '/') ?>',
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
                        defaultDate: "<?php echo $filterDate?>",
                        locale: '<?php echo $Router->lang?>',
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
                        defaultDate: "<?php echo $filterDate?>",
                        locale: '<?php echo $Router->lang?>',
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
$departure = !empty( $_SESSION['order']['fromCityId']) ?  $_SESSION['order']['fromCityId'] : '';
$arrival = !empty( $_SESSION['order']['toCityId']) ?  $_SESSION['order']['toCityId'] : '';
$orderDate = !empty( $_SESSION['order']['date']) ?  $_SESSION['order']['date'] : '';
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
            url:  '<?php echo  rtrim(url($Router->writelink(3)), '/') ?>',
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
                        locale: '<?php echo $Router->lang?>',
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
                        locale: '<?php echo $Router->lang?>',
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
            url: '<?php echo  rtrim(url($Router->writelink(3)), '/') ?>', // Путь к PHP-файлу, обрабатывающему запрос
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
            url: '<?php echo  rtrim(url($Router->writelink(3)), '/') ?>',
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
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_VASHE_SOOBSCHENIE_OTPRAVLENO']?>', '<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_MY_SVYAZHEMSYA_S_VAMI_V_BLIZHAJSHEE_VREMYA']?>');
                }else{
                    out('<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE']?>', '<?php echo $GLOBALS['dictionary']['MSG_MSG_CONTACTS_POPROBUJTE_POZZHE']?>');
                }
            }
        })
    }

</script>
