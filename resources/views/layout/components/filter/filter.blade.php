<form class="main_filter"
      autocomplete="off"
      method="GET"
      action="{{ $formAction ?? \App\Helpers\LocaleHelper::localizedRoute('tickets.index') }}"
      data-reset-url="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}">

    <div class="flex-row gap-8">

        {{-- Откуда --}}
        <div class="col-lg-20 col-sm-6 col-xs-12">
            <div class="filter_block_wrapper">
                <div class="filter_city_select_wrapper flex-row">
                    <div class="filter_block_title city_select_title par">
                        @lang('dictionary.MSG_ALL_ZVIDKI')
                    </div>

                    <select class="filter_city_select"
                            id="filter_departure"
                            name="from"
                            data-initial-value="{{ (int)($filterDeparture ?? 0) }}">
                        <option value="" selected>Выберите город</option>
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

                    <select class="filter_city_select"
                            id="filter_arrival"
                            name="to"
                            data-initial-value="{{ (int)($filterArrival ?? 0) }}"
                            disabled>
                        <option value="" selected>Выберите город</option>
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

                    <input type="hidden" name="kids" class="kids_passengers" value="0">
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

    function initSelect2IfNeeded() {
        if (!hasSelect2()) return;

        jQuery('.filter_city_select').each(function () {
            const $el = jQuery(this);
            if ($el.hasClass('select2-hidden-accessible')) return;

            $el.select2({ width: '100%' });
        });
    }

    function setSelectValue(selectEl, value) {
        if (!selectEl) return;

        selectEl.value = value;

        if (hasSelect2()) {
            jQuery(selectEl).val(value).trigger('change.select2');
        }
    }

    /**
     * ✅ КЛЮЧЕВОЕ ИСПРАВЛЕНИЕ:
     * Возвращаем НОРМАЛЬНЫЙ Promise, а не jqXHR.
     * Тогда .then/.catch/.finally работают при ЛЮБОЙ версии jQuery.
     */
    function requestSaleCities(payload) {
        if (!window.jQuery) {
            return Promise.reject(new Error('jQuery is required'));
        }

        const apiUrl = '{{ rtrim(url('/api'), '/') }}';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: 'POST',
                url: apiUrl,
                data: payload,
                dataType: 'json',
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                headers: { 'X-CSRF-TOKEN': csrf },
                timeout: 20000,
                success: function (data) { resolve(data); },
                error: function (xhr, status, error) {
                    reject({ xhr: xhr, status: status, error: error });
                }
            });
        });
    }

    function normalizeArrayResponse(response) {
        if (Array.isArray(response)) return response;

        // На всякий случай: если сервер вдруг отдаст строку
        if (typeof response === 'string') {
            try {
                const parsed = JSON.parse(response);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        return [];
    }

    function fillSelectOptions(selectEl, items, selectedValue) {
        if (!selectEl) return;

        const prev = selectedValue != null ? String(selectedValue) : '';
        selectEl.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Выберите город';
        selectEl.appendChild(placeholder);

        (Array.isArray(items) ? items : []).forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.id);
            option.textContent = item.title || '';
            if (prev && option.value === prev) option.selected = true;
            selectEl.appendChild(option);
        });

        if (hasSelect2()) {
            jQuery(selectEl).trigger('change.select2');
        }
    }

    function resetArrivalSelect(disabled) {
        const arr = document.getElementById('filter_arrival');
        fillSelectOptions(arr, [], '');
        if (arr) arr.disabled = !!disabled;
    }

    function loadToCitiesForSale(fromId, selectedArrival = '') {
        const arr = document.getElementById('filter_arrival');

        if (!fromId) {
            resetArrivalSelect(true);
            return Promise.resolve([]);
        }

        if (arr) arr.disabled = true;

        return requestSaleCities({
            request: 'getToCitiesForSale',
            lang: '{{ app()->getLocale() }}',
            from_id: fromId
        }).then((response) => {
            const items = normalizeArrayResponse(response);
            fillSelectOptions(arr, items, selectedArrival);
            if (arr) arr.disabled = false;
            return items;
        }).catch(() => {
            // если запрос упал — хотя бы не блокируем "Куда"
            resetArrivalSelect(false);
            return [];
        });
    }


    function ensureFilterLoadingOverlay() {
        let overlay = document.getElementById('filter_loading_overlay');
        if (overlay) return overlay;

        overlay = document.createElement('div');
        overlay.id = 'filter_loading_overlay';
        overlay.innerHTML = '<div style="width:36px;height:36px;border:3px solid rgba(64,166,255,.25);border-top-color:#40a6ff;border-radius:50%;animation:filterLoaderSpin .7s linear infinite;"></div>';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(255,255,255,.65);z-index:2147483646;display:none;align-items:center;justify-content:center;pointer-events:all;';

        if (!document.getElementById('filter_loader_keyframes')) {
            const style = document.createElement('style');
            style.id = 'filter_loader_keyframes';
            style.textContent = '@keyframes filterLoaderSpin{to{transform:rotate(360deg);}}';
            document.head.appendChild(style);
        }

        document.body.appendChild(overlay);
        return overlay;
    }

    function setFilterLoadingState(isLoading) {
        const overlay = ensureFilterLoadingOverlay();
        const forms = document.querySelectorAll('.main_filter');

        forms.forEach((form) => {
            form.querySelectorAll('input, select, button').forEach((el) => {
                if (isLoading) {
                    el.dataset.prevDisabled = el.disabled ? '1' : '0';
                    el.disabled = true;
                } else if (el.dataset.prevDisabled === '0') {
                    el.disabled = false;
                    delete el.dataset.prevDisabled;
                } else if (el.dataset.prevDisabled === '1') {
                    delete el.dataset.prevDisabled;
                }
            });
        });

        overlay.style.display = isLoading ? 'flex' : 'none';
        window.__mxFilterLoading = !!isLoading;
    }

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

    function hasOption(selectEl, value) {
        if (!selectEl) return false;
        const v = String(value);

        // CSS.escape может отсутствовать в очень старых окружениях — делаем фолбэк
        const esc = (window.CSS && typeof CSS.escape === 'function')
            ? CSS.escape(v)
            : v.replace(/"/g, '\\"');

        return !!selectEl.querySelector(`option[value="${esc}"]`);
    }

    async function switchDirections() {
        if (window.__mxFilterLoading) return;
        const dep = document.getElementById('filter_departure');
        const arr = document.getElementById('filter_arrival');
        if (!dep || !arr) return;

        const currentDeparture = dep.value;
        const currentArrival = arr.value;

        if (!currentDeparture || !currentArrival) return;

        // если текущий arrival не может быть departure — не свапаем
        if (!hasOption(dep, currentArrival)) return;

        setSelectValue(dep, currentArrival);
        await loadToCitiesForSale(currentArrival, currentDeparture);

        if (window.jQuery) {
            jQuery(dep).trigger('change');
            jQuery(arr).trigger('change');
        }
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
            const isOpen = submenu.style.display === 'block';
            submenu.style.display = isOpen ? 'none' : 'block';
            element.classList.toggle('active', !isOpen);
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
        setFilterLoadingState(true);

        const depSel = document.getElementById('filter_departure');
        const arrSel = document.getElementById('filter_arrival');

        const initialDeparture = depSel?.dataset?.initialValue || '';
        const initialArrival = arrSel?.dataset?.initialValue || '';

        resetArrivalSelect(true);

        requestSaleCities({
            request: 'getFromCitiesForSale',
            lang: '{{ app()->getLocale() }}'
        }).then((response) => {
            const fromCities = normalizeArrayResponse(response);

            if (fromCities.length > 0) {
                fillSelectOptions(depSel, fromCities, initialDeparture);
                return fromCities;
            }

            // fallback: если sale-список пуст — подстрахуемся общим списком
            return requestSaleCities({
                request: 'getCities',
                lang: '{{ app()->getLocale() }}'
            }).then((fallbackResponse) => {
                const fallbackCities = normalizeArrayResponse(fallbackResponse);
                fillSelectOptions(depSel, fallbackCities, initialDeparture);
                return fallbackCities;
            });
        }).then(() => {
            const selectedDeparture = depSel?.value || '';
            if (!selectedDeparture) {
                resetArrivalSelect(true);
                return;
            }
            return loadToCitiesForSale(selectedDeparture, initialArrival);
        }).catch(() => {
            fillSelectOptions(depSel, [], '');
            resetArrivalSelect(true);
        }).finally(() => {
            initSelect2IfNeeded();
            setFilterLoadingState(false);

            // перебиваем “автовыбор первого города”
            setTimeout(forceDefaultIfNoParams, 0);
            setTimeout(forceDefaultIfNoParams, 200);
            setTimeout(forceDefaultIfNoParams, 600);
        });

        if (window.jQuery) {
            jQuery('#filter_departure').on('change', function () {
                const fromId = this.value || '';
                loadToCitiesForSale(fromId, '').then(() => {
                    jQuery('#filter_arrival').trigger('change');
                });
            });
        }

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

        document.addEventListener('click', function (event) {
            const passengersWrapper = document.querySelector('.passagers_filter_wrapper');
            if (passengersWrapper && !passengersWrapper.contains(event.target)) {
                const submenu = passengersWrapper.querySelector('.filter_submenu');
                const block = passengersWrapper.querySelector('.filter_block');
                if (submenu && submenu.style.display === 'block') {
                    submenu.style.display = 'none';
                    block.classList.remove('active');
                }
            }
        });
    });

    window.addEventListener('pageshow', function () {
        forceDefaultIfNoParams();
    });
</script>
@endpush
