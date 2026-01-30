@extends('layout.app')

@section('page-styles')
    <style>
        .header {
            padding: 0px;
        }
        .payment-status-note {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #f8f9fb;
            color: #2b2f33;
            font-size: 14px;
        }
    </style>
@endsection

@section('content')
<div class="content" style="padding-top:60px;">
    <div class="thx_content_wrapper">
        <div class="thx_block">
            <div class="container">
                <div class="thx_block_title h2_title">
                    {{ __('dictionary.MSG_MSG_THX_PAGE_DYAKUYU_ZA_BRONYUVANNYA_BILETU') }}
                </div>
                <div class="thx_block_subtitle par">
                    {{ __('dictionary.MSG_MSG_THX_PAGE_DANI_VASHOGO_BILETU') }}
                </div>
                <div id="payment-status-note" class="payment-status-note" style="display:none;"></div>
                <a href="{{ route('auth') }}" class="private_link h4_title blue_btn">
                    <span class="hidden-xs">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') }}
                    </span>
                    <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') }}
                    </span>
                </a>
            </div>
        </div>
        <div class="txh_image">
            <img src="{{ asset('images/legacy/common/thx_img.png') }}" alt="thanks" class="fit_img">
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
@php
    $paymentDebugEnabled = request()->query('debug') === '1';
    $paymentDebugToken = $paymentDebugEnabled ? env('PAYMENT_DEBUG_TOKEN') : null;
@endphp
@if($paymentDebugEnabled && $paymentDebugToken)
    <meta name="payment-debug-token" content="{{ $paymentDebugToken }}">
@endif
<script>
    $(document).ready(function () {
        // AJAX request to clear session data on page load
        $.ajax({
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url: '{{ route("booking.thank-you.clear-session") }}',
            success: function (response) {
                // Handle response
                if (response.data === 'ok') {
                    // Session data successfully cleared
                    console.log('Session data successfully cleared');
                } else {
                    // Error clearing session data
                    console.log('Error clearing session data');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error clearing session data:', error);
            }
        });
    });
</script>
<script>
    (function () {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id') || '';
        const uniqid = urlParams.get('uniqid') || '';
        const lang = '{{ app()->getLocale() }}';
        const pollingEndpoint = `/ajax/payment/${encodeURIComponent(lang)}`;
        const debugEnabled = urlParams.get('debug') === '1';
        const note = document.getElementById('payment-status-note');

        console.log('[PAYMENT THANKYOU] params', { order_id: orderId, uniqid: uniqid, lang: lang });
        console.log('[PAYMENT THANKYOU] polling endpoint', pollingEndpoint);

        if (!orderId && !uniqid) {
            console.warn('[PAYMENT THANKYOU] missing order_id/uniqid in URL');
            if (note) {
                note.style.display = 'block';
                note.textContent = 'Оплата обробляється. Якщо лист із квитком не прийде протягом 5–10 хвилин, зверніться до підтримки.';
            }
            return;
        }

        let pollCount = 0;
        const maxPolls = 6;

        function updateNote(text) {
            if (!note) return;
            note.style.display = 'block';
            note.textContent = text;
        }

        function pollStatus() {
            pollCount += 1;
            const payload = new URLSearchParams({
                request: 'order_events',
                order_id: orderId,
                uniqid: uniqid,
                poll: String(pollCount),
                check_remote: pollCount >= 6 ? '1' : '0'
            });
            if (debugEnabled) {
                payload.set('debug', '1');
            }

            fetch(pollingEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: payload.toString()
            })
                .then((resp) => resp.text().then((raw) => ({ resp, raw })))
                .then(({ resp, raw }) => {
                    let parsed = null;
                    try {
                        parsed = JSON.parse(raw);
                    } catch (e) {
                        parsed = { __parse_error: e.message };
                    }

                    const missingFields = [];
                    if (!parsed || typeof parsed !== 'object') {
                        missingFields.push('status');
                    } else {
                        if (!Object.prototype.hasOwnProperty.call(parsed, 'status')) {
                            missingFields.push('status');
                        }
                        if (!Object.prototype.hasOwnProperty.call(parsed, 'payment_status')) {
                            missingFields.push('payment_status');
                        }
                    }

                    console.log('[PAYMENT THANKYOU] poll response', {
                        poll: pollCount,
                        http_status: resp.status,
                        raw: raw,
                        parsed: parsed,
                        missing_fields: missingFields
                    });
                    if (debugEnabled && parsed && parsed._debug) {
                        console.log('[PAYMENT THANKYOU] debug', parsed._debug);
                    }

                    if (parsed && parsed.status === 'ok' && parsed.payment_status === 2) {
                        updateNote('Оплату підтверджено. Квиток буде надіслано на email.');
                        return;
                    }

                    if (parsed && parsed.status === 'error') {
                        updateNote('Не вдалося підтвердити оплату. Якщо оплата успішна, зверніться до підтримки.');
                        return;
                    }

                    updateNote('Очікуємо підтвердження оплати...');

                    if (pollCount < maxPolls) {
                        setTimeout(pollStatus, 5000);
                    }
                })
                .catch((err) => {
                    console.warn('[PAYMENT THANKYOU] poll failed', err);
                    updateNote('Проблема з перевіркою оплати. Спробуйте оновити сторінку.');
                });
        }

        pollStatus();
    })();
