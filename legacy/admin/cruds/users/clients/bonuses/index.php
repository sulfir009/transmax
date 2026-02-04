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
$clientId = (int)($_GET['client_id'] ?? 0);

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
 * 1) ДОСТАЁМ НУЖНЫЕ ПРАВА ИЗ $_params
 * access — право на просмотр страницы (опционально)
 * access_edit — право на начисление
 */
$accessView = $_params['access'] ?? null;
$accessEdit = $_params['access_edit'] ?? null;

/**
 * 2) Проверяем право на просмотр (как в остальных CRUD)
 * Если у вас в проекте accessView не используется — блок можно убрать,
 * но обычно он нужен.
 */
if ($accessView && !$Admin->CheckPermission($accessView)) {
    // Тут можно сделать красивую страницу, но достаточно сообщения.
    die('Нет прав на просмотр страницы (access). Требуется permission id: ' . (int)$accessView);
}

/**
 * --- GET flash ---
 */
if (!empty($_GET['success'])) $success = (string)$_GET['success'];
if (!empty($_GET['error'])) $error = (string)$_GET['error'];

/**
 * --- Поиск клиентов ---
 */
if ($searchQuery !== '') {
    $like = '%' . $searchQuery . '%';

    $sql = "SELECT id, name, second_name, email, phone, bonus_balance_cents
            FROM `" . DB_PREFIX . "_clients`
            WHERE email LIKE ? OR phone LIKE ?
            LIMIT 10";

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        $error = 'Prepare failed (search): ' . sqlErr($db);
    } else {
        mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
        if (!mysqli_stmt_execute($stmt)) {
            $error = 'Execute failed (search): ' . sqlErr($db);
        } else {
            $res = mysqli_stmt_get_result($stmt);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $foundClients[] = $row;
                }
            }
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
            $res = mysqli_stmt_get_result($stmt);
            $selectedClient = $res ? mysqli_fetch_assoc($res) : null;
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
    $comment = trim((string)($_POST['comment'] ?? ''));

    $amountRaw = str_replace(',', '.', $amountRaw);
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
    $res = mysqli_stmt_get_result($stmt);
    $exists = $res ? (bool)mysqli_fetch_assoc($res) : false;
    mysqli_stmt_close($stmt);

    if (!$exists) {
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
