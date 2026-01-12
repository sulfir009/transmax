@extends('layout.app')

@section('page-styles')
<style>
    .result-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    .status-icon {
        font-size: 60px;
        margin: 20px 0;
    }
    .status-success {
        color: #28a745;
    }
    .status-error {
        color: #dc3545;
    }
    .status-pending {
        color: #ffc107;
    }
    .payment-details {
        margin: 30px 0;
        text-align: left;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
    }
    .payment-details dt {
        font-weight: bold;
        margin-top: 10px;
    }
    .payment-details dd {
        margin-left: 0;
        margin-bottom: 10px;
    }
    .action-buttons {
        margin-top: 30px;
    }
    .btn {
        display: inline-block;
        padding: 10px 20px;
        margin: 0 10px;
        background: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background 0.3s;
    }
    .btn:hover {
        background: #0056b3;
    }
</style>
@endsection

@section('content')
<div class="result-container">
    @if($payment->isPaid())
        <div class="status-icon status-success">✓</div>
        <h1>Платеж успешно проведен!</h1>
    @elseif($payment->isFailed())
        <div class="status-icon status-error">✗</div>
        <h1>Платеж не удался</h1>
    @else
        <div class="status-icon status-pending">⏳</div>
        <h1>Платеж обрабатывается</h1>
    @endif

    <dl class="payment-details">
        <dt>Номер заказа:</dt>
        <dd>{{ $payment->order_id }}</dd>
        
        <dt>Сумма:</dt>
        <dd>{{ $payment->amount }} {{ $payment->currency }}</dd>
        
        <dt>Статус:</dt>
        <dd>{{ $payment->status }}</dd>
        
        @if($payment->payment_id)
            <dt>ID платежа LiqPay:</dt>
            <dd>{{ $payment->payment_id }}</dd>
        @endif
        
        @if($payment->sender_card_mask)
            <dt>Карта:</dt>
            <dd>{{ $payment->sender_card_mask }}</dd>
        @endif
        
        @if($payment->paid_at)
            <dt>Дата оплаты:</dt>
            <dd>{{ $payment->paid_at->format('d.m.Y H:i:s') }}</dd>
        @endif
    </dl>

    <div class="action-buttons">
        <a href="{{ route('payment.index') }}" class="btn">Новый платеж</a>
        <a href="{{ route('payment.history') }}" class="btn">История платежей</a>
    </div>
</div>

@if($payment->isPending())
<script>
// Автообновление статуса для pending платежей
setInterval(function() {
    fetch('{{ route('payment.status', $payment->order_id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.payment.is_paid || data.payment.is_failed) {
                window.location.reload();
            }
        });
}, 5000); // каждые 5 секунд
</script>
@endif
@endsection
