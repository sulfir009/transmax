<form class="main_filter"
      autocomplete="off"
      method="GET"
      action="{{ $formAction ?? route('tickets.index') }}"
      data-reset-url="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}">

    <div class="flex-row gap-8">

        {{-- Откуда --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper">
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par">
                        @lang('dictionary.MSG_ALL_ZVIDKI')
                    </div>

                    <select class="filter_city_select" id="filter_departure" name="from">
                        {{-- ВАЖНО: НЕ disabled. Это реальный пункт, который можно выбрать обратно --}}
                        <option value="" {{ empty($filterDeparture) ? 'selected' : '' }}>Выберите город</option>

                        @foreach($cities as $city)
                            <option value="{{ $city['id'] }}"
                                {{ !empty($filterDeparture) && (int)$filterDeparture === (int)$city['id'] ? 'selected' : '' }}>
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

                    {{-- ВАЖНО: name="arrival" --}}
                    <select class="filter_city_select" id="filter_arrival" name="to">
                        <option value="" {{ empty($filterArrival) ? 'selected' : '' }}>Выберите город</option>

                        @foreach($cities as $city)
                            <option value="{{ $city['id'] }}"
                                {{ !empty($filterArrival) && (int)$filterArrival === (int)$city['id'] ? 'selected' : '' }}>
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
    function hasSelect2() {
        return window.jQuery && jQuery.fn && typeof jQuery.fn.select2 === 'function';
    }

    // ВАЖНО: не задаём placeholder в select2 config,
    // иначе option value="" может исчезнуть из списка и ты не сможешь "выбрать Выберите город".
    function initSelect2IfNeeded() {
        if (!hasSelect2()) return;

        jQuery('.filter_city_select').each(function () {
            const $el = jQuery(this);
            if ($el.hasClass('select2-hidden-accessible')) return;

            $el.select2({
                width: '100%'
            });
        });
    }

    function setSelectValue(selectEl, value) {
        if (!selectEl) return;

        selectEl.value = value;

        if (hasSelect2()) {
            jQuery(selectEl).val(value).trigger('change.select2');
        }
    }

    // Если в URL нет параметров — ставим "Выберите город"
    function forceDefaultIfNoParams() {
        const params = new URLSearchParams(window.location.search);

        const dep = params.get('from') ?? params.get('departure');
        const arr = params.get('to') ?? params.get('arrival');

        const noDep = !dep || dep === '0';
        const noArr = !arr || arr === '0';

        if (!(noDep && noArr)) return;

        setSelectValue(document.getElementById('filter_departure'), '');
        setSelectValue(document.getElementById('filter_arrival'), '');
    }

    // Меняем местами значения
    function switchDirections() {
        const dep = document.getElementById('filter_departure');
        const arr = document.getElementById('filter_arrival');
        if (!dep || !arr) return;

        const tmp = dep.value;
        setSelectValue(dep, arr.value);
        setSelectValue(arr, tmp);
    }
    window.switchDirections = switchDirections;

    function countPassagers(btn, operation, type, maxValue = 15) {
        const counterWrapper = btn.closest('.passengers_counter');
        const valueElement = counterWrapper.querySelector('.p_counter_value');
        const inputElement = counterWrapper.querySelector('input[type="hidden"]');
        const totalElement = document.querySelector(`.${type}_total`);

        let currentValue = parseInt(valueElement.textContent, 10);

        if (operation === 'plus' && currentValue < maxValue) currentValue++;
        if (operation === 'minus') {
            if (type === 'adults' && currentValue > 1) currentValue--;
            if (type === 'kids' && currentValue > 0) currentValue--;
        }

        valueElement.textContent = currentValue;
        inputElement.value = currentValue;
        if (totalElement) totalElement.textContent = currentValue;
    }

    function toggleSubmenu(element) {
        const submenu = element.nextElementSibling;
        if (submenu && submenu.classList.contains('filter_submenu')) {
            submenu.classList.toggle('active');
            element.classList.toggle('active');
        }
    }

    function toggleFilterCalendar() {
        const dateInput = document.getElementById('filter_date_input');
        if (dateInput && dateInput._flatpickr) {
            dateInput._flatpickr.open();
            return;
        }
        if (dateInput) dateInput.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        // 1) сначала ставим дефолт, пока Select2 ещё не вмешался
        forceDefaultIfNoParams();

        // 2) инициализируем Select2 (если ещё не был инициализирован в footer_scripts)
        initSelect2IfNeeded();

        // 3) ещё раз — после инициализации (перебиваем “автовыбор первого города”)
        setTimeout(forceDefaultIfNoParams, 0);
        setTimeout(forceDefaultIfNoParams, 200);
        setTimeout(forceDefaultIfNoParams, 600);

        // Редирект если города не выбраны
        document.querySelectorAll('.main_filter').forEach((form) => {
            if (form.dataset.emptyRedirectBound === '1') return;
            form.dataset.emptyRedirectBound = '1';

            form.addEventListener('submit', function (event) {
                const depSel = form.querySelector('#filter_departure');
                const arrSel = form.querySelector('#filter_arrival');
                if (!depSel || !arrSel) return;

                if (!depSel.value || !arrSel.value) {
                    event.preventDefault();
                    const resetUrl = form.dataset.resetUrl || '{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}';
                    window.location.href = resetUrl;
                }
            });
        });

        // закрытие пассажиров по клику вне
        document.addEventListener('click', function (event) {
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
    });

    // bfcache: возврат назад/вперёд
    window.addEventListener('pageshow', function () {
        forceDefaultIfNoParams();
    });
</script>
@endpush
