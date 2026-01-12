<?php

namespace App\Repository\Site;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SiteSettingsRepository
{
    private string $dbPrefix;

    public function __construct()
    {
        $this->dbPrefix = config('database.prefix', 'mt');
    }

    /**
     * Получить все настройки сайта
     * 
     * @return array
     */
    public function getSettings(): array
    {
        return Cache::remember("site_settings", 3600, function () {
            try {
                // Проверяем существование таблицы
                if (!DB::getSchemaBuilder()->hasTable($this->dbPrefix . '_site_settings')) {
                    return $this->getDefaultSettings();
                }
                
                $settings = DB::table($this->dbPrefix . '_site_settings')
                    ->pluck('value', 'code')
                    ->toArray();

                // Дополняем настройки значениями из env если они отсутствуют в БД
                return array_merge($this->getDefaultSettings(), $settings);
            } catch (\Exception $e) {
                \Log::warning('Cannot get site settings from database: ' . $e->getMessage());
                return $this->getDefaultSettings();
            }
        });
    }
    
    /**
     * Получить настройки по умолчанию из конфигурации и env
     * 
     * @return array
     */
    private function getDefaultSettings(): array
    {
        return [
            'CONTACT_PHONE' => config('contacts.phone', '+38 (048) 777-77-77'),
            'CONTACT_EMAIL' => config('contacts.email', 'info@example.com'),
            'VIBER' => config('contacts.social.viber', ''),
            'TELEGRAM' => config('contacts.social.telegram', ''),
            'FB' => config('contacts.social.facebook', ''),
            'INST' => config('contacts.social.instagram', ''),
            'CONTACT_MAP' => config('contacts.map', ''),
        ];
    }

    /**
     * Получить конкретную настройку
     * 
     * @param string $code
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $code, $default = null)
    {
        $settings = $this->getSettings();
        return $settings[$code] ?? $default;
    }

    /**
     * Получить контактный телефон
     * 
     * @return string|null
     */
    public function getContactPhone(): ?string
    {
        return $this->getSetting('CONTACT_PHONE');
    }

    /**
     * Получить контактный email
     * 
     * @return string|null
     */
    public function getContactEmail(): ?string
    {
        return $this->getSetting('CONTACT_EMAIL');
    }

    /**
     * Получить ссылки на социальные сети
     * 
     * @return array
     */
    public function getSocialLinks(): array
    {
        $settings = $this->getSettings();
        
        return [
            'viber' => $settings['VIBER'] ?? null,
            'telegram' => $settings['TELEGRAM'] ?? null,
            'facebook' => $settings['FB'] ?? null,
            'instagram' => $settings['INST'] ?? null,
        ];
    }

    /**
     * Получить код карты
     * 
     * @return string|null
     */
    public function getContactMap(): ?string
    {
        return $this->getSetting('CONTACT_MAP');
    }
}
