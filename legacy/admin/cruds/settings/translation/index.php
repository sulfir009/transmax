<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php'; ?>
<!DOCTYPE html>
<html lang="<?= $Admin->lang ?>">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <? if ($Admin->CheckPermission($_params['access'])) { ?>
        <div class="content-wrapper">
            <div class="content-header">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <? if ($Admin->checkPermission(1)) { ?>
                            <a class="btn btn-success float-right" role="button" href="<?= $_SERVER['REQUEST_URI'] . '/create.php' ?>">
                                <i class="fas fa-plus"></i> Добавить язык
                            </a>
                        <? } ?>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    <div class="table-responsive table-striped table-valign-middle">
                        <table class="table m-0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th style="text-align:center;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? $countElems = $Db->getOne("SELECT COUNT(id) FROM `".$_params['table']."` ");
                            $pagination = pagination((int)$countElems['COUNT(id)'], $_params['num_page']);
                            $getTableElems = $Db->getAll("SELECT * FROM `" . $_params['table'] . "` ORDER BY id DESC LIMIT " . $pagination['from'] . "," . $pagination['per_page'] . " ");
                            foreach ($getTableElems AS $key=>$Elem) {
                                ?>
                                <tr <? if ($Elem['active'] == '0') { ?>
                                    class="disabled"
                                <? } ?>>
                                    <td><?= $Elem['id'] ?></td>
                                    <td><b><?= $Elem['title'] ?> (<?= $Elem['code'] ?>)</b></td>
                                    <td align="center" width="210">
                                        <div class="btn-group wgroup">
                                            <? if ($Admin->CheckPermission(1)) { ?>
                                            <a onclick="refresh_elem(<?= $Elem['id'] ?>, '<?= $_params['table'] ?>')"
                                               class="btn btn-success" title="Активировать/дезактивировать">
                                                <i <?if ($Elem['active'] == '0'){?>
                                  class="fas fa-toggle-off"
                                <?} else { ?> class="fas fa-toggle-on" <?} ?>></i>
                                            </a>
                                            <?}?>
                                            <a href="<?= parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '/edit.php' ?>?id=<?= $Elem['id']  ?>" class="btn btn-default" title="Редактировать">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <? if ($Admin->CheckPermission(1)) { ?>
                                                <a onclick="return confirm('Вы уверены что хотите удалить этот язык?')"
                                                   href="<?= $_SERVER['REQUEST_URI'] . '/delete.php' ?>?id=<?= $Elem['id'] ?>" class="btn btn-danger"
                                                   title="Удалить">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <? } ?>
                                        </div>
                                    </td>
                                </tr>
                            <? } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    <?php } else { ?>
        <div class="content-wrapper empty_content">
            <h1>У Вас нет прав доступа для просмотра этого раздела!</h1>
        </div>
    <? } ?>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
</body>
</html>
