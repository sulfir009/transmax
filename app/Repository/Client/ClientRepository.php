<?php

namespace App\Repository\Client;

use Illuminate\Support\Facades\DB;

class ClientRepository
{
    const TABLE = 'mt_clients';
    const TABLE_ORDERS = 'mt_orders';
    const TABLE_TOURS = 'mt_tours';
    const TABLE_CITIES = 'mt_cities';
    const TABLE_RETURN_REASONS = 'mt_return_reasons';
    const TABLE_ORDERS_PASSENGERS = 'mt_orders_passangers';
    const TABLE_TOURS_SALES = 'mt_tours_sales';

    /**
     * Получить клиентов, купивших билеты онлайн
     *
     * @param string $lang
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getClientsWithOnlinePayment(string $lang, int $offset = 0, int $limit = 20): array
    {
        $result =  DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
                'c.name',
                'c.second_name',
                'c.patronymic',
                'c.email',
                'c.phone',
                'departure_city.title_' . $lang . ' as departure_city',
                'arrival_city.title_' . $lang . ' as arrival_city'
            ])
            ->leftJoin(self::TABLE . ' as c', 'c.id', '=', 'o.client_id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'o.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as departure_city', 'departure_city.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as arrival_city', 'arrival_city.id', '=', 't.arrival')
            ->where('o.payment_status', 2)
            ->where('o.ticket_return', 0)
            ->orderBy('o.date', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        /*dd($result);*/
        return $result;
    }

    public function getOrderInfoById($id)
    {
        $result = DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
            ])
            ->where('o.id', '=',$id)
            ->get()
            ->toArray();

        return $result;
    }

    /**
     * Получить клиентов, купивших билеты за наличные
     *
     * @param string $lang
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getClientsWithCashPayment(string $lang, int $offset = 0, int $limit = 20): array
    {
        return DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
                'c.name',
                'c.second_name',
                'c.patronymic',
                'c.email',
                'c.phone',
                'departure_city.title_' . $lang . ' as departure_city',
                'arrival_city.title_' . $lang . ' as arrival_city'
            ])
            ->leftJoin(self::TABLE . ' as c', 'c.id', '=', 'o.client_id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'o.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as departure_city', 'departure_city.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as arrival_city', 'arrival_city.id', '=', 't.arrival')
            ->where('o.payment_status', 1)
            ->where('o.ticket_return', 0)
            ->orderBy('o.date', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Получить клиентов с возвращенными билетами
     *
     * @param string $lang
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getClientsWithReturnedTickets(string $lang, int $offset = 0, int $limit = 20): array
    {
        return DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
                'c.name',
                'c.second_name',
                'c.patronymic',
                'c.email',
                'c.phone',
                'departure_city.title_' . $lang . ' as departure_city',
                'arrival_city.title_' . $lang . ' as arrival_city',
                'rr.title_' . $lang . ' as return_reason_title'
            ])
            ->leftJoin(self::TABLE . ' as c', 'c.id', '=', 'o.client_id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'o.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as departure_city', 'departure_city.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as arrival_city', 'arrival_city.id', '=', 't.arrival')
            ->leftJoin(self::TABLE_RETURN_REASONS . ' as rr', 'rr.id', '=', 'o.return_reason')
            ->where('o.ticket_return', 1)
            ->orderBy('o.return_date', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Подсчитать количество заказов по типу оплаты
     *
     * @param int $paymentStatus
     * @param bool $returned
     * @return int
     */
    public function countOrdersByPaymentType(int $paymentStatus, bool $returned = false): int
    {
        $query = DB::table(self::TABLE_ORDERS)
            ->where('payment_status', $paymentStatus);

        if ($returned) {
            $query->where('ticket_return', 1);
        } else {
            $query->where('ticket_return', 0);
        }

        return $query->count();
    }

    /**
     * Подсчитать количество возвращенных билетов
     *
     * @return int
     */
    public function countReturnedTickets(): int
    {
        return DB::table(self::TABLE_ORDERS)
            ->where('ticket_return', 1)
            ->count();
    }

    /**
     * Получить клиента по ID
     *
     * @param int $id
     * @return array|null
     */
    public function getClientById(int $id): ?array
    {
        $result = DB::table(self::TABLE)
            ->where('id', $id)
            ->first();

        return $result ? (array) $result : null;
    }

    /**
     * Обновить данные клиента
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateClient(int $id, array $data): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Получить информацию о заказе для отправки билета/брони
     *
     * @param int $orderId
     * @return array|null
     */
    public function getOrderForEmail(int $orderId): ?array
    {
        $result = DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
                'c.name',
                'c.second_name',
                'c.email',
                'c.phone',
                'departure_city.title_ru as departure_city',
                'arrival_city.title_ru as arrival_city',
                't.title_ru as tour_title'
            ])
            ->leftJoin(self::TABLE . ' as c', 'c.id', '=', 'o.client_id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'o.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as departure_city', 'departure_city.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as arrival_city', 'arrival_city.id', '=', 't.arrival')
            ->where('o.id', $orderId)
            ->first();

        return $result ? (array) $result : null;
    }

    /**
     * Получить детальную информацию о возврате
     *
     * @param int $orderId
     * @param string $lang
     * @return array|null
     */
    public function getReturnDetails(int $orderId, string $lang): ?array
    {
        $result = DB::table(self::TABLE_ORDERS . ' as o')
            ->select([
                'o.*',
                'c.name',
                'c.second_name',
                'c.patronymic',
                'c.email',
                'c.phone',
                'departure_city.title_' . $lang . ' as departure_city',
                'arrival_city.title_' . $lang . ' as arrival_city',
                'rr.title_' . $lang . ' as return_reason_title'
            ])
            ->leftJoin(self::TABLE . ' as c', 'c.id', '=', 'o.client_id')
            ->leftJoin(self::TABLE_TOURS . ' as t', 't.id', '=', 'o.tour_id')
            ->leftJoin(self::TABLE_CITIES . ' as departure_city', 'departure_city.id', '=', 't.departure')
            ->leftJoin(self::TABLE_CITIES . ' as arrival_city', 'arrival_city.id', '=', 't.arrival')
            ->leftJoin(self::TABLE_RETURN_REASONS . ' as rr', 'rr.id', '=', 'o.return_reason')
            ->where('o.id', $orderId)
            ->first();

        return $result ? (array) $result : null;
    }

    /**
     * Получить пассажиров заказа
     *
     * @param string $orderId
     * @return array
     */
    public function getOrderPassengers(string $orderId): array
    {
        return DB::table(self::TABLE_ORDERS_PASSENGERS)
            ->where('order_id', $orderId)
            ->get()
            ->toArray();
    }
}
