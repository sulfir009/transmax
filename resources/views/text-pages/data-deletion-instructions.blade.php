@extends('text-pages.base')

{{-- Data Deletion Instructions specific customizations --}}

@section('page-styles')
    @parent
    <style>
        /* Additional styles for data deletion instructions */
        .deletion-steps {
            counter-reset: step-counter;
            margin: 30px 0;
        }
        
        .deletion-steps .step {
            position: relative;
            padding-left: 50px;
            margin-bottom: 25px;
            counter-increment: step-counter;
        }
        
        .deletion-steps .step::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #0066cc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .deletion-warning {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
        
        .deletion-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #0c5460;
        }
        
        .deletion-code {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }
        
        .deletion-contact {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 20px;
            margin: 30px 0;
        }
        
        .deletion-contact h3 {
            margin-top: 0;
            color: #856404;
        }
        
        .deletion-contact a {
            color: #0066cc;
            font-weight: 600;
        }
    </style>
@endsection

@section('page-scripts')
    @parent
    <script>
        $(document).ready(function() {
            // Copy code blocks on click
            $('.deletion-code').on('click', function() {
                var $temp = $("<textarea>");
                $("body").append($temp);
                $temp.val($(this).text()).select();
                document.execCommand("copy");
                $temp.remove();
                
                // Show notification
                var $notification = $('<div class="copy-notification">Скопировано!</div>');
                $('body').append($notification);
                $notification.fadeIn().delay(1000).fadeOut(function() {
                    $(this).remove();
                });
            });
        });
    </script>
    
    <style>
        .copy-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
            z-index: 9999;
        }
        
        .deletion-code {
            cursor: pointer;
            position: relative;
        }
        
        .deletion-code:hover::after {
            content: "Нажмите для копирования";
            position: absolute;
            bottom: -25px;
            left: 0;
            font-size: 12px;
            color: #666;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
@endsection
