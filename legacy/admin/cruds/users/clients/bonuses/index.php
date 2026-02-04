<?php
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';

use App\Models\Client;
use App\Services\BonusService;

$searchQuery = trim((string)($_GET['q'] ?? ''));
$clientId = (int)($_GET['client_id'] ?? 0);
$error = '';
$success = '';
$foundClients = [];
$selectedClient = null;

if ($searchQuery !== '') {
    $searchSafe = mysqli_real_escape_string($db, $searchQuery);
    $foundClients = $Db->getAll("SELECT id, name, second_name, email, phone, bonus_balance_cents FROM `" . DB_PREFIX . "_clients` WHERE email LIKE '%{$searchSafe}%' OR phone LIKE '%{$searchSafe}%' LIMIT 10");
}

if ($clientId > 0) {
    $selectedClient = Client::find($clientId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Admin->CheckPermission($_params['access_edit'] ?? 1)) {
    $clientId = (int)($_POST['client_id'] ?? 0);
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $comment = trim((string)($_POST['comment'] ?? ''));

    $amountRaw = str_replace(',', '.', $amountRaw);
    $amountValue = is_numeric($amountRaw) ? (float)$amountRaw : 0;
    $amountCents = (int)round($amountValue * 100);

    if ($clientId <= 0 || $amountCents <= 0) {
        $error = 'Укажите клиента и сумму больше 0.';
    } else {
        $client = Client::find($clientId);
        if (!$client) {
            $error = 'Клиент не найден.';
        } else {
            $bonusCredited = false;
            $adminId = (int)($Admin->id ?? 0);
            $commentClean = strip_tags($comment);

            if (function_exists('app')) {
                try {
                    $bonusService = app(BonusService::class);
                    $bonusService->credit($client, $amountCents, 'manual_add', [
                        'comment' => $commentClean,
                    ], null, $adminId);
                    $bonusCredited = true;
                } catch (Exception $e) {
                    $bonusCredited = false;
                }
            }

            if (!$bonusCredited) {
                $commentSafe = mysqli_real_escape_string($db, $commentClean);
                $meta = json_encode(['comment' => $commentClean], JSON_UNESCAPED_UNICODE);
                $metaSafe = mysqli_real_escape_string($db, (string)$meta);
                $Db->query("UPDATE `" . DB_PREFIX . "_clients` SET bonus_balance_cents = bonus_balance_cents + " . (int)$amountCents . " WHERE id = '" . (int)$clientId . "' LIMIT 1");
                $Db->query("INSERT INTO `" . DB_PREFIX . "_bonus_transactions` (`client_id`,`amount_cents`,`type`,`order_id`,`admin_id`,`meta`,`created_at`,`updated_at`) VALUES ('" . (int)$clientId . "','" . (int)$amountCents . "','manual_add',NULL,'" . (int)$adminId . "','" . $metaSafe . "',NOW(),NOW())");
            }

            $success = 'Бонусы успешно начислены.';
            $selectedClient = $client->fresh();
        }
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
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php } ?>
                        <?php if ($success) { ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php } ?>

                        <form method="get" class="form-inline mb-3">
                            <label class="mr-2">Поиск по телефону или email</label>
                            <input type="text" class="form-control mr-2" name="q" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="+380... или user@mail.com">
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
                                <?php foreach ($foundClients as $client) { ?>
                                    <tr>
                                        <td><?= (int)$client['id'] ?></td>
                                        <td><?= htmlspecialchars(trim($client['name'] . ' ' . $client['second_name']), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($client['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($client['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format(((int)$client['bonus_balance_cents']) / 100, 2, '.', '') ?> грн</td>
                                        <td>
                                            <a class="btn btn-sm btn-info" href="?q=<?= urlencode($searchQuery) ?>&client_id=<?= (int)$client['id'] ?>">Выбрать</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php if ($selectedClient) { ?>
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5>Клиент: <?= htmlspecialchars($selectedClient->full_name ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p>Email: <?= htmlspecialchars($selectedClient->email ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                    <p>Телефон: <?= htmlspecialchars($selectedClient->phone ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                    <p>Баланс бонусов: <?= number_format(((int)$selectedClient->bonus_balance_cents) / 100, 2, '.', '') ?> грн</p>

                                    <form method="post">
                                        <input type="hidden" name="client_id" value="<?= (int)$selectedClient->id ?>">
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
