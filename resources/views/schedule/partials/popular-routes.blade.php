@php
    $popularRoutes = $popularRoutes ?? collect();
    if (is_array($popularRoutes)) {
        $popularRoutes = collect($popularRoutes);
    }
@endphp

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
