@extends('text-pages.base')

{{-- Return Conditions specific customizations can be added here if needed --}}

@section('page-styles')
    @parent
    <style>
        /* Additional styles specific to return conditions page */
        .return-list {
            margin: 20px 0;
            padding-left: 20px;
        }
        
        .return-list li {
            margin-bottom: 15px;
            line-height: 1.8;
        }
        
        .return-warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        
        .return-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #0c5460;
        }
    </style>
@endsection
