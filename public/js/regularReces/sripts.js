let options = {
    router: {
        requestCallback: '/ajax/callback'
    }
};

jQuery(document).ready(function (){
    /*document.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            document.getElementById('successModal').style.display = 'none';
            window.location.reload();
        });
    });*/

    // Удаляем красную рамку при вводе данных
    $('input[name="name"], #callback_phone, #callback_departure, #callback_arrival').on('input change', function() {
        $(this).removeClass('error-border');
    });

    const stationSelect = document.getElementById('stationSelect');
    if (stationSelect) {
        stationSelect.addEventListener('change', function () {
            const raceId = this.value;
            const tour = $('#stationSelect').data('tour');

            fetch(`/ajax/regular-races?stop_id=${raceId}&tour=${tour}`)
                .then(response => response.text())
                .then(html => {
                    const contentWay = document.querySelector('[data-content-way]');
                    if (contentWay) {
                        contentWay.innerHTML = html;
                    }
                })
                .catch(err => {
                    console.error('Ошибка при загрузке расписания:', err);
                });
        });
    }


    $('.requestCallback').on('click', function (e){
        e.preventDefault();

        let form = $(this).closest('form');

        // Удаляем предыдущие ошибки
        form.find('.error-border').removeClass('error-border');

        let params = {
            name: form.find('input[name="name"]').val(),
            phone: $.trim($('#callback_phone').val()),
            date: form.find('input[name="date"]').val(),
            arrival: $.trim($('#callback_arrival').val()),
            departure: $.trim($('#callback_departure').val()),
            comment: form.find('textarea[name="comment"]').val(),
        };

        // Валидация обязательных полей
        let hasErrors = false;

        if (!params.name || params.name.trim() === '') {
            form.find('input[name="name"]').addClass('error-border');
            hasErrors = true;
        }

        if (!params.phone || params.phone.trim() === '') {
            $('#callback_phone').addClass('error-border');
            hasErrors = true;
        }

        if (!params.departure || params.departure === '') {
            $('#callback_departure').addClass('error-border');
            hasErrors = true;
        }

        if (!params.arrival || params.arrival === '') {
            $('#callback_arrival').addClass('error-border');
            hasErrors = true;
        }

        if (hasErrors) {
            return false;
        }

        sendRequestOrder(params);
    });

    function sendRequestOrder(params)
    {
        $.ajax({
            type: "POST",
            url: options.router.requestCallback,
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify(params),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                document.getElementById('successModal').style.display = 'flex';
            },
            error: function (xhr) {
                alert('Request din`t send');
            }
        });
    }

    const calendarModal = document.getElementById('calendar-modal');
    const calendarElement = document.getElementById('calendar');
    const saveBtn = document.getElementById('save-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const dateInput = document.getElementById('table_date');

    let selectedDate = null;
    let allowedDays = [];
    let buyOnline = false;
    let arrival = '';
    let departure = '';
    let redirect = '';

// Функция для обновления календаря
    function updateCalendar() {
        calendar.set('disable', [
            function(date) {
                // Блокируем даты до сегодняшнего дня
                return date < new Date().setHours(0, 0, 0, 0);
            }
        ]);

        calendar.redraw(); // Обновляем календарь для применения стилей
    }

    function clearHighlightDays(calendarElement) {
        const highlightedDays = calendarElement.querySelectorAll('.highlight-day');
        highlightedDays.forEach(dayElem => dayElem.classList.remove('highlight-day'));
        calendar.redraw();
    }

    function createHiddenInput(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        return input;
    }


// Инициализация Flatpickr
    const calendar = calendarElement ? flatpickr(calendarElement, {
        inline: true,
        dateFormat: "Y-m-d",
        position: "auto center",
        disableMobile: true,
        showMonths: 1,
        monthSelectorType: 'static',
        locale: document.documentElement.getAttribute('lang'),
        prevArrow: "←",
        nextArrow: "→",

        onChange: function(selectedDates, dateStr) {
            selectedDate = dateStr;
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const date = new Date(dayElem.dateObj);
            const day = date.getDay();

            if (allowedDays.includes(day)) {
                dayElem.classList.add("highlight-day");
            }

            if (day === 0 || day === 6) {
                dayElem.style.color = "red";
                dayElem.style.borderRadius = 100;
                dayElem.borderColor = 'blue';
            }
        }
    }) : null;

// Открытие календаря при клике на input
    if (dateInput && calendarModal && calendar) {
        dateInput.addEventListener('focus', () => {
            buyOnline = false;
            arrival = '';
            departure = '';
            redirect = '';
            const days = [];
            allowedDays = days;
            updateCalendar();
            calendarModal.style.display = 'flex';
        });
    }

    function getNearestDate(button) {
        const dateAttr = button.getAttribute('data-date');
        const todayAttr = button.getAttribute('data-today');

        if (dateAttr) {
            return dateAttr;
        }

        const daysRaw = button.getAttribute('data-days') || '';
        const days = daysRaw
            .split(',')
            .map(Number)
            .filter(Number.isFinite);

        const baseDate = todayAttr ? new Date(`${todayAttr}T00:00:00`) : new Date();

        if (days.length) {
            for (let i = 0; i <= 14; i += 1) {
                const candidate = new Date(baseDate);
                candidate.setDate(baseDate.getDate() + i);
                const day = candidate.getDay() === 0 ? 7 : candidate.getDay();
                if (days.includes(day)) {
                    return candidate.toISOString().slice(0, 10);
                }
            }
        }

        return todayAttr || new Date().toISOString().slice(0, 10);
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.buy-online-btn');
        if (!button) {
            return;
        }

        event.preventDefault();

        const arrivalId = button.getAttribute('data-arrival');
        const departureId = button.getAttribute('data-departure');

        if (!arrivalId || !departureId) {
            alert('Не удалось определить направление. Пожалуйста, попробуйте позже.');
            console.error('Buy online: missing arrival/departure', {
                arrivalId,
                departureId,
                button,
            });
            return;
        }

        const redirectUrl = button.getAttribute('data-redirect') || '/bilety';
        const date = getNearestDate(button);

        if (!date) {
            alert('Не удалось определить дату поездки. Попробуйте ещё раз.');
            console.error('Buy online: missing date', {
                redirectUrl,
                arrivalId,
                departureId,
                button,
            });
            return;
        }

        const url = new URL(redirectUrl, window.location.origin);
        url.searchParams.set('from', departureId);
        url.searchParams.set('to', arrivalId);
        url.searchParams.set('date', date);
        url.searchParams.set('adults', '1');
        url.searchParams.set('kids', '0');

        window.location.href = url.toString();
    });

// Сохранение даты
    if (saveBtn && calendarModal) {
        saveBtn.addEventListener('click', () => {
            if (buyOnline) {
                if (selectedDate) {
                    const calendarElement = document.querySelector('.flatpickr-calendar');
                    clearHighlightDays(calendarElement);

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = redirect;

                    // Добавляем необходимые поля
                    form.appendChild(createHiddenInput('departure', departure));
                    form.appendChild(createHiddenInput('arrival', arrival));
                    form.appendChild(createHiddenInput('date', selectedDate));
                    form.appendChild(createHiddenInput('adults', 1));
                    form.appendChild(createHiddenInput('kids', 0));

                    // Добавляем форму на страницу и отправляем
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    alert("Пожалуйста, выберите дату.");
                }
            } else {
                if (selectedDate) {
                    dateInput.value = selectedDate;
                    calendarModal.style.display = 'none';
                    const calendarElement = document.querySelector('.flatpickr-calendar');
                    clearHighlightDays(calendarElement);
                } else {
                    alert("Пожалуйста, выберите дату.");
                }
            }


        });
    }

// Отмена
    if (cancelBtn && calendarModal) {
        cancelBtn.addEventListener('click', () => {
            const calendarElement = document.querySelector('.flatpickr-calendar');
            if (calendarElement) {
                clearHighlightDays(calendarElement);
            }
            calendarModal.style.display = 'none';
        });
    }
});
