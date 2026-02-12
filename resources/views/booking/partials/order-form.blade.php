{{-- Форма оформления билета --}}
@php
    // Нормализуем количество пассажиров (минимум 1)
    $passengersCount = max(1, (int)($passengers ?? 1));
@endphp

<div class="ticket_order_block shadow_block">
    <div class="block_title h2_title">
        @lang('dictionary.MSG_MSG_BOOKING_OFORMLENNYA_KVITKA')
    </div>

    <div class="ticket_order_block_subtitle par">
        @lang('dictionary.MSG_MSG_BOOKING_ZAZNACHENI_DANI_NEOBHIDNI_DLYA_ZDIJSNENNYA_BRONYUVANNYA_I_BUDUTI_PEREVIRENI_PID_CHAS_POSADKI_V_AVTOBUS')
    </div>

    {{-- Обертка всех пассажиров. data-max-passengers используется JS для лимитов --}}
    <div class="customer_data" id="b2_passengers_wrap" data-max-passengers="{{ $passengersCount }}">

        {{-- Пассажир №1 (всегда видимый, основной покупатель) --}}
        <div class="b2_passenger_title">
            @lang('dictionary.BOOKING_PASSENGER_CONTACT_DATA') №1
        </div>

        <div class="b2_grid">
            <div class="row">
                <input
                    type="text"
                    class="c_input par req_input"
                    data-passengers-family-name
                    placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')"
                    id="family_name"
                    name="family_name"
                    value="{{ $clientInfo['second_name'] ?? '' }}"
                >
            </div>

            <div class="row">
                <input
                    type="text"
                    class="c_input par req_input"
                    data-passengers-family-name
                    placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')"
                    id="name"
                    name="name"
                    value="{{ $clientInfo['name'] ?? '' }}"
                >
            </div>
        </div>

        <div class="b2_free_seat">
            @lang('dictionary.MSG_MSG_BOOKING_VILINA_ROZSADKA') <span class="b2_req">*</span>
        </div>

        <div class="b2_child_notice par">
            @lang('dictionary.BOOKING_CHILDREN_UNDER_THREE_NOTICE')
        </div>

        {{-- Остальные пассажиры (генерируются в DOM, но скрыты display:none, если их нет в выборе) --}}
        {{-- Цикл начинаем с 1, так как 0 (первый) уже выведен выше --}}
        @for ($i = 1; $i < 10; $i++) {{-- Генерируем с запасом или используем $passengersCount, если лимит жесткий --}}
<div class="b2_passenger_wrap js_passenger_block {{ ($i < $passengersCount) ? '' : 'is_hidden' }}"
     data-passenger-index="{{ $i }}">


                <div class="b2_passenger_title">
                    @lang('dictionary.BOOKING_PASSENGER_CONTACT_DATA') №{{ $i + 1 }}

                    {{-- Кнопка удаления пассажира --}}
                    <button
                        type="button"
                        class="b2_remove_dot js_remove_passenger"
                        data-passenger-index="{{ $i }}"
                        title="@lang('dictionary.BOOKING_REMOVE_PASSENGER')"
                        aria-label="@lang('dictionary.BOOKING_REMOVE_PASSENGER') №{{ $i + 1 }}"
                    ></button>
                </div>

                <div class="b2_grid">
                    <div class="row">
                        <input
                            type="text"
                            class="c_input par req_input"
                            placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')"
                            name="passengers[{{ $i }}][family_name]"
                            value=""
                        >
                    </div>

                    <div class="row">
                        <input
                            type="text"
                            class="c_input par req_input"
                            placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')"
                            name="passengers[{{ $i }}][name]"
                            value=""
                        >
                    </div>
                    
                    {{-- Скрытые поля для совместимости, если нужны --}}
                    <input type="hidden" name="passengers[{{ $i }}][patronymic]" value="">
                    <input type="hidden" name="passengers[{{ $i }}][birthdate]" value="">
                </div>
            </div>
        @endfor

        {{-- Кнопка “Добавить пассажира” --}}
        <div class="b2_add_row" id="b2_add_row" style="{{ ($passengersCount >= 10) ? 'display:none;' : '' }}">
            <button type="button" class="b2_add_btn" id="b2_add_passenger_btn">+</button>

            <button type="button" class="b2_add_text_btn" id="b2_add_passenger_text">
                @lang('dictionary.BOOKING_ADD_PASSENGER')<span class="b2_req">*</span>
            </button>
        </div>

    </div>
</div>

{{-- ВСТАВИТЬ В КОНЕЦ ФАЙЛА ВМЕСТО СТАРОГО СКРИПТА --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. ЛОГИКА УДАЛЕНИЯ
    $(document).on('click', '.js_remove_passenger', function() {
        var $block = $(this).closest('.js_passenger_block');
        
        // А. Очищаем значения (визуально и физически)
        $block.find('input').val('');

        // Б. УДАЛЯЕМ атрибут name. 
        // Поле без name не отправится на сервер ни в каком виде.
        $block.find('input').each(function() {
            var $input = $(this);
            // Сохраняем имя в запасной атрибут, чтобы можно было восстановить
            if ($input.attr('name')) {
                $input.attr('data-temp-name', $input.attr('name'));
                $input.removeAttr('name');
            }
            // Для надежности отключаем
            $input.prop('disabled', true);
        });

        // В. Скрываем блок
        $block.addClass('is_hidden').hide();
        
        console.log('Пассажир удален. Атрибуты name удалены.');
    });

    // 2. ЛОГИКА ВОССТАНОВЛЕНИЯ (Кнопка "Добавить")
    $('#b2_add_passenger_btn, #b2_add_passenger_text').on('click', function() {
        // Ищем первый скрытый блок
        var $hiddenBlock = $('.js_passenger_block.is_hidden').first();
        
        if ($hiddenBlock.length) {
            // Восстанавливаем атрибут name из запаса
            $hiddenBlock.find('input').each(function() {
                var $input = $(this);
                if ($input.attr('data-temp-name')) {
                    $input.attr('name', $input.attr('data-temp-name'));
                }
                $input.prop('disabled', false);
            });
            
            // Показываем блок
            $hiddenBlock.removeClass('is_hidden').show();
        }
    });
});
</script>
