@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href=<?php echo  mix('css/style.css'); ?> />
@endsection

@section('content')
    <div class="content">
        <div class="page_content_wrapper">
            <div class="login_container">
                <div class="flex-row gap-30">
                    <div class="col-lg-6">
                        <a href="{{ route('main') }}" class="login_backlink h3_title flex_ac">
                            <img src="{{ asset('images/legacy/common/blue_arrow_left_2.svg') }}" alt="">
                            @lang('dictionary.MSG_MSG_LOGIN_POVERNUTISYA_NA_GOLOVNU')
                        </a>
                        <div class="login_form_wrapper">
                            <div class="login_page_title h2_title">
                                @lang('dictionary.MSG_MSG_LOGIN_UVIJTI')
                            </div>
                            <div class="login_tabs">
                                <div class="login_inputs_wrapper">
                                    <div class="login_input_row">
                                        <input
                                            class="c_input"
                                            type="text"
                                            placeholder="@lang('dictionary.MSG_MSG_LOGIN_EMAIL')"
                                            id="email"
                                            pattern="[^\u0400-\u04FF]*"
                                            maxlength="255"
                                            oninput="this.value = this.value.replace(/[^\x00-\x7F]/g, '');"
                                        >
                                    </div>
                                    <div class="login_input_row">
                                        <input
                                            class="c_input"
                                            type="password"
                                            placeholder="@lang('dictionary.MSG_MSG_LOGIN_PAROLI')"
                                            id="password"
                                        >
                                    </div>
                                    <div class="login_input_row">
                                        <button class="send_login_code_btn h4_title blue_btn flex_ac" onclick="auth()">
                                            @lang('dictionary.MSG_MSG_LOGIN_VOJTI')
                                        </button>
                                    </div>

                                    <div class="login_input_row">
                                        <a href="#" class="send_login_code_btn h4_title orange_btn flex_ac">
                                            @lang('dictionary.MSG_MSG_LOGIN_ZAREGISTRIROVATISYA')
                                        </a>
                                    </div>
                                    <div class="login_social_auth">
                                        <div class="login_input_row">
                                            <a href="{{ $googleAuthLink }}" class="social_auth_link google flex_ac">
                                                <img src="{{ asset('images/legacy/google.svg') }}" alt="" class="fit_img">
                                                Google
                                            </a>
                                        </div>
                                        <div class="login_input_row">
                                            <a href="{{ $facebookAuthLink }}" class="social_auth_link google flex_ac">
                                                <img src="{{ asset('images/legacy/facebook.svg') }}" alt="" class="fit_img">
                                                Facebook
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="login_clarification par">
                                    <div class="">
                                        <a href="#" class="forg_pass">
                                            @lang('dictionary.MSG_MSG_LOGIN_FORGOT_PASS')
                                        </a>
                                    </div>
                                    <div class="">
                                        @lang('dictionary.MSG_MSG_LOGIN_ESCHE_NET_LICHNOGO_KABINETA')
                                        <a href="#">@lang('dictionary.MSG_MSG_LOGIN_ZAREGISTRIROVATISYA')</a>
                                    </div>
                                    @if($loginPageTxt)
                                        {!! $loginPageTxt !!}
                                    @endif
                                    <p>
                                        @lang('dictionary.MSG_MSG_LOGIN_UMOVI')
                                        <a href="{{ route('offer') }}">@lang('dictionary.MSG_MSG_LOGIN_PUBLICHNO_OFERTI')</a>
                                        @lang('dictionary.MSG_MSG_LOGIN_TA')
                                        <a href="{{ route('privacy.policy') }}">@lang('dictionary.MSG_MSG_LOGIN_POLITIKI_KONFIDENCIJNOSTI')</a>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="login_logo">
                            <img src="{{ asset('images/legacy/common/login_page_logo.png') }}" alt="login logo" class="fit_img">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        function auth() {
            var email = $.trim($('#email').val());
            var password = $.trim($('#password').val());

            if (!isEmail(email)) {
                out('@lang('dictionary.MSG_MSG_REGISTER_NEVERNYJ_EMAIL')', '@lang('dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO')');
                return false;
            }

            initLoader();

            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url: '/ajax/ru',
                data: {
                    'request': 'auth',
                    'login': email,
                    'password': password
                },
                success: function(response) {
                    removeLoader();
                    if ($.trim(response.data) == 'ok') {
                        @if(session()->has('order'))
                            location.href = '/majbutni-pozdki/';
                        @else
                            location.href = '/majbutni-pozdki/';
                        @endif
                    } else if ($.trim(response.data) == 'email_not_found') {
                        out('@lang('dictionary.MSG_MSG_REGISTER_EMAIL_UKAZAN_NEVERNO')');
                    } else {
                        out($.trim(response.data));
                    }
                }
            });
        }

        function isEmail(email) {
            if (email.length < 5) {
                return false;
            }

            var parts = email.split('@');
            if (parts.length !== 2) {
                return false;
            }

            var domain = parts[1];
            if (domain.length < 4) {
                return false;
            }

            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }

        function out(msg, txt) {
            if (msg == undefined || msg == '' || $('.alert').length > 0) {
                return false;
            }

            let alert = document.createElement('div');
            $(alert).addClass('alert');

            let alertContent = document.createElement('div');
            $(alertContent).addClass('alert_content').appendTo(alert);

            let appendOverlay = document.createElement('div');
            $(appendOverlay).addClass('alert_overlay').appendTo(alert);

            let alertTitle = document.createElement('div');
            $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

            if (txt != '') {
                let alertTxt = document.createElement('div');
                $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
            }

            let closeBtn = document.createElement('button');
            $(closeBtn).addClass('alert_ok').text(close_btn).appendTo(alertContent);

            $('body').append(alert);
            $(alert).fadeIn();

            $('.alert_ok,.alert_overlay').on('click', function() {
                $('.alert').fadeOut();
                setTimeout(function() {
                    $('.alert').remove();
                }, 350)
            });
        }

        function initLoader() {
            let loader = document.createElement("div");
            loader.classList.add("loader");
            let loaderIcon = document.createElement("i");
            loaderIcon.className = "fas fa-3x fa-sync-alt fa-spin";
            loader.append(loaderIcon);
            document.querySelector("body").prepend(loader);
        }

        function removeLoader() {
            $('.loader').remove();
        }
    </script>
@endsection
