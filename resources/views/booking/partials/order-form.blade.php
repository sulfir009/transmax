{{-- Форма оформления билета --}}
<div class="ticket_order_block shadow_block">
    <div class="block_title h2_title">
        @lang('dictionary.MSG_MSG_BOOKING_OFORMLENNYA_KVITKA')
    </div>
    <div class="ticket_order_block_subtitle par">
        @lang('dictionary.MSG_MSG_BOOKING_ZAZNACHENI_DANI_NEOBHIDNI_DLYA_ZDIJSNENNYA_BRONYUVANNYA_I_BUDUTI_PEREVIRENI_PID_CHAS_POSADKI_V_AVTOBUS')
    </div>
    
    <div class="customer_data">
        {{-- Данные первого пассажира --}}
        <div class="ticket_order_block_subtitle passengers_inputs">
            Контактные данные пассажира №1
        </div>
        <div class="flex-row gap-y-26 gap-x-30">
            <div class="col-sm-6 col-xs-12">
                <div class="row">
                    <input type="text" 
                           class="c_input par req_input" 
                           data-passengers-family-name 
                           placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')" 
                           id="family_name"
                           value="{{ $clientInfo['second_name'] ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6 col-xs-12">
                <div class="row">
                    <input type="text" 
                           class="c_input par req_input" 
                           placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')" 
                           id="name"
                           value="{{ $clientInfo['name'] ?? '' }}">
                </div>
            </div>
            <div class="col-lg-6 col-xs-12">
                <div class="ticket_seat par flex_ac">
                    <span>@lang('dictionary.MSG_MSG_BOOKING_MISCE_V_AVTOBUSI')</span>
                    <span>@lang('dictionary.MSG_MSG_BOOKING_VILINA_ROZSADKA')</span>
                </div>
            </div>
        </div>

        {{-- Дополнительные пассажиры --}}
        @for ($i = 1; $i < $passengers; $i++)
            <div class="ticket_order_block_subtitle passengers_inputs customer_data">
                Контактные данные пассажира №{{ $i + 1 }}
            </div>
            <div class="flex-row gap-y-26 gap-x-30">
                <div class="col-sm-6 col-xs-12">
                    <div class="row">
                        <input type="text" 
                               class="c_input par req_input" 
                               placeholder="@lang('dictionary.MSG_MSG_BOOKING_PRIZVISCHE')" 
                               name="passengers[{{ $i }}][family_name]" 
                               data-passengers-family-name 
                               value="">
                    </div>
                </div>
                <div class="col-sm-6 col-xs-12">
                    <div class="row">
                        <input type="text" 
                               class="c_input par req_input" 
                               placeholder="@lang('dictionary.MSG_MSG_BOOKING_IMYA_')" 
                               name="passengers[{{ $i }}][name]" 
                               value="">
                    </div>
                </div>
            </div>
        @endfor
    </div>
</div>
