<?php
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';

/**
 * ПОДКЛЮЧАЕМ config.php ЭТОГО CRUD'а
 * Там обычно лежит $_params['access'] и $_params['access_edit'].
 */
if (file_exists(__DIR__ . '/config.php')) {
    include __DIR__ . '/config.php';
}

$searchQuery = trim((string)($_GET['q'] ?? ''));
$clientId    = (int)($_GET['client_id'] ?? 0);

$error = '';
$success = '';

$foundClients = [];
$selectedClient = null;

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function redirectTo(string $url): void {
    header('Location: ' . $url);
    exit;
}
function sqlErr(mysqli $db): string {
    return 'MySQL[' . $db->errno . ']: ' . $db->error;
}

/**
 * Нормализация телефона -> только цифры (для ввода из формы)
 */
function normalizePhoneToDigits(string $value): string {
    $out = preg_replace('/\D+/', '', $value);
    return $out !== null ? $out : '';
}

/**
 * SQL-выражение "телефон только цифры" на стороне MySQL.
 * Важно: IFNULL чтобы не получить NULL при phone=NULL.
 * Добавили чистку скрытых символов (на будущее): \r \n \t NBSP (CHAR(160)).
 * Даже если у тебя их сейчас нет — это не ломает и повышает надёжность.
 */
function phoneSqlDigitsExpr(string $column = 'phone'): string {
    $col = "IFNULL($column,'')";
    return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(".$col.",
        CHAR(13), ''), CHAR(10), ''), CHAR(9), ''), CHAR(160), ''), ' ', ''), '+', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', '')";
}

/**
 * Строим варианты для поиска:
 * - как ввели (digits)
 * - last10 (часто это локальный номер без страны)
 * - UA конверсия 380XXXXXXXXX <-> 0XXXXXXXXX
 * - last9 (иногда хранят без ведущего 0)
 */
function buildPhoneVariants(string $qDigits): array {
    $vars = [];
    if ($qDigits === '') return $vars;

    $vars[] = $qDigits;

    $len = strlen($qDigits);

    if ($len >= 10) {
        $vars[] = substr($qDigits, -10);
    }

    if ($len === 12 && substr($qDigits, 0, 3) === '380') {
        $vars[] = '0' . substr($qDigits, 3); // 380671234567 -> 0671234567
    }

    if ($len === 10 && $qDigits[0] === '0') {
        $vars[] = '380' . substr($qDigits, 1); // 0671234567 -> 380671234567
    }

    if ($len >= 9) {
        $vars[] = substr($qDigits, -9);
    }

    // чистим дубли/пустые (без стрелочных функций — совместимее)
    $vars = array_filter($vars, function ($v) { return $v !== ''; });
    $vars = array_values(array_unique($vars));

    return $vars;
}

/**
 * Динамический bind_param для переменного количества плейсхолдеров.
 */
function bindDynamicParams(mysqli_stmt $stmt, string $types, array $values): bool {
    if ($types === '' || empty($values)) {
        return true;
    }

    $params = [$types];
    foreach ($values as $k => $v) {
        $params[] = &$values[$k]; // важно: по ссылке
    }

    return call_user_func_array([$stmt, 'bind_param'], $params);
}

/**
 * Фетч SELECT-результатов максимально совместимо:
 * - если есть mysqlnd -> mysqli_stmt_get_result
 * - если нет -> bind_result + fetch через metadata
 */
function stmtFetchAllAssoc(mysqli_stmt $stmt): array {
    $out = [];

    $res = mysqli_stmt_get_result($stmt);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $out[] = $row;
        }
        return $out;
    }

    $meta = mysqli_stmt_result_metadata($stmt);
    if (!$meta) return $out;

    $fields = [];
    $row = [];
    $bind = [];

    while ($f = mysqli_fetch_field($meta)) {
        $fields[] = $f->name;
        $row[$f->name] = null;
        $bind[] = &$row[$f->name];
    }
    mysqli_free_result($meta);

    call_user_func_array([$stmt, 'bind_result'], $bind);

    while (mysqli_stmt_fetch($stmt)) {
        $copy = [];
        foreach ($fields as $name) {
            $copy[$name] = $row[$name];
        }
        $out[] = $copy;
    }

    return $out;
}

function stmtFetchOneAssoc(mysqli_stmt $stmt): ?array {
    $rows = stmtFetchAllAssoc($stmt);
    return !empty($rows) ? $rows[0] : null;
}

/**
 * 1) ДОСТАЁМ НУЖНЫЕ ПРАВА ИЗ $_params
 * access — право на просмотр страницы (опционально)
 * access_edit — право на начисление
 */
$accessView = $_params['access'] ?? null;
$accessEdit = $_params['access_edit'] ?? null;

/**
 * 2) Проверяем право на просмотр (как в остальных CRUD)
 */
if ($accessView && !$Admin->CheckPermission($accessView)) {
    die('Нет прав на просмотр страницы (access). Требуется permission id: ' . (int)$accessView);
}

/**
 * --- GET flash ---
 */
