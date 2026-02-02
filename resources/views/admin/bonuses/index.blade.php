<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Бонуси клієнтів</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 24px; }
        .card { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .row + .row { margin-top: 12px; }
        .input { padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn { background: #2b6cb0; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; }
        .btn.secondary { background: #4a5568; }
        .table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .table th, .table td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; }
        .alert { padding: 10px 12px; border-radius: 6px; margin-bottom: 12px; }
        .alert.success { background: #e6fffa; color: #285e61; }
        .alert.error { background: #fff5f5; color: #c53030; }
    </style>
</head>
<body>
<div class="card">
    <h1>Бонуси клієнтів</h1>

    @if(session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert error">{{ session('error') }}</div>
    @endif

    <form class="row" method="get" action="{{ route('admin.bonuses.index') }}">
        <input class="input" type="text" name="query" value="{{ $query }}" placeholder="Email або телефон">
        <button class="btn" type="submit">Знайти</button>
    </form>

    @if($query !== '')
        <p>Знайдено: {{ $clients->count() }}</p>
    @endif

    @if($clients->count() > 0)
        <table class="table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Клієнт</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Баланс</th>
                <th>Нарахувати бонуси</th>
            </tr>
            </thead>
            <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ trim($client->name . ' ' . $client->second_name) }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ number_format(($client->bonus_balance_cents ?? 0) / 100, 2, '.', '') }} грн</td>
                    <td>
                        <form class="row" method="post" action="{{ route('admin.bonuses.credit') }}">
                            @csrf
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <input class="input" type="text" name="amount_uah" placeholder="Сума, грн" required>
                            <input class="input" type="text" name="comment" placeholder="Коментар">
                            <button class="btn secondary" type="submit">Нарахувати</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @elseif($query !== '')
        <p>Клієнтів не знайдено.</p>
    @endif
</div>
</body>
</html>
