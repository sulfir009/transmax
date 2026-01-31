# SEO Audit (Step 0)

## Где формируется `<head>`
- Основной Blade layout: `resources/views/layout/app.blade.php` подключает `resources/views/layout/components/header/head.blade.php`.  
- Legacy-страницы:
  - Основной legacy-шаблон `resources/views/legacy/public/pages/index.php` использует `layout.components.header.head` (Blade).
  - Часть legacy-страниц использует legacy include `resources/views/legacy/public/blocks/head.php` (обновлено на рендер Blade head).  

## Текущий механизм языков
- Роут-группы по префиксам `/uk` и `/en` уже есть в `app/Providers/RouteServiceProvider.php`.  
- Locale устанавливается в `app/Http/Middleware/LanguageMiddleware.php` через первый сегмент URL или сессию.
- Session/lang хранится в `App\Service\Site`.  

## Страницы
- Главная: `Route::get('/', HomeController@index)` → `resources/views/pages/home.blade.php`.
- Расписание/маршруты: `ScheduleController@index`, ранее по query (`?departure=&arrival=`).  
- Статические: `about`, `contacts`, `avtopark`, `faq`, текстовые страницы в `TextPageController`.  
- Блог: явных маршрутов в Laravel-части нет (legacy возможен через таблицы).  

## Что было до реализации
- `<head>` есть, но без canonical, hreflang, OG, schema.
- Языки уже имеют префиксы, но часть ссылок строилась без учета локали.
- Расписание использует query-формат, ЧПУ отсутствовали.
- sitemap.xml отсутствовал как автогенерация.
- H1 отсутствует на части страниц (about/contacts/faq/autopark/schedule).
- 404 кастомного шаблона не было.