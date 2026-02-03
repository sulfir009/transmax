@extends('layout.app')

@section('title', __('MSG_CONTACTS_TITLE'))

@section('page-styles')
    <link rel="stylesheet" href="{{ mix('css/legacy/libs/nice_select/nice-select.css') }}">
    <style>
        .contact_txt_wrapper {
            padding: 60px 0;
            background-color: #f8f9fa;
        }

        .contacts_info_blocks {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .contact_txt_title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1a1a1a;
        }

        .contact_txt {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 30px;
        }

        .contacts_booking_link {
            display: inline-flex;
            align-items: center;
            padding: 15px 40px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .contacts_booking_link:hover {
            background-color: #0052a3;
            color: white;
        }

        .contact_img img {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }

        .contact_form_wrapper {
            padding: 80px 0;
        }

        .contact_form_txt_title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a1a1a;
        }

        .contact_form_txt {
            font-size: 16px;
            color: #666;
            margin-bottom: 40px;
        }

        .contact_form .row {
            margin-bottom: 20px;
        }

        .c_input {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .c_input:focus {
            outline: none;
            border-color: #0066cc;
        }

        .c_input.error {
            border-color: #dc3545;
        }

        textarea.c_input {
            min-height: 150px;
            resize: vertical;
        }

        .phone_input_wrapper {
            display: flex;
            gap: 10px;
        }

        .phone_country_code {
            min-width: 120px;
        }

        .customer_phone_input {
            flex: 1;
        }

        .send_contact_btn {
            padding: 15px 50px;
            background-color: #0066cc;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .send_contact_btn:hover {
            background-color: #0052a3;
        }

        .contacts_map_title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #1a1a1a;
        }

        .contact_row {
            margin-bottom: 20px;
            font-size: 16px;
            color: #333;
        }

        .contact_row a {
            color: #0066cc;
            text-decoration: none;
        }

        .contact_row a:hover {
            text-decoration: underline;
        }

        .contacts_messagers {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .m_link {
            display: flex;
            width: 40px;
            height: 40px;
            transition: transform 0.3s;
        }

        .m_link:hover {
            transform: scale(1.1);
        }

        .contact_map {
            margin-top: 40px;
            border-radius: 12px;
            overflow: hidden;
            height: 400px;
        }

        .contact_map iframe {
            width: 100%;
            height: 100%;
        }

        @media (max-width: 1200px) {
            .contacts_info_blocks {
                flex-direction: column;
            }
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            {{-- Блок с информацией о контактах --}}
            <div class="contact_txt_wrapper">
                <div class="container">
                    <div class="flex-row gap-30 contacts_info_blocks">
                        <div class="col-xl-6">
                            <div class="contact_txt_info">
                                <div class="contact_txt_title h2_title">
                                    {{ $contactInfo['title'] ?? '' }}
                                </div>
                                <div class="contact_txt par">
                                    {!! $contactInfo['text'] ?? '' !!}
                                </div>
                                <a href="{{ route('tickets.index') }}" class="contacts_booking_link h4_title flex_ac blue_btn">
                                    @lang('MSG_MSG_CONTACTS_ZABRONYUVATI_BILET')
                                </a>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="contact_img">
                                @if(!empty($contactInfo['image']))
                                    <img src="{{ asset('images/legacy/upload/wellcome/' . $contactInfo['image']) }}"
                                         alt="contact_image"
                                         class="fit_img">
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Блок с формой обратной связи и картой --}}
            <div class="contact_form_wrapper">
                <div class="container">
                    <div class="flex-row gap-30">
                        {{-- Форма обратной связи --}}
                        <div class="col-xl-6">
                            <div class="contact_form_block">
                                <div class="contact_form_txt_block">
                                    <div class="contact_form_txt_title h2_title">
                                        @lang('MSG_MSG_CONTACTS_FORMA_ZVOROTNIOGO_ZVYAZKU')
                                    </div>
                                    <div class="contact_form_txt par">
                                        {!! $feedbackFormText['text'] ?? '' !!}
                                    </div>
                                </div>
                                <form id="contactForm" class="contact_form">
                                    @csrf
                                    <div class="row">
                                        <input class="c_input req_input"
                                               type="text"
                                               placeholder="@lang('MSG_CONTACTS_IMYA')"
                                               id="name"
                                               name="name"
                                               required>
                                    </div>
                                    <div class="row">
                                        <input class="c_input"
                                               type="email"
                                               placeholder="@lang('MSG_CONTACTS_EMAIL')"
                                               id="email"
                                               name="email">
                                    </div>
                                    <div class="row">
                                        <div class="phone_input_wrapper flex_ac">
                                            <select class="phone_country_code flex_ac"
                                                    onchange="changeInputMask(this)"
                                                    id="phone_code"
                                                    name="phone_code">
                                                @foreach($phoneCodes as $k => $phoneCode)
                                                    <option value="{{ $phoneCode->id }}"
                                                            data-mask="{{ $phoneCode->phone_mask }}"
                                                            data-placeholder="{{ $phoneCode->phone_example }}"
                                                            {{ $k == 0 ? 'selected' : '' }}>
                                                        {{ $phoneCode->phone_country }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="text"
                                                   class="customer_phone_input inter"
                                                   placeholder="{{ $firstPhoneExample }}"
                                                   id="phone"
                                                   name="phone">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <textarea class="c_input req_input"
                                                  placeholder="@lang('MSG_MSG_CONTACTS_POVIDOMLENNYA')"
                                                  id="message"
                                                  name="message"
                                                  required></textarea>
                                    </div>
                                    <button type="button" class="send_contact_btn h4_title blue_btn" onclick="sendFeedback()">
                                        @lang('MSG_CONTACTS_VIDPRAVITI')
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Карта и контактная информация --}}
                        <div class="col-xl-6">
                            <div class="contacts_map">
                                <div class="contacts_map_title h2_title">
                                    @lang('MSG_CONTACTS_NASHI_KONTAKTI')
                                </div>
                                <div class="flex-row gap-30">
                                    <div class="col-md-7">
                                        <div class="contact_row h5_title">
                                            @lang('MSG_CONTACTS_ADRESA')
                                            @lang('MSG_MSG_CONTACTS_65000_M_ODESA_VUL_STAROSINNA_7')
                                        </div>
                                        <div class="contact_row h5_title">
                                            @lang('MSG_CONTACTS_TELEFON')
                                            <a href="tel:{{ $siteSettings['CONTACT_PHONE'] ?? '' }}">
                                                {{ $siteSettings['CONTACT_PHONE'] ?? '' }}
                                            </a>
                                        </div>
                                        <div class="contact_row h5_title">
                                            @lang('MSG_CONTACTS_EMAIL')
                                            <a href="mailto:{{ $siteSettings['CONTACT_EMAIL'] ?? '' }}">
                                                {{ $siteSettings['CONTACT_EMAIL'] ?? '' }}
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="contact_row h4_title">
                                            @lang('MSG_CONTACTS_MI_U_SOCMEREZHAH')
                                        </div>
                                        <div class="contacts_messagers flex_ac">
                                            @if(!empty($siteSettings['VIBER']))
                                                <a href="{{ $siteSettings['VIBER'] }}" class="m_link" target="_blank">
                                                    <img src="{{ asset('images/legacy/common/viber.svg') }}" alt="Viber" class="fit_img">
                                                </a>
                                            @endif
                                            @if(!empty($siteSettings['TELEGRAM']))
                                                <a href="{{ $siteSettings['TELEGRAM'] }}" class="m_link" target="_blank">
                                                    <img src="{{ asset('images/legacy/common/telegram.svg') }}" alt="Telegram" class="fit_img">
                                                </a>
                                            @endif
                                            @if(!empty($siteSettings['FB']))
                                                <a href="{{ $siteSettings['FB'] }}" class="m_link" target="_blank">
                                                    <img src="{{ asset('images/legacy/common/facebook.svg') }}" alt="Facebook" class="fit_img">
                                                </a>
                                            @endif
                                            @if(!empty($siteSettings['INST']))
                                                <a href="{{ $siteSettings['INST'] }}" class="m_link" target="_blank">
                                                    <img src="{{ asset('images/legacy/common/instagram.svg') }}" alt="Instagram" class="fit_img">
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="contact_map">
                                    @if(!empty($siteSettings['CONTACT_MAP']))
                                        {!! html_entity_decode(html_entity_decode($siteSettings['CONTACT_MAP'])) !!}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script src="{{ mix('js/legacy/libs/jquery.maskedinput.min.js') }}"></script>
    <script src="{{ mix('js/legacy/libs/jquery.nice-select.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('.phone_country_code').niceSelect();
            $('.customer_phone_input').mask("{{ $firstPhoneMask }}");
        });

        function changeInputMask(item) {
            let selectedOption = $(item).find(':selected');
            $('.customer_phone_input').mask($(selectedOption).data('mask'));
            $('.customer_phone_input').attr('placeholder', $(selectedOption).data('placeholder'));
        }

        function sendFeedback() {
            let name = $.trim($('#name').val());
            let email = $.trim($('#email').val());
            let phone = $.trim($('#phone').val());
            let message = $.trim($('#message').val());

            // Валидация обязательных полей
            let hasError = false;
            $('.req_input').each(function() {
                if ($.trim($(this).val()) == '') {
                    $(this).addClass('error');
                    hasError = true;
                } else {
                    $(this).removeClass('error');
                }
            });

            if (hasError) {
                out('@lang("MSG_MSG_CONTACTS_ZAPOLNITE_OBYAZATELINYE_POLYA")',
                    '@lang("MSG_MSG_CONTACTS_POLYA_OTMECHENNYE__YAVLYAYUTSYA_OBYAZATELINYMI_DLYA_ZAPOLNENIYA")');
                return false;
            }

            // Валидация email если заполнен
            if (email && !isEmail(email)) {
                $('#email').addClass('error');
                out('@lang("MSG_MSG_CONTACTS_EMAIL_UKAZAN_NEVERNO")',
                    '@lang("MSG_MSG_CONTACTS_UKAZHITE_PRAVILINYJ_EMAIL")');
                return false;
            }

            // Показываем загрузчик
            initLoader();

            $.ajax({
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: '{{ route("contacts.feedback") }}',
                data: {
                    'name': name,
                    'email': email,
                    'phone': phone,
                    'message': message
                },
                success: function(response) {
                    removeLoader();

                    if (response.status === 'ok') {
                        // Очищаем форму
                        $('#contactForm').find('input, textarea').val('');
                        $('.customer_phone_input').attr('placeholder', '{{ $firstPhoneExample }}');

                        out('@lang("MSG_MSG_CONTACTS_VASHE_SOOBSCHENIE_OTPRAVLENO")',
                            '@lang("MSG_MSG_CONTACTS_MY_SVYAZHEMSYA_S_VAMI_V_BLIZHAJSHEE_VREMYA")');
                    } else {
                        out('@lang("MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE")',
                            '@lang("MSG_MSG_CONTACTS_POPROBUJTE_POZZHE")');
                    }
                },
                error: function() {
                    removeLoader();
                    out('@lang("MSG_MSG_CONTACTS_NE_UDALOSI_OTPRAVITI_SOOBSCHENIE")',
                        '@lang("MSG_MSG_CONTACTS_POPROBUJTE_POZZHE")');
                }
            });
        }

        // Функция проверки email
        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }
    </script>
@endsection
