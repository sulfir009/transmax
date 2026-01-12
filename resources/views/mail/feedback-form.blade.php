<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новое сообщение с формы обратной связи</title>
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
            background-color: #0066cc;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .field {
            margin-bottom: 15px;
            padding: 10px;
            background-color: white;
            border-left: 3px solid #0066cc;
        }
        .field-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .field-value {
            color: #333;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Новое сообщение с формы обратной связи</h2>
    </div>
    
    <div class="content">
        <div class="field">
            <div class="field-label">Имя:</div>
            <div class="field-value">{{ $data['name'] }}</div>
        </div>
        
        @if(!empty($data['email']))
        <div class="field">
            <div class="field-label">Email:</div>
            <div class="field-value">
                <a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a>
            </div>
        </div>
        @endif
        
        @if(!empty($data['phone']))
        <div class="field">
            <div class="field-label">Телефон:</div>
            <div class="field-value">
                <a href="tel:{{ $data['phone'] }}">{{ $data['phone'] }}</a>
            </div>
        </div>
        @endif
        
        <div class="field">
            <div class="field-label">Сообщение:</div>
            <div class="field-value">{!! nl2br(e($data['message'])) !!}</div>
        </div>
        
        <div class="field">
            <div class="field-label">Дата отправки:</div>
            <div class="field-value">{{ $data['date'] }}</div>
        </div>
    </div>
    
    <div class="footer">
        <p>Это письмо было отправлено автоматически с сайта {{ config('app.name') }}</p>
    </div>
</body>
</html>
