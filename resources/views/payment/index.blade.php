@extends('layout.app')

@section('page-styles')
<style>
    .payment-container {
        max-width: 600px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .payment-form {
        margin-top: 30px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    .btn-primary {
        background: #28a745;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn-primary:hover {
        background: #218838;
    }
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .test-mode-notice {
        background: #fff3cd;
        color: #856404;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #ffeaa7;
    }
</style>
@endsection

@section('content')
<div class="payment-container">
    <h1>Оплата через LiqPay</h1>
    
    @if(config('services.liqpay.sandbox'))
        <div class="test-mode-notice">
            <strong>Тестовый режим!</strong> Платежи не будут реальными. 
            Используйте тестовые карты из документации LiqPay.
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('payment.create') }}" method="POST" class="payment-form">
        @csrf
        
        <div class="form-group">
            <label for="amount" class="form-label">Сумма платежа ({{ config('services.liqpay.currency') }})</label>
            <input type="number" 
                   name="amount" 
                   id="amount" 
                   class="form-control" 
                   min="0.01" 
                   step="0.01" 
                   required
                   placeholder="100.00">
            @error('amount')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Описание платежа</label>
            <input type="text" 
                   name="description" 
                   id="description" 
                   class="form-control" 
                   required
                   placeholder="Оплата заказа">
            @error('description')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-primary">
            Перейти к оплате
        </button>
    </form>

    <div style="margin-top: 30px;">
        <h3>Или оплатить через AJAX (без перенаправления)</h3>
        <button onclick="payWithAjax()" class="btn-primary">
            Оплатить через AJAX
        </button>
    </div>

    <div style="margin-top: 20px;">
        <a href="{{ route('payment.history') }}">История платежей</a>
    </div>
</div>
@endsection

@section('page-scripts')
<script src="//static.liqpay.ua/libjs/checkout.js"></script>
<script>
function payWithAjax() {
    const amount = document.getElementById('amount').value;
    const description = document.getElementById('description').value;
    
    if (!amount || !description) {
        alert('Заполните все поля');
        return;
    }
    
    fetch('{{ route('payment.create') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            amount: amount,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            LiqPayCheckout.init({
                data: data.data,
                signature: data.signature,
                embedTo: "#liqpay_checkout",
                language: "{{ config('services.liqpay.language') }}",
                mode: "popup" // embed, popup
            }).on("liqpay.callback", function(data){
                console.log('Payment callback:', data);
                // Обновляем статус платежа
                checkPaymentStatus(data.order_id);
            }).on("liqpay.ready", function(data){
                console.log('LiqPay ready');
            }).on("liqpay.close", function(data){
                console.log('LiqPay closed');
            });
        }
    });
}

function checkPaymentStatus(orderId) {
    fetch(`/payment/status/${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.payment.is_paid) {
                alert('Платеж успешно проведен!');
                window.location.href = '{{ route('payment.history') }}';
            } else if (data.payment.is_failed) {
                alert('Платеж не удался');
            }
        });
}
</script>
@endsection
