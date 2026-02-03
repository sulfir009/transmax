<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';

$table = DB_PREFIX . '_seo_templates';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['templates']) && is_array($_POST['templates'])) {
        foreach ($_POST['templates'] as $id => $value) {
            $id = (int) $id;
            $value = mysqli_real_escape_string($db, $value);
            mysqli_query($db, "UPDATE `{$table}` SET template_text = '{$value}', updated_at = NOW() WHERE id = {$id}");
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$templates = $Db->getAll("SELECT id, `key`, `lang`, template_text FROM `{$table}` ORDER BY `key`, `lang`");
$grouped = [];
foreach ($templates as $template) {
    $grouped[$template['key']][] = $template;
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
                        <h1 class="m-0">SEO шаблоны</h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <p>Доступные переменные: <code>[route]</code>, <code>[price]</code></p>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <form method="POST" action="">
                    <?php foreach ($grouped as $key => $items) { ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><?php echo htmlspecialchars($key); ?></h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($items as $item) { ?>
                                    <div class="form-group">
                                        <label><?php echo strtoupper($item['lang']); ?></label>
                                        <textarea class="form-control" name="templates[<?php echo (int) $item['id']; ?>]" rows="3"><?php echo htmlspecialchars($item['template_text']); ?></textarea>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="card-footer" style="text-align:center;">
                        <button type="submit" class="btn btn-success btn-lg">Сохранить</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
</body>
</html>