<div class="advantages_slider_wrapper">
    <div class="advantages_slider">
        @foreach($advantages as $advantage)
            <div class="advantage_slide">
                <div class="advantage_slide_content">
                    <div class="advantage_img">
                        <img src="{{ asset('images/legacy/upload/advantage/' . $advantage->image) }}"
                             alt="advantage"
                             class="fit_img">
                    </div>
                    <div class="advantage_description">
                        <div class="advantage_title h2_title">
                                <p>{!! $advantage->title !!}</p>
                        </div>
                        <div class="advantage_txt par">
                                <p>{!! $advantage->preview !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="advantages_slider_nav slick_slider_nav"></div>
</div>
