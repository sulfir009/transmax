<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::any('/admin/catalog/tours/ajax.php', function(Request $request) {
    $path = base_path('legacy/admin/cruds/catalog/tours/ajax.php');
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();

        // Принудительно передаем данные
        $_POST = $request->all();
        $_REQUEST = $_POST; // Чтобы работали и $_REQUEST тоже

        include $path;

        $output = ob_get_clean();
        return response($output)->header('Content-Type', 'application/json');
    }

    return response()->json(['error' => 'File not found'], 404);
});

Route::post('/ajax/site/lang', '\App\Http\Controllers\Ajax\SiteController@changeLang')->name('changeLang');

//Route::get('/o-nas', '\App\Http\Controllers\LegacyController@index')->name('about.us');//71
//Route::get('/avtopark', '\App\Http\Controllers\LegacyController@index')->name('avtopark');//72
//Route::get('/raspisanie', '\App\Http\Controllers\LegacyController@index')->name('schedule');//73
//Route::get('/voprosi-i-otveti', '\App\Http\Controllers\LegacyController@index')->name('faq');//74
//Route::get('/kontakti', '\App\Http\Controllers\LegacyController@index')->name('kontakti');
Route::get('/avtorizaciya', '\App\Http\Controllers\LegacyController@index')->name('auth'); //77
Route::get('/majbutni-pozdki', '\App\Http\Controllers\LegacyController@index')->name('future_races'); //80
//Route::get('/politika-konfidencijnosti', '\App\Http\Controllers\LegacyController@index')->name('privacy.policy');//83
//Route::get('/dogovir-oferti', '\App\Http\Controllers\LegacyController@index')->name('offer_agreement');//84
//Route::get('/dyakuyu-za-bronyuvannya-biletu', '\App\Http\Controllers\LegacyController@index')->name('thanks');//90

Route::match(['GET', 'POST', 'PUT', 'DELETE'],
    '/admin/{folder1}/{folder2}/{folder3}/{folder4}/{folder5}/{file}',
    function ($folder1, $folder2, $folder3, $folder4, $folder5, $file) {
    $path = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$folder4}/{$folder5}/{$file}";
    //dd($path);
    ///legacy/admin/cruds/users/clients/buy/edit.php
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();
        return response($output);
    }

    abort(404, 'File not found');
})->where([
    'folder1' => '[a-zA-Z0-9_-]+',
    'folder2' => '[a-zA-Z0-9_-]+',
    'folder3' => '[a-zA-Z0-9_-]+',
    'folder4' => '[a-zA-Z0-9_-]+',
    'file'    => '.*\.php$',
]);

Route::any('/ajax/{lang}', function(Request $request, $lang) {
    $path = base_path('legacy/public/pages/ajax.php');

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();

        return response()->json([
            'lang' => $lang,
            'data' => $output
        ]);
    }

    return response()->json(['error' => 'File not found'], 404);
})->where('lang', '.*');

Route::any('/public/pages/private/{file}', function(Request $request, $file) {
    $path = base_path('legacy/public/pages/private/' . $file);

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {

        // Парсим JSON вручную
        if (str_starts_with($request->header('Content-Type'), 'application/json')) {
            $_POST = json_decode($request->getContent(), true) ?? [];
        } else {
            $_POST = $request->post();
        }
       /* return response()->json([$_POST]);*/
        $_GET = $request->query();
        $_REQUEST = array_merge($_GET, $_POST);

        // Сохраняем лог
        file_put_contents(
            storage_path('logs/input.log'),
            json_encode(['post' => $_POST, 'request' => $_REQUEST], JSON_PRETTY_PRINT)
        );

        ob_start();
        include $path;
        $output = ob_get_clean();

        return new \Symfony\Component\HttpFoundation\Response($output);
    }

    return response()->json(['error' => 'File not found'], 404);
})->where('file', '.*\.php$');

Route::any('/admin', '\App\Http\Controllers\LegacyController@admin');
Route::any('/public/pages/{file}', function(string $file) {
    $path = __DIR__ .'/../legacy/public/pages/' . $file;

    // Проверяем, существует ли файл и имеет ли расширение .php
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        // Запускаем файл, передавая параметры
        ob_start();
        include $path; // Подключаем файл
        $output = ob_get_clean();
        return response($output);
    }
    abort(404, 'File not found');
})->where('file', '.*\.php$');

Route::any('/admin/cruds/content/{file}', function (Request $request, $file) {
    $path = __DIR__ . '/../legacy/admin/cruds/content/' . $file;

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();
        $data['data'] = $output;
        if (!empty($request->query('parent'))) {
            $data['parent'] = $request->query('parent');
        }

        return response($output);
    }

    return response()->json(['error' => 'File not found'], 404);
})->where('file', '.*\.php$');

Route::any('/admin/{file}', function(string $file) {
    $path = __DIR__ .'/../legacy/admin/' . $file;
    // Проверяем, существует ли файл и имеет ли расширение .php
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        // Запускаем файл, передавая параметры
        ob_start();
        include $path; // Подключаем файл
        $output = ob_get_clean();
        return response($output);
    }
    abort(404, 'File not found');
})->where('file', '.*\.php$');

Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/admin/{folder1}/{folder2}/{folder3}', function ($folder1, $folder2, $folder3) {
    $path = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/index.php";
    $secondPath = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/edit.php";

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();
        return response($output);
    }

    if (file_exists($secondPath) && pathinfo($secondPath, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $secondPath;
        $output = ob_get_clean();
        return response($output);
    }

    abort(404, 'File not found');
})->where([
    'folder1' => '[a-zA-Z0-9_-]+',
    'folder2' => '[a-zA-Z0-9_-]+',
    'folder3' => '[a-zA-Z0-9_-]+'
]);

