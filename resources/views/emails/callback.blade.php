<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Нова заявка на дзвінок</title>
</head>
<body>
<div>
    <h2>Нова заявка на дзвінок</h2>
    <p><strong>Від пункту:</strong> {{ $data['departure'] }}</p>
    <p><strong>До пункту:</strong> {{ $data['arrival'] }}</p>
    <p><strong>Телефон:</strong> {{ $data['phone'] }}</p>
    <p><strong>Повідомлення:</strong> {{ $data['message'] }}</p>
    <p><strong>Дата:</strong> {{ $data['date'] }}</p>
</div>
</body>
</html>
