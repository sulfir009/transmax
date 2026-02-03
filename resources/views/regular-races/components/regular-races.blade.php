@foreach($regularRaces as $alias => $races)
    @if ($races->isEmpty() || $stations[$alias]->isEmpty())
        @continue
    @endif
    <div class="text_above_block">
        <img src="{{ asset('images/legacy/' . $alias . '.png') }}" alt="light">
        <h2 class="text_above">@lang('reqular_race_' . $alias)</h2>
    </div>
    <div class="row">
        @foreach($races as $race)
            @if($stopId > 0 && $race->stops->first()->stop_id != $stopId)
                @continue
            @endif
            <div class="col-md-6">
                <div class="block">
                    <div class="bus_image_table">
                        <img src="{{ asset('images/legacy/bus.png') }}" alt="bus" class="img-fluid">
                        <h3 class="overlay-image-up">{{ $race->departure }}
                            - {{ $race->arrive }}</h3>
                        @if (\App\Service\Site::lang() == 'en')
                            <h3 class="overlay-image-down">{{ collect($race)->get('days_en', '') }}</h3>
                        @elseif( \App\Service\Site::lang() == 'ua')
                            <h3 class="overlay-image-down">{{ collect($race)->get('days_ua', '') }}</h3>
                        @else
                            <h3 class="overlay-image-down">{{ collect($race)->get('days_ru', '_') }}</h3>
                        @endif
                    </div>
                    <ul>
                        @foreach($race->stops as $stop)
                            <li>{!! date('H:i',strtotime($stop->arrival_time)) !!} — {!! $stop->stopCity !!} <a
                                    href="#"> {!! $stop->stopTitle !!}</a></li>
                        @endforeach
                    </ul>
                    <div class="btn_line">
                        <button class="block_table_btn buy-online-btn" data-days="{{ $race->days }}" data-arrival="{{ $race->arrivalId }}" data-departure="{{ $race->departureId }}" data-redirect="{{ route('tickets.index') }}">@lang('buy_online')</button>
                        <a href="#form-callback-reserve" class="block_table_btn book-btn">@lang('reserve')</a>
                    </div>
                </div>
            </div>
@endforeach
    </div>
@endforeach
