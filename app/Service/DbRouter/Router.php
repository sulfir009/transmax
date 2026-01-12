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
        return rtrim($Url[$this->lang], '/');
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
        $page_data = $this->routerRepository->getPageById($cpu->page_id ?? 0);
        if (!$page_data) {
            return null;
        }
        $this->lang = $cpu['lang'];
        $result = array("lang" => $cpu['lang'], "page" => $page_data['page'], "page_id" => $cpu['page_id'], "cpu" => $url, "elem_id" => $cpu['elem_id'], 'status' => 'ok');
        return $result;
    }

    public function isCurrentPage()
    {
        $path = '/' . request()->path();
    }
}
