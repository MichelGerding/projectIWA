<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsSeeder extends Seeder
{
    /**
     * generate 1 subscription of each type. 
     * uses the same stations as in MeasurementSeeder
     */
    public function run(): void
    {
        // live data
        (new Subscription([
            "id" => 1,
            "subscription_type" => 1,
            "apikey" => base64_encode(password_hash(fake()->unique()->name, PASSWORD_DEFAULT))
        ]))->save();
        DB::table("subscription_station")->insert([
            "id" => 1,
            "subscription" => 1,
            "station" => 100020
        ]);

        // periodic one station
        (new Subscription([
            "id" => 2,
            "subscription_type" => 2,
            "apikey" => base64_encode(password_hash(fake()->unique()->name, PASSWORD_DEFAULT))
        ]))->save();
        DB::table("subscription_station")->insert([
            "id" => 2,
            "subscription" => 2,
            "station" => 100020
        ]);

        // periodic multiple stations
        (new Subscription([
            "id" => 3,
            "subscription_type" => 2,
            "apikey" => base64_encode(password_hash(fake()->unique()->name, PASSWORD_DEFAULT))
        ]))->save();
        DB::table("subscription_station")->insert([
            "id" => 3,
            "subscription" => 3,
            "station" => 100020
        ]);
        DB::table("subscription_station")->insert([
            "id" => 4,
            "subscription" => 3,
            "station" => 724019
        ]);





    }
}
