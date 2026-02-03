/**
 * Фикс для правильной инициализации календаря в фильтре
 * Решает проблему двойной инициализации и конфликта стилей
 */

// Функция для безопасной инициализации календаря
function initFilterCalendar() {
    // Ждем загрузки DOM и всех скриптов
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilterCalendar);
        return;
    }
    
    // Проверяем доступность flatpickr
    if (typeof flatpickr === 'undefined') {
        console.warn('Flatpickr не загружен, повторная попытка через 100ms');
        setTimeout(initFilterCalendar, 100);
        return;
    }
    
    // Находим все элементы календаря
    const filterDateInputs = document.querySelectorAll('.filter_date, #filter_date_input');
    
    filterDateInputs.forEach(input => {
        // Проверяем что календарь еще не инициализирован
        if (!input._flatpickr) {
            // Получаем параметры для инициализации
            const lang = input.closest('form').querySelector('[name="lang"]')?.value || 
                        document.documentElement.lang || 'uk';
            const currentDate = input.value || input.placeholder || new Date().toISOString().split('T')[0];
            
            // Инициализируем flatpickr с правильными параметрами
            const fp = flatpickr(input, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "F j, Y",
                defaultDate: currentDate,
                minDate: "today",
                locale: lang,
                static: true,
                disableMobile: true,
                appendTo: input.parentElement, // Важно для правильного позиционирования
                onReady: function(dateObj, dateStr, instance) {
                    // Добавляем класс для стилизации
                    instance.calendarContainer.classList.add('filter-calendar');
                }
            });
            
            console.log('Календарь инициализирован для элемента:', input);
        }
    });
}

// Функция для переинициализации календаря при AJAX обновлениях
function reinitFilterCalendar() {
    // Уничтожаем существующие инстансы
    const filterDateInputs = document.querySelectorAll('.filter_date, #filter_date_input');
    filterDateInputs.forEach(input => {
        if (input._flatpickr) {
            input._flatpickr.destroy();
        }
    });
    
    // Инициализируем заново
    setTimeout(initFilterCalendar, 100);
}

// Экспортируем функции в глобальную область видимости
window.initFilterCalendar = initFilterCalendar;
window.reinitFilterCalendar = reinitFilterCalendar;

// Запускаем инициализацию
initFilterCalendar();

// Слушаем кастомное событие для переинициализации после AJAX
document.addEventListener('filterUpdated', reinitFilterCalendar);