Route::match(
    ['GET', 'POST', 'PUT', 'DELETE'],
    '/admin/{folder1}/{folder2}/{folder3}/{folder4}',
    function ($folder1, $folder2, $folder3, $folder4) {
        $path = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$folder4}/index.php";
        $secondPath = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$folder4}/edit.php";

        if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            ob_start();
            include $path;
            $output = ob_get_clean();
            return response($output);
        }

        if (file_exists($secondPath) && pathinfo($secondPath, PATHINFO_EXTENSION) === 'php') {
            ob_start();
            include $secondPath;
            $output = ob_get_clean();
            return response($output);
        }

        abort(404, 'File not found');
    })->where([
    'folder1' => '[a-zA-Z0-9_-]+',
    'folder2' => '[a-zA-Z0-9_-]+',
    'folder3' => '[a-zA-Z0-9_-]+',
    'folder4' => '[a-zA-Z0-9_-]+',
]);

Route::match(
    ['GET', 'POST', 'PUT', 'DELETE'],
    '/admin/{folder1}/{folder2}/{folder3}/{folder4}/{folder5}',
    function ($folder1, $folder2, $folder3, $folder4, $folder5) {
    $path = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$folder4}/{$folder5}/index.php";
    $secondPath = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$folder4}/{$folder5}/edit.php";

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();
        return response($output);
    }

    if (file_exists($secondPath) && pathinfo($secondPath, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $secondPath;
        $output = ob_get_clean();
        return response($output);
    }

    abort(404, 'File not found');
})->where([
    'folder1' => '[a-zA-Z0-9_-]+',
    'folder2' => '[a-zA-Z0-9_-]+',
    'folder3' => '[a-zA-Z0-9_-]+',
    'folder4' => '[a-zA-Z0-9_-]+',
    'folder5' => '[a-zA-Z0-9_-]+',
]);

Route::match(['GET', 'POST', 'PUT', 'DELETE'], '/admin/{folder1}/{folder2}/{folder3}/{file}', function ($folder1, $folder2, $folder3, $file) {
    $path = __DIR__ . "/../legacy/admin/{$folder1}/{$folder2}/{$folder3}/{$file}";
    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();
        include $path;
        $output = ob_get_clean();
        return response($output);
    }

    abort(404, 'File not found');
})->where([
    'folder1' => '[a-zA-Z0-9_-]+',
    'folder2' => '[a-zA-Z0-9_-]+',
    'folder3' => '[a-zA-Z0-9_-]+',
    'file'    => '.*\.php$'
]);
Route::any('/api', function(Request $request) {
    $path = base_path('legacy/public/pages/appAjax.php');

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        ob_start();

        // Принудительно передаем данные
        $_POST = $request->all();
        $_REQUEST = $_POST; // Чтобы работали и $_REQUEST тоже

        include $path;

        $output = ob_get_clean();
        return response($output)->header('Content-Type', 'application/json');
    }

    return response()->json(['error' => 'File not found'], 404);
});

// Google OAuth authentication route
Route::get('/social/google.php', function(Request $request) {
    $path = base_path('legacy/social/google.php');

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        // Load Laravel environment helper
        require_once base_path('vendor/autoload.php');

        // Передаем GET параметры
        $_GET = $request->query();
        $_POST = $request->post();
        $_REQUEST = array_merge($_GET, $_POST);

        // Do NOT start session here - Laravel already handles it
        // session_start() is already handled by Laravel's session middleware

        ob_start();
        include $path;
        $output = ob_get_clean();

        // If the script outputs headers/redirects, we need to handle them properly
        if (headers_sent()) {
            return new \Illuminate\Http\Response('');
        }

        return new \Symfony\Component\HttpFoundation\Response($output);
    }

    abort(404, 'File not found');
});

// Facebook OAuth authentication route
Route::get('/social/facebook.php', function(Request $request) {
    $path = base_path('legacy/social/facebook.php');

    if (file_exists($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        // Load Laravel environment helper
        require_once base_path('vendor/autoload.php');

        // Передаем GET параметры
        $_GET = $request->query();
        $_POST = $request->post();
        $_REQUEST = array_merge($_GET, $_POST);

        // Do NOT start session here - Laravel already handles it
        // session_start() is already handled by Laravel's session middleware

        ob_start();
        include $path;
        $output = ob_get_clean();

        // If the script outputs headers/redirects, we need to handle them properly
        if (headers_sent()) {
            return new \Illuminate\Http\Response('');
        }

        return new \Symfony\Component\HttpFoundation\Response($output);
    }

    abort(404, 'File not found');
});

Route::any('/admin/{path}', '\App\Http\Controllers\LegacyController@admin')->where('path', '.*');
Route::any('{path}', '\App\Http\Controllers\LegacyController@index')->where('path', '.*');
