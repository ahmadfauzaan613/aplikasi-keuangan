<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'amount' => $this->faker->randomFloat(2, 10000, 10000000),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'category' => $this->faker->randomElement([
                'Gaji', 'Bonus', 'Investasi', 'Makanan & Minuman', 'Transportasi', 'Belanja', 'Utilitas/Tagihan', 'Hiburan', 'Lain-lain'
            ]),
            'description' => $this->faker->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'metadata' => [
                'ip' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
        ];
    }
}
