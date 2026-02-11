<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\BonusController;

use App\Http\Controllers\Debug\PaymentDebugController;
use App\Http\Controllers\Payments\MonobankPaymentController;
use App\Http\Controllers\Payments\MonobankWebhookController;

use App\Http\Middleware\VerifyCsrfToken;

/**
 * Локализованный сегмент для страницы расписания:
 *  - /uk/rozklad
 *  - /en/schedule
 *  - /raspisanie (ru по умолчанию)
 */
if (!function_exists('schedulePathForLocale')) {
    function schedulePathForLocale(?string $locale): string
    {
        return match ($locale) {
            'uk' => 'rozklad',
            'en' => 'schedule',
            default => 'raspisanie',
        };
    }
}

/**
 * ВАЖНО:
 * Эти редиректы должны быть ВНЕ локальных групп,
 * иначе они превратятся в /en/ru, /uk/ru и т.д.
 */
Route::get('/ru', function () {
    $query = request()->getQueryString();
    return redirect('/' . ($query ? ('?' . $query) : ''), 301);
});
Route::get('/ru/', function () {
    $query = request()->getQueryString();
    return redirect('/' . ($query ? ('?' . $query) : ''), 301);
});

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])
    ->name('sitemap');


/**
 * Все “обычные” web-роуты, которые должны существовать в КАЖДОЙ локали.
 * (и в ru без префикса, и в /en, /uk)
 */
