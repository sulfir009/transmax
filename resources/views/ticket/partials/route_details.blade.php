<div class="route_details_content">
    <div class="route_details_header">
        <h3 class="route_details_title">@lang('dictionary.MSG_MSG_TICKETS_MARSHRUT_POEZDKI')</h3>
        <button class="close_popup_btn" onclick="toggleRouteDetails('0')">
            <img src="{{ asset('images/legacy/common/close.svg') }}" alt="close">
        </button>
    </div>
    
    <div class="route_details_body">
        <div class="route_stops_list">
            @foreach($stops as $index => $stop)
                @php
                    $isStart = $stop->stop_id == $departureId;
                    $isEnd = $stop->stop_id == $arrivalId;
                    $stopInfo = \DB::table(DB_PREFIX . '_cities')
                        ->select('title_' . app()->getLocale() . ' as title')
                        ->where('id', $stop->stop_id)
                        ->first();
                @endphp
                
                <div class="route_stop_item {{ $isStart ? 'departure_stop' : '' }} {{ $isEnd ? 'arrival_stop' : '' }}">
                    <div class="stop_time">
                        @if($stop->departure_time)
                            <span class="departure_time">{{ date('H:i', strtotime($stop->departure_time)) }}</span>
                        @endif
                        @if($stop->arrival_time && $stop->departure_time && $stop->arrival_time != $stop->departure_time)
                            <span class="time_separator">-</span>
                            <span class="arrival_time">{{ date('H:i', strtotime($stop->arrival_time)) }}</span>
                        @endif
                    </div>
                    
                    <div class="stop_info">
                        <div class="stop_name">{{ $stopInfo->title ?? '' }}</div>
                        @if($isStart)
                            <div class="stop_badge departure_badge">@lang('dictionary.MSG_MSG_TICKETS_POSADKA')</div>
                        @endif
                        @if($isEnd)
                            <div class="stop_badge arrival_badge">@lang('dictionary.MSG_MSG_TICKETS_VYSADKA')</div>
                        @endif
                    </div>
                    
                    @if($stop->arrival_day > 0)
                        <div class="stop_day">+{{ $stop->arrival_day }} @lang('dictionary.MSG_MSG_TICKETS_DEN')</div>
                    @endif
                </div>
                
                @if(!$loop->last)
                    <div class="route_stop_connector"></div>
                @endif
            @endforeach
        </div>
        
        <div class="route_details_footer">
            <button class="blue_btn confirm_route_btn" onclick="toggleRouteDetails('0')">
                @lang('dictionary.MSG_MSG_TICKETS_ZAKRYT')
            </button>
        </div>
    </div>
</div>