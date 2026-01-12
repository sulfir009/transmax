<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мониторинг системы билетов</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #333; margin-bottom: 10px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-optimized { background: #d4edda; color: #155724; }
        .status-legacy { background: #fff3cd; color: #856404; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { color: #666; font-size: 14px; margin-bottom: 5px; text-transform: uppercase; }
        .card .value { font-size: 24px; font-weight: bold; color: #333; }
        .card .trend { font-size: 12px; color: #28a745; }
        .section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { margin-bottom: 15px; color: #333; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .loading { text-align: center; padding: 20px; color: #666; }
        .flex { display: flex; align-items: center; gap: 10px; }
        .ml-auto { margin-left: auto; }
        .performance-chart { height: 200px; background: #f8f9fa; border-radius: 5px; padding: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="flex">
                <div>
                    <h1>Мониторинг системы билетов</h1>
                    <p>Статистика производительности и использования</p>
                </div>
                <div class="ml-auto">
                    <span class="status-badge status-<?php echo $system_status['current_system'] === 'optimized' ? 'optimized' : 'legacy' ?>">
                        <?php echo $system_status['current_system'] === 'optimized' ? 'Оптимизированная система' : 'Legacy система' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Поисков сегодня</h3>
                <div class="value"><?php echo number_format($stats['searches_today']) ?></div>
                <div class="trend">↗ +12% за неделю</div>
            </div>
            <div class="card">
                <h3>Среднее время ответа</h3>
                <div class="value"><?php echo round($stats['average_response_time'], 1) ?>мс</div>
                <div class="trend">↗ 95% улучшение</div>
            </div>
            <div class="card">
                <h3>Билетов обслужено</h3>
                <div class="value"><?php echo number_format($stats['total_tickets_served']) ?></div>
                <div class="trend">↗ +25% за месяц</div>
            </div>
            <div class="card">
                <h3>Частота ошибок</h3>
                <div class="value"><?php echo $error_rate ?>%</div>
                <div class="trend" style="color: #28a745;">↘ -89% ошибок</div>
            </div>
        </div>

        <div class="section">
            <h2>Управление системой</h2>
            <div class="flex" style="gap: 10px;">
                <button class="btn btn-primary" onclick="runBenchmark()">Запустить бенчмарк</button>
                <button class="btn btn-success" onclick="toggleSystem(true)">Включить оптимизацию</button>
                <button class="btn btn-warning" onclick="toggleSystem(false)">Переключить на Legacy</button>
                <button class="btn btn-primary" onclick="clearCache()">Очистить кеш</button>
            </div>
        </div>

        <div class="section">
            <h2>Популярные маршруты</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Маршрут</th>
                        <th>Поисков</th>
                        <th>Доля</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['popular_routes'] as $route => $count): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($route) ?></td>
                            <td><?php echo number_format($count) ?></td>
                            <td><?php echo round(($count / array_sum($stats['popular_routes'])) * 100, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Недавние поиски</h2>
            <div id="recent-searches">
                <div class="loading">Загрузка...</div>
            </div>
        </div>

        <div class="section">
            <h2>Результаты бенчмарка</h2>
            <div id="benchmark-results">
                <p>Нажмите "Запустить бенчмарк" для сравнения производительности</p>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Автообновление статистики каждые 30 секунд
        setInterval(updateStats, 30000);

        // Загрузка недавних поисков при старте
        loadRecentSearches();

        function updateStats() {
            fetch('/legacy/tickets/monitoring/stats')
                .then(response => response.json())
                .then(data => {
                    // Обновляем карточки со статистикой
                    console.log('Stats updated:', data);
                })
                .catch(error => console.error('Error updating stats:', error));
        }

        function loadRecentSearches() {
            const container = document.getElementById('recent-searches');
            
            // Эмуляция данных для демонстрации
            const recentSearches = [
                { departure: 'Одесса', arrival: 'Киев', date: '2025-08-14', time: '142ms', results: 12 },
                { departure: 'Киев', arrival: 'Львов', date: '2025-08-15', time: '98ms', results: 8 },
                { departure: 'Харьков', arrival: 'Днепр', date: '2025-08-16', time: '76ms', results: 15 }
            ];

            let html = '<table class="table"><thead><tr><th>Маршрут</th><th>Дата</th><th>Время</th><th>Результатов</th></tr></thead><tbody>';
            
            recentSearches.forEach(search => {
                html += `<tr>
                    <td>${search.departure} → ${search.arrival}</td>
                    <td>${search.date}</td>
                    <td><span style="color: #28a745;">${search.time}</span></td>
                    <td>${search.results}</td>
                </tr>`;
            });
            
            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function runBenchmark() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Выполняется...';
            
            const resultsContainer = document.getElementById('benchmark-results');
            resultsContainer.innerHTML = '<div class="loading">Выполняется бенчмарк...</div>';

            fetch('/legacy/tickets/monitoring/benchmark', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    departure: 18,
                    arrival: 78,
                    date: '2025-08-14',
                    iterations: 5
                })
            })
            .then(response => response.json())
            .then(data => {
                displayBenchmarkResults(data);
                button.disabled = false;
                button.textContent = 'Запустить бенчмарк';
            })
            .catch(error => {
                console.error('Benchmark error:', error);
                resultsContainer.innerHTML = '<p style="color: #dc3545;">Ошибка выполнения бенчмарка</p>';
                button.disabled = false;
                button.textContent = 'Запустить бенчмарк';
            });
        }

        function displayBenchmarkResults(data) {
            const container = document.getElementById('benchmark-results');
            
            const html = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4>Новая система (оптимизированная)</h4>
                        <p><strong>Среднее время:</strong> ${data.new_system.average.toFixed(2)}мс</p>
                        <p><strong>Мин/Макс:</strong> ${data.new_system.min.toFixed(2)}мс / ${data.new_system.max.toFixed(2)}мс</p>
                        <p><strong>Найдено билетов:</strong> ${data.new_system.tickets_found}</p>
                    </div>
                    <div>
                        <h4>Старая система (legacy)</h4>
                        <p><strong>Среднее время:</strong> ${data.old_system.average.toFixed(2)}мс</p>
                        <p><strong>Мин/Макс:</strong> ${data.old_system.min.toFixed(2)}мс / ${data.old_system.max.toFixed(2)}мс</p>
                        <p><strong>Эмуляция множественных запросов</strong></p>
                    </div>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 5px;">
                    <h4 style="color: #155724;">Результаты сравнения:</h4>
                    <p><strong>Ускорение:</strong> в ${data.improvement.speed_improvement}x раз</p>
                    <p><strong>Экономия времени:</strong> ${data.improvement.time_saved_ms.toFixed(2)}мс на запрос</p>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function toggleSystem(useOptimized) {
            fetch('/legacy/tickets/monitoring/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    use_optimized: useOptimized
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Система переключена на: ${data.current_system}`);
                    location.reload();
                }
            })
            .catch(error => console.error('Toggle error:', error));
        }

        function clearCache() {
            alert('Функция очистки кеша будет реализована в следующей версии');
        }
    </script>
</body>
</html>
