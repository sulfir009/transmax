@extends('layout.app')

@section('page-styles')
    <style>
        .header { padding: 0px; }

        .thx_debug_wrap{
            margin-top: 18px;
            padding: 14px 14px;
            border: 1px solid #A3E8F9;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: inset 0px 2px 25px rgba(53, 186, 240, 0.10);
            font-family: Montserrat, system-ui;
        }
        .thx_debug_title{
            font-weight: 800;
            font-size: 12px;
            color: #303233;
            margin-bottom: 8px;
        }
        .thx_debug_row{
            font-size: 11px;
            color: #6E7172;
            line-height: 1.3;
            margin-bottom: 6px;
        }
        .thx_debug_row b{ color:#303233; }
        .thx_debug_log{
            margin-top: 10px;
            max-height: 240px;
            overflow: auto;
            padding: 10px;
            border-radius: 10px;
            border: 1px dashed #35BAF0;
            background: #F7FDFF;
            font-size: 11px;
            color:#303233;
            white-space: pre-wrap;
        }
        .thx_badge_ok{
            display:inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background:#17a34a;
            color:#fff;
            font-weight:800;
            font-size: 10px;
            vertical-align: middle;
        }
        .thx_badge_wait{
            display:inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background:#0b74de;
            color:#fff;
            font-weight:800;
            font-size: 10px;
            vertical-align: middle;
        }
        .thx_badge_err{
            display:inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background:#dc2626;
            color:#fff;
            font-weight:800;
            font-size: 10px;
            vertical-align: middle;
        }

        .thx_actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:12px;
        }
        .thx_btn_small{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid #35BAF0;
            background:#fff;
            color:#35BAF0;
            font-weight:800;
            font-size: 11px;
            cursor:pointer;
            user-select:none;
        }
        .thx_btn_small:disabled{
            opacity:.55;
            cursor: not-allowed;
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

                <a href="{{ route('auth') }}" class="private_link h4_title blue_btn">
                    <span class="hidden-xs">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PEREJTI_U_PERSONALINIJ_KABINET') }}
                    </span>
                    <span class="hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                        {{ __('dictionary.MSG_MSG_THX_PAGE_PERSONALINIJ_KABINET') }}
                    </span>
                </a>

                <div class="thx_debug_wrap" id="thx_debug_wrap" style="display:none;">
                    <div class="thx_debug_title">
                        Payment debug
                        <span id="thx_state_badge" class="thx_badge_wait">WAIT</span>
                    </div>

                    <div class="thx_debug_row">
                        <b>order_id:</b> <span id="thx_order_id">—</span>
                    </div>
                    <div class="thx_debug_row">
                        <b>uniqid:</b> <span id="thx_uniqid">—</span>
                    </div>
                    <div class="thx_debug_row">
                        <b>source:</b> <span id="thx_source">—</span>
                    </div>
                    <div class="thx_debug_row">
                        <b>after_id:</b> <span id="thx_after_id">0</span>
                    </div>

                    <div class="thx_actions">
                        <button type="button" class="thx_btn_small" id="thx_btn_fetch_once">
                            Fetch events once
                        </button>
                        <button type="button" class="thx_btn_small" id="thx_btn_clear_session">
                            Clear session now
                        </button>
                        <button type="button" class="thx_btn_small" id="thx_btn_stop_trace">
                            Stop trace
                        </button>
                    </div>

                    <div class="thx_debug_log" id="thx_debug_log"></div>
                </div>

            </div>
        </div>

        <div class="txh_image">
            <img src="{{ asset('images/legacy/common/thx_img.png') }}" alt="thanks" class="fit_img">
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
$(document).ready(function () {

    // =========================================================
    // CSRF
    // =========================================================
    var CSRF_TOKEN = '';
    try {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) CSRF_TOKEN = meta.content;
    } catch(e){}

    // =========================================================
    // Helpers
    // =========================================================
    function safeJsonParse(v){
        if (v === null || v === undefined) return null;
        if (typeof v === 'object') return v;
        var s = String(v);
        if (!s.trim()) return null;
        try { return JSON.parse(s); } catch(e){ return null; }
    }

    function getParam(name){
        try{
            var u = new URL(window.location.href);
            return u.searchParams.get(name) || '';
        }catch(e){
            return '';
        }
    }

    // =========================================================
    // Debug UI refs
    // =========================================================
    var $wrap   = $('#thx_debug_wrap');
    var $badge  = $('#thx_state_badge');
    var $oid    = $('#thx_order_id');
    var $uniq   = $('#thx_uniqid');
    var $src    = $('#thx_source');
    var $after  = $('#thx_after_id');
    var $log    = $('#thx_debug_log');

    function logLine(line){
        var ts = '';
        try { ts = new Date().toISOString(); } catch(e){ ts = ''+Date.now(); }
        $log.append('['+ts+'] ' + line + "\n");
        if ($log[0]) $log.scrollTop($log[0].scrollHeight);
        console.log('[THX]', line);
    }

    function setBadge(state){
        if (state === 'OK') {
            $badge.removeClass('thx_badge_wait thx_badge_err').addClass('thx_badge_ok').text('OK');
        } else if (state === 'ERR') {
            $badge.removeClass('thx_badge_wait thx_badge_ok').addClass('thx_badge_err').text('ERR');
        } else {
            $badge.removeClass('thx_badge_ok thx_badge_err').addClass('thx_badge_wait').text('WAIT');
        }
    }

    // =========================================================
    // GET orderId/uniqid (NO SESSION!)
    // 1) URL
    // 2) localStorage fallback
    // =========================================================
    var orderId = parseInt(getParam('order_id') || getParam('order') || '0', 10) || 0;
    var uniqid  = (getParam('uniqid') || '').trim();
    var source  = '';

    if (orderId || uniqid) {
        source = 'url';
    } else {
        // localStorage fallback
        try {
            var raw = localStorage.getItem('mt_last_order');
            var obj = safeJsonParse(raw);
            if (obj && typeof obj === 'object') {
                var oid = parseInt(obj.order_id || 0, 10) || 0;
                var uq  = (obj.uniqid || '').toString().trim();
                if (oid) orderId = oid;
                if (uq) uniqid = uq;
                if (orderId || uniqid) source = 'localStorage';
            }
        } catch(e){}
    }

    if (!source) source = 'none';

    // =========================================================
    // Show debug panel only if we have identifiers
    // =========================================================
    if (orderId || uniqid) {
        $wrap.show();
        $oid.text(orderId ? String(orderId) : '—');
        $uniq.text(uniqid ? uniqid : '—');
        $src.text(source);
        setBadge('WAIT');
        logLine('Loaded. order_id=' + orderId + ' uniqid=' + uniqid + ' source=' + source);
    } else {
        console.log('[THX] No order_id/uniqid in URL and localStorage. No trace.');
        return;
    }

    // =========================================================
    // TRACE order_events
    // ВАЖНО: у тебя /ajax/ru отвечает legacy для order_events.
    // Поэтому тут мы НЕ ГАДАЕМ, а делаем:
    // - если сервер вернул ok:true/events[] -> печатаем
    // - иначе -> показываем ERR и выводим сырой ответ
    // =========================================================
    var afterId = 0;
    var timer = null;
    var startedAt = Date.now();
    var maxTraceMs = 25000;

    function fetchEventsOnce(){
        if (!orderId) {
            logLine('No order_id => cannot fetch events');
            return;
        }

        $.ajax({
            type: 'post',
            url: '/ajax/ru',
            dataType: 'text', // НЕ json, чтобы не падать на мусорных ответах
            timeout: 15000,
            headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
            data: {
                request: 'order_events',
                order_id: orderId,
                after_id: afterId
            },
            success: function(raw){
                var parsed = safeJsonParse(raw);

                if (!parsed || typeof parsed !== 'object' || parsed.ok !== true || !Array.isArray(parsed.events)) {
                    setBadge('ERR');
                    logLine('order_events BAD response (raw): ' + (raw ? String(raw).substring(0, 1200) : '(empty)'));
                    return;
                }

                var events = parsed.events || [];
                if (!events.length) return;

                for (var i=0; i<events.length; i++){
                    var ev = events[i] || {};
                    var payloadObj = safeJsonParse(ev.payload);
                    var payloadStr = payloadObj ? JSON.stringify(payloadObj) : (ev.payload ? String(ev.payload) : '');

                    logLine(
                        '#' + (ev.id || '-') +
                        ' type=' + (ev.type || '-') +
                        ' msg=' + (ev.message || '-') +
                        (payloadStr ? (' payload=' + payloadStr) : '')
                    );

                    var idNum = parseInt(ev.id || 0, 10) || 0;
                    if (idNum > afterId) afterId = idNum;
                }

                $after.text(String(afterId));

                var lastType = String((events[events.length-1] || {}).type || '');
                if (lastType.indexOf('payment_success') !== -1 || lastType.indexOf('email_sent') !== -1) {
                    setBadge('OK');
                }
            },
            error: function(xhr, status, error){
                setBadge('ERR');
                logLine('order_events ajax error: HTTP ' + (xhr ? xhr.status : '-') + ' ' + status + ' ' + error);
                if (xhr && xhr.responseText) logLine('responseText: ' + xhr.responseText.substring(0, 1200));
            }
        });
    }

    function startTrace(){
        if (!orderId) return;
        if (timer) return;

        startedAt = Date.now();
        timer = setInterval(function(){
            fetchEventsOnce();
            if ((Date.now() - startedAt) > maxTraceMs) stopTrace();
        }, 1500);

        fetchEventsOnce();
    }

    function stopTrace(){
        if (timer) {
            clearInterval(timer);
            timer = null;
            logLine('Trace stopped');
        }
    }

    // =========================================================
    // Clear session: НЕ требуем JSON (твоя ошибка parsererror была тут)
    // =========================================================
    function clearSessionNow(){
        $.ajax({
            type: 'post',
            url: '{{ route("booking.thank-you.clear-session") }}',
            dataType: 'text',
            timeout: 15000,
            headers: CSRF_TOKEN ? { 'X-CSRF-TOKEN': CSRF_TOKEN } : {},
            data: {
                order_id: orderId || null,
                uniqid: uniqid || null
            },
            success: function(raw){
                var parsed = safeJsonParse(raw);
                if (parsed && parsed.data === 'ok') {
                    logLine('Session cleared: OK');
                } else if (raw && String(raw).trim() !== '') {
                    logLine('Session clear response: ' + String(raw).substring(0, 600));
                } else {
                    logLine('Session clear empty response (OK-soft)');
                }
            },
            error: function(xhr, status, error){
                logLine('Session clear ajax error: HTTP ' + (xhr ? xhr.status : '-') + ' ' + status + ' ' + error);
            }
        });
    }

    // =========================================================
    // Start trace
    // =========================================================
    if (orderId) {
        startTrace();

        setTimeout(function(){
            fetchEventsOnce();
            clearSessionNow();
        }, maxTraceMs + 1200);
    } else {
        setTimeout(function(){
            clearSessionNow();
        }, 8000);
    }

    // =========================================================
    // Buttons
    // =========================================================
    $('#thx_btn_fetch_once').on('click', function(){ fetchEventsOnce(); });
    $('#thx_btn_clear_session').on('click', function(){ clearSessionNow(); });
    $('#thx_btn_stop_trace').on('click', function(){ stopTrace(); });

});
</script>
@endsection
