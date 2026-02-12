{{-- /home/vv513819/maxtransltd.com/www/resources/views/layout/components/footer/footer_scripts.blade.php --}}

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

    /* КЛЮЧЕВОЕ: календарь всегда поверх любых фильтров/меню */
    z-index: 2147483647 !important;
}

/* Стабилизируем размеры после анимации открытия */
.flatpickr-calendar.animate,
.flatpickr-calendar:not(.open) {
    width: 307.875px !important;
    max-width: 307.875px !important;
    min-width: 307.875px !important;
}

.flatpickr-calendar.open {
    /* z-index выставлен на .flatpickr-calendar выше */
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

/*
    ==============================
    MAXTRANS FIX: ПОЗИЦИЯ КАЛЕНДАРЯ
    ==============================
*/
.flatpickr-calendar[data-mx-fp="1"]{
    position: fixed !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
    transform: none !important;
    margin: 0 !important;
    z-index: 2147483647 !important;
}

/* На мобильных можно дать календарю чуть шире, чтобы не вылезал за края */
@media (max-width: 768px){
    .flatpickr-calendar{
        width: 90vw !important;
        max-width: 320px !important;
        min-width: 0 !important;
    }
    .flatpickr-days,
    .dayContainer{
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
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
    const legacyAjaxUrl = @json(url('/ajax/' . app()->getLocale()));
    

/**
 * MAXTRANS FIX: cities НЕ должны зависеть от даты.
 * Мы убираем date из AJAX, когда request=getCities (и похожие).
 * Это лечит ситуацию: "сегодня городов нет, на другую дату появляются".
 */
(function ($) {
    if (!window.jQuery || !$.ajaxPrefilter) return;

    function stripDateFromObject(obj) {
        if (!obj || typeof obj !== 'object') return obj;

        // request может быть в разных регистрах/ключах
        const req = obj.request || obj.action || obj.r || '';
        if (String(req) !== 'getCities') return obj;

        // Удаляем все возможные варианты ключей даты
        delete obj.date;
        delete obj.filter_date;
        delete obj.selected_date;
        delete obj.day;
        return obj;
    }

    function stripDateFromString(dataStr) {
        // dataStr: "request=getCities&date=2026-02-12&..."
        if (typeof dataStr !== 'string') return dataStr;
        if (dataStr.indexOf('request=getCities') === -1) return dataStr;

        const p = new URLSearchParams(dataStr);
        p.delete('date');
        p.delete('filter_date');
        p.delete('selected_date');
        p.delete('day');
        return p.toString();
    }

    $.ajaxPrefilter(function (options, originalOptions) {
        // Берём data оттуда, где она реально лежит
        const data = (options && options.data !== undefined) ? options.data : (originalOptions ? originalOptions.data : undefined);

        // FormData
        if (data instanceof FormData) {
            const req = data.get('request');
            if (String(req) === 'getCities') {
                data.delete('date');
                data.delete('filter_date');
                data.delete('selected_date');
                data.delete('day');
            }
            return;
        }

        // URLSearchParams
        if (data instanceof URLSearchParams) {
            const req = data.get('request');
            if (String(req) === 'getCities') {
                data.delete('date');
                data.delete('filter_date');
                data.delete('selected_date');
                data.delete('day');
            }
            options.data = data;
            return;
        }

        // Object
        if (data && typeof data === 'object') {
            options.data = stripDateFromObject(data);
            return;
        }

        // String
        if (typeof data === 'string') {
            options.data = stripDateFromString(data);
        }
    });
})(window.jQuery);


    
    $('.cb_phone_country_code').niceSelect();

    function mxParseHighlightedWeekdays(response) {
        const raw = (response ?? '').toString().trim();
        if (!raw) return [];

        // Ожидаем только список дней недели (1..7) в текстовом формате
        if (!/^[\d,\s\r\n]+$/.test(raw)) {
            return [];
        }

        const days = [];
        raw.split(/\r?\n/).forEach((line) => {
            line.split(',').forEach((chunk) => {
                const day = Number(String(chunk).trim());
                if (Number.isInteger(day) && day >= 1 && day <= 7) {
                    days.push(day);
                }
            });
        });

        return Array.from(new Set(days));
    }
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
            url: legacyAjaxUrl,
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

                if (aIndex === -1 && bIndex !== -1) return 1;
                if (aIndex !== -1 && bIndex === -1) return -1;

                if (aIndex !== bIndex) {
                    return aIndex - bIndex;
                } else {
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
        let adultsQty = +$('.adults_total').text();
        let kidsQty = +$('.kids_total').text();
        let currentQty = (type === 'adults') ? adultsQty : kidsQty;

        let newQty = 0;

        if (act === 'plus' && (adultsQty + kidsQty) < maxSeats) {
            newQty = currentQty + 1;
        } else if (act === 'minus' && currentQty >= 1) {
            newQty = currentQty - 1;
        } else {
            return;
        }

        $(item).closest('.passengers_counter').find('.p_counter_value').text(newQty);

        if (type === 'kids') {
            $('.kids_total').text(newQty);
            $('.kids_passengers').val(newQty);
        } else if (type === 'adults') {
            $('.adults_total').text(newQty);
            $('.adults_passengers').val(newQty);
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

    //function switchDirections(){
    //    let currentDeparture = $('#filter_departure').val();
    //    let currentArrival = $('#filter_arrival').val();
    //    $('#filter_arrival').val(currentDeparture).trigger('change');
    //    $('#filter_departure').val(currentArrival).trigger('change');
    //}

    document.querySelectorAll('.tour_date_link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var date = this.getAttribute('data-date');
            document.querySelector('.filter_date').value = date;
            document.querySelector('.main_filter').submit();
        });
    });

    const currentDate = new Date();
    const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());

    let filterDatePicker;
    let isFilterInitialized = false;

    /*
        ============================
        MAXTRANS: FIX POSITION HELPERS
        ============================
    */
    window.__mxFpOpenInstance = null;
    window.__mxFpRafId = null;

    function mxFpNormalizeCallbackArray(value){
        if (!value) return [];
        if (Array.isArray(value)) return value;
        if (typeof value === 'function') return [value];
        return [];
    }

    function mxFpMarkCalendar(fp){
        if (!fp || !fp.calendarContainer) return;
        fp.calendarContainer.setAttribute('data-mx-fp', '1');
    }

    function mxFpGetAnchor(fp){
        if (!fp) return null;
        if (fp.altInput) return fp.altInput;
        if (fp.input) return fp.input;
        return null;
    }

    function mxFpPlaceCalendarNow(fp){
        if (!fp || !fp.calendarContainer) return;

        const cal = fp.calendarContainer;
        const anchor = mxFpGetAnchor(fp);

        if (!anchor) return;

        mxFpMarkCalendar(fp);

        cal.style.setProperty('position', 'fixed', 'important');
        cal.style.setProperty('right', 'auto', 'important');
        cal.style.setProperty('bottom', 'auto', 'important');
        cal.style.setProperty('transform', 'none', 'important');
        cal.style.setProperty('margin', '0', 'important');
        cal.style.setProperty('z-index', '2147483647', 'important');

        const gap = 8;
        const rect = anchor.getBoundingClientRect();

        cal.style.setProperty('top', '0px', 'important');
        cal.style.setProperty('left', '0px', 'important');

        const calRect = cal.getBoundingClientRect();
        const calW = calRect.width || cal.offsetWidth || 0;
        const calH = calRect.height || cal.offsetHeight || 0;

        let top = rect.bottom + gap;
        let left = rect.left;

        if (calH > 0 && (top + calH) > (window.innerHeight - gap)) {
            top = rect.top - calH - gap;
        }

        top = Math.max(gap, top);

        if (calW > 0 && (left + calW) > (window.innerWidth - gap)) {
            left = window.innerWidth - calW - gap;
        }
        left = Math.max(gap, left);

        if (window.innerWidth <= 768 && calW > 0) {
            let centeredLeft = (window.innerWidth - calW) / 2;
            centeredLeft = Math.max(gap, centeredLeft);
            left = centeredLeft;
        }

        cal.style.setProperty('top', top + 'px', 'important');
        cal.style.setProperty('left', left + 'px', 'important');
    }

    function mxFpRequestPlace(fp){
        if (!fp) return;

        if (window.__mxFpRafId) {
            cancelAnimationFrame(window.__mxFpRafId);
            window.__mxFpRafId = null;
        }

        window.__mxFpRafId = requestAnimationFrame(function(){
            mxFpPlaceCalendarNow(fp);
            window.__mxFpRafId = null;
        });
    }

    function mxFpEnsureHooks(fp){
        if (!fp || !fp.config || fp.__mxFixed === true) return;
        fp.__mxFixed = true;

        fp.config.onOpen = mxFpNormalizeCallbackArray(fp.config.onOpen);
        fp.config.onClose = mxFpNormalizeCallbackArray(fp.config.onClose);
        fp.config.onMonthChange = mxFpNormalizeCallbackArray(fp.config.onMonthChange);
        fp.config.onYearChange = mxFpNormalizeCallbackArray(fp.config.onYearChange);
        fp.config.onValueUpdate = mxFpNormalizeCallbackArray(fp.config.onValueUpdate);

        fp.config.onOpen.push(function(selectedDates, dateStr, instance){
            window.__mxFpOpenInstance = instance;
            mxFpMarkCalendar(instance);
            mxFpRequestPlace(instance);
            requestAnimationFrame(function(){ mxFpPlaceCalendarNow(instance); });
            requestAnimationFrame(function(){ mxFpPlaceCalendarNow(instance); });
        });

        fp.config.onMonthChange.push(function(selectedDates, dateStr, instance){
            if (instance && instance.isOpen) mxFpRequestPlace(instance);
        });

        fp.config.onYearChange.push(function(selectedDates, dateStr, instance){
            if (instance && instance.isOpen) mxFpRequestPlace(instance);
        });

        fp.config.onValueUpdate.push(function(selectedDates, dateStr, instance){
            if (instance && instance.isOpen) mxFpRequestPlace(instance);
        });

        fp.config.onClose.push(function(selectedDates, dateStr, instance){
            if (window.__mxFpOpenInstance === instance) window.__mxFpOpenInstance = null;
        });

        if (fp.isOpen) {
            window.__mxFpOpenInstance = fp;
            mxFpRequestPlace(fp);
        }
    }

    function mxFpTryFixByInputElement(inputEl){
        if (!inputEl) return;
        if (inputEl._flatpickr) {
            mxFpEnsureHooks(inputEl._flatpickr);
        }
    }

    function mxFpTryFixBySelector(selector){
        const el = document.querySelector(selector);
        if (!el) return;
        mxFpTryFixByInputElement(el);
    }

    /**
     * ============================
     * FLATPICKR INIT (FILTER MAIN)
     * ============================
     */
    document.addEventListener("DOMContentLoaded", function() {
        const filterInput = document.getElementById("filter_date_input") || document.querySelector(".filter_date");
        if (!filterInput) return;

        // ✅ ВАЖНО: сохраняем выбранную дату ПЕРЕД любыми destroy/re-init
        function mxGetSelectedYmdBeforeDestroy() {
            if (!filterInput) return '';
            try {
                if (filterInput._flatpickr && Array.isArray(filterInput._flatpickr.selectedDates) && filterInput._flatpickr.selectedDates.length) {
                    return filterInput._flatpickr.formatDate(filterInput._flatpickr.selectedDates[0], "Y-m-d");
                }
            } catch (e) {}

            const v = (filterInput.value || '').trim();
            return /^\d{4}-\d{2}-\d{2}$/.test(v) ? v : '';
        }

        // Если где-то выше уже инициализировали flatpickr, уничтожаем и делаем заново
        if (filterInput._flatpickr) {
            try { filterInput._flatpickr.destroy(); } catch (e) {}
        }

        // ✅ keepDate — дата, которую пользователь выбрал (не сбрасываем при смене городов)
        function initBasicFilterPicker(keepDate) {
            if (filterInput._flatpickr) {
                try { filterInput._flatpickr.destroy(); } catch (e) {}
            }

            filterDatePicker = flatpickr(filterInput, {
                minDate: "today",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",

                // ✅ ключевой фикс:
                defaultDate: (keepDate && keepDate.trim() !== '')
                    ? keepDate
                    : "<?php echo isset($filterDate) ? $filterDate : date('Y-m-d')?>",

                locale: "<?php echo isset($lang) ? $lang : 'uk'?>",

                static: false,
                appendTo: document.body,
                disableMobile: true,
                position: "below left",

                onReady: function(selectedDates, dateStr, instance){
                    mxFpMarkCalendar(instance);
                    mxFpEnsureHooks(instance);
                },

                onOpen: function(selectedDates, dateStr, instance){
                    mxFpEnsureHooks(instance);
                    mxFpRequestPlace(instance);
                },

                onChange: function(selectedDates, dateStr, instance) {
                    const currentDate = new Date();
                    const threeYearsAgo = new Date(currentDate.getFullYear() - 3, currentDate.getMonth(), currentDate.getDate());
                    if (selectedDates.length && selectedDates[0] < threeYearsAgo) {
                        instance.setDate(threeYearsAgo);
                    }
                }
            });

            if (filterInput._flatpickr) {
                mxFpEnsureHooks(filterInput._flatpickr);
            }
        }

        if (!window.jQuery || !$('#filter_departure').length || !$('#filter_arrival').length) {
            initBasicFilterPicker(mxGetSelectedYmdBeforeDestroy());
            return;
        }

        let filterDatePickerLocal = null;

        function initHighlightedPicker(highlightedDaysArray, keepDate) {
            if (filterDatePickerLocal) {
                try { filterDatePickerLocal.destroy(); } catch (e) {}
            }
            if (filterInput._flatpickr) {
                try { filterInput._flatpickr.destroy(); } catch (e) {}
            }

            const allowedDays = Array.isArray(highlightedDaysArray)
                ? highlightedDaysArray.map(Number).filter((day) => day >= 1 && day <= 7)
                : [];
            const isAllowed = function(date) {
                let dayOfWeek = date.getDay();
                if (dayOfWeek === 0) dayOfWeek = 7;
                return allowedDays.includes(dayOfWeek);
            };

            filterDatePickerLocal = flatpickr(filterInput, {
                minDate: "today",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",

                // ✅ ключевой фикс:
                defaultDate: (keepDate && keepDate.trim() !== '')
                    ? keepDate
                    : "<?php echo isset($filterDate) ? $filterDate : date('Y-m-d')?>",

                locale: "<?php echo $lang?>",

                static: false,
                appendTo: document.body,
                disableMobile: true,
                position: "below left",

                disable: [
                    function(date) {
                        if (!allowedDays.length) return false;
                        return !isAllowed(date);
                    }
                ],

                onReady: function(selectedDates, dateStr, instance){
                    mxFpMarkCalendar(instance);
                    mxFpEnsureHooks(instance);
                },

                onOpen: function(selectedDates, dateStr, instance){
                    mxFpEnsureHooks(instance);
                    mxFpRequestPlace(instance);
                },

                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    let dayOfWeek = dayElem.dateObj.getDay();
                    if (dayOfWeek === 0) dayOfWeek = 7;
                    if (allowedDays.includes(dayOfWeek)) {
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

            filterDatePicker = filterDatePickerLocal;

            if (filterInput._flatpickr) {
                mxFpEnsureHooks(filterInput._flatpickr);
            }
        }

        function sendFilterRequest() {
            const keepDate = mxGetSelectedYmdBeforeDestroy(); // ✅ сохраняем выбранную пользователем дату

            const departure = $('#filter_departure').val();
            const arrival = $('#filter_arrival').val();

            // ✅ раньше тут дата сбрасывалась на сегодня из-за re-init
            // теперь re-init сохраняет keepDate
            if (!departure || !arrival) {
                initBasicFilterPicker(keepDate);
                return;
            }

            console.log("Отправляем запрос с параметрами departure:", departure, "и arrival:", arrival);
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: legacyAjaxUrl,
                data: {
                    request: 'filter_date',
                    departure: departure,
                    arrival: arrival
                },
                success: function(response) {
                    console.log("Получен ответ от сервера:", response);

                    const highlightedDaysArray = mxParseHighlightedWeekdays(response);

                    if (highlightedDaysArray.length) {
                        console.log(highlightedDaysArray);

                        initHighlightedPicker(highlightedDaysArray, keepDate); // ✅ держим дату
                        isFilterInitialized = true;
                    } else {
                        console.log("Нет доступных дней для выбранных параметров.");
                        initBasicFilterPicker(keepDate); // ✅ держим дату
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка при выполнении запроса:", error);
                    initBasicFilterPicker(keepDate); // ✅ держим дату
                }
            });
        }

        sendFilterRequest();
        $('#filter_departure, #filter_arrival').on("change", sendFilterRequest);
    });

    <?php
    $departure = isset($_SESSION['order']['fromCityId']) ? $_SESSION['order']['fromCityId'] : '';
    $arrival = isset($_SESSION['order']['toCityId']) ? $_SESSION['order']['toCityId'] : '';
    $orderDate = isset($_SESSION['order']['date']) ? $_SESSION['order']['date'] : '';
    ?>

    /**
     * ============================
     * FLATPICKR INIT (BOOKING)
     * ============================
     */
    document.addEventListener("DOMContentLoaded", function() {
        const filterInput = document.querySelector(".filter_date_booking");

        if (!filterInput) {
            return;
        }

        if (filterInput._flatpickr) {
            try { filterInput._flatpickr.destroy(); } catch (e) {}
        }

        let filterDatePickerLocal = null;

        function initBookingPickerBasic() {
            if (filterDatePickerLocal) {
                try { filterDatePickerLocal.destroy(); } catch (e) {}
            }
            if (filterInput._flatpickr) {
                try { filterInput._flatpickr.destroy(); } catch (e) {}
            }

            filterDatePickerLocal = flatpickr(filterInput, {
                minDate: "today",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",
                defaultDate: "<?php echo $orderDate?>",
                locale: "<?php echo $lang?>",

                static: false,
                appendTo: document.body,
                disableMobile: true,
                position: "below left",

                onReady: function(selectedDates, dateStr, instance){
                    mxFpMarkCalendar(instance);
                    mxFpEnsureHooks(instance);
                },

                onOpen: function(selectedDates, dateStr, instance){
                    mxFpEnsureHooks(instance);
                    mxFpRequestPlace(instance);
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

            if (filterInput._flatpickr) {
                mxFpEnsureHooks(filterInput._flatpickr);
            }
        }

        function initBookingPickerHighlighted(highlightedDaysArray) {
            if (filterDatePickerLocal) {
                try { filterDatePickerLocal.destroy(); } catch (e) {}
            }
            if (filterInput._flatpickr) {
                try { filterInput._flatpickr.destroy(); } catch (e) {}
            }

            const allowedDays = Array.isArray(highlightedDaysArray)
                ? highlightedDaysArray.map(Number).filter((day) => day >= 1 && day <= 7)
                : [];
            const isAllowed = function(date) {
                let dayOfWeek = date.getDay();
                if (dayOfWeek === 0) dayOfWeek = 7;
                return allowedDays.includes(dayOfWeek);
            };

            filterDatePickerLocal = flatpickr(filterInput, {
                minDate: "today",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",
                defaultDate: "<?php echo $orderDate?>",
                locale: "<?php echo $lang?>",

                static: false,
                appendTo: document.body,
                disableMobile: true,
                position: "below left",

                disable: [
                    function(date) {
                        if (!allowedDays.length) return false;
                        return !isAllowed(date);
                    }
                ],

                onReady: function(selectedDates, dateStr, instance){
                    mxFpMarkCalendar(instance);
                    mxFpEnsureHooks(instance);
                },

                onOpen: function(selectedDates, dateStr, instance){
                    mxFpEnsureHooks(instance);
                    mxFpRequestPlace(instance);
                },

                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    let dayOfWeek = dayElem.dateObj.getDay();
                    if (dayOfWeek === 0) dayOfWeek = 7;
                    if (allowedDays.includes(dayOfWeek)) {
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

            if (filterInput._flatpickr) {
                mxFpEnsureHooks(filterInput._flatpickr);
            }
        }

        function sendFilterRequest() {
            const departure = "<?php echo  $departure ?>";
            const arrival = "<?php echo  $arrival ?>";
            console.log("Отправляем запрос с параметрами departure:", departure, "и arrival:", arrival);

            $.ajax({
                type: 'post',
                url: legacyAjaxUrl,
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

                    const highlightedDaysArray = mxParseHighlightedWeekdays(response);

                    if (highlightedDaysArray.length) {
                        console.log(highlightedDaysArray);

                        initBookingPickerHighlighted(highlightedDaysArray);
                        isFilterInitialized = true;

                    } else {
                        console.log("Нет доступных дней для выбранных параметров.");
                        initBookingPickerBasic();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ошибка при выполнении запроса:", error);
                    initBookingPickerBasic();
                }
            });

        }

        function updateSessionDate(date) {
            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: legacyAjaxUrl,
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
        $('.callback_popUp').find('.error-border').removeClass('error-border');

        let params = {
            phone: $.trim($('#callback_phone').val()),
            arrival: $.trim($('.callback_popUp select[name="to_location"]').val()),
            departure: $.trim($('.callback_popUp select[name="from_location"]').val()),
            comment: $.trim($('#callback_message').val()),
        };

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
        $('.callback_form').find('.error-border').removeClass('error-border');

        let departure = $.trim($('#callback_departure').val());
        let arrival = $.trim($('#callback_arrival').val());
        let phone = $.trim($('#callback_phone').val());
        let message = $.trim($('#callback_message').val());

        let hasErrors = false;

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
            url: legacyAjaxUrl,
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
        var loader = document.querySelector('.loader');

        if (loader) {
            loader.remove();
        }
    };

    document.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            document.getElementById('successModal').style.display = 'none';
            window.location.reload();
        });
    });

    /*
        ============================
        MAXTRANS: GLOBAL LISTENERS
        ============================
    */
    document.addEventListener('DOMContentLoaded', function(){
        setTimeout(function(){
            mxFpTryFixBySelector('#filter_date_input');
            mxFpTryFixBySelector('.filter_date');
            mxFpTryFixBySelector('.filter_date_booking');
        }, 0);

        window.addEventListener('scroll', function(){
            if (window.__mxFpOpenInstance && window.__mxFpOpenInstance.isOpen) {
                mxFpRequestPlace(window.__mxFpOpenInstance);
            }
        }, true);

        window.addEventListener('resize', function(){
            if (window.__mxFpOpenInstance && window.__mxFpOpenInstance.isOpen) {
                mxFpRequestPlace(window.__mxFpOpenInstance);
            }
        }, true);

        document.addEventListener('click', function(e){
            const t = e.target;

            const calendarBtn = t && (t.closest ? t.closest('.filter_calendar_btn') : null);
            if (calendarBtn) {
                const inp = document.getElementById('filter_date_input') || document.querySelector('.filter_date');
                if (inp && inp._flatpickr) {
                    mxFpEnsureHooks(inp._flatpickr);
                    if (inp._flatpickr.isOpen) {
                        mxFpRequestPlace(inp._flatpickr);
                    }
                }
                return;
            }

            if (t && t.classList && t.classList.contains('flatpickr-input')) {
                if (t._flatpickr) {
                    mxFpEnsureHooks(t._flatpickr);
                } else {
                    mxFpTryFixBySelector('#filter_date_input');
                    mxFpTryFixBySelector('.filter_date_booking');
                }
            }
        }, true);
    });
</script>
