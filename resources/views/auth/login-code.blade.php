@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href=<?php echo  mix('css/style.css'); ?> />
@endsection

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            <div class="login_container">
                @if($contactType === 'phone')
                    {{-- Phone verification section --}}
                    <div class="flex-row gap-30">
                        <div class="col-md-6">
                            <a href="{{ route('main') }}" class="login_backlink h3_title flex_ac">
                                <img src="{{ asset('images/legacy/common/blue_arrow_left_2.svg') }}" alt="">
                                @lang('dictionary.MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU')
                            </a>
                            <div class="login_page_title h2_title">
                                @lang('dictionary.MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA')
                            </div>
                            <div class="login_inputs_wrapper">
                                <form id="phone-verification-form" method="POST" action="{{ route('auth.login-code.verify') }}">
                                    @csrf
                                    <input type="hidden" name="contact_type" value="phone">

                                    <div class="row login_input_row">
                                        <label class="par input_label">
                                            @lang('dictionary.MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_NOMER')
                                            <span>{{ $contactValue }}</span>
                                        </label>
                                        <input
                                            class="c_input"
                                            type="text"
                                            name="code"
                                            placeholder="@lang('dictionary.MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA')"
                                            maxlength="4"
                                            required
                                        >
                                    </div>

                                    <div class="row login_input_row">
                                        <button type="submit" class="send_login_code_btn h4_title flex_ac blue_btn">
                                            @lang('dictionary.MSG_MSG_LOGIN_PIDTVERDITI')
                                        </button>
                                    </div>

                                    <div class="login_clarification par">
                                        <p>
                                            @lang('dictionary.MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA')
                                            <a href="{{ route('privacy.policy') }}">@lang('dictionary.MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH')</a>.
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="login_logo">
                                <img src="{{ asset('images/legacy/common/login_page_logo.png') }}" alt="login logo" class="fit_img">
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Email verification section --}}
                    <div class="flex-row gap-30">
                        <div class="col-md-6">
                            <a href="{{ route('main') }}" class="login_backlink h3_title flex_ac">
                                <img src="{{ asset('images/legacy/common/blue_arrow_left_2.svg') }}" alt="">
                                @lang('dictionary.MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU')
                            </a>
                            <div class="login_page_title h2_title">
                                @lang('dictionary.MSG_MSG_LOGIN_UVIJTI_ABO_ZARESTRUVATISYA')
                            </div>
                            <div class="login_inputs_wrapper">
                                <form id="email-verification-form" method="POST" action="{{ route('auth.login-code.verify') }}">
                                    @csrf
                                    <input type="hidden" name="contact_type" value="email">

                                    <div class="row login_input_row">
                                        <label class="par input_label">
                                            @lang('dictionary.MSG_MSG_LOGIN_VVEDITI_KOD_VIDPRAVLENIJ_NA_EMAIL')
                                            <span>{{ $contactValue }}</span>
                                        </label>
                                        <input
                                            class="c_input"
                                            type="text"
                                            name="code"
                                            placeholder="@lang('dictionary.MSG_MSG_LOGIN_KOD_PIDTVERDZHENNYA')"
                                            maxlength="4"
                                            required
                                        >
                                    </div>

                                    <div class="row login_input_row">
                                        <button type="submit" class="send_login_code_btn h4_title flex_ac blue_btn">
                                            @lang('dictionary.MSG_MSG_LOGIN_PIDTVERDITI')
                                        </button>
                                    </div>

                                    <div class="login_clarification par">
                                        <p>
                                            @lang('dictionary.MSG_MSG_LOGIN_VI_NADATE_I_PIDTVERDZHUTE_ZGODU_NA')
                                            <a href="{{ route('privacy.policy') }}">@lang('dictionary.MSG_MSG_LOGIN_OBROBKU_PERSONALINIH_DANIH')</a>.
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="login_logo">
                                <img src="{{ asset('images/legacy/common/login_page_logo.png') }}" alt="login logo" class="fit_img">
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[id$="-verification-form"]');

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;

                    // Disable button during request
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Перевірка...';

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Успешная верификация - редирект
                            window.location.href = data.redirect || '/cabinet';
                        } else {
                            // Показать ошибку
                            alert(data.message || 'Неправильний код');
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Виникла помилка. Спробуйте ще раз');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
                });

                // Allow only digits in code input
                const codeInput = form.querySelector('input[name="code"]');
                if (codeInput) {
                    codeInput.addEventListener('input', function(e) {
                        this.value = this.value.replace(/[^0-9]/g, '');
                    });
                }
            }
        });
    </script>
@endsection
