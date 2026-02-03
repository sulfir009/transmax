<div class="route_prices_popup_content">
    <div class="route_prices_header">
        <h3>@lang('dictionary.MSG_MSG_SCHEDULE_PRICE_TABLE')</h3>
        <button class="close_popup_btn" onclick="toggleRoutePricesSchedule('0')">
            <span>&times;</span>
        </button>
    </div>
    
    <div class="route_prices_body">
        @if(isset($prices['tour']) && $prices['tour'])
            <div class="route_info">
                <div class="route_title">
                    <strong>@lang('dictionary.MSG_MSG_SCHEDULE_MARSHRUT'):</strong>
                    {{ $prices['tour']->departureCityRelation->getTitle(app()->getLocale()) }} - 
                    {{ $prices['tour']->arrivalCityRelation->getTitle(app()->getLocale()) }}
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
                                <tr @if($price->from_stop == $prices['departureId'] && $price->to_stop == $prices['arrivalId']) class="highlighted" @endif>
                                    <td>{{ $price->fromStop->getTitle(app()->getLocale()) ?? '' }}</td>
                                    <td>{{ $price->toStop->getTitle(app()->getLocale()) ?? '' }}</td>
                                    <td>{{ number_format($price->price, 2) }} @lang('dictionary.MSG_MSG_SCHEDULE_CURRENCY')</td>
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
