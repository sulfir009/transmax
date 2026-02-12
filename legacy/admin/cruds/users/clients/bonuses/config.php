<?php

if (!isset($_params) || !is_array($_params)) {
    $_params = [];
}

/**
 * 1) Берём PATH запроса без query (?q=...),
 *    и убираем /index.php если внезапно попался.
 */
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uriPath = '/' . ltrim($uriPath, '/');
$uriPath = preg_replace('#/index\.php$#i', '', $uriPath);
$uriPath = rtrim($uriPath, '/'); // без хвостового /

/**
 * 2) Таблица меню — mt_menu_admin (у тебя DB_PREFIX = mt)
 */
$tableMenu = DB_PREFIX . '_menu_admin';

/**
 * 3) Генератор кандидатов link:
 *    - с/без ведущего /
 *    - с / в конце (в БД у тебя хранятся со слешем на конце)
 *    - вариант с {ADMIN_PANEL} (на случай записей вида {ADMIN_PANEL}/...)
 */
if (!function_exists('legacy_build_menu_link_variants')) {
    function legacy_build_menu_link_variants(string $path): array
    {
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        $variants = [];

        // 1) как есть: /admin/.../
        $variants[] = $path . '/';
        $variants[] = ltrim($path, '/') . '/';

        // 2) вариант с плейсхолдером: /{ADMIN_PANEL}/.../
        $adminSegment = trim(ADMIN_PANEL, '/'); // например "admin"
        if ($adminSegment !== '') {
            $withPlaceholder = preg_replace(
                '#^/' . preg_quote($adminSegment, '#') . '(?=/|$)#',
                '/{ADMIN_PANEL}',
                $path
            );
            $variants[] = $withPlaceholder . '/';
            $variants[] = ltrim($withPlaceholder, '/') . '/';
        }

        // уникализируем
        $variants = array_values(array_unique($variants));

        return $variants;
    }
}

/**
 * 4) Поиск секции в mt_menu_admin по link с подъёмом к родителям:
 *    /admin/cruds/users/clients/bonuses
 * -> /admin/cruds/users/clients
 * -> /admin/cruds/users
 * ...
 */
if (!function_exists('legacy_find_section_by_link_fallback')) {
    function legacy_find_section_by_link_fallback(mysqli $db, string $tableMenu, string $path): array
    {
        $stmt = mysqli_prepare($db, "SELECT * FROM `{$tableMenu}` WHERE `link` = ? LIMIT 1");
        if (!$stmt) {
            // Если даже prepare упал — это серьёзная ошибка окружения
            throw new RuntimeException('Prepare failed: ' . $db->error);
        }

        $p = '/' . ltrim($path, '/');
        $p = rtrim($p, '/');

        while ($p !== '' && $p !== '/') {

            foreach (legacy_build_menu_link_variants($p) as $candidateLink) {
                mysqli_stmt_bind_param($stmt, 's', $candidateLink);
                mysqli_stmt_execute($stmt);

                $res = mysqli_stmt_get_result($stmt);
                if ($res) {
                    $row = mysqli_fetch_assoc($res);
                    if (is_array($row) && !empty($row['id'])) {
                        mysqli_stmt_close($stmt);
                        return $row;
                    }
                }
            }

            // обрезаем последний сегмент
            $p = preg_replace('#/[^/]+$#', '', $p);
            if ($p === '') break;
        }

        mysqli_stmt_close($stmt);
        return [];
    }
}

// 5) Находим секцию
try {
    $Section = legacy_find_section_by_link_fallback($db, $tableMenu, $uriPath);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Menu section lookup failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

$sectionId = (int)($Section['id'] ?? 0);
if ($sectionId <= 0) {
    // Жёсткая диагностика (чтобы не было “пусто и непонятно”)
    http_response_code(404);
    echo "Section not found for URI: " . htmlspecialchars($uriPath, ENT_QUOTES, 'UTF-8') . "<br>";
    echo "Tried links:<br><pre>";
    $p = $uriPath;
    $p = '/' . ltrim($p, '/');
    $p = rtrim($p, '/');
    while ($p !== '' && $p !== '/') {
        foreach (legacy_build_menu_link_variants($p) as $cand) {
            echo htmlspecialchars($cand, ENT_QUOTES, 'UTF-8') . "\n";
        }
        $p = preg_replace('#/[^/]+$#', '', $p);
        if ($p === '') break;
    }
    echo "</pre>";
    exit;
}

/**
 * 6) Заполняем $_params из найденной секции (как в других CRUD)
 *    ВАЖНО: access_edit у тебя строка "1,2" — это ок, CheckPermission обычно умеет.
 */
$_params['table']         = $Section['assoc_table']   ?? ($_params['table'] ?? null);
$_params['title']         = $Section['title']         ?? ($_params['title'] ?? 'Бонусы клиентов');
$_params['access']        = $Section['access']        ?? ($_params['access'] ?? null);
$_params['num_page']      = $Section['num_page']      ?? ($_params['num_page'] ?? null);
$_params['access_delete'] = $Section['access_delete'] ?? ($_params['access_delete'] ?? null);
$_params['access_edit']   = $Section['access_edit']   ?? ($_params['access_edit'] ?? null);
$_params['page_id']       = $Section['page_id']       ?? ($_params['page_id'] ?? null);

/**
 * 7) Подтягиваем настройки секции из mt_menu_admin_settings
 */
$getSectionParams = mysqli_query(
    $db,
    "SELECT * FROM `" . DB_PREFIX . "_menu_admin_settings` WHERE section_id = " . $sectionId
);

if ($getSectionParams) {
    while ($SectionParam = mysqli_fetch_assoc($getSectionParams)) {
        $SectionParam['value'] = str_replace('{ADMIN_PANEL}', ADMIN_PANEL, $SectionParam['value']);
        $_params[$SectionParam['param']] = $SectionParam['value'];
    }
}

// 8) как у тебя в других конфигах
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$parent = isset($_GET['parent']) ? (int)$_GET['parent'] : 0;
