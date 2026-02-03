{{-- Контактная информация --}}
<div class="customer_contact_data shadow_block">
    <div class="block_title h2_title">
        @lang('dictionary.MSG_MSG_BOOKING_KONTAKTNA_INFORMACIYA')
    </div>
    <div class="par">
        @lang('dictionary.MSG_MSG_BOOKING_VKAZUJTE_KOREKTNI_E-MAIL')
    </div>

    <div class="customer_data">
        <div class="flex-row gap-y-26 gap-x-30">
            <div class="col-lg-6 col-xs-12">
                <div class="row">
                    <input type="text"
                           class="c_input par"
                           placeholder="@lang('dictionary.MSG_MSG_BOOKING_E-MAIL')"
                           id="email"
                           value="{{ $clientInfo->email ?? '' }}"
                           pattern="[^\u0400-\u04FF]*"
                           maxlength="255"
                           oninput="this.value = this.value.replace(/[^\x00-\x7F]/g, ''); validateEmail(this)">
                    <span id="email-error" style="display: none; color: red;">
                        @lang('dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO')
                    </span>
                </div>
            </div>
            <div class="col-lg-6 col-xs-12">
                <div class="phone_input_wrapper flex_ac">
                    <select class="phone_country_code flex_ac" onchange="changeInputMask(this)">
                        @foreach ($phoneCodes as $phoneCode)
                            <option value="{{ $phoneCode->id }}"
                                    data-mask="{{ $phoneCode->phone_mask }}"F
                                    data-placeholder="{{ $phoneCode->phone_example }}"
                                    @if (($clientInfo->phone_code ?? 0) == $phoneCode->id) selected @endif>
                                {{ $phoneCode->phone_country }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text"
                           class="customer_phone_input inter req_input"
                           placeholder="{{ $firstPhoneExample }}"
                           id="phone"
                           value="{{ $clientInfo->phone ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="customer_contact_data_bottom flex_ac">
        <label class="c_checkbox_wrapper flex_ac">
            <input type="checkbox" class="c_checkbox_checker" hidden>
            <span class="c_checkbox"></span>
            <span class="c_checkbox_title par">
                @lang('dictionary.MSG_MSG_BOOKING_NADSILAJTE_MENI_ZNIZHKI_TA_IDE_BYUDZHETNIH_PODOROZHEJ')
            </span>
        </label>
        <button class="have_promocode_btn par flex_ac" onclick="togglePromocodeBlock()">
            @lang('dictionary.MSG_MSG_BOOKING_U_MENE__PROMOKOD')
            <img src="{{ asset('images/legacy/common/blue_arrow_down.svg') }}" alt="arrow down">
        </button>
    </div>
</div>
