<?php

namespace App\Service\DbRouter;

use App\Repository\Site\RouterRepository;
use Illuminate\Support\Facades\Session;

class Router
{
    protected $routerRepository;
    protected $lang;

    public function __construct()
    {
        $this->routerRepository = new \App\Repository\Site\RouterRepository();
        $this->lang = Session::get('lang', 'ru');
    }

    public function getURLs($pageId, $elemId = 0): array
    {
        $urls = [];
        $routers = $this->routerRepository->getURLs($pageId, $elemId);
        foreach ($routers as $route) {
            $urls[$route->lang] = $route->route;
        }

        return $urls;
    }

    public function writelink($pageId, $elemId = 0)
    {
        $Url = $this->getURLs($pageId, $elemId);
        $route = $Url[$this->lang] ?? null;
        $fallback = $this->getPrivateFallbackRoute($pageId);

        if ($route && $fallback && $this->shouldUsePrivateFallback($route)) {
            return $fallback;
        }

        if (!empty($route)) {
            return rtrim($route, '/');
        }

        return $fallback ?? '/';
    }

    public function GetCPU()
    {
        $url = $_SERVER['REQUEST_URI'];
        //очистка
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $url = preg_replace("/[^\x20-\xFF]/", "", strval($url));
        $url = str_replace("%22", "", $url);
        $urlR = explode("?", $url);
        $url = $urlR[0];
        /* Вариант со слэшем */

        $cpu = $this->routerRepository->getByUrl($url);
        if (!$cpu) {
            return ['status' => '404'];
        }

        $page_data = $this->routerRepository->getPageById($cpu->page_id ?? 0);
        if (!$page_data) {
            return ['status' => '404'];
        }
        $this->lang = $cpu['lang'];
        $result = array("lang" => $cpu['lang'], "page" => $page_data['page'], "page_id" => $cpu['page_id'], "cpu" => $url, "elem_id" => $cpu['elem_id'], 'status' => 'ok');
        return $result;
    }

    protected function getPrivateFallbackRoute($pageId): ?string
    {
        $map = [
            78 => '/public/pages/private/history.php',
            79 => '/public/pages/private/contacts.php',
            80 => '/public/pages/private/future.php',
            81 => '/public/pages/private/bonuses.php',
            82 => '/public/pages/private/payment_data.php',
        ];

        return $map[$pageId] ?? null;
    }

    protected function shouldUsePrivateFallback(string $route): bool
    {
        $normalized = ltrim($route, '/');
        if (str_starts_with($normalized, 'public/pages/private/')) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9_]{12,}$/i', $normalized);
    }

    public function isCurrentPage()
    {
        $path = '/' . request()->path();
    }
}
