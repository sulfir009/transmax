@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('css/ticket_filter_hero.css') }}?v=1">
    @include('schedule.partials.popular-routes-styles')
    <style>
        .mt_schedule_scope {
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
            color: #1d1f2b;
        }

        .mt_schedule_scope .mt_schedule_body {
            padding: 32px 0 0;
        }

        .mt_schedule_scope .mt_schedule_breadcrumbs {
            font-size: 12px;
            color: #6c6f82;
            margin-bottom: 10px;
        }

        .mt_schedule_scope .mt_schedule_breadcrumbs a {
            color: inherit;
            text-decoration: none;
        }

        .mt_schedule_scope .mt_schedule_kicker {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c6f82;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .mt_schedule_scope .mt_schedule_route {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .mt_schedule_scope .mt_schedule_intro {
            font-size: 14px;
            color: #6c6f82;
            margin-bottom: 18px;
        }

        .mt_schedule_scope .mt_schedule_title {
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .mt_schedule_scope .mt_schedule_subtitle {
            font-size: 14px;
            color: #6c6f82;
            margin-bottom: 20px;
        }

        .mt_schedule_scope .mt_schedule_sort {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .mt_schedule_scope .mt_schedule_sort_label {
            font-size: 12px;
            font-weight: 600;
            color: #6c6f82;
        }

        .mt_schedule_scope .mt_schedule_sort_buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .mt_schedule_scope .mt_schedule_sort_btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid #cbd4ff;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            text-decoration: none;
            color: #2c3163;
            background: #ffffff;
        }

        .mt_schedule_scope .mt_schedule_sort_btn.active {
            background: #4d80ff;
            border-color: #4d80ff;
            color: #ffffff;
        }

        .mt_schedule_scope .mt_schedule_seo {
            padding: 28px 0 60px;
        }

        .mt_schedule_scope .mt_schedule_seo_title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .mt_schedule_scope .mt_schedule_seo_text {
            font-size: 14px;
            line-height: 1.6;
            color: #5a6077;
            margin-bottom: 20px;
        }

        .mt_schedule_scope .mt_schedule_routes {
            display: grid;
            gap: 16px;
        }

    </style>
@endsection

@section('content')
@php
    use Illuminate\Support\Carbon;

    $routeHeading = null;
    $routeName = null;

    if (!empty($filters['departure']) && !empty($filters['arrival']) && $routes->count() > 0) {
        $collection = $routes->getCollection();
        $firstItem = $collection->first();
        $firstRoute = is_array($firstItem) ? reset($firstItem) : $firstItem;

        if ($firstRoute) {
            $fromCity = trim((string) ($firstRoute->departure_city ?? ''));
            $toCity = trim((string) ($firstRoute->arrival_city ?? ''));
            if ($fromCity !== '' && $toCity !== '') {
                $routeName = $fromCity . ' - ' . $toCity;
                $routeHeading = mb_strtoupper($routeName, 'UTF-8');
            }
        }
    }

    $scheduleYear = now()->year;
    $scheduleTitle = $routeHeading
        ? "РОЗКЛАД АВТОБУСІВ {$routeHeading} НА {$scheduleYear} РІК"
        : "РОЗКЛАД АВТОБУСІВ НА {$scheduleYear} РІК";

    $sortOptions = [
        'price' => 'ЦІНА',
        'dep' => 'ЧАС ВІДПРАВЛЕННЯ',
        'arr' => 'ЧАС ПРИБУТТЯ',
        'popular' => 'ПОПУЛЯРНІСТЬ',
    ];

    $routeH1 = __('alias_schedule');
    if ($routeName !== null) {
        $tripDate = request()->get('date', now()->toDateString());
        try {
            $dateLabel = Carbon::parse($tripDate)->locale(app()->getLocale())->translatedFormat('d F');
        } catch (\Throwable $e) {
            $dateLabel = Carbon::now()->locale(app()->getLocale())->translatedFormat('d F');
        }

        $routeH1 = match (app()->getLocale()) {
            'ru' => "Расписание автобусов {$routeName} на {$dateLabel}",
            'en' => "Bus timetable {$routeName} for {$dateLabel}",
            default => "Розклад автобусів {$routeName} на {$dateLabel}",
        };
    }
@endphp

<div class="content mt_schedule_scope">
    @include('ticket.partials.main_filter_wrapper', [
        'cities' => $cities ?? [],
        'filterDeparture' => $filters['departure'] ?? null,
        'filterArrival' => $filters['arrival'] ?? null,
        'filterDate' => request()->get('date', date('Y-m-d')),
        'adults' => request()->get('adults', 1),
        'kids' => request()->get('kids', 0),
        'dictionary' => $dictionary ?? [],
        'lang' => $lang ?? 'uk',
        'formAction' => \App\Helpers\LocaleHelper::localizedRoute('tickets.index'),

    ])

    <div class="mt_schedule_body">
        <div class="container">
            <nav class="mt_schedule_breadcrumbs">
                <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}">Квитки на автобус</a>
                <span> / </span>
                <span>Розклад</span>
            </nav>

            <div class="mt_schedule_kicker">Квитки на автобус</div>
            <h1 class="mt_schedule_route">{{ $routeH1 }}</h1>
            <div class="mt_schedule_intro">Виберіть дату, щоб купити квиток на автобус.</div>

            <h2 class="mt_schedule_title">{{ $scheduleTitle }}</h2>
            <p class="mt_schedule_subtitle">@lang('dictionary.MSG_MSG_TICKETS_VIZD_TA_PRIBUTTYA_ZA_MISCEVIM_CHASOM')</p>

            <div class="mt_schedule_sort">
                <div class="mt_schedule_sort_label">СОРТУВАТИ</div>
                <div class="mt_schedule_sort_buttons">
                    @foreach($sortOptions as $sortKey => $sortLabel)
                        @php
                            $query = array_merge(request()->except(['sort', 'page']), ['sort' => $sortKey]);
                            $sortUrl = url()->current() . '?' . http_build_query($query);
                        @endphp
                        <a class="mt_schedule_sort_btn {{ request('sort') === $sortKey ? 'active' : '' }}" href="{{ $sortUrl }}">
                            {{ $sortLabel }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @include('schedule.partials.popular-routes', ['popularRoutes' => $popularRoutes ?? collect()])

        <section class="mt_schedule_seo">
            <div class="container">
                <div class="mt_schedule_seo_title">Автобус {{ $routeHeading ? $routeHeading : __('alias_schedule') }}</div>
                <div class="mt_schedule_seo_text">
                    <p>
                        Сполучення між містами щодня залишається затребуваним, а розклад допомагає
                        обрати зручний час для подорожі. Ми показуємо найактуальніші рейси та даємо
                        можливість швидко забронювати квиток онлайн.
                    </p>
                    <p>
                        Якщо ви шукаєте маршрути між популярними напрямками, скористайтесь фільтром,
                        сортуванням та підбором рейсів. Під час бронювання можна одразу обрати кількість
                        пасажирів і переглянути ціни на поїздки.
                    </p>
                </div>

                <div class="mt_schedule_routes">
                    <div class="routes_title h2_title">
                        @lang('dictionary.MSG__NASHI_NAPRAVLENNYA')
                    </div>
                    <div class="routes_subtitle par">
                        @lang('dictionary.MSG__BEZLICH_VARIANTIV_AVTOBUSNIH_POZDOK_DLYA_VASHIH_PODOROZHEJ_U_BUDI')
                    </div>

                    <div class="routes_lists_wrapper">
                        <div class="route_list_block">
                            <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_KRANI')</div>
                            <div class="route_list">
                                @foreach($countries as $country)
                                    <div>
                                        <a href="{{ route('schedule') }}?country={{ $country->id }}"
                                           class="shedule_link">{{ $country->title }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="route_list_block">
                            <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_ROZKLAD')</div>
                            <div class="route_list">
                                @foreach($cities as $city)
                                    <div>
                                        <a href="{{ route('schedule') }}?city={{ $city->id }}"
                                           class="shedule_link">{{ $city->title }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="route_list_block">
                            <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_MIZHNARODNI')</div>
                            <div class="route_list">
                                @php $printedRoutes = []; @endphp
                                @foreach($internationalRoutes as $route)
                                    @php $routeString = $route->departure_city_id . '_' . $route->arrival_city_id; @endphp
                                    @if(!in_array($routeString, $printedRoutes))
                                        <div>
                                            <a href="{{ route('schedule') }}?departure={{ $route->departure_city_id }}&arrival={{ $route->arrival_city_id }}"
                                               class="shedule_link">{{ $route->departure_city }} → {{ $route->arrival_city }}</a>
                                        </div>
                                        @php $printedRoutes[] = $routeString; @endphp
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="route_list_block">
                            <div class="route_list_title h3_title">@lang('dictionary.MSG_ALL_VNUTRISHNI')</div>
                            <div class="route_list">
                                @php $printedRoutes = []; @endphp
                                @foreach($domesticRoutes as $route)
                                    @php $routeString = $route->departure_city_id . '_' . $route->arrival_city_id; @endphp
                                    @if(!in_array($routeString, $printedRoutes))
                                        <div>
                                            <a href="{{ route('schedule') }}?departure={{ $route->departure_city_id }}&arrival={{ $route->arrival_city_id }}"
                                               class="shedule_link">{{ $route->departure_city }} → {{ $route->arrival_city }}</a>
                                        </div>
                                        @php $printedRoutes[] = $routeString; @endphp
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