$registerWebRoutes = static function (string $locale): void {

    // ===== Payments Monobank =====
    Route::get('/payment/monobank/start/{order}', [MonobankPaymentController::class, 'start'])
        ->name('payment.monobank.start');

    Route::get('/payment/monobank/return/{order}', [MonobankPaymentController::class, 'return'])
        ->name('payment.monobank.return');

    Route::get('/payment/monobank/success/{order}', [MonobankPaymentController::class, 'success'])
        ->name('payment.monobank.success');

    Route::get('/payment/monobank/fail/{order}', [MonobankPaymentController::class, 'fail'])
        ->name('payment.monobank.fail');

    Route::match(['GET', 'POST'], '/payment/monobank/webhook', [MonobankWebhookController::class, 'handle'])
        ->withoutMiddleware([VerifyCsrfToken::class])
        ->name('payment.monobank.webhook');

    // Debug endpoint
    Route::get('/__debug/payment/status', [PaymentDebugController::class, 'status'])
        ->name('debug.payment.status');

    // Main home
    Route::get('/', [HomeController::class, 'index'])->name('main');

    // Cabinet (temporary)
    Route::get('/cabinet', function () {
        return redirect('/');
    })->name('auth');

    // Admin bonuses
    Route::get('/admin/bonuses', [BonusController::class, 'index'])->name('admin.bonuses.index');
    Route::post('/admin/bonuses/credit', [BonusController::class, 'credit'])->name('admin.bonuses.credit');

    // Debug session
    Route::get('/debug/session', [\App\Http\Controllers\DebugController::class, 'sessionDebug']);

    // ===== Schedule (локализованный сегмент + локализованные ajax урлы) =====
    $scheduleBase = schedulePathForLocale($locale);

    Route::get('/' . $scheduleBase, [\App\Http\Controllers\ScheduleController::class, 'index'])
        ->name('schedule');

    Route::get('/' . $scheduleBase . '/{from}-{to}', [\App\Http\Controllers\ScheduleController::class, 'route'])
        ->name('schedule.route');

    // ВАЖНО: эти POST тоже должны быть под тем же сегментом, иначе на /uk/rozklad будет ходить в /uk/schedule/*
    Route::post('/' . $scheduleBase . '/route-details', [\App\Http\Controllers\ScheduleController::class, 'getRouteDetails'])
        ->name('schedule.route-details');

    Route::post('/' . $scheduleBase . '/route-prices', [\App\Http\Controllers\ScheduleController::class, 'getRoutePrices'])
        ->name('schedule.route-prices');

    Route::post('/' . $scheduleBase . '/remember-ticket', [\App\Http\Controllers\ScheduleController::class, 'rememberTicket'])
        ->name('schedule.remember-ticket');

    // Спец редиректы, которые ты хотел
    if ($locale === 'ru') {
        Route::get('/rozklad', fn () => redirect('/ru/rozklad', 301));
        Route::get('/schedule', fn () => redirect('/en/schedule', 301));
        Route::get('/djmjvn6tl1nl_yp', fn () => redirect('/en/schedule', 301));
    }

    if ($locale === 'en') {
        Route::get('/djmjvn6tl1nl_yp', fn () => redirect()->route('schedule', [], 301));
    }

    // ===== Autopark / About / Contacts =====
    Route::get('/avtopark', [\App\Http\Controllers\AutoparkController::class, 'index'])->name('avtopark');
    Route::post('/avtopark/load-more', [\App\Http\Controllers\AutoparkController::class, 'loadMore'])->name('autopark.load-more');
    Route::post('/avtopark/order-bus', [\App\Http\Controllers\AutoparkController::class, 'orderBus'])->name('autopark.order-bus');

    Route::get('/o-nas', [\App\Http\Controllers\AboutController::class, 'index'])->name('about.us');
    Route::get('/pro-nas', fn () => redirect()->route('about.us'));

    Route::get('/kontakti', [\App\Http\Controllers\ContactController::class, 'index'])->name('kontakti');
    Route::post('/kontakti/feedback', [\App\Http\Controllers\ContactController::class, 'sendFeedback'])->name('contacts.feedback');

    // ===== Text pages =====
    Route::get('/politika-konfidencijnosti', [\App\Http\Controllers\TextPageController::class, 'privacyPolicy'])->name('privacy.policy');
    Route::get('/usloviya-ispolzovaniya', [\App\Http\Controllers\TextPageController::class, 'termsOfUse'])->name('terms.of.use');
    Route::get('/oferta', [\App\Http\Controllers\TextPageController::class, 'offer'])->name('offer');
    Route::get('/pravila-perevozok', [\App\Http\Controllers\TextPageController::class, 'transportRules'])->name('transport.rules');
    Route::get('/usloviya-vozvrata', [\App\Http\Controllers\TextPageController::class, 'returnConditions'])->name('return.conditions');
    Route::get('/instrukciya-po-udaleniyu-dannyh', [\App\Http\Controllers\TextPageController::class, 'dataDeletionInstructions'])->name('data.deletion.instructions');
    Route::get('/mobilnoe-prilozhenie', [\App\Http\Controllers\TextPageController::class, 'mobileApp'])->name('mobile.app');

    // ===== FAQ =====
    Route::get('/voprosi-i-otveti', [\App\Http\Controllers\FaqController::class, 'index'])->name('faq');
    Route::post('/faq/search', [\App\Http\Controllers\FaqController::class, 'search'])->name('faq.search');

    Route::get('/thanks', fn () => '<h1>Спасибо! (TODO: implement)</h1><a href="/">На главную</a>')->name('thanks');

    // Debug html header
    if (true) {
        Route::get('/html/header', fn () => view('html.header.index'));
    }

    // Regular races
    Route::get('/regular_races/{tour}', '\App\Http\Controllers\RegularRaceController@index')->name('regular_races');
    Route::post('/ajax/callback', '\App\Http\Controllers\Ajax\CallbackController@send')->name('callback_request');
    Route::get('/ajax/regular-races', '\App\Http\Controllers\Ajax\RegularRacesController@loadPartialRaces')->name('regular-races-items');

    // Site AJAX
    Route::post('/ajax/site/lang', '\App\Http\Controllers\Ajax\SiteController@changeLang')->name('ajax.site.lang');

    // Payment page ajax (Monobank/LiqPay)
    Route::post('/ajax/payment/{lang}', '\App\Http\Controllers\PaymentPageController@ajax')
        ->where('lang', 'ru|en|uk')
        ->name('payment.page.ajax');

    // Legacy AJAX (должен быть ПЕРЕД вторыми ajax)
    Route::match(['GET', 'POST'], '/ajax/{lang}', '\App\Http\Controllers\Ajax\LegacyAjaxController@handleRequest')
        ->where('lang', 'ru|en|uk')
        ->name('ajax.legacy');

    Route::post('/ajax/booking/{lang}', '\App\Http\Controllers\BookingController@ajax')->name('booking.ajax');

    // Create legacy payment from payment page
    Route::post('/payment/page/legacy-create', '\App\Http\Controllers\PaymentPageController@createLegacyPayment')
        ->name('payment.page.legacy.create')
        ->withoutMiddleware('\App\Http\Middleware\VerifyCsrfToken');

    // Thank you page (legacy)
    Route::get('/thank-you', '\App\Http\Controllers\PaymentPageController@thankYou')->name('payment.thank-you');

    // Thank you (refactor)
    Route::get('/dyakuyu-za-bronyuvannya-biletu', [\App\Http\Controllers\ThankYouController::class, 'index'])
        ->name('booking.thank-you');

    Route::post('/ajax/booking/clear-session', [\App\Http\Controllers\ThankYouController::class, 'clearSessionData'])
        ->name('booking.thank-you.clear-session');

    // Tickets
    Route::any('/bilety/{slug?}', '\App\Http\Controllers\TicketController@index')->name('tickets.index');
    Route::get('/tickets/data', 'App\Http\Controllers\TicketController@data')->name('tickets.data');
    Route::get('/tickets/payment', '\App\Http\Controllers\TicketController@payment')->name('tickets.payment');

    // Booking
    Route::any('/bronyuvannya-kvitka', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/booking/{order}/apply-bonuses', [BookingController::class, 'applyBonuses'])
        ->name('booking.apply-bonuses');

    // Payment (temporary without auth)
    Route::get('/payment', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payment.create');
    Route::get('/payment/result', [\App\Http\Controllers\PaymentController::class, 'result'])->name('payment.result');
    Route::get('/payment/status/{orderId}', [\App\Http\Controllers\PaymentController::class, 'status'])->name('payment.status');
    Route::post('/payment/refund/{orderId}', [\App\Http\Controllers\PaymentController::class, 'refund'])->name('payment.refund');
    Route::get('/payment/history', [\App\Http\Controllers\PaymentController::class, 'history'])->name('payment.history');

    // LiqPay callback
    Route::post('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');

    // Legacy payments
    Route::post('/payment/legacy/create', [\App\Http\Controllers\LegacyPaymentController::class, 'createFromLegacy'])->name('payment.legacy.create');
    Route::post('/payment/legacy/callback', [\App\Http\Controllers\LegacyPaymentController::class, 'callback'])->name('payment.legacy.callback');
};


// ===== Локали =====
$supportedLocales = ['en', 'ru', 'uk'];
$defaultLocale = 'ru';

/**
 * ВАЖНО:
 * - middleware массив должен содержать ТОЛЬКО строки
 * - forceLocale:* должен быть middleware-алиасом (см. пункт 3 ниже)
 */
foreach ($supportedLocales as $locale) {

    $mw = [
        'language',
        'forceLocale:' . $locale,
    ];

    if ($locale === $defaultLocale) {
        Route::middleware($mw)->group(function () use ($locale, $registerWebRoutes) {
            $registerWebRoutes($locale);
        });
    } else {
        Route::middleware($mw)
            ->prefix($locale)
            ->name($locale . '.')
            ->group(function () use ($locale, $registerWebRoutes) {
                $registerWebRoutes($locale);
            });
    }
}
