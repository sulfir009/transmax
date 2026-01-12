{{-- Блок оплаты --}}
<div class="for_payment shadow_block">
    <div class="for_payment_title h2_title flex_ac">
        @lang('dictionary.MSG_MSG_BOOKING_DO_SPLATI')
        <span class="total_price h2_title">
            {{ $totalPrice }} @lang('dictionary.MSG_MSG_BOOKING_GRN')
        </span>
    </div>
    <div class="for_payment_subtitle par">
        @lang('dictionary.MSG_MSG_BOOKING_VASHI_PLATIZHNI_')
    </div>
    
    <div class="for_payment_paymethod_logos flex_ac">
        <div class="row">
            <img src="{{ asset('images/legacy/common/maestro.svg') }}" alt="maestro" class="fit_img">
        </div>
        <div class="row">
            <img src="{{ asset('images/legacy/common/mastercard.svg') }}" alt="mastercard" class="fit_img">
        </div>
        <div class="row">
            <img src="{{ asset('images/legacy/common/visa.svg') }}" alt="visa" class="fit_img">
        </div>
    </div>
    
    <div class="for_payment_accept">
        <label class="c_checkbox_wrapper flex_ac">
            <input type="checkbox" class="c_checkbox_checker" hidden id="terms_accept" checked>
            <span class="c_checkbox"></span>
            <span class="c_checkbox_title par">
                @lang('dictionary.MSG_MSG_BOOKING_YA_PRIJMAYU_UMOVI') 
                <a href="{{ $Router->writelink(84) }}" class="small_link">
                    @lang('dictionary.MSG_MSG_BOOKING_PUBLICHNO_OFERTI')
                </a>, 
                <a href="{{ $Router->writelink(83) }}" class="small_link">
                    @lang('dictionary.MSG_MSG_BOOKING_POLITIKI_KONFIDENCIJNOSTI')
                </a> 
                @lang('dictionary.MSG_MSG_BOOKING_I') 
                <a href="{{ $Router->writelink(87) }}" class="small_link">
                    @lang('dictionary.MSG_MSG_BOOKING_POVERNENNYA')
                </a>
            </span>
        </label>
        
        <label class="c_checkbox_wrapper flex_ac">
            <input type="checkbox" class="c_checkbox_checker req_check" hidden id="personal_data_process" checked>
            <span class="c_checkbox"></span>
            <span class="c_checkbox_title par">
                @lang('dictionary.MSG_MSG_BOOKING_YA_DAYU_ZGODU_NA_OBROBKU_PERSONALINIH_DANIH')
            </span>
        </label>
        
        <label class="c_checkbox_wrapper flex_ac">
            <input type="checkbox" class="c_checkbox_checker" hidden id="save_my_data">
            <span class="c_checkbox"></span>
            <span class="c_checkbox_title par">
                @lang('dictionary.MSG_MSG_BOOKING_ZBEREGTI_DANI')
            </span>
        </label>
    </div>
</div>
