{{-- Блок промокода --}}
<div class="customer_promocode shadow_block">
    <div class="customer_promocode_header">
        <div class="block_title h2_title flex_ac customer_promocode_block_title">
            @lang('dictionary.MSG_MSG_BOOKING_PROMOKOD')
            <span class="customer_promocode_clarification par">
                @lang('dictionary.MSG_MSG_BOOKING_OPCIONALINO')
            </span>
        </div>
        <button class="close_customer_promocode" onclick="togglePromocodeBlock()">
            <img src="{{ asset('images/legacy/common/close.svg') }}" alt="close">
        </button>
    </div>
    <div class="row">
        <input type="text" 
               class="c_input par" 
               placeholder="@lang('dictionary.MSG_MSG_BOOKING_PROMOKOD')"
               id="promocode">
    </div>
</div>
