<?php

namespace App\Service;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Service\TicketService;

class ClientService
{
    protected string $dbPrefix;
    protected TicketService $ticketService;

    public function __construct()
    {
        // Загружаем конфигурацию legacy
        require_once config_path('legacy.php');
        $this->dbPrefix = DB_PREFIX;
        $this->ticketService = new TicketService();
    }

    /**
     * Отправка билета для онлайн покупки
     * Использует существующий TicketService
     */
    public function sendTicket($orderId)
    {
        try {
            Log::info('ClientService: Sending ticket for order: ' . $orderId);

            // Получаем информацию о заказе
            $orderInfo = DB::table($this->dbPrefix . '_orders')
                ->where('id', $orderId)
                ->first();



            if (!$orderInfo) {
                Log::error('ClientService: Order not found: ' . $orderId);
                return false;
            }

            // Проверяем, что это онлайн заказ
            if ($orderInfo->payment_status != 2) {
                Log::error('ClientService: Order is not paid online: ' . $orderId);
                return false;
            }

            // Получаем информацию о пассажирах
            $passengers = DB::table($this->dbPrefix . '_orders_passangers')
                ->where('order_id', $orderInfo->uniqid)
                ->get();

            Log::info('ClientService: Found passengers: ' . $passengers->count());

            // Используем существующий TicketService для отправки билета
            return $this->ticketService->processSuccessfulPayment($orderInfo->uniqid, []);

        } catch (\Exception $e) {
            Log::error('ClientService: Error sending ticket: ' . $e->getMessage());
            Log::error('ClientService: Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Отправка брони для оплаты наличными
     * Использует шаблон из ajax.php для order_mail
     */
    public function sendBooking($orderId)
    {
        try {
            Log::info('ClientService: Sending booking for order: ' . $orderId);

            // Получаем информацию о заказе
            $orderInfo = $this->getOrderInfo($orderId);

            if (!$orderInfo) {
                Log::error('ClientService: Order not found: ' . $orderId);
                return false;
            }

            // Получаем информацию о билете
            $ticketInfo = $this->getTicketInfo($orderInfo);

            if (!$ticketInfo) {
                Log::error('ClientService: Ticket info not found for order: ' . $orderId);
                return false;
            }

            // Получаем информацию о пассажирах
            $passengers = DB::table($this->dbPrefix . '_orders_passangers')
                ->where('order_id', $orderInfo->uniqId)
                ->get();

            // Отправляем письмо о брони
            $this->sendBookingEmail($orderInfo, $ticketInfo, $passengers);

            // Отправляем администратору (если не тест)
            $isTest = stripos($orderInfo->client_name, 'test') !== false ||
                      stripos($orderInfo->client_surname, 'test') !== false;

            if (!$isTest) {
                $this->sendBookingEmailToAdmin($orderInfo, $ticketInfo, $passengers);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('ClientService: Error sending booking: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Обработка возврата билета
     */
    public function processReturn($orderId, $reason)
    {
        try {
            Log::info('ClientService: Processing return for order: ' . $orderId);

            // Получаем информацию о заказе
            $orderInfo = $this->getOrderInfo($orderId);

            if (!$orderInfo) {
                Log::error('ClientService: Order not found: ' . $orderId);
                return false;
            }

            // Обновляем статус заказа
            DB::table($this->dbPrefix . '_orders')
                ->where('id', $orderId)
                ->update([
                    'ticket_return' => 1,
                    'return_reason' => $reason,
                    'return_date' => now()
                ]);

            // Отправляем уведомление о возврате
            $this->sendReturnNotification($orderInfo, $reason);

            return true;

        } catch (\Exception $e) {
            Log::error('ClientService: Error processing return: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Получение информации о заказе
     */
    private function getOrderInfo($orderId)
    {
        return DB::table($this->dbPrefix . '_orders as o')
            ->join($this->dbPrefix . '_cities as departure_station', 'departure_station.id', '=', 'o.from_stop')
            ->join($this->dbPrefix . '_cities as arrival_station', 'arrival_station.id', '=', 'o.to_stop')
            ->join($this->dbPrefix . '_cities as departure_city', 'departure_city.id', '=', 'departure_station.section_id')
            ->join($this->dbPrefix . '_cities as arrival_city', 'arrival_city.id', '=', 'arrival_station.section_id')
            ->join($this->dbPrefix . '_tours as t', 't.id', '=', 'o.tour_id')
            ->join($this->dbPrefix . '_buses as bus', 'bus.id', '=', 't.bus')
            ->select(
                'o.*',
                'departure_station.title_uk as departure_station',
                'arrival_station.title_uk as arrival_station',
                'departure_city.title_uk as departure_city',
                'arrival_city.title_uk as arrival_city',
                'bus.title_uk as bus_title'
            )
            ->where('o.id', $orderId)
            ->first();
    }

    /**
     * Получение информации о билете (копия из TicketService)
     */
    private function getTicketInfo($orderInfo)
    {
        return DB::select("
            SELECT
                from_stop.departure_time,
                from_city.title_uk AS departure_station,
                departure_city.title_uk AS departure_city,
                to_stop.arrival_time,
                to_city.title_uk AS arrival_station,
                arrival_city.title_uk AS arrival_city,
                bus.title_uk AS bus,
                prices.price
            FROM `{$this->dbPrefix}_tours_stops` AS from_stop
            JOIN `{$this->dbPrefix}_cities` AS from_city ON from_stop.stop_id = from_city.id
            JOIN `{$this->dbPrefix}_tours` AS tours ON from_stop.tour_id = tours.id
            JOIN `{$this->dbPrefix}_cities` AS departure_city ON departure_city.id = tours.departure
            JOIN `{$this->dbPrefix}_tours_stops` AS to_stop ON from_stop.tour_id = to_stop.tour_id
            JOIN `{$this->dbPrefix}_cities` AS to_city ON to_stop.stop_id = to_city.id
            JOIN `{$this->dbPrefix}_cities` AS arrival_city ON arrival_city.id = tours.arrival
            JOIN `{$this->dbPrefix}_buses` AS bus ON tours.bus = bus.id
            JOIN `{$this->dbPrefix}_tours_stops_prices` AS prices ON
                    prices.tour_id = from_stop.tour_id AND
                    prices.from_stop = from_stop.stop_id AND
                    prices.to_stop = to_stop.stop_id
            WHERE from_stop.tour_id = ?
            AND from_stop.stop_id = ?
            AND to_stop.stop_id = ?
        ", [$orderInfo->tour_id, $orderInfo->from_stop, $orderInfo->to_stop])[0] ?? null;
    }

    /**
     * Отправка email о брони клиенту
     */
    private function sendBookingEmail($orderInfo, $ticketInfo, $passengers)
    {
        $subject = "Ваша бронь";
        $to = $orderInfo->client_email;
        $message = $this->getBookingEmailTemplate($orderInfo, $ticketInfo, $passengers);
        $this->sendEmail($to, $subject, $message);
    }

    /**
     * Отправка email о брони администратору
     */
    private function sendBookingEmailToAdmin($orderInfo, $ticketInfo, $passengers)
    {
        $subject = $orderInfo->passagers > 1
            ? "Бронь {$orderInfo->passagers} билетов:"
            : "Бронь билета:";
        $to = "max210183@ukr.net";
        $message = $this->getBookingAdminEmailTemplate($orderInfo, $ticketInfo, $passengers);
        $this->sendEmail($to, $subject, $message);
    }

    /**
     * Шаблон email для брони клиенту (аналогично order_mail из ajax.php)
     */
    private function getBookingEmailTemplate($orderInfo, $ticketInfo, $passengers)
    {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $html = '
        <html>
        <head>
            <title>Ваша бронь</title>
            <style>
                .email-content {
                    border-left: 4px solid #40A6FF;
                    padding-left: 10px;
                }
                .email-content table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .email-content td {
                    padding: 5px 10px;
                }
                .email-titles {
                    font-weight: bold;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <a href="https://www.maxtransltd.com">
                <img src="' . $imagePath . '" alt="MaxTrans LTD" class="logo">
                </a>
            </div>
            <p>Ваша бронь:</p>
            <div class="email-content">
                <table>
                    <tr>
                        <td class="email-titles">Бронь</td>
                        <td>' . $orderInfo->id . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Рейс</td>
                        <td>' . $orderInfo->departure_city . ' - ' . $orderInfo->arrival_city . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Выезд</td>
                        <td>' . $orderInfo->tour_date . ' ' . substr($ticketInfo->departure_time, 0, 5) . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Откуда</td>
                        <td>' . $orderInfo->departure_city . ' ' . $orderInfo->departure_station . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Куда</td>
                        <td>' . $orderInfo->arrival_city . ' ' . $orderInfo->arrival_station . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Пассажир</td>
                        <td>' . $orderInfo->client_name . ' ' . $orderInfo->client_surname . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Телефон</td>
                        <td>' . $orderInfo->client_phone . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">E-mail</td>
                        <td>' . $orderInfo->client_email . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Цена билета</td>
                        <td>' . $ticketInfo->price . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Количество пассажиров</td>
                        <td>' . $orderInfo->passagers . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Сумма заказа</td>
                        <td>' . ($ticketInfo->price * $orderInfo->passagers) . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Способ оплаты</td>
                        <td>Наличными</td>
                    </tr>
                </table>
                <p><strong>Внимание!</strong> Это бронь билета. Для получения билета необходимо произвести оплату наличными в офисе или водителю.</p>
                <p>В стоимость билета включено перевозка одного места багажа весом до 25 кг. За каждую дополнительную единицу багажа предусмотрена доплата в размере 10% от стоимости билета.</p>
                <p>Перевозчик: Maks Trans LTD</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Шаблон email для администратора
     */
    private function getBookingAdminEmailTemplate($orderInfo, $ticketInfo, $passengers)
    {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $title = $orderInfo->passagers > 1
            ? "Бронь {$orderInfo->passagers} билетов:"
            : "Бронь билета:";

        $html = '
        <html>
        <head>
            <title>' . $title . '</title>
            <style>
                .email-content {
                    border-left: 4px solid #40A6FF;
                    padding-left: 10px;
                }
                .email-content table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .email-content td {
                    padding: 5px 10px;
                }
                .email-titles {
                    font-weight: bold;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <a href="https://www.maxtransltd.com">
                <img src="' . $imagePath . '" alt="MaxTrans LTD" class="logo">
                </a>
            </div>
            <p>' . $title . '</p>
            <div class="email-content">
                <table>
                    <tr>
                        <td class="email-titles">Покупатель</td>
                        <td>' . $orderInfo->client_name . ' ' . $orderInfo->client_surname . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Пассажиров</td>
                        <td>' . $orderInfo->passagers . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Бронь</td>
                        <td>' . $orderInfo->id . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Рейс</td>
                        <td>' . $orderInfo->departure_city . ' - ' . $orderInfo->arrival_city . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Выезд</td>
                        <td>' . $orderInfo->tour_date . ' ' . substr($ticketInfo->departure_time, 0, 5) . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Откуда</td>
                        <td>' . $orderInfo->departure_city . ' ' . $orderInfo->departure_station . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Куда</td>
                        <td>' . $orderInfo->arrival_city . ' ' . $orderInfo->arrival_station . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Телефон</td>
                        <td>' . $orderInfo->client_phone . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">E-mail</td>
                        <td>' . $orderInfo->client_email . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Цена</td>
                        <td>' . $ticketInfo->price . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Сумма заказа</td>
                        <td>' . ($ticketInfo->price * $orderInfo->passagers) . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Способ оплаты</td>
                        <td>Наличными</td>
                    </tr>
                </table>
                <p>Перевозчик: Maks Trans LTD</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Отправка уведомления о возврате
     */
    private function sendReturnNotification($orderInfo, $reason)
    {
        $subject = "Возврат билета";
        $to = $orderInfo->client_email;
        $message = $this->getReturnNotificationTemplate($orderInfo, $reason);
        $this->sendEmail($to, $subject, $message);
    }

    /**
     * Шаблон уведомления о возврате
     */
    private function getReturnNotificationTemplate($orderInfo, $reason)
    {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $html = '
        <html>
        <head>
            <title>Возврат билета</title>
            <style>
                .email-content {
                    border-left: 4px solid #40A6FF;
                    padding-left: 10px;
                }
                .email-content table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .email-content td {
                    padding: 5px 10px;
                }
                .email-titles {
                    font-weight: bold;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <a href="https://www.maxtransltd.com">
                <img src="' . $imagePath . '" alt="MaxTrans LTD" class="logo">
                </a>
            </div>
            <p><b>Уважаемый(ая) ' . $orderInfo->client_name . '</b></p>
            <p>Ваш запрос на возврат билета принят в обработку.</p>
            <div class="email-content">
                <table>
                    <tr>
                        <td class="email-titles">Заказ</td>
                        <td>' . $orderInfo->id . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Рейс</td>
                        <td>' . $orderInfo->departure_city . ' - ' . $orderInfo->arrival_city . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Дата поездки</td>
                        <td>' . $orderInfo->tour_date . '</td>
                    </tr>
                    <tr>
                        <td class="email-titles">Причина возврата</td>
                        <td>' . $reason . '</td>
                    </tr>
                </table>
                <p>Мы обработаем ваш запрос в кратчайшие сроки. Возврат средств будет произведен в соответствии с условиями возврата.</p>
                <p>Если у вас возникнут вопросы, обращайтесь по телефону <a href="tel:+380971603474">+380 97 160 34 74</a>.</p>
                <p>С уважением,<br>компания Max Trans LTD</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Отправка email
     */
    private function sendEmail($to, $subject, $message)
    {
        $fromName = "Max Trans LTD";
        $fromEmail = "info@maxtransltd.com";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: "' . $fromName . '" <' . $fromEmail . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        // Настройка параметров SMTP
        ini_set('SMTP', 'mail.adm.tools');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'info@maxtransltd.com');
        ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');

        try {
            if (!mail($to, $subject, $message, $headers)) {
                throw new \Exception('Mail sending failed.');
            }
            Log::info('Email sent successfully to: ' . $to);
            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }
}
