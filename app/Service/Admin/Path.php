<?php

namespace App\Service\Admin;

use App\Repository\Admin\MenuRepository;

class Path
{
    static function getSection(string $scriptName, string $requestUri)
    {
        $pathSection = str_replace(
            ADMIN_PANEL ,
            '{ADMIN_PANEL}',
            str_replace($scriptName, '', $requestUri)
        );
        $pathSection = preg_replace(
            '~/[^/]+\.php$~',
            '',
            parse_url($pathSection, PHP_URL_PATH)
        );

        return (new MenuRepository())->getSection($pathSection . '/');
    }

}
