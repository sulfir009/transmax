<div class="flex-row gap-30 numbers_wrapper">
    <div class="col-xxl-6 col-xs-12">
        <div class="index_numbers">
            <div class="index_numbers_block_title h2_title">{!! $numbersInfo['title'] !!}</div>
            <div class="number_blocks_wrapper">
                @if(!empty($numbersInfo['number1']))
                    <div class="number_block">
                        <div class="number_block_title h3_title">{!! $numbersInfo['text1'] !!}</div>
                        <div class="number_block_value">
                            <div class="index_number_wrapper flex_ac">
                                <div class="index_number h2_title">{!! $numbersInfo['number1'] !!}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($numbersInfo['number2']))
                    <div class="number_block">
                        <div class="number_block_title h3_title">{!! $numbersInfo['text2'] !!}</div>
                        <div class="number_block_value">
                            <div class="index_number_wrapper flex_ac">
                                <div class="index_number h2_title">{!! $numbersInfo['number2'] !!}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($numbersInfo['number3']))
                    <div class="number_block">
                        <div class="number_block_title h3_title">{!! $numbersInfo['text3'] !!}</div>
                        <div class="number_block_value">
                            <div class="index_number_wrapper flex_ac">
                                <div class="index_number h2_title">{!! $numbersInfo['number3'] !!}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($numbersInfo['number4']))
                    <div class="number_block">
                        <div class="number_block_title h3_title">{!! $numbersInfo['text4'] !!}</div>
                        <div class="number_block_value">
                            <div class="index_number_wrapper flex_ac">
                                <div class="index_number h2_title">{!! $numbersInfo['number4'] !!}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <a href="{{ route('kontakti') }}" class="h4_title buy_ticket_link blue_btn">
                @lang('MSG__ZAMOVITI_KVITOK')
            </a>
        </div>
    </div>
    <div class="col-xxl-5 col-xs-12">
        <div class="index_map">
            <img src="{!! asset('images/legacy/upload/wellcome/' . $numbersInfo['image']) !!}"
                 alt="map"
                 class="fit_img">
        </div>
    </div>
</div>
