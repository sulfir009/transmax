@extends('layout.app')

@section('page-styles')
<style>
    .history-container {
        max-width: 1000px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .history-table th,
    .history-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .history-table th {
        background: #f8f9fa;
        font-weight: bold;
    }
    .history-table tr:hover {
        background: #f8f9fa;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-success {
        background: #d4edda;
        color: #155724;
    }
    .status-error {
        background: #f8d7da;
        color: #721c24;
    }
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    .action-link {
        color: #007bff;
        text-decoration: none;
        margin: 0 5px;
    }
    .action-link:hover {
        text-decoration: underline;
    }
    .pagination {
        margin-top: 30px;
        text-align: center;
    }
    .no-payments {
        text-align: center;
        padding: 50px;
        color: #6c757d;
    }
</style>
@endsection

@section('content')
<div class="history-container">
    <h1>История платежей</h1>
    
    <div style="margin-bottom: 20px;">
        <a href="{{ route('payment.index') }}" class="action-link">← Новый платеж</a>
    </div>

    @if($payments->count() > 0)
        <table class="history-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Номер заказа</th>
                    <th>Описание</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at->format('d.m.Y H:i') }}</td>
                        <td>{{ $payment->order_id }}</td>
                        <td>{{ $payment->description }}</td>
                        <td>{{ $payment->amount }} {{ $payment->currency }}</td>
                        <td>
                            @if($payment->isPaid())
                                <span class="status-badge status-success">Оплачено</span>
                            @elseif($payment->isFailed())
                                <span class="status-badge status-error">Ошибка</span>
                            @else
                                <span class="status-badge status-pending">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="#" class="action-link" onclick="checkStatus('{{ $payment->order_id }}')">Статус</a>
                            @if($payment->isPaid())
                                <a href="#" class="action-link" onclick="refund('{{ $payment->order_id }}')">Возврат</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">
            {{ $payments->links() }}
        </div>
    @else
        <div class="no-payments">
            <p>У вас пока нет платежей</p>
            <a href="{{ route('payment.index') }}">Создать первый платеж</a>
        </div>
    @endif
</div>
@endsection

@section('page-scripts')
<script>
function checkStatus(orderId) {
    fetch(`/payment/status/${orderId}`)
        .then(response => response.json())
        .then(data => {
            alert(`Статус платежа: ${data.payment.status}\nОплачен: ${data.payment.is_paid ? 'Да' : 'Нет'}`);
            if (data.payment.status !== '{{ $payment->status ?? '' }}') {
                window.location.reload();
            }
        });
}

function refund(orderId) {
    if (!confirm('Вы уверены, что хотите сделать возврат?')) {
        return;
    }
    
    const amount = prompt('Введите сумму возврата (оставьте пустым для полного возврата):');
    
    fetch(`/payment/refund/${orderId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Запрос на возврат отправлен');
        } else {
            alert('Ошибка: ' + data.message);
        }
    });
}
</script>
@endsection