if (!empty($_GET['success'])) $success = (string)$_GET['success'];
if (!empty($_GET['error']))   $error   = (string)$_GET['error'];

/**
 * --- Поиск клиентов ---
 */
if ($searchQuery !== '') {
    $like = '%' . $searchQuery . '%';

    $phoneDigits   = normalizePhoneToDigits($searchQuery);
    $phoneVariants = buildPhoneVariants($phoneDigits);

    $whereParts = ["email LIKE ?", "phone LIKE ?"];
    $types  = 'ss';
    $values = [$like, $like];

    if (!empty($phoneVariants)) {
        $phoneExpr = phoneSqlDigitsExpr('phone');

        // LIKE по вариантам
        foreach ($phoneVariants as $pv) {
            $whereParts[] = $phoneExpr . " LIKE ?";
            $types .= 's';
            $values[] = '%' . $pv . '%';
        }

        // Точное совпадение last10 (самый частый/точный кейс)
        $last10 = (strlen($phoneDigits) >= 10) ? substr($phoneDigits, -10) : '';
        if ($last10 !== '') {
            $whereParts[] = "RIGHT(" . $phoneExpr . ", 10) = ?";
            $types .= 's';
            $values[] = $last10;
        }
    }

    $sql = "SELECT id, name, second_name, email, phone, bonus_balance_cents
            FROM `" . DB_PREFIX . "_clients`
            WHERE " . implode(' OR ', $whereParts) . "
            LIMIT 10";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        $error = 'Prepare failed (search): ' . sqlErr($db);
    } else {
        if (!bindDynamicParams($stmt, $types, $values)) {
            $error = 'Bind failed (search): ' . sqlErr($db);
        } elseif (!mysqli_stmt_execute($stmt)) {
            $error = 'Execute failed (search): ' . sqlErr($db);
        } else {
            $foundClients = stmtFetchAllAssoc($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * --- Выбранный клиент ---
 */
if ($clientId > 0 && !$error) {
    $sql = "SELECT id, name, second_name, email, phone, bonus_balance_cents
            FROM `" . DB_PREFIX . "_clients`
            WHERE id = ?
            LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        $error = 'Prepare failed (select client): ' . sqlErr($db);
    } else {
        mysqli_stmt_bind_param($stmt, 'i', $clientId);
        if (!mysqli_stmt_execute($stmt)) {
            $error = 'Execute failed (select client): ' . sqlErr($db);
        } else {
            $selectedClient = stmtFetchOneAssoc($stmt);
            if (!$selectedClient) {
                $error = 'Клиент не найден.';
            }
        }
        mysqli_stmt_close($stmt);
    }
}

/**
 * --- Начисление бонусов (POST) ---
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ВАЖНО: если access_edit не задан — это конфиг-ошибка
    if (!$accessEdit) {
        $err = 'Конфиг-ошибка: не задан $_params["access_edit"] в bonuses/config.php';
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . (int)($_POST['client_id'] ?? 0) . '&error=' . urlencode($err));
    }

    // ВАЖНО: реальная проверка прав
    if (!$Admin->CheckPermission($accessEdit)) {
        $err = 'Нет прав на начисление бонусов (access_edit). Требуется permission id: ' . (int)$accessEdit;
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . (int)($_POST['client_id'] ?? 0) . '&error=' . urlencode($err));
    }

    $postClientId = (int)($_POST['client_id'] ?? 0);
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $comment   = trim((string)($_POST['comment'] ?? ''));

    $amountRaw   = str_replace(',', '.', $amountRaw);
    $amountValue = is_numeric($amountRaw) ? (float)$amountRaw : 0.0;
    $amountCents = (int)round($amountValue * 100);

    $commentClean = trim(strip_tags($comment));
    if (mb_strlen($commentClean, 'UTF-8') > 255) {
        $commentClean = mb_substr($commentClean, 0, 255, 'UTF-8');
    }

    if ($postClientId <= 0 || $amountCents <= 0) {
        $err = 'Укажите клиента и сумму больше 0.';
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . $postClientId . '&error=' . urlencode($err));
    }

    // Проверка клиента
    $sqlCheck = "SELECT id FROM `" . DB_PREFIX . "_clients` WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($db, $sqlCheck);
    if (!$stmt) {
        $err = 'Prepare failed (check client): ' . sqlErr($db);
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . $postClientId . '&error=' . urlencode($err));
    }
    mysqli_stmt_bind_param($stmt, 'i', $postClientId);
    mysqli_stmt_execute($stmt);
    $row = stmtFetchOneAssoc($stmt);
    mysqli_stmt_close($stmt);

    if (!$row) {
        $err = 'Клиент не найден (в legacy БД).';
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . $postClientId . '&error=' . urlencode($err));
    }

    // ТРАНЗАКЦИЯ
    mysqli_begin_transaction($db);
    try {
        // UPDATE
        $sqlUpd = "UPDATE `" . DB_PREFIX . "_clients`
                   SET bonus_balance_cents = bonus_balance_cents + ?
                   WHERE id = ?
                   LIMIT 1";
        $stmtUpd = mysqli_prepare($db, $sqlUpd);
        if (!$stmtUpd) throw new RuntimeException('Prepare failed (update): ' . sqlErr($db));

        mysqli_stmt_bind_param($stmtUpd, 'ii', $amountCents, $postClientId);
        if (!mysqli_stmt_execute($stmtUpd)) {
            mysqli_stmt_close($stmtUpd);
            throw new RuntimeException('Execute failed (update): ' . sqlErr($db));
        }
        $affected = mysqli_stmt_affected_rows($stmtUpd);
        mysqli_stmt_close($stmtUpd);

        if ($affected <= 0) {
            throw new RuntimeException('UPDATE не изменил строку (affected_rows=0). Проверь права на UPDATE/триггеры/таблицу.');
        }

        // INSERT
        $meta = json_encode(['comment' => $commentClean], JSON_UNESCAPED_UNICODE);
        $adminId = (int)($Admin->id ?? 0);

        $sqlIns = "INSERT INTO `" . DB_PREFIX . "_bonus_transactions`
                   (`client_id`,`amount_cents`,`type`,`order_id`,`admin_id`,`meta`,`created_at`,`updated_at`)
                   VALUES (?, ?, 'manual_add', NULL, ?, ?, NOW(), NOW())";

        $stmtIns = mysqli_prepare($db, $sqlIns);
        if (!$stmtIns) throw new RuntimeException('Prepare failed (insert): ' . sqlErr($db));

        mysqli_stmt_bind_param($stmtIns, 'iiis', $postClientId, $amountCents, $adminId, $meta);
        if (!mysqli_stmt_execute($stmtIns)) {
            mysqli_stmt_close($stmtIns);
            throw new RuntimeException('Execute failed (insert): ' . sqlErr($db));
        }
        mysqli_stmt_close($stmtIns);

        mysqli_commit($db);

        $ok = 'Бонусы начислены: ' . number_format($amountCents / 100, 2, '.', '') . ' грн';
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . $postClientId . '&success=' . urlencode($ok));

    } catch (Throwable $e) {
        mysqli_rollback($db);
        $err = $e->getMessage();
        redirectTo('?q=' . urlencode($searchQuery) . '&client_id=' . $postClientId . '&error=' . urlencode($err));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Бонусы клиентов</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">

                        <?php if ($error) { ?>
                            <div class="alert alert-danger"><?= h($error) ?></div>
                        <?php } ?>

                        <?php if ($success) { ?>
                            <div class="alert alert-success"><?= h($success) ?></div>
                        <?php } ?>

                        <form method="get" class="form-inline mb-3">
                            <label class="mr-2">Поиск по телефону или email</label>
                            <input type="text" class="form-control mr-2" name="q"
                                   value="<?= h($searchQuery) ?>"
                                   placeholder="+380... или user@mail.com">
                            <button type="submit" class="btn btn-primary">Найти</button>
                        </form>

                        <?php if (!empty($foundClients)) { ?>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
                                    <th>Баланс</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($foundClients as $c) { ?>
                                    <tr>
                                        <td><?= (int)$c['id'] ?></td>
                                        <td><?= h(trim(($c['name'] ?? '') . ' ' . ($c['second_name'] ?? ''))) ?></td>
                                        <td><?= h($c['email'] ?? '') ?></td>
                                        <td><?= h($c['phone'] ?? '') ?></td>
                                        <td><?= number_format(((int)($c['bonus_balance_cents'] ?? 0)) / 100, 2, '.', '') ?> грн</td>
                                        <td>
                                            <a class="btn btn-sm btn-info"
                                               href="?q=<?= urlencode($searchQuery) ?>&client_id=<?= (int)$c['id'] ?>">
                                                Выбрать
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php if ($selectedClient) { ?>
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5>Клиент: <?= h(trim(($selectedClient['name'] ?? '') . ' ' . ($selectedClient['second_name'] ?? ''))) ?></h5>
                                    <p>Email: <?= h($selectedClient['email'] ?? '') ?></p>
                                    <p>Телефон: <?= h($selectedClient['phone'] ?? '') ?></p>
                                    <p>Баланс бонусов: <?= number_format(((int)($selectedClient['bonus_balance_cents'] ?? 0)) / 100, 2, '.', '') ?> грн</p>

                                    <form method="post">
                                        <input type="hidden" name="client_id" value="<?= (int)$selectedClient['id'] ?>">

                                        <div class="form-group">
                                            <label>Сумма начисления (грн)</label>
                                            <input type="text" class="form-control" name="amount" placeholder="100.00" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Комментарий</label>
                                            <input type="text" class="form-control" name="comment" maxlength="255">
                                        </div>

                                        <button type="submit" class="btn btn-success">Начислить</button>
                                    </form>

                                    <hr>
                                    <small class="text-muted">
                                        Требуемое право на начисление: <b>access_edit = <?= (int)$accessEdit ?></b>
                                    </small>
                                </div>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
</body>
</html>
