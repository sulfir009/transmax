@extends('layout.app')

@section('page-styles')
    <link rel="stylesheet" href="{{ mix('css/legacy/style_table.css') }}">
    <link rel="stylesheet" href="{{ mix('css/responsive.css') }}">
@endsection

@section('content')
    <div class="content">
        <div class="purchase_steps_wrapper">
            <div class="tabs_links_container">
                <div class="purchase_steps">
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">1. @lang('dictionary.MSG_MSG_TICKETS_VIBIR_AVTOBUSA')</div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title active">2. @lang('data_ticket_page')</div>
                    </div>
                    <div class="purchase_step_wrapper">
                        <div class="purchase_step h4_title">3. @lang('payment_ticket_page')</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="page_content_wrapper">
            <div class="container">
                <div class="ticket_page_title h2_title">
                    @lang('dictionary.MSG_MSG_TICKETS_VVEDITE_DANNYE_PASSAZHIROV')
                </div>
                
                <div class="passenger_data_form">
                    {{-- Здесь будет форма для ввода данных пассажиров --}}
                    <p>Страница ввода данных пассажиров (в разработке)</p>
                    
                    <div class="form_buttons">
                        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
                            @lang('dictionary.MSG_MSG_TICKETS_NAZAD')
                        </a>
                        <a href="{{ route('tickets.payment') }}" class="btn btn-primary">
                            @lang('dictionary.MSG_MSG_TICKETS_DALEE')
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
    <script>
        // JavaScript для страницы данных пассажиров
    </script>
@endsection