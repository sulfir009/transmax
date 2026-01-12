<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'active' => 1,
            'client_id' => $this->faker->numberBetween(0, 100), // 0 для гостевых заказов
            'tour_id' => $this->faker->numberBetween(1, 100),
            'from_stop' => $this->faker->numberBetween(1, 50),
            'to_stop' => $this->faker->numberBetween(1, 50),
            'tour_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'passagers' => $this->faker->numberBetween(1, 4), // Правильное имя поля
            'document' => $this->faker->numberBetween(1, 3),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'ticket_return' => 0,
            'return_reason' => 0,
            'return_payment_type' => 0,
            'return_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'client_name' => $this->faker->firstName(),
            'client_surname' => $this->faker->lastName(),
            'client_email' => $this->faker->email(),
            'client_phone' => '+380' . $this->faker->numerify('#########'),
            'uniqid' => uniqid('order_', true),
            'payment_status' => $this->faker->randomElement([1, 2, 3, 4]) // 1-pending, 2-completed, 3-failed, 4-cancelled
        ];
    }

    /**
     * Заказ со статусом "в ожидании"
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 1,
        ]);
    }

    /**
     * Заказ со статусом "завершен"
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 2,
        ]);
    }

    /**
     * Заказ со статусом "неудачный"
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 3,
        ]);
    }

    /**
     * Заказ со статусом "отменен"
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 4,
        ]);
    }

    /**
     * Гостевой заказ
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => 0,
        ]);
    }

    /**
     * Заказ на сегодня
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'tour_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Заказ на завтра
     */
    public function tomorrow(): static
    {
        return $this->state(fn (array $attributes) => [
            'tour_date' => now()->addDay()->format('Y-m-d'),
        ]);
    }

    /**
     * Активный заказ
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => 1,
        ]);
    }

    /**
     * Неактивный заказ
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => 0,
        ]);
    }
}
