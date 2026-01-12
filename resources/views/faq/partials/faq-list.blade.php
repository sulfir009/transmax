@forelse($faqs as $faq)
    <div class="question_wrapper" id="faq-{{ $faq->id ?? $loop->index }}">
        <div class="question h4_title" onclick="toggleAnswer(this)">
            {{ $faq->question }}
            <button class="toggle_answer_btn"></button>
        </div>
        <div class="answer par">
            @if(isset($faq->answer_html))
                {!! $faq->answer_html !!}
            @else
                {{ $faq->answer }}
            @endif
        </div>
    </div>
@empty
    <div class="no_results">
        <p>@lang('dictionary.MSG_FAQ_NO_RESULTS')</p>
    </div>
@endforelse
