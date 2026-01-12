<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение заказа билета</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .ticket-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .route-block {
            margin: 15px 0;
        }
        .route-point {
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        .route-point:last-child {
            border-bottom: none;
        }
        .price-block {
            background-color: #e8f4f8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .passengers-list {
            margin: 15px 0;
        }
        .passenger-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .passenger-item:last-child {
            border-bottom: none;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        strong {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Подтверждение заказа билета</h1>
    </div>

    <div class="content">
        <p>Здравствуйте, {{ $passengerData['name'] ?? '' }} {{ $passengerData['family_name'] ?? '' }}!</p>
        
        <p>Ваш заказ успешно оформлен. Ниже приведена информация о вашей поездке:</p>

        <div class="ticket-info">
            <h2>Информация о маршруте</h2>
            
            <div class="route-block">
                <div class="route-point">
                    <strong>Отправление:</strong><br>
                    {{ $ticketInfo['departure_city'] }} {{ $ticketInfo['departure_station'] }}<br>
                    Время: {{ date('H:i', strtotime($ticketInfo['departure_time'])) }}<br>
                    Дата: {{ $order['date'] }}
                </div>
                
                <div class="route-point">
                    <strong>Прибытие:</strong><br>
                    {{ $ticketInfo['arrival_city'] }} {{ $ticketInfo['arrival_station'] }}<br>
                    Время: {{ date('H:i', strtotime($ticketInfo['arrival_time'])) }}
                </div>
            </div>

            <div class="route-block">
                <strong>Автобус:</strong> {{ $ticketInfo['bus'] }}<br>
                <strong>Количество пассажиров:</strong> {{ $order['passengers'] }}
            </div>
        </div>

        @if(!empty($passengerData['passengers']))
        <div class="passengers-list">
            <h3>Данные пассажиров:</h3>
            @foreach($passengerData['passengers'] as $index => $passenger)
            <div class="passenger-item">
                <strong>Пассажир {{ $index + 1 }}:</strong><br>
                {{ $passenger['name'] ?? '' }} {{ $passenger['surname'] ?? '' }} {{ $passenger['patronymic'] ?? '' }}
                @if(!empty($passenger['birth_date']))
                <br>Дата рождения: {{ $passenger['birth_date'] }}
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <div class="price-block">
            <h3>Информация об оплате</h3>
            <strong>Цена за билет:</strong> {{ $ticketInfo['price'] }} грн<br>
            <strong>Общая стоимость:</strong> {{ $totalPrice }} грн<br>
            <strong>Статус:</strong> 
            @if(($order['paymethod'] ?? 'cash') === 'cash')
                Ожидает оплаты наличными
            @else
                Оплачено картой
            @endif
        </div>

        @if(($order['paymethod'] ?? 'cash') === 'cash')
        <div class="warning">
            <strong>Важно!</strong> Оплатите билет наличными при посадке в автобус. 
            Обязательно сохраните это письмо и предъявите его водителю.
        </div>
        @endif

        <div class="contact-info">
            <h3>Ваши контактные данные:</h3>
            <strong>Email:</strong> {{ $passengerData['email'] ?? '' }}<br>
            <strong>Телефон:</strong> +{{ $passengerData['phone_code'] ?? '' }} {{ $passengerData['phone'] ?? '' }}
        </div>

        <p style="margin-top: 30px;">
            Если у вас возникли вопросы, пожалуйста, свяжитесь с нашей службой поддержки.
        </p>

        <p>
            <strong>Желаем вам приятной поездки!</strong>
        </p>
    </div>

    <div class="footer">
        <p>Это письмо было отправлено автоматически. Пожалуйста, не отвечайте на него.</p>
        <p>&copy; {{ date('Y') }} Все права защищены.</p>
    </div>
</body>
</html>
