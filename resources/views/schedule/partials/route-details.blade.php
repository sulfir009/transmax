<div class="route_details_popup_content">
    <div class="route_details_header">
        <h3>@lang('dictionary.MSG_MSG_SCHEDULE_GRAFIK_I_RASPISANIE_REJSA')</h3>
        <button class="close_popup_btn" onclick="toggleRouteDetailsSchedule('0')">
            <span>&times;</span>
        </button>
    </div>
    
    <div class="route_details_body">
        @if(isset($details['tour']) && $details['tour'])
            <div class="route_info">
                <div class="route_title">
                    <strong>@lang('dictionary.MSG_MSG_SCHEDULE_MARSHRUT'):</strong>
                    {{ $details['tour']->departureCityRelation->getTitle(app()->getLocale()) }} - 
                    {{ $details['tour']->arrivalCityRelation->getTitle(app()->getLocale()) }}
                </div>
                
                @if($details['tour']->busRelation)
                    <div class="bus_info">
                        <strong>@lang('dictionary.MSG_MSG_SCHEDULE_AVTOBUS'):</strong>
                        {{ $details['tour']->busRelation->getTitle(app()->getLocale()) }}
                    </div>
                @endif
            </div>
            
            @if(isset($details['stops']) && $details['stops']->count() > 0)
                <div class="stops_list">
                    <h4>@lang('dictionary.MSG_MSG_SCHEDULE_OSTANOVKI')</h4>
                    <table class="stops_table">
                        <thead>
                            <tr>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_OSTANOVKA')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_VREMYA_PRIBYTIYA')</th>
                                <th>@lang('dictionary.MSG_MSG_SCHEDULE_VREMYA_OTPRAVLENIYA')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details['stops'] as $stop)
                                <tr>
                                    <td>{{ optional($stop->stopCity)->getTitle(app()->getLocale()) ?? '' }}</td>
                                    <td>{{ $stop->arrival_time ?? '-' }}</td>
                                    <td>{{ $stop->departure_time ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            <p>@lang('dictionary.MSG_MSG_SCHEDULE_NET_DANNYH')</p>
        @endif
    </div>
</div>
