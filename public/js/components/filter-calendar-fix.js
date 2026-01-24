/**
 * Фикс для правильной инициализации календаря в фильтре
 * - на мобилке календарь всегда overlay поверх (fixed + max z-index)
 * - appendTo = body
 * - при открытии занижаем слой пассажиров
 */

function initFilterCalendar() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilterCalendar);
        return;
    }

    if (typeof flatpickr === 'undefined') {
        console.warn('Flatpickr не загружен, повторная попытка через 100ms');
        setTimeout(initFilterCalendar, 100);
        return;
    }

    const filterDateInputs = document.querySelectorAll('.filter_date, #filter_date_input');
    const isMobile = window.matchMedia('(max-width: 768px)').matches;

    function makeCalendarTop(instance) {
        const cal = instance.calendarContainer;
        if (!cal) return;

        // Всегда держим календарь в body
        if (cal.parentElement !== document.body) {
            document.body.appendChild(cal);
        }

        // Максимальный z-index (если что-то у тебя тоже огромным z-index — этот почти не перебить)
        cal.style.zIndex = '2147483647';

        // Позиционируем относительно видимого поля (altInput)
        instance._positionElement = instance.altInput || instance.input;

        // На мобилке делаем fixed — тогда никакие блоки формы физически не могут перекрыть
        if (isMobile) {
            cal.style.position = 'fixed';
            cal.style.margin = '0';

            const el = instance._positionElement;
            const rect = el.getBoundingClientRect();
            const pad = 8;

            // Дадим flatpickr сначала посчитать размеры
            const calW = cal.offsetWidth || 320;

            let left = rect.left;
            if (left + calW + pad > window.innerWidth) {
                left = Math.max(pad, window.innerWidth - calW - pad);
            }

            const top = rect.bottom + pad;

            cal.style.top = `${top}px`;
            cal.style.left = `${left}px`;
            cal.style.right = 'auto';
            cal.style.bottom = 'auto';
        } else {
            // На десктопе пусть flatpickr сам позиционирует
            cal.style.position = '';
            cal.style.top = '';
            cal.style.left = '';
            cal.style.right = '';
            cal.style.bottom = '';
        }
    }

    function bindReposition(instance) {
        if (instance.__mtBound) return;
        instance.__mtBound = true;

        const handler = () => {
            if (instance.isOpen) makeCalendarTop(instance);
        };

        instance.__mtHandler = handler;

        // scroll с capture=true, чтобы ловить скроллы внутри контейнеров
        window.addEventListener('scroll', handler, true);
        window.addEventListener('resize', handler);
    }

    function unbindReposition(instance) {
        if (!instance.__mtBound) return;
        instance.__mtBound = false;

        window.removeEventListener('scroll', instance.__mtHandler, true);
        window.removeEventListener('resize', instance.__mtHandler);

        instance.__mtHandler = null;
    }

    filterDateInputs.forEach(input => {
        // Всегда уничтожаем существующий инстанс (иначе бывают конфликты altInput/позиции)
        if (input._flatpickr) {
            input._flatpickr.destroy();
        }

        const lang =
            input.closest('form')?.querySelector('[name="lang"]')?.value ||
            document.documentElement.lang ||
            'uk';

        // defaultDate: лучше брать только input.value (оно в формате Y-m-d).
        // placeholder/altInput может быть "Январь 22, 2026" и flatpickr это не всегда корректно парсит.
        const currentDate = input.value ? input.value : null;

        flatpickr(input, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y",
            defaultDate: currentDate,
            minDate: "today",
            locale: lang,

            disableMobile: true,

            // На мобилке точно НЕ static (иначе календарь может “жить” внутри формы)
            static: !isMobile ? true : false,

            // Ключевое: календарь в body
            appendTo: document.body,

            position: "auto left",

            onReady: function(dateObj, dateStr, instance) {
                instance.calendarContainer.classList.add('filter-calendar');
                makeCalendarTop(instance);
                bindReposition(instance);
            },

            onOpen: function(dateObj, dateStr, instance) {
                document.body.classList.add('fp-filter-open');

                // Пусть flatpickr выставит базовую позицию, потом мы “дожмём” fixed/top/left/z-index
                requestAnimationFrame(() => {
                    instance.positionCalendar();
                    makeCalendarTop(instance);
                });
            },

            onClose: function(dateObj, dateStr, instance) {
                document.body.classList.remove('fp-filter-open');
                unbindReposition(instance);
            }
        });

        console.log('Календарь инициализирован (TOP/FIXED) для:', input);
    });
}

function reinitFilterCalendar() {
    const filterDateInputs = document.querySelectorAll('.filter_date, #filter_date_input');
    filterDateInputs.forEach(input => {
        if (input._flatpickr) input._flatpickr.destroy();
    });

    setTimeout(initFilterCalendar, 100);
}

window.initFilterCalendar = initFilterCalendar;
window.reinitFilterCalendar = reinitFilterCalendar;

initFilterCalendar();
document.addEventListener('filterUpdated', reinitFilterCalendar);
