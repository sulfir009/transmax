{{-- Контактная информация (V2 под дизайн как на фото) --}}
<div class="customer_contact_data shadow_block">
    <div class="block_title">
        Контактная информация
    </div>

    <div class="par">
        Введите корректные e-mail и номер телефона, чтобы получить билет.
    </div>

    <div class="b2_grid" style="margin-top:10px;">
        <div>
            <input type="text"
                   class="c_input"
                   placeholder="E-mail"
                   id="email"
                   value="{{ $clientInfo->email ?? '' }}"
                   pattern="[^\u0400-\u04FF]*"
                   maxlength="255"
                   oninput="this.value = this.value.replace(/[^\x00-\x7F]/g, ''); validateEmail(this)">

            <span id="email-error" style="display:none; color:red; font-size:12px;">
                @lang('dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO')
            </span>
        </div>

        <div class="phone_input_wrapper flex_ac">
            <select class="phone_country_code flex_ac" onchange="changeInputMask(this)">
                @foreach ($phoneCodes as $phoneCode)
                    <option value="{{ $phoneCode->id }}"
                            data-mask="{{ $phoneCode->phone_mask }}"
                            data-placeholder="{{ $phoneCode->phone_example }}"
                            @if (($clientInfo->phone_code ?? 0) == $phoneCode->id) selected @endif>
                        {{ $phoneCode->phone_country }}
                    </option>
                @endforeach
            </select>

            <input type="text"
                   class="customer_phone_input req_input"
                   placeholder="{{ $firstPhoneExample }}"
                   id="phone"
                   value="{{ $clientInfo->phone ?? '' }}">
        </div>
    </div>

{{-- Легенда как на фото --}}
<div class="b2_legend">
    <div class="b2_legend_item b2_legend_item--ticket">
        <span class="b2_legend_dot b2_legend_dot--blue"></span>
        Отправим билет<span class="b2_req">*</span>
    </div>

    <div class="b2_legend_item b2_legend_item--changes">
        <span class="b2_legend_dot b2_legend_dot--orange"></span>
        Сообщим об изменениях<span class="b2_req">*</span>
    </div>
</div>
<style>
    
    .booking_v2 .b2_legend{
  margin-top: 10px;
  display:flex;
  flex-direction: column;     /* как на фото */
  gap: 6px;
  font-size: 10px;
  font-weight: 700;
  color:#6E7172;
}
.booking_v2 .b2_legend_item{
  display:flex;
  align-items:center;
  gap: 6px;
}
.booking_v2 .b2_legend_dot{
  width:10px;
  height:10px;
  border-radius: 999px;
  display:inline-block;
}
.booking_v2 .b2_legend_dot--blue{ background:#35BAF0; }
.booking_v2 .b2_legend_dot--orange{ background:#F2994A; }

</style>

</div>
