@extends('layout.app')

@section('title', $pageData['page_title'] ?? $pageData['title'] ?? '')
@section('meta_description', $pageData['meta_description'] ?? '')
@section('meta_keywords', $pageData['meta_keywords'] ?? '')

@section('page-styles')
    <style>
        .text-page {
            padding: 60px 0;
            min-height: 500px;
        }
        
        .text-page-content {
            font-size: 16px;
            line-height: 1.8;
            color: #333;
        }
        
        .text-page-content h1 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #1a1a1a;
        }
        
        .text-page-content h2 {
            font-size: 28px;
            font-weight: 600;
            margin-top: 30px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        
        .text-page-content h3 {
            font-size: 22px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .text-page-content p {
            margin-bottom: 20px;
        }
        
        .text-page-content ul,
        .text-page-content ol {
            margin-bottom: 20px;
            padding-left: 30px;
        }
        
        .text-page-content li {
            margin-bottom: 10px;
        }
        
        .text-page-content strong {
            font-weight: 600;
            color: #333;
        }
        
        .text-page-content a {
            color: #0066cc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .text-page-content a:hover {
            color: #0052a3;
            text-decoration: underline;
        }
        
        .text-page-content blockquote {
            border-left: 4px solid #0066cc;
            padding-left: 20px;
            margin: 20px 0;
            font-style: italic;
            color: #666;
        }
        
        .text-page-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .text-page-content table th {
            background-color: #f5f5f5;
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: 600;
        }
        
        .text-page-content table td {
            padding: 12px;
            border: 1px solid #ddd;
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <div class="text-page">
            <div class="container">
                <div class="text-page-content">
                    {!! $pageData['text'] !!}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        $(document).ready(function() {
            // Add smooth scrolling for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
            
            // Add target="_blank" for external links
            $('.text-page-content a').each(function() {
                var href = $(this).attr('href');
                if (href && href.indexOf('http') === 0 && href.indexOf(window.location.hostname) === -1) {
                    $(this).attr('target', '_blank');
                    $(this).attr('rel', 'noopener noreferrer');
                }
            });
        });
    </script>
@endsection
