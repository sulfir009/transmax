@foreach($buses as $bus)
    <div class="bus flex-row gap-30">
        <div class="col-lg-6">
            <div class="bus_img">
                @foreach($bus['images'] as $busImage)
                    <img src="{{ asset('images/legacy/upload/buses/' . $busImage) }}" alt="bus" class="fit_img">
                @endforeach
            </div>
        </div>
        <div class="col-lg-6">
            <div class="bus_info">
                <div class="bus_title h2_title">
                    {{ $bus['title'] }}
                </div>
                <div class="bus_seats flex_ac h4_title">
                    @lang('MSG_MSG_BUSES_KILIKISTI_MISCI')
                    <span class="total_seats h2_title">
                        {{ $bus['seats_qty'] }}
                    </span>
                </div>
                <div class="bus_info_delimiter"></div>
                <div class="bus_options">
                    <div class="flex-row gap-30">
                        @foreach($bus['options'] as $option)
                            <div class="col-sm-4 col-xs-6">
                                <div class="bus_option flex_ac par">
                                    <div class="check_imitation"></div>
                                    {{ $option['title'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <button class="order_bus_link h4_title flex_ac blue_btn" 
                        onclick="toggleOrderBus('{{ $bus['id'] }}', {{ $bus['seats_qty'] }})">
                    @lang('MSG_MSG_BUSES_ZAMOVITI_AVTOBUS')
                </button>
            </div>
        </div>
    </div>
@endforeach
