@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('css/ticket_filter_hero.css') }}?v=1">
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

        .mt_schedule_scope .mt_schedule_popular {
            padding: 40px 0 20px;
        }

        .mt_schedule_scope .mt_schedule_popular_title {
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .mt_schedule_scope .mt_schedule_popular_grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .mt_schedule_scope .mt_schedule_popular_card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 26px rgba(20, 24, 57, 0.08);
            padding: 18px 20px;
        }

        .mt_schedule_scope .mt_schedule_popular_card_title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .mt_schedule_scope .mt_schedule_popular_list {
            display: grid;
            gap: 6px;

            /* ✅ чтобы "все маршруты" не делали карточку бесконечной */
            max-height: 320px;
            overflow: auto;
            padding-right: 6px;
        }

        .mt_schedule_scope .mt_schedule_popular_item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 13px;
        }

        .mt_schedule_scope .mt_schedule_popular_item a {
            color: #ff7a00;
            text-decoration: none;
        }

        .mt_schedule_scope .mt_schedule_popular_item a:hover {
            text-decoration: underline;
        }

        .mt_schedule_scope .mt_schedule_popular_price {
            font-weight: 600;
            color: #2c3163;
            white-space: nowrap;
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

        /* ✅ на больших экранах 3 колонки удобнее */
        @media (min-width: 1200px) {
            .mt_schedule_scope .mt_schedule_popular_grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .mt_schedule_scope .mt_schedule_popular_grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
@php
    $routeHeading = null;
    if (!empty($filters['departure']) && !empty($filters['arrival']) && $routes->count() > 0) {
        $collection = $routes->getCollection();
        $firstItem = $collection->first();
        $firstRoute = is_array($firstItem) ? reset($firstItem) : $firstItem;
        if ($firstRoute) {
            $routeHeading = mb_strtoupper($firstRoute->departure_city . ' — ' . $firstRoute->arrival_city, 'UTF-8');
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

    /**
     * ✅ ВАЖНО:
     * $popularRoutes теперь приходит из контроллера.
     * Тут только страховка, чтобы Blade не упал, если переменную не передали.
     */
    $popularRoutes = $popularRoutes ?? collect();
    if (is_array($popularRoutes)) {
        $popularRoutes = collect($popularRoutes);
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
        'formAction' => route('tickets.index'),
    ])

    <div class="mt_schedule_body">
        <div class="container">
            <nav class="mt_schedule_breadcrumbs">
                <a href="{{ \App\Helpers\LocaleHelper::localizedRoute('schedule') }}">Квитки на автобус</a>
                <span> / </span>
                <span>Розклад</span>
            </nav>

            <div class="mt_schedule_kicker">Квитки на автобус</div>
            <h1 class="mt_schedule_route">{{ $routeHeading ?? __('alias_schedule') }}</h1>
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

        <section class="mt_schedule_popular">
            <div class="container">
                <div class="mt_schedule_popular_title">ПОПУЛЯРНІ РЕЙСИ</div>

                @if($popularRoutes->isEmpty())
                    <p class="mt_schedule_intro">@lang('dictionary.MSG_MSG_SCHEDULE_NET_MARSHRUTOV')</p>
                @else
                    <div class="mt_schedule_popular_grid">
                        @foreach($popularRoutes as $popular)
                            @php
                                $items = collect($popular['items'] ?? []);
                            @endphp

                            <div class="mt_schedule_popular_card">
                                <div class="mt_schedule_popular_card_title">{{ $popular['title'] ?? '' }}</div>

                                <div class="mt_schedule_popular_list">
                                    @foreach($items as $item)
                                        <div class="mt_schedule_popular_item">
                                            <a href="{{ $item['url'] ?? '#' }}">{{ $item['label'] ?? '' }}</a>
                                            <span class="mt_schedule_popular_price">{{ $item['price'] ?? '—' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

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
