<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Debug\PaymentDebugController;
use App\Http\Controllers\Payments\MonobankPaymentController;
use App\Http\Controllers\Payments\MonobankWebhookController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\BonusController;


Route::get('/ru', function () {
    $query = request()->getQueryString();
    return redirect('/' . ($query ? ('?' . $query) : ''), 301);
});

Route::get('/ru/', function () {
    $query = request()->getQueryString();
    return redirect('/' . ($query ? ('?' . $query) : ''), 301);
});

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

$registerWebRoutes = static function (string $locale): void {
    app()->setLocale($locale);

    // старт выставления инвойса
    Route::get('/payment/monobank/start/{order}', [MonobankPaymentController::class, 'start'])
        ->name('payment.monobank.start');

    // success/return/fail — только редиректы, без логики “оплачено”
    Route::get('/payment/monobank/return/{order}', [MonobankPaymentController::class, 'return'])
        ->name('payment.monobank.return');

    Route::get('/payment/monobank/success/{order}', [MonobankPaymentController::class, 'success'])
        ->name('payment.monobank.success');

    Route::get('/payment/monobank/fail/{order}', [MonobankPaymentController::class, 'fail'])
        ->name('payment.monobank.fail');

Route::match(['GET', 'POST'], '/payment/monobank/webhook', [MonobankWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('payment.monobank.webhook');


    // Debug endpoint (guarded by debug=1 + token in controller)
    Route::get('/__debug/payment/status', [PaymentDebugController::class, 'status'])
        ->name('debug.payment.status');

    // Main home page route
    Route::get('/', [HomeController::class, 'index'])->name('main');

    // Temporary routes for header navigation (TODO: implement these pages)
    Route::get('/cabinet', function() {
        return redirect('/');
    })->name('auth');

    Route::get('/admin/bonuses', [BonusController::class, 'index'])->name('admin.bonuses.index');
    Route::post('/admin/bonuses/credit', [BonusController::class, 'credit'])->name('admin.bonuses.credit');

    // Debug route for session testing
Route::get('/debug/session', [\App\Http\Controllers\DebugController::class, 'sessionDebug']);


    // Schedule routes
    Route::get('/' . schedulePathForLocale($locale), [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedule');
    Route::get('/' . schedulePathForLocale($locale) . '/{from}-{to}', [\App\Http\Controllers\ScheduleController::class, 'route'])->name('schedule.route');
    Route::post('/schedule/route-details', [\App\Http\Controllers\ScheduleController::class, 'getRouteDetails'])->name('schedule.route-details');
    Route::post('/schedule/route-prices', [\App\Http\Controllers\ScheduleController::class, 'getRoutePrices'])->name('schedule.route-prices');
    Route::post('/schedule/remember-ticket', [\App\Http\Controllers\ScheduleController::class, 'rememberTicket'])->name('schedule.remember-ticket');

    if ($locale === 'ru') {
        Route::get('/rozklad', function() {
            return redirect('/ru/rozklad', 301);
        });
        Route::get('/schedule', function() {
            return redirect('/en/schedule', 301);
        });
        Route::get('/djmjvn6tl1nl_yp', function() {
            return redirect('/en/schedule', 301);
        });
    }
    
        if ($locale === 'en') {
        Route::get('/djmjvn6tl1nl_yp', function() {
            return redirect()->route('schedule', [], 301);
        });
    }
    
        // Autopark page
    Route::get('/avtopark', [\App\Http\Controllers\AutoparkController::class, 'index'])->name('avtopark');
    Route::post('/avtopark/load-more', [\App\Http\Controllers\AutoparkController::class, 'loadMore'])->name('autopark.load-more');
    Route::post('/avtopark/order-bus', [\App\Http\Controllers\AutoparkController::class, 'orderBus'])->name('autopark.order-bus');
    
        // About Us page
    Route::get('/o-nas', [\App\Http\Controllers\AboutController::class, 'index'])->name('about.us');
    Route::get('/pro-nas', function() {
        return redirect()->route('about.us');
    });
    
        // Contacts page
    Route::get('/kontakti', [\App\Http\Controllers\ContactController::class, 'index'])->name('kontakti');
    Route::post('/kontakti/feedback', [\App\Http\Controllers\ContactController::class, 'sendFeedback'])->name('contacts.feedback');

        // Text pages
        Route::get('/politika-konfidencijnosti', [\App\Http\Controllers\TextPageController::class, 'privacyPolicy'])->name('privacy.policy');
        Route::get('/usloviya-ispolzovaniya', [\App\Http\Controllers\TextPageController::class, 'termsOfUse'])->name('terms.of.use');
        Route::get('/oferta', [\App\Http\Controllers\TextPageController::class, 'offer'])->name('offer');
        Route::get('/pravila-perevozok', [\App\Http\Controllers\TextPageController::class, 'transportRules'])->name('transport.rules');
        Route::get('/usloviya-vozvrata', [\App\Http\Controllers\TextPageController::class, 'returnConditions'])->name('return.conditions');
        Route::get('/instrukciya-po-udaleniyu-dannyh', [\App\Http\Controllers\TextPageController::class, 'dataDeletionInstructions'])->name('data.deletion.instructions');
        Route::get('/mobilnoe-prilozhenie', [\App\Http\Controllers\TextPageController::class, 'mobileApp'])
            ->name('mobile.app');

        // FAQ routes
        Route::get('/voprosi-i-otveti', [\App\Http\Controllers\FaqController::class, 'index'])->name('faq');
        Route::post('/faq/search', [\App\Http\Controllers\FaqController::class, 'search'])->name('faq.search');

        Route::get('/thanks', function() {
            return '<h1>Спасибо! (TODO: implement)</h1><a href="/">На главную</a>';
        })->name('thanks');

        if(true) {
            Route::get('/html/header', function() {
                return view('html.header.index');
            });
        }

        Route::get('/regular_races/{tour}', '\App\Http\Controllers\RegularRaceController@index')->name('regular_races');
        Route::post('/ajax/callback', '\App\Http\Controllers\Ajax\CallbackController@send')->name('callback_request');
        Route::get('/ajax/regular-races', '\App\Http\Controllers\Ajax\RegularRacesController@loadPartialRaces')->name('regular-races-items');

        // Site AJAX routes
        Route::post('/ajax/site/lang', '\App\Http\Controllers\Ajax\SiteController@changeLang')->name('ajax.site.lang');

        // AJAX для страницы оплаты (Monobank/LiqPay)
        Route::post('/ajax/payment/{lang}', '\App\Http\Controllers\PaymentPageController@ajax')
            ->where('lang', 'ru|en|uk')
            ->name('payment.page.ajax');

        // Legacy AJAX route - должен быть перед вторыми ajax маршрутами
        Route::match(['GET', 'POST'], '/ajax/{lang}', '\App\Http\Controllers\Ajax\LegacyAjaxController@handleRequest')
            ->where('lang', 'ru|en|uk')
            ->name('ajax.legacy');

        Route::post('/ajax/booking/{lang}', '\App\Http\Controllers\BookingController@ajax')->name('booking.ajax');

        // Маршрут для создания legacy платежа со страницы оплаты
        Route::post('/payment/page/legacy-create', '\App\Http\Controllers\PaymentPageController@createLegacyPayment')
            ->name('payment.page.legacy.create')
            ->withoutMiddleware('\App\Http\Middleware\VerifyCsrfToken');

        // Страница благодарности после оплаты (legacy route for backward compatibility)
        Route::get('/thank-you', '\App\Http\Controllers\PaymentPageController@thankYou')->name('payment.thank-you');

        // Thank you page routes (рефакторная версия)
        Route::get('/dyakuyu-za-bronyuvannya-biletu', [\App\Http\Controllers\ThankYouController::class, 'index'])
            ->name('booking.thank-you');

        Route::post('/ajax/booking/clear-session', [\App\Http\Controllers\ThankYouController::class, 'clearSessionData'])
            ->name('booking.thank-you.clear-session');

        // Маршруты для билетов
        Route::any('/bilety', '\App\Http\Controllers\TicketController@index')->name('tickets.index');
        Route::get('/tickets/data', 'App\Http\Controllers\TicketController@data')->name('tickets.data');
        Route::get('/tickets/payment', '\App\Http\Controllers\TicketController@payment')->name('tickets.payment');

        // Альтернативный маршрут для бронирования
        Route::any('/bronyuvannya-kvitka', [BookingController::class, 'index'])->name('booking.index');
        Route::post('/booking/{order}/apply-bonuses', [BookingController::class, 'applyBonuses'])
            ->name('booking.apply-bonuses');

        // Payment routes (временно без авторизации для тестирования)
        Route::get('/payment', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payment.create');
        Route::get('/payment/result', [\App\Http\Controllers\PaymentController::class, 'result'])->name('payment.result');
        Route::get('/payment/status/{orderId}', [\App\Http\Controllers\PaymentController::class, 'status'])->name('payment.status');
        Route::post('/payment/refund/{orderId}', [\App\Http\Controllers\PaymentController::class, 'refund'])->name('payment.refund');
        Route::get('/payment/history', [\App\Http\Controllers\PaymentController::class, 'history'])->name('payment.history');

        // LiqPay callback (без авторизации и CSRF)
        Route::post('/payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');

        // Legacy платежи (без CSRF для совместимости с legacy кодом)
        Route::post('/payment/legacy/create', [\App\Http\Controllers\LegacyPaymentController::class, 'createFromLegacy'])->name('payment.legacy.create');
        Route::post('/payment/legacy/callback', [\App\Http\Controllers\LegacyPaymentController::class, 'callback'])->name('payment.legacy.callback');
};

Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])
    ->name('sitemap');

$supportedLocales = ['en', 'ru', 'uk'];
$defaultLocale = 'ru';

foreach ($supportedLocales as $locale) {
    if ($locale === $defaultLocale) {
        Route::middleware('language')
            ->group(function () use ($locale, $registerWebRoutes) {
                $registerWebRoutes($locale);
            });
    } else {
        Route::middleware('language')
            ->prefix($locale)
            ->name($locale . '.')
            ->group(function () use ($locale, $registerWebRoutes) {
                $registerWebRoutes($locale);
            });
    }
}
