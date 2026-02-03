<form class="main_filter" method="post" action="{{ $formAction ?? route('tickets.index') }}">
    @csrf
    <div class="flex-row gap-8">
        {{-- Откуда --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper">
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par">
                        @lang('dictionary.MSG_ALL_ZVIDKI')
                    </div>
                    <select class="filter_city_select" id="filter_departure" name="departure">
                        @foreach($cities as $city)
                            <option value="{{ $city['id'] }}"
                                {{ $filterDeparture == $city['id'] ? 'selected' : '' }}>
                                {{ $city['title'] }}
                            </option>
                        @endforeach
                    </select>
                    <button class="reverse_filter_btn" onclick="switchDirections()" type="button">
                        <img src="{{ asset('images/legacy/common/pair_arrows.svg') }}" alt="pair_arrows">
                    </button>
                </div>
            </div>
        </div>

        {{-- Куда --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper">
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par">
                        @lang('dictionary.MSG_ALL_KUDA')
                    </div>
                    <select class="filter_city_select" id="filter_arrival" name="arrival">
                        @foreach($cities as $city)
                            <option value="{{ $city['id'] }}"
                                {{ $filterArrival == $city['id'] ? 'selected' : '' }}>
                                {{ $city['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Дата --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper">
                <div class="filter_date_wrapper">
                    <div class="filter_date_title par">
                        @lang('dictionary.MSG_ALL_KOLI')
                    </div>
                    <input type="text"
                           class="filter_date"
                           name="date"
                           value="{{ $filterDate ?? date('Y-m-d') }}"
                           id="filter_date_input"
                           placeholder="{{ date('Y-m-d') }}">
                    <button class="filter_calendar_btn" type="button" onclick="toggleFilterCalendar()">
                        <img src="{{ asset('images/legacy/common/filter_calendar.svg') }}" alt="calendar" class="fit_img">
                    </button>
                </div>
            </div>
        </div>

        {{-- Пассажиры --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper passagers_filter_wrapper">
                <div class="filter_block passagers" onclick="toggleSubmenu(this)">
                    <div class="filter_block_title par">
                        {{ $dictionary['MSG_ALL_PASAZHIRI'] ?? __('dictionary.MSG_ALL_PASAZHIRI') }}
                    </div>
                    <div class="filter_block_value flex_ac filter_passagers_total">
                        <div>
                            <span class="adults_total">{{ $filterAdults }}</span>
                            {{ $dictionary['MSG_ALL_DOROSLIH'] ?? __('dictionary.MSG_ALL_DOROSLIH') }}
                        </div>
                        <div>
                            <span class="kids_total">{{ $filterKids }}</span>
                            {{ $dictionary['MSG_ALL_DITEJ'] ?? __('dictionary.MSG_ALL_DITEJ') }}
                        </div>
                    </div>
                </div>
                <div class="passagers_counter_wrapper filter_submenu">
                    {{-- Взрослые --}}
                    <div class="passengers_counter_block flex_ac adult_passagers">
                        <div class="passengers_counter_title h5_title">
                            {{ $dictionary['MSG_ALL_DOROSLIH'] ?? __('dictionary.MSG_ALL_DOROSLIH') }}
                        </div>
                        <div class="passengers_counter adults flex_ac">
                            <button class="counter_btn minus"
                                    onclick="countPassagers(this,'minus','adults')"
                                    type="button">
                                <img src="{{ asset('images/legacy/common/minus.svg') }}" alt="minus">
                            </button>
                            <div class="p_counter_value par adults_passagers">{{ $filterAdults }}</div>
                            <input type="hidden"
                                   name="adults"
                                   class="adults_passengers"
                                   value="{{ $filterAdults }}">
                            <button class="counter_btn plus"
                                    onclick="countPassagers(this,'plus','adults', 15)"
                                    type="button">
                                <img src="{{ asset('images/legacy/common/plus.svg') }}" alt="plus">
                            </button>
                        </div>
                    </div>

                    {{-- Дети --}}
                    <div class="passengers_counter_block flex_ac">
                        <div class="passengers_counter_title h5_title">
                            {{ $dictionary['MSG_ALL_DITEJ'] ?? __('dictionary.MSG_ALL_DITEJ') }}
                            <span>{{ $dictionary['MSG_ALL_DO_3_ROKIV_-_BEZKOSHTOVNO'] ?? __('dictionary.MSG_ALL_DO_3_ROKIV_-_BEZKOSHTOVNO') }}</span>
                        </div>
                        <div class="passengers_counter kids flex_ac">
                            <button class="counter_btn minus"
                                    onclick="countPassagers(this,'minus','kids')"
                                    type="button">
                                <img src="{{ asset('images/legacy/common/minus.svg') }}" alt="minus">
                            </button>
                            <div class="p_counter_value par kids_passagers">{{ $filterKids }}</div>
                            <input type="hidden"
                                   name="kids"
                                   class="kids_passengers"
                                   value="{{ $filterKids }}">
                            <button class="counter_btn plus"
                                    onclick="countPassagers(this,'plus','kids', 15)"
                                    type="button">
                                <img src="{{ asset('images/legacy/common/plus.svg') }}" alt="plus">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Кнопка поиска --}}
        <div class="col-lg-20 col-xs-12">
            <input type="submit"
                   class="filter_btn btn_txt blue_btn flex_ac"
                   value="{{ $dictionary['MSG_ALL_ZNAJTI_KVITOK'] ?? __('dictionary.MSG_ALL_ZNAJTI_KVITOK') }}">
        </div>
    </div>
</form>

@push('scripts')
<script>
    // Передаем переменную filterDate в глобальную область видимости
    window.filterDate = '{{ $filterDate ?? date('Y-m-d') }}';

    // Функция переключения направлений
    function switchDirections() {
        const departureSelect = document.getElementById('filter_departure');
        const arrivalSelect = document.getElementById('filter_arrival');

        const tempValue = departureSelect.value;
        departureSelect.value = arrivalSelect.value;
        arrivalSelect.value = tempValue;
    }

    // Функция подсчета пассажиров
    function countPassagers(btn, operation, type, maxValue = 15) {
        const counterWrapper = btn.closest('.passengers_counter');
        const valueElement = counterWrapper.querySelector('.p_counter_value');
        const inputElement = counterWrapper.querySelector('input[type="hidden"]');
        const totalElement = document.querySelector(`.${type}_total`);

        let currentValue = parseInt(valueElement.textContent);

        if (operation === 'plus' && currentValue < maxValue) {
            currentValue++;
        } else if (operation === 'minus') {
            if (type === 'adults' && currentValue > 1) {
                currentValue--;
            } else if (type === 'kids' && currentValue > 0) {
                currentValue--;
            }
        }

        valueElement.textContent = currentValue;
        inputElement.value = currentValue;
        if (totalElement) {
            totalElement.textContent = currentValue;
        }
    }

    // Функция переключения подменю
    function toggleSubmenu(element) {
        const submenu = element.nextElementSibling;
        if (submenu && submenu.classList.contains('filter_submenu')) {
            submenu.classList.toggle('active');
            element.classList.toggle('active');
        }
    }

    // Функция переключения календаря
    function toggleFilterCalendar() {
        const dateInput = document.getElementById('filter_date_input');
        if (dateInput && dateInput._flatpickr) {
            dateInput._flatpickr.open();
        } else if (dateInput) {
            // Попытка найти существующий flatpickr по классу
            const filterDate = document.querySelector('.filter_date');
            if (filterDate && filterDate._flatpickr) {
                filterDate._flatpickr.open();
            } else {
                dateInput.focus();
            }
        }
    }

    // Закрытие подменю при клике вне элемента
    document.addEventListener('click', function(event) {
        const passengersWrapper = document.querySelector('.passagers_filter_wrapper');
        if (passengersWrapper && !passengersWrapper.contains(event.target)) {
            const submenu = passengersWrapper.querySelector('.filter_submenu');
            const block = passengersWrapper.querySelector('.filter_block');
            if (submenu && submenu.classList.contains('active')) {
                submenu.classList.remove('active');
                block.classList.remove('active');
            }
        }
    });

    // НЕ инициализируем датапикер здесь, так как он уже инициализируется в footer_scripts.blade.php
    // для элементов с классом .filter_date
    // Если нужна дополнительная логика, добавляем её после основной инициализации
    document.addEventListener('DOMContentLoaded', function() {
        // Ждем немного, чтобы основная инициализация из footer_scripts завершилась
        setTimeout(function() {
            const filterDateInput = document.getElementById('filter_date_input');
            
            // Если календарь не был инициализирован в footer_scripts (на случай если элемент не был найден)
            if (filterDateInput && typeof flatpickr !== 'undefined' && !filterDateInput._flatpickr) {
                console.warn('Flatpickr не был инициализирован в footer_scripts, инициализируем здесь');
                // Здесь можем добавить запасную инициализацию если потребуется
            }
        }, 500);
    });
</script>
@endpush
