@extends('layout.app')

@section('page-styles')
<style>
    .checkout-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        text-align: center;
    }
    .payment-info {
        margin: 20px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    .payment-info p {
        margin: 10px 0;
    }
    .liqpay-form {
        margin: 30px 0;
    }
</style>
@endsection

@section('content')
<div class="checkout-container">
    <h1>Подтверждение платежа</h1>
    
    <div class="payment-info">
        <p><strong>Номер заказа:</strong> {{ $payment->order_id }}</p>
        <p><strong>Сумма:</strong> {{ $payment->amount }} {{ $payment->currency }}</p>
        <p><strong>Описание:</strong> {{ $payment->description }}</p>
    </div>

    <div class="liqpay-form">
        {!! $form !!}
    </div>

    <p>Вы будете перенаправлены на защищенную страницу оплаты LiqPay</p>
</div>
@endsection
