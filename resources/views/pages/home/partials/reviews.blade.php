<div class="reviews_block_title h2_title">
    {{ $dictionary['MSG_ALL_VIDGUKI'] ?? 'Відгуки' }}
</div>
</div>
<div class="slider_container">
    <div class="reviews_slider_wrapper">
        <div class="reviews_slider">
            @foreach($reviews as $review)
                <div class="review_slide">
                    <div class="review_slide_content shadow_block">
                        <div class="review_slide_icon">
                            <img src="{{ asset('images/legacy/common/review_icon.svg') }}" alt="review icon">
                        </div>
                        <div class="review_slide_txt par">
                            {{ $review->review }}
                        </div>
                        <div class="review_slide_reviewer_info flex_ac">
                            <div class="review_slider_reviewer_image">
                                <img src="{{ asset('images/legacy/upload/reviews/' . $review->image) }}" 
                                     alt="{{ $review->name }}">
                            </div>
                            <div class="review_slider_reviewer_name">
                                {{ $review->name }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="reviews_slider_nav slick_slider_nav"></div>
    </div>
</div>
