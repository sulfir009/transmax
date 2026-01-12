/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!*********************************************!*\
  !*** ./resources/js/regularReces/sripts.js ***!
  \*********************************************/
__webpack_require__.r(__webpack_exports__);
var options = {
  router: {
    requestCallback: '/ajax/callback'
  }
};
jQuery(document).ready(function () {
  /*document.querySelectorAll('[data-close]').forEach(function (el) {
      el.addEventListener('click', function () {
          document.getElementById('successModal').style.display = 'none';
          window.location.reload();
      });
  });*/

  // Удаляем красную рамку при вводе данных
  $('input[name="name"], #callback_phone, #callback_departure, #callback_arrival').on('input change', function () {
    $(this).removeClass('error-border');
  });
  document.getElementById('stationSelect').addEventListener('change', function () {
    var raceId = this.value;
    var tour = $('#stationSelect').data('tour');
    fetch("/ajax/regular-races?stop_id=".concat(raceId, "&tour=").concat(tour)).then(function (response) {
      return response.text();
    }).then(function (html) {
      document.querySelector('[data-content-way]').innerHTML = html;
    })["catch"](function (err) {
      console.error('Ошибка при загрузке расписания:', err);
    });
  });
  $('.requestCallback').on('click', function (e) {
    e.preventDefault();
    var form = $(this).closest('form');

    // Удаляем предыдущие ошибки
    form.find('.error-border').removeClass('error-border');
    var params = {
      name: form.find('input[name="name"]').val(),
      phone: $.trim($('#callback_phone').val()),
      date: form.find('input[name="date"]').val(),
      arrival: $.trim($('#callback_arrival').val()),
      departure: $.trim($('#callback_departure').val()),
      comment: form.find('textarea[name="comment"]').val()
    };

    // Валидация обязательных полей
    var hasErrors = false;
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
  function sendRequestOrder(params) {
    $.ajax({
      type: "POST",
      url: options.router.requestCallback,
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify(params),
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function success(response) {
        document.getElementById('successModal').style.display = 'flex';
      },
      error: function error(xhr) {
        alert('Request din`t send');
      }
    });
  }
  var buyOnlineBtns = document.querySelectorAll('.buy-online-btn');
  var calendarModal = document.getElementById('calendar-modal');
  var calendarElement = document.getElementById('calendar');
  var saveBtn = document.getElementById('save-btn');
  var cancelBtn = document.getElementById('cancel-btn');
  var dateInput = document.getElementById('table_date');
  var selectedDate = null;
  var allowedDays = [];
  var buyOnline = false;
  var arrival = '';
  var departure = '';
  var redirect = '';

  // Функция для обновления календаря
  function updateCalendar() {
    calendar.set('disable', [function (date) {
      // Блокируем даты до сегодняшнего дня
      return date < new Date().setHours(0, 0, 0, 0);
    }]);
    calendar.redraw(); // Обновляем календарь для применения стилей
  }
  function clearHighlightDays(calendarElement) {
    var highlightedDays = calendarElement.querySelectorAll('.highlight-day');
    highlightedDays.forEach(function (dayElem) {
      return dayElem.classList.remove('highlight-day');
    });
    calendar.redraw();
  }
  function createHiddenInput(name, value) {
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    return input;
  }

  // Инициализация Flatpickr
  var calendar = flatpickr(calendarElement, {
    inline: true,
    dateFormat: "Y-m-d",
    position: "auto center",
    disableMobile: true,
    showMonths: 1,
    monthSelectorType: 'static',
    locale: document.documentElement.getAttribute('lang'),
    prevArrow: "←",
    nextArrow: "→",
    onChange: function onChange(selectedDates, dateStr) {
      selectedDate = dateStr;
    },
    onDayCreate: function onDayCreate(dObj, dStr, fp, dayElem) {
      var date = new Date(dayElem.dateObj);
      var day = date.getDay();
      if (allowedDays.includes(day)) {
        dayElem.classList.add("highlight-day");
      }
      if (day === 0 || day === 6) {
        dayElem.style.color = "red";
        dayElem.style.borderRadius = 100;
        dayElem.borderColor = 'blue';
      }
    }
  });

  // Открытие календаря при клике на input
  dateInput.addEventListener('focus', function () {
    buyOnline = false;
    arrival = '';
    departure = '';
    redirect = '';
    var days = [];
    allowedDays = days;
    updateCalendar();
    calendarModal.style.display = 'flex';
  });

  // Открытие календаря по кнопке "Купить онлайн"
  buyOnlineBtns.forEach(function (button) {
    button.addEventListener('click', function () {
      var days = button.getAttribute('data-days').split(',').map(Number);
      allowedDays = days;
      updateCalendar();
      calendarModal.style.display = 'flex';
      buyOnline = true;
      arrival = button.getAttribute('data-arrival');
      departure = button.getAttribute('data-departure');
      redirect = button.getAttribute('data-redirect');
    });
  });

  // Сохранение даты
  saveBtn.addEventListener('click', function () {
    if (buyOnline) {
      if (selectedDate) {
        var _calendarElement = document.querySelector('.flatpickr-calendar');
        clearHighlightDays(_calendarElement);
        var form = document.createElement('form');
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
        var _calendarElement2 = document.querySelector('.flatpickr-calendar');
        clearHighlightDays(_calendarElement2);
      } else {
        alert("Пожалуйста, выберите дату.");
      }
    }
  });

  // Отмена
  cancelBtn.addEventListener('click', function () {
    var calendarElement = document.querySelector('.flatpickr-calendar');
    clearHighlightDays(calendarElement);
    calendarModal.style.display = 'none';
  });
});
/******/ })()
;