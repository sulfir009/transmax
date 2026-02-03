@extends('layout.app')

@section('page-styles')
    <style>
        .faq_txt_wrapper {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        
        .faq_info_blocks {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .faq_txt_block {
            padding: 20px;
        }
        
        .faq_block_title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        
        .faq_block_txt {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
            margin-bottom: 30px;
        }
        
        .faq_booking_link {
            display: inline-flex;
            align-items: center;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .faq_booking_link:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }
        
        .faq_img {
            width: 100%;
            height: 100%;
            min-height: 400px;
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .faq_img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .faq_wrapper {
            padding: 60px 0;
        }
        
        .faq {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .question_wrapper {
            margin-bottom: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background-color: white;
        }
        
        .question {
            padding: 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            transition: background-color 0.3s;
            font-size: 18px;
            font-weight: 500;
        }
        
        .question:hover {
            background-color: #e9ecef;
        }
        
        .toggle_answer_btn {
            width: 24px;
            height: 24px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center;
            background-size: 100%;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
            flex-shrink: 0;
        }
        
        .toggle_answer_btn.active {
            transform: rotate(180deg);
        }
        
        .answer {
            padding: 20px;
            display: none;
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            background-color: white;
        }
        
        .answer p {
            margin-bottom: 15px;
        }
        
        .answer p:last-child {
            margin-bottom: 0;
        }
        
        .answer ul,
        .answer ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .answer li {
            margin-bottom: 8px;
        }
        
        .answer a {
            color: #007bff;
            text-decoration: none;
        }
        
        .answer a:hover {
            text-decoration: underline;
        }
        
        .faq_search_wrapper {
            margin-bottom: 40px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .faq_search_input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .faq_search_input:focus {
            border-color: #007bff;
        }
        
        .no_results {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .faq_info_blocks {
                flex-direction: column;
            }
            
            .faq_block_title {
                font-size: 24px;
            }
            
            .question {
                font-size: 16px;
                padding: 15px;
            }
            
            .answer {
                padding: 15px;
                font-size: 14px;
            }
            
            .faq_img {
                min-height: 250px;
            }
        }
    </style>
@endsection

@section('content')
<div class="content">
    <div class="page_content_wrapper">
        @if($faqInfo)
        <div class="faq_txt_wrapper">
            <div class="container">
                <div class="flex-row gap-30 faq_info_blocks">
                    <div class="col-xl-6">
                        <div class="faq_txt_block">
                            <div class="faq_block_title h2_title">{{ $faqInfo['title'] }}</div>
                            <div class="faq_block_txt par">
                                {!! $faqInfo['text'] !!}
                            </div>
                            <a href="{{ route('booking.index') }}" class="faq_booking_link h4_title flex_ac blue_btn">
                                @lang('dictionary.MSG___ZABRONYUVATI_BILET')
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-6">
                        <div class="faq_img">
                            @if($faqInfo['image'])
                                <img src="{{ asset('images/legacy/upload/wellcome/' . $faqInfo['image']) }}" alt="faq_img" class="fit_img">
                            @else
                                <img src="{{ asset('images/placeholder-faq.jpg') }}" alt="faq_img" class="fit_img">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="faq_wrapper">
            <div class="container">
                {{-- Search input (optional) --}}
                <div class="faq_search_wrapper" style="display: none;">
                    <input 
                        type="text" 
                        class="faq_search_input" 
                        placeholder="@lang('dictionary.MSG_FAQ_SEARCH_PLACEHOLDER')" 
                        id="faq_search"
                    >
                </div>

                <div class="faq" id="faq_list">
                    @include('faq.partials.faq-list', ['faqs' => $faqs])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    function toggleAnswer(item) {
        $(item).next().slideToggle();
        $(item).find('.toggle_answer_btn').toggleClass('active');
    }

    // Optional: Search functionality
    $(document).ready(function() {
        let searchTimeout;
        
        $('#faq_search').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val();
            
            searchTimeout = setTimeout(function() {
                searchFaqs(query);
            }, 300);
        });
    });

    function searchFaqs(query) {
        $.ajax({
            url: '{{ route("faq.search") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                query: query
            },
            success: function(response) {
                if (response.success) {
                    $('#faq_list').html(response.html);
                }
            },
            error: function() {
                console.error('Error searching FAQs');
            }
        });
    }

    // Add smooth scrolling for anchor links
    $(document).ready(function() {
        if (window.location.hash) {
            const hash = window.location.hash;
            const target = $(hash);
            
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 500);
                
                // Open the answer if it's a FAQ item
                const question = target.find('.question');
                if (question.length && !question.next('.answer').is(':visible')) {
                    toggleAnswer(question[0]);
                }
            }
        }
    });

    // Track FAQ opens for analytics (optional)
    $('.question').on('click', function() {
        const questionText = $(this).text().trim();
        
        // Send analytics event if you have analytics set up
        if (typeof gtag !== 'undefined') {
            gtag('event', 'faq_open', {
                'event_category': 'FAQ',
                'event_label': questionText
            });
        }
    });
</script>

{{-- Structured data for SEO --}}
@if(isset($faqs) && $faqs->count() > 0)
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        @foreach($faqs as $index => $faq)
        {
            "@type": "Question",
            "name": "{{ addslashes($faq->question) }}",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "{{ addslashes(strip_tags($faq->answer)) }}"
            }
        }@if(!$loop->last),@endif
        @endforeach
    ]
}
</script>
@endif
@endsection
