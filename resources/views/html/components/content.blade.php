<section class="ticketing">
    <div class="tickets-line-left">
        <img src="<?= asset('images/legacy/tickets-line-left.png'); ?>" alt="tickets-line-left">
    </div>
    <div class="tickets-pin-bus-left">
        <img src="<?= asset('images/legacy/tickets-pin-bus.png'); ?>" alt="tickets-pin-bus">
    </div>
    <div class="tickets-line-right">
        <img src="<?= asset('images/legacy/tickets-line-right.png'); ?>" alt="tickets-line-left">
    </div>
    <div class="tickets-pin-bus-right">
        <img src="<?= asset('images/legacy/tickets-pin-bus.png'); ?>" alt="tickets-pin-bus">
    </div>
    <div class="tickets-mob-line">
        <img src="<?= asset('images/legacy/tickets-mob-line.png'); ?>" alt="tickets-mob-line">
    </div>
    <div class="nav-steps-ticketing">
        <div class="nav-steps-size">
            <button class="step-btn-ticketing" data-step="1">1. Выбор билета</button>
            <div class="separator"></div>
            <button class="step-btn-ticketing" data-step="2">2. Бронирование билета</button>
            <div class="separator"></div>
            <button class="step-btn-ticketing" data-step="3">3. Оплата</button>
        </div>
    </div>
    <div class="page_content_wrapper step-ticketing active" id="step-ticketing1">
            
            <div class="container">
                                <div class="ticket_page_title h2_title reccomend_title">Ближайшие доступные даты для вашего маршрута.</div>
                <div class="recommend_dates">
                                    </div>
                                <div class="ticket_page_subtitle par">Отправление и прибытие указаны по местному времени.</div>
                <div class="ticket_page_title h2_title">
                    Расписание автобусов                    Балчик - Баштанка на 10 Июля                </div>
                <div class="sort_block hidden-xl hidden-lg hidden-md hidden-sm hidden-xs">
                    <div class="sort_block_tile h3_title">Сортировать</div>
                    <div class="sort_options flex_ac">
                        <button class="sort_option active h5_title flex_ac desc" data-sort="1" data-sort-direction="1" onclick="changeSort(this)">
                            Цена                        </button>
                        <button class="sort_option h5_title flex_ac desc" data-sort="2" data-sort-direction="1" onclick="changeSort(this)">
                            Время отправления                        </button>
                        <button class="sort_option h5_title flex_ac desc" data-sort="3" data-sort-direction="1" onclick="changeSort(this)">
                            Время прибытия                        </button>
                        <!--<button class="sort_option h5_title flex_ac desc" data-sort="4" data-sort-direction="1"
                                onclick="changeSort(this)">
                                                    </button>-->
                    </div>
                </div>
                <div class="mobile_sort_filter hidden-xxl flex_ac">
                    <select class="sort_select flex_ac" style="display: none;">
                        <option value="" hidden="" selected="" disabled="">сортировать по</option>
                        <option value="1">Цена</option>
                        <option value="2">Время отправления</option>
                        <option value="3">Время прибытия</option>
                        <option value="4">Популярность</option>
                    </select><div class="nice-select sort_select flex_ac" tabindex="0"><span class="current">сортировать по</span><ul class="list"><li data-value="" class="option selected disabled">сортировать по</li><li data-value="1" class="option">Цена</li><li data-value="2" class="option">Время отправления</li><li data-value="3" class="option">Время прибытия</li><li data-value="4" class="option">Популярность</li></ul></div>
                    <button class="mobile_filter_btn" onclick="toggleMobileFilter()">
                        <img src="https://new.maxtransltd.com/images/legacy/common/filter.svg" alt="filter">
                    </button>
                </div>
            </div>
            <div class="catalog_filter_overlay overlay hidden-xxl" onclick="toggleMobileFilter()"></div>
            <div class="tickets_catalog_wrapper">
                <div class="container">
                    <div class="tickets_catalog">
                        <!--<div class="catalog_filter">
                            <button class="close_filter hidden-xxl" onclick="toggleMobileFilter()">
                                <img src="" alt="arrow left">
                            </button>
                            <div class="catalog_filter_title h3_title">
                                                            </div>
                            <div class="catalog_filters">
                                                                    <button class="catalog_filter_reset_btn h5_title flex_ac">
                                        <img src="" alt="refresh">
                                                                            </button>
                                    <div class="selected_catalog_filters">
                                        <div class="selected_catalog_filter">
                                        <span class="par">
                                                                                    </span>
                                            <button class="remove_selected_filter">
                                                <img src="" alt="remove filter">
                                            </button>
                                        </div>
                                        <div class="selected_catalog_filter">
                                        <span class="par">
                                                                                    </span>
                                            <button class="remove_selected_filter">
                                                <img src="" alt="remove filter">
                                            </button>
                                        </div>
                                        <div class="selected_catalog_filter">
                                        <span class="par">
                                            Wi-Fi
                                        </span>
                                            <button class="remove_selected_filter">
                                                <img src="" alt="remove filter">
                                            </button>
                                        </div>
                                        <div class="selected_catalog_filter">
                                        <span class="par">
                                            Кава
                                        </span>
                                            <button class="remove_selected_filter">
                                                <img src="" alt="remove filter">
                                            </button>
                                        </div>
                                    </div>
                                                                <div class="ride_options">
                                    <label class="c_radio_wrapper flex_ac">
                                        <input type="radio" hidden class="c_radio_checker filter_option stops_option" value="0" name="ride_option" checked>
                                        <span class="c_radio"></span>
                                        <span class="c_radio_title par"></span>
                                    </label>
                                    <label class="c_radio_wrapper flex_ac">
                                        <input type="radio" hidden class="c_radio_checker filter_option stops_option" value="1" name="ride_option">
                                        <span class="c_radio"></span>
                                        <span class="c_radio_title par"></span>
                                    </label>
                                    <label class="c_radio_wrapper flex_ac">
                                        <input type="radio" hidden class="c_radio_checker filter_option stops_option" value="2" name="ride_option">
                                        <span class="c_radio"></span>
                                        <span class="c_radio_title par"></span>
                                    </label>
                                </div>
                                <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title active"
                                             onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_char_param">
                                                <div class="ranger_wrapper">
                                                    <div id="price_range" class="value_ranger"></div>
                                                </div>
                                                <div class="price_range_minmax_values flex_ac">
                                                    <div class="price_range_minmax_value btn_txt">
                                                        <span class="filter_price_min"></span> ₴
                                                    </div>
                                                    <div class="price_range_minmax_value btn_txt">
                                                        <span class="filter_price_max"></span> ₴
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                                                <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title" onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_checkbox_params">
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option departure_time_option" value="1" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option departure_time_option" value="2" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option departure_time_option" value="3" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option departure_time_option" value="4" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option departure_time_option" value="5" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                                                 <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title" onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_checkbox_params">
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option arrival_time_option" value="1" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option arrival_time_option" value="2" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option arrival_time_option" value="3" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option arrival_time_option" value="4" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                                <label class="c_checkbox_wrapper flex_ac">
                                                    <input type="checkbox" class="c_checkbox_checker filter_option arrival_time_option" value="5" hidden>
                                                    <span class="c_checkbox"></span>
                                                    <span class="c_checkbox_title par"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                 <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title" onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_checkbox_params">
                                                                                                    <label class="c_checkbox_wrapper flex_ac">
                                                        <input type="checkbox" class="c_checkbox_checker departure_station_checker filter_option" hidden value="">
                                                        <span class="c_checkbox"></span>
                                                        <span class="c_checkbox_title par">
                                                                                                            </span>
                                                    </label>
                                                                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title" onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_checkbox_params">
                                                                                                    <label class="c_checkbox_wrapper flex_ac">
                                                        <input type="checkbox" class="c_checkbox_checker arrival_station_checker filter_option" hidden value="">
                                                        <span class="c_checkbox"></span>
                                                        <span class="c_checkbox_title par">
                                                                                                            </span>
                                                    </label>
                                                                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="filter_chars_block_wrapper">
                                    <div class="filter_chars_block">
                                        <div class="filter_chars_title h4_title" onclick="toggleFilterParams(this)">
                                                                                        <img src="" alt="arrow down">
                                        </div>
                                        <div class="filter_char_params">
                                            <div class="filter_checkbox_params">
                                                                                                    <label class="c_checkbox_wrapper flex_ac">
                                                        <input type="checkbox" class="c_checkbox_checker filter_option bus_options_checker" hidden value="">
                                                        <span class="c_checkbox"></span>
                                                        <span class="c_checkbox_title par">
                                                                                                                    </span>
                                                    </label>
                                                                                            </div>
                                        </div>
                                    </div>
                                </div>

                                                                    <button class="catalog_filter_reset_btn h5_title flex_ac">
                                        <img src="" alt="refresh">
                                                                            </button>
                                
                            </div>
                        </div>-->
                        <div class="catalog_elements">
                            <div class="catalog_elements_title h3_title">
                                Найдено 0 автобусов</div>
                            <div class="catalog_elements_subtitle par">Время отправления и прибытия указано по местному времени.</div>

                            <div class="ticket_cards_wrapper">
                                                            </div>
                            <div class="pagination_wrapper">
                                                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div class="step-ticketing" id="step-ticketing2">
        <div class="ticket-form-container">
            <h2 class="ticket-form-title">Оформление билета</h2>
            <p class="ticket-form-description">Укажите данные необходимые для осуществления бронирования.</p>

            <form id="ticketForm">
                <div class="ticket-form-passenger" id="ticket-form-passenger-1">
                    <div class="tickets-count-block">
                        <h3 class="ticket-form-subtitle">Контактные данные пассажира №1</h3>
                    </div>
                    <div class="ticket-form-input-space">
                        <input type="text" name="lastname[]" placeholder="Фамилия" required class="ticket-form-input" />
                        <input type="text" name="firstname[]" placeholder="Имя" required class="ticket-form-input" />
                    </div>
                </div>

                <div id="ticket-form-passenger-list"></div>
                
                <div class="ticket-form-button">
                    <button type="button" class="ticket-form-add-btn" id="add-ticket-passenger-btn"><img src="<?= asset('images/legacy/add-passager.png'); ?>" alt="add-passager">Добавить пассажира *</button>
                </div>

                <p class="ticket-form-note">В автобусе свободная рассадка <span style="color:red">*</span></p>
            </form>
        </div>

        <div class="ticket-contact-form">
            <h2 class="ticket-form-title">Контактная информация</h2>
            <p class="ticket-form-description">Введите корректные e-mail и номер телефона, чтобы получить билет.</p>
            <div class="ticket-form-input-space">
                <div class="contact-input">
                    <input type="email" name="email[]" placeholder="E-mail" required class="ticket-contact-input" />
                    <div class="ticket-contact-note">
                        <div class="ticket-contact-img">
                            <img src="<?= asset('images/legacy/ticket-ticket.png'); ?>" alt="add-passager">
                        </div>
                        <p>Отправим билет <span style="color:red">*</span></p>
                    </div>
                </div>
                <div class="contact-input">
                    <div class="ticket-phone-wrapper">
                        <select class="ticket-phone-country" name="country">
                            <option value="+38">UA</option>
                            <option value="+7">CZ</option>
                            <option value="+1">PL</option>
                            <option value="+48">GR</option>
                        </select>
                        <input type="tel" name="tel[]" placeholder="(123) 586 96 85" required class="ticket-phone-input" />
                    </div>
                    <div class="ticket-contact-note">
                        <div>
                            <img src="<?= asset('images/legacy/ticket-bell.png'); ?>" alt="add-passager">
                        </div>
                        <p>Сообщим об изменениях <span style="color:red">*</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="ticket-trip-form">
            <h2 class="ticket-form-title">Маршрут</h2>
            <div class="ticket-trip-schedule">
                <div class="trip-block">
                    <p class="trip-time-from">21:45</p>
                    <div>
                        <img src="<?= asset('images/legacy/line-trip.png'); ?>" alt="line-trip">
                    </div>
                    <div>
                        <img src="<?= asset('images/legacy/left-trip-pic.png'); ?>" alt="left-trip" class="fit-img-tickets">
                    </div>
                    <p class="trip-city-text">Одесса-Главная</p>
                </div>
                <div class="trip-center-arrow">
                    <p>Время в пути  8 ч. 48 мин.</p>
                    <div class="trip-arrow">
                        <img src="<?= asset('images/legacy/trip-arrow.png'); ?>" alt="trip-arrow" class="fit_img">
                        <div class="tickets-arrow-arrow">
                            <img src="<?= asset('images/legacy/tickets-line-arrow-arrow.png'); ?>" alt="arrow">
                        </div>
                    </div>
                </div>
                <div class="trip-block">
                    <p class="trip-time-to">21:45</p>
                    <div>
                        <img src="<?= asset('images/legacy/line-trip.png'); ?>" alt="line-trip">
                    </div>
                    <div>
                        <img src="<?= asset('images/legacy/right-trip-pic.png'); ?>" alt="right-trip" class="fit-img-tickets">
                    </div>
                    <p class="trip-city-text">Одесса-Главная</p>
                </div>
            </div>
            <div class="trip-calendar">
                <div>
                    <p>Когда: <span id="ticket-selected-date">не выбрано</span></p>
                    <p>Пассажиров: <span id="passenger-count">1</p>
                </div>
                <div>
                    <button class="ticket-calendar-button" id="ticket-datepicker-button" type="button">
                        <img src="<?= asset('images/legacy/tickets-calendar.png'); ?>" alt="Открыть календарь" class="fit_img">
                    </button>
                </div>
            </div>
            <div id="ticket-calendar-container" style="display: none;"></div>
            <div class="trip-price">
                <div class="trip-price-strings">
                    <p class="trip-price-left">Цена: </p>
                    <p class="trip-price-right">1800 грн</p>
                </div>
                <div class="trip-price-strings">
                    <p class="trip-price-left">К оплате: </p>
                    <p class="trip-price-right">3600 грн</p>
                </div>
            </div>
            <div class="trip-btn-block">
                <button class="trip-price-btn" data-step="3">Перейти к оплате</button>
            </div>
        </div>
    </div>

    <div class="step-ticketing" id="step-ticketing3">
        <div class="ticket-payment-container">
            <h2 class="ticket-form-title">Оплата</h2>
            <p class="ticket-form-description">Ваши платежные и личные данные надежно защищены в соответствии 
            с международными стандартами безопасности.</p>
            <div class="payment-option-container">
                <div><img src="<?= asset('images/legacy/bank-card.png'); ?>" class="payment-checker__icon_sm" alt="bank-card"></div>
                <label class="payment-checker payment-checker--card">
                    <input type="radio" name="payment" class="payment-checker__input" checked>
                    <span class="payment-checker__circle"></span>
                    <span class="payment-checker__text">
                        <span class="payment-checker__label">Банковской картой</span>
                        <img src="<?= asset('images/legacy/bank-card.png'); ?>" class="payment-checker__icon" alt="card">
                        <span class="payment-checker__price">12649 грн</span>
                    </span>
                </label>

                <div><img src="<?= asset('images/legacy/apple-pay.png'); ?>" class="payment-checker__icon_sm" alt="applepay"></div>
                <label class="payment-checker payment-checker--apple">
                    <input type="radio" name="payment" class="payment-checker__input">
                    <span class="payment-checker__circle"></span>
                    <span class="payment-checker__text">
                        <span class="payment-checker__label">ApplePay</span>
                        <img src="<?= asset('images/legacy/apple-pay.png'); ?>" class="payment-checker__icon" alt="applepay">
                        <span class="payment-checker__price">12649 грн</span>
                    </span>
                </label>
            </div>
            <div class="bank-img-cards">
                <img src="<?= asset('images/legacy/cards-pay.png'); ?>" alt="right-trip" class="fit_img">
            </div>
            <div class="custom-checkbox-wrapper">
                <label class="custom-checkbox custom-checkbox--offer">
                    <input type="checkbox" class="custom-checkbox__input" checked>
                    <span class="custom-checkbox__box"></span>
                    <span class="custom-checkbox__text">
                        <strong>Я принимаю условия</strong> публичной оферты, политика конфиденциальности и возврат
                    </span>
                </label>
                <label class="custom-checkbox custom-checkbox--consent">
                    <input type="checkbox" class="custom-checkbox__input">
                    <span class="custom-checkbox__box"></span>
                    <span class="custom-checkbox__text"><strong>Я даю согласие на обработку персональных данных</strong></span>
                </label>
            </div>
            <div class="trip-btn-block">
                <button class="trip-price-btn">Оплатить</button>
            </div>
        </div>
    </div>
    <div class="tickets-bus-img">
        <img src="<?= asset('images/legacy/bus-tickets.png'); ?>" alt="bus-tickets" class="fit_img">
    </div>
    <div id="calendar-modal" class="modal">
        <div class="modal-content">
            <p class="calendar-header">@lang('calendar_desc')</p>
            <div id="calendar" class="calendar"></div>
            <div class="modal-buttons">
                <button id="save-btn" class="calendar_btn">@lang('save')</button>
                <button id="cancel-btn" class="calendar_btn_cancel">@lang('cancel')</button>
            </div>
        </div>
    </div>
</section>

