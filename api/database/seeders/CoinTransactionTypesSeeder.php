<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CoinTransactionType;

class CoinTransactionTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Welcome bonus', 'type' => 'C'],
            ['name' => 'Coin purchase', 'type' => 'C'],
            ['name' => 'Game fee', 'type' => 'D'],
            ['name' => 'Match stake', 'type' => 'D'],
            ['name' => 'Game payout', 'type' => 'C'],
            ['name' => 'Match payout', 'type' => 'C'],
        ];

        foreach ($types as $type) {
            CoinTransactionType::firstOrCreate(
                ['name' => $type['name']],
                ['type' => $type['type']]
            );
        }
    }
}