</script>
<script>
    (function () {
        const urlParams = new URLSearchParams(window.location.search);
        const debugEnabled = urlParams.get('debug') === '1';
        if (!debugEnabled) {
            return;
        }

        const debugTokenMeta = document.querySelector('meta[name="payment-debug-token"]');
        const debugToken = debugTokenMeta ? debugTokenMeta.content : '';
        const orderId = urlParams.get('order_id') || '';
        const uniqid = urlParams.get('uniqid') || '';
        let pollCount = 0;

        window.__PAYMENT_DEBUG = true;
        console.log('[PAYMENT TRACE] Debug enabled', {
            order_id: orderId,
            uniqid: uniqid,
            token_present: !!debugToken
        });

        function safeJsonParse(raw) {
            try {
                return JSON.parse(raw);
            } catch (e) {
                return { __parse_error: e.message };
            }
        }

        function detectHandlerHint(parsed) {
            if (parsed && typeof parsed === 'object') {
                if (Object.prototype.hasOwnProperty.call(parsed, 'lang') && Object.prototype.hasOwnProperty.call(parsed, 'data')) {
                    return 'legacy_wrapper';
                }
                if (parsed.debug_meta && parsed.debug_meta.handled_by) {
                    return parsed.debug_meta.handled_by;
                }
                if (parsed.data === 'ok' || parsed.status === 'ok') {
                    return 'payment_controller';
                }
            }
            return 'unknown';
        }

        function logPollResult(context) {
            console.groupCollapsed(`[PAYMENT TRACE] poll #${context.pollIndex} ${context.method} ${context.url}`);
            console.log('timestamp', new Date().toISOString());
            console.log('payload', context.payload);
            console.log('http_status', context.status);
            console.log('content_type', context.contentType);
            console.log('correlation_id', context.correlationId);
            console.log('raw_response', context.rawResponse);
            console.log('parsed', context.parsed);
            console.log('handler_hint', context.handlerHint);
            console.log('bad_response_reason', context.badResponseReason);
            console.groupEnd();
        }

        function attachDebugHeaders(options) {
            if (!debugToken) return;
            options.headers = options.headers || {};
            options.headers['X-Debug-Token'] = debugToken;
        }

        function getPayload(options) {
            if (!options) return {};
            if (typeof options.data === 'string') {
                const params = new URLSearchParams(options.data);
                return Object.fromEntries(params.entries());
            }
            if (options.data && typeof options.data === 'object') {
                return options.data;
            }
            return {};
        }

        function maybeTriggerStatusDebug(payload) {
            if (!debugToken) return;
            const oid = payload.order_id || orderId;
            const uq = payload.uniqid || uniqid;
            if (!oid && !uq) return;
            const debugUrl = `/__debug/payment/status?debug=1&order_id=${encodeURIComponent(oid || '')}&uniqid=${encodeURIComponent(uq || '')}`;
            fetch(debugUrl, {
                method: 'GET',
                headers: {
                    'X-Debug-Token': debugToken
                }
            })
                .then((resp) => resp.json().then((data) => ({ resp, data })))
                .then(({ resp, data }) => {
                    console.groupCollapsed('[PAYMENT TRACE] debug status');
                    console.log('http_status', resp.status);
                    console.log('correlation_id', resp.headers.get('X-Correlation-Id'));
                    console.log('summary', {
                        order_found: data.order_found,
                        payment_status: data.order_fields ? data.order_fields.payment_status : null,
                        ticket_files: data.ticket_status ? data.ticket_status.files_found : null,
                        admin_online: data.admin_status ? data.admin_status.order_in_online_list : null,
                        webhook_seen: !!data.webhook_seen,
                        last_finalize_error: data.email_status ? data.email_status.last_finalize_error : null
                    });
                    console.log('full', data);
                    console.groupEnd();
                })
                .catch((err) => {
                    console.warn('[PAYMENT TRACE] debug status failed', err);
                });
        }

        const originalFetch = window.fetch;
        if (typeof originalFetch === 'function') {
            window.fetch = function (input, init) {
                const url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
                const payload = init && init.body ? init.body : null;
                const payloadText = typeof payload === 'string' ? payload : '';
                const hasOrderEvents = url.includes('order_events') || payloadText.includes('order_events');
                if (hasOrderEvents) {
                    pollCount += 1;
                    const debugUrl = url + (url.includes('?') ? '&' : '?') + 'debug=1';
                    const headers = new Headers((init && init.headers) || {});
                    if (debugToken) {
                        headers.set('X-Debug-Token', debugToken);
                    }
                    const nextInit = Object.assign({}, init, { headers });
                    return originalFetch(debugUrl, nextInit).then(async (resp) => {
                        const raw = await resp.clone().text();
                        const parsed = safeJsonParse(raw);
                        logPollResult({
                            pollIndex: pollCount,
                            method: (nextInit && nextInit.method) || 'GET',
                            url: debugUrl,
                            payload: payloadText,
                            status: resp.status,
                            contentType: resp.headers.get('content-type'),
                            correlationId: resp.headers.get('X-Correlation-Id'),
                            rawResponse: raw.substring(0, 1000),
                            parsed: parsed,
                            handlerHint: detectHandlerHint(parsed),
                            badResponseReason: parsed && parsed.__parse_error ? parsed.__parse_error : null
                        });
                        maybeTriggerStatusDebug(getPayload({ data: payloadText }));
                        return resp;
                    });
                }
                return originalFetch(input, init);
            };
        }

        if (window.$ && $.ajax) {
            const originalAjax = $.ajax;
            $.ajax = function (options) {
                const payload = getPayload(options);
                const isOrderEvents = payload.request === 'order_events';
                if (isOrderEvents) {
                    pollCount += 1;
                    options.url = options.url + (options.url.includes('?') ? '&' : '?') + 'debug=1';
                    attachDebugHeaders(options);
                    const originalSuccess = options.success;
                    const originalError = options.error;
                    options.success = function (response, textStatus, xhr) {
                        const raw = xhr && xhr.responseText ? xhr.responseText : (typeof response === 'string' ? response : JSON.stringify(response));
                        const parsed = typeof response === 'object' ? response : safeJsonParse(raw);
                        const handlerHint = detectHandlerHint(parsed);
                        const badResponseReason = parsed && parsed.__parse_error ? parsed.__parse_error : null;
                        logPollResult({
                            pollIndex: pollCount,
                            method: options.type || 'GET',
                            url: options.url,
                            payload: payload,
                            status: xhr ? xhr.status : null,
                            contentType: xhr ? xhr.getResponseHeader('content-type') : null,
                            correlationId: xhr ? xhr.getResponseHeader('X-Correlation-Id') : null,
                            rawResponse: raw ? raw.substring(0, 1000) : '',
                            parsed: parsed,
                            handlerHint: handlerHint,
                            badResponseReason: badResponseReason
                        });
                        maybeTriggerStatusDebug(payload);
                        if (originalSuccess) originalSuccess(response, textStatus, xhr);
                    };
                    options.error = function (xhr, textStatus, errorThrown) {
                        logPollResult({
                            pollIndex: pollCount,
                            method: options.type || 'GET',
                            url: options.url,
                            payload: payload,
                            status: xhr ? xhr.status : null,
                            contentType: xhr ? xhr.getResponseHeader('content-type') : null,
                            correlationId: xhr ? xhr.getResponseHeader('X-Correlation-Id') : null,
                            rawResponse: xhr && xhr.responseText ? xhr.responseText.substring(0, 1000) : '',
                            parsed: safeJsonParse(xhr && xhr.responseText ? xhr.responseText : ''),
                            handlerHint: 'error',
                            badResponseReason: errorThrown || textStatus
                        });
                        maybeTriggerStatusDebug(payload);
                        if (originalError) originalError(xhr, textStatus, errorThrown);
                    };
                }
                return originalAjax.apply(this, arguments);
            };
        }
    })();
</script>
@endsection
