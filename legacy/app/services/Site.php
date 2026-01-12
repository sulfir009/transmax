<?php

require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/app/Enum/SiteEnum.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/config.php");

class Site
{
    public $scheme;
    public $host;

    public function __construct()
    {
        $this->scheme = $_SERVER['REQUEST_SCHEME'];
        $this->host = $_SERVER['SERVER_NAME'];
    }
    public function getHost(): string
    {
        return $this->host;
    }

    public function getFullHost(): string
    {
        return $this->scheme . '://' . $this->host;
    }

    public function buildLink(string $link): string
    {
        return $this->getFullHost() . $link;
    }

    public function isProd(): bool
    {
        return $this->host == SERVER_PROD;
    }

    public function isTest(): bool
    {
        return $this->host == SERVER_TEST;
    }

    public function isDev(): bool
    {
        return !($this->isProd() || $this->isTest());
    }
}
