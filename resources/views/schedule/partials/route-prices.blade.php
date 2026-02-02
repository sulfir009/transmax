<div class="route_prices_popup_content">
    <div class="route_prices_header">
        <h3>@lang('dictionary.MSG_MSG_SCHEDULE_PRICE_TABLE')</h3>
        <button class="close_popup_btn" onclick="toggleRoutePricesSchedule('0')">
            <span>&times;</span>
        </button>
    </div>

    <div class="route_prices_body">
        @if(isset($prices['tour']) && $prices['tour'])
            @php
                $tour = $prices['tour'];
                $locale = app()->getLocale();
            @endphp

            <div class="route_info">
                <div class="route_title">
                    <strong>@lang('dictionary.MSG_MSG_SCHEDULE_MARSHRUT'):</strong>
                    {{ optional($tour->departureCityRelation)->getTitle($locale) ?? '' }} -
                    {{ optional($tour->arrivalCityRelation)->getTitle($locale) ?? '' }}
                </div>
            </div>

            @if(isset($prices['prices']) && $prices['prices']->count() > 0)
                <div class="prices_list">
                    <h4>@lang('dictionary.MSG_MSG_SCHEDULE_CENY_NA_BILETY')</h4>
                    <table class="prices_table">
                        <thead>
                            <tr>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_OT')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_DO')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_CENA')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prices['prices'] as $price)
                                @php
                                    $isHighlighted = (
                                        ($price->from_stop ?? null) == ($prices['departureId'] ?? null)
                                        && ($price->to_stop ?? null) == ($prices['arrivalId'] ?? null)
                                    );
                                @endphp

                                <tr class="{{ $isHighlighted ? 'highlighted' : '' }}">
                                    <td>{{ optional($price->fromStop)->getTitle($locale) ?? '' }}</td>
                                    <td>{{ optional($price->toStop)->getTitle($locale) ?? '' }}</td>
                                    <td>{{ number_format((float)$price->price, 2, '.', ' ') }} @lang('dictionary.MSG_MSG_SCHEDULE_CURRENCY')</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p>@lang('dictionary.MSG_MSG_SCHEDULE_CENY_NE_NAJDENY')</p>
            @endif
        @else
            <p>@lang('dictionary.MSG_MSG_SCHEDULE_NET_DANNYH')</p>
        @endif
    </div>
</div>
