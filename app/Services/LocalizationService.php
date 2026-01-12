<?php

namespace App\Services;

class LocalizationService
{
    private array $dictionary;
    private string $currentLang;

    public function __construct()
    {
        $this->currentLang = $this->detectLanguage();
        $this->loadDictionary();
    }

    /**
     * Определить текущий язык
     */
    private function detectLanguage(): string
    {
        // Проверяем сессию
        if (session()->has('language')) {
            return session('language');
        }

        // Проверяем URL или заголовки
        $uri = request()->getRequestUri();
        if (str_contains($uri, '/uk/') || str_starts_with($uri, '/uk')) {
            return 'uk';
        }
        if (str_contains($uri, '/en/') || str_starts_with($uri, '/en')) {
            return 'en';
        }

        // По умолчанию русский
        return 'ru';
    }

    /**
     * Загрузить словарь переводов
     */
    private function loadDictionary(): void
    {
        $this->dictionary = [
            'ru' => [
                'MSG_MSG_TICKETS_VIBIR_AVTOBUSA' => 'Выбор автобуса',
                'MSG_MSG_PAYMENT_PAGE_OPLATA' => 'Оплата',
                'MSG_MSG_PAYMENT_PAGE_OBERITI_SPOSIB_OPLATI' => 'Выберите способ оплаты',
                'MSG_MSG_PAYMENT_PAGE_DLYA_OFORMLENNYA_ZAMOVLENNYA_OPLATITI_JOGO_DO' => 'Для оформления заказа оплатите его до',
                'MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA' => 'Банковской картой',
                'MSG_MSG_PAYMENT_PAGE_GOTIVKOYU' => 'Наличными',
                'MSG_MSG_PAYMENT_PAGE_GRN' => 'грн',
                'MSG_MSG_PAYMENT_PAGE_OPLATITI' => 'Оплатить',
                'MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI' => 'Ваши платежные и личные данные надежно защищены',
                'MSG_MSG_PAYMENT_PAGE_MARSHRUT' => 'Маршрут',
                'MSG_MSG_PAYMENT_PAGE_PASAZHIRIV' => 'Пассажиров',
                'MSG_MSG_PAYMENT_PAGE_CINA' => 'Цена',
                'MSG_MSG_PAYMENT_PAGE_DO_SPLATI' => 'К оплате',
                'MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE' => 'Не удалось оформить заказ, попробуйте позже',
                'MSG_ALL_KOLI' => 'Когда',
                'BOOKING' => 'Бронирование',
                'PAYMENT' => 'Оплата',
                'RETURN_CONDITIONS' => 'Условия возврата'
            ],
            'uk' => [
                'MSG_MSG_TICKETS_VIBIR_AVTOBUSA' => 'Вибір автобуса',
                'MSG_MSG_PAYMENT_PAGE_OPLATA' => 'Оплата',
                'MSG_MSG_PAYMENT_PAGE_OBERITI_SPOSIB_OPLATI' => 'Оберіть спосіб оплати',
                'MSG_MSG_PAYMENT_PAGE_DLYA_OFORMLENNYA_ZAMOVLENNYA_OPLATITI_JOGO_DO' => 'Для оформлення замовлення оплатіть його до',
                'MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA' => 'Банківською карткою',
                'MSG_MSG_PAYMENT_PAGE_GOTIVKOYU' => 'Готівкою',
                'MSG_MSG_PAYMENT_PAGE_GRN' => 'грн',
                'MSG_MSG_PAYMENT_PAGE_OPLATITI' => 'Оплатити',
                'MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI' => 'Ваші платіжні та особисті дані надійно захищені',
                'MSG_MSG_PAYMENT_PAGE_MARSHRUT' => 'Маршрут',
                'MSG_MSG_PAYMENT_PAGE_PASAZHIRIV' => 'Пасажирів',
                'MSG_MSG_PAYMENT_PAGE_CINA' => 'Ціна',
                'MSG_MSG_PAYMENT_PAGE_DO_SPLATI' => 'До сплати',
                'MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE' => 'Не вдалося оформити замовлення, спробуйте пізніше',
                'MSG_ALL_KOLI' => 'Коли',
                'BOOKING' => 'Бронювання',
                'PAYMENT' => 'Оплата',
                'RETURN_CONDITIONS' => 'Умови повернення'
            ],
            'en' => [
                'MSG_MSG_TICKETS_VIBIR_AVTOBUSA' => 'Bus selection',
                'MSG_MSG_PAYMENT_PAGE_OPLATA' => 'Payment',
                'MSG_MSG_PAYMENT_PAGE_OBERITI_SPOSIB_OPLATI' => 'Choose payment method',
                'MSG_MSG_PAYMENT_PAGE_DLYA_OFORMLENNYA_ZAMOVLENNYA_OPLATITI_JOGO_DO' => 'To complete the order, pay it before',
                'MSG_MSG_PAYMENT_PAGE_BANKIVSIKA_KARTKA' => 'Bank card',
                'MSG_MSG_PAYMENT_PAGE_GOTIVKOYU' => 'Cash',
                'MSG_MSG_PAYMENT_PAGE_GRN' => 'UAH',
                'MSG_MSG_PAYMENT_PAGE_OPLATITI' => 'Pay',
                'MSG_MSG_PAYMENT_PAGE_VASHI_PLATIZHNI_TA_OSOBISTI_DANI_NADIJNO_ZAHISCHENI' => 'Your payment and personal data are securely protected',
                'MSG_MSG_PAYMENT_PAGE_MARSHRUT' => 'Route',
                'MSG_MSG_PAYMENT_PAGE_PASAZHIRIV' => 'Passengers',
                'MSG_MSG_PAYMENT_PAGE_CINA' => 'Price',
                'MSG_MSG_PAYMENT_PAGE_DO_SPLATI' => 'To pay',
                'MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE' => 'Failed to place order, try again later',
                'MSG_ALL_KOLI' => 'When',
                'BOOKING' => 'Booking',
                'PAYMENT' => 'Payment',
                'RETURN_CONDITIONS' => 'Return conditions'
            ]
        ];
    }

    /**
     * Получить перевод по ключу
     */
    public function get(string $key, string $default = ''): string
    {
        return $this->dictionary[$this->currentLang][$key] ?? $default ?: $key;
    }

    /**
     * Получить текущий язык
     */
    public function getCurrentLang(): string
    {
        return $this->currentLang;
    }

    /**
     * Установить язык
     */
    public function setLang(string $lang): void
    {
        $this->currentLang = $lang;
        session(['language' => $lang]);
    }

    /**
     * Получить все переводы для текущего языка
     */
    public function all(): array
    {
        return $this->dictionary[$this->currentLang] ?? [];
    }
}
