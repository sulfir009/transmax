# Bonus System (UAH) — MaxTrans

## Таблицы
- `mt_clients` (или `{DB_PREFIX}_clients`)
  - `bonus_balance_cents` — баланс в копейках.
- `mt_bonus_transactions` (ledger)
  - `client_id`, `amount_cents`, `type`, `order_id`, `admin_id`, `meta`.
- `mt_orders`
  - `bonus_redeemed_cents`, `bonus_cashback_cents`, `bonus_use_requested`.

## Правила списания
- Все суммы в копейках (integer), отображение в гривнах.
- Списание доступно только при выборе чекбокса “Рассчитаться бонусами”.
- Максимум списания:
  - не больше суммы “К оплате”;
  - не больше 20% от суммы “К оплате”.
- Расчёт лимита: `min(balance, payable, floor(payable * 0.20))`.

## Команда разовой раздачи
```bash
php artisan bonuses:grant-initial --dry-run
php artisan bonuses:grant-initial --chunk=1000
```

Команда начисляет 100 грн (10000 копеек) клиентам, у которых есть хотя бы один оплаченный заказ, и не дублирует начисление при повторном запуске (`type=initial_grant`).

## Где начисляется кешбек и где списываются бонусы
- **Списание бонусов + кешбек**: `app/Service/TicketService.php` → `applyBonusOperations()` вызывается после успешной оплаты.
  - Кешбек рассчитывается от суммы после вычета бонусов: `round(payable_after_bonus * 0.05)`.
- **Предпросчёт списания и сохранение выбора**:
  - Страница оплаты: `resources/views/booking/payment_page.php` (checkbox + пересчёт суммы).
  - AJAX сохранение: `legacy/public/pages/ajax.php` (`request=bonus_preview`).

