<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Generate a subscription and contract
     */
    public function run(): void
    {
        (new Subscription([
            "id" => 4,
            "subscription_type" => 3,
            "apikey" => base64_encode(password_hash(fake()->unique()->name, PASSWORD_DEFAULT))
        ]))->save();
        (new Contract([
            "id" => 1,
            "subscription" => 4,
            "station_selection_json" => json_encode( [
                "filters" => [
                    [
                        "type" => "coords",
                        "value" => "48.856614,2.3522219",
                        "range" => 100
                    ],
                    [
                        "type" => "measurement",
                        "value" => [
                            "temp",
                        ]
                    ]
                ]
            ])
        ]))->save();
    }
}
