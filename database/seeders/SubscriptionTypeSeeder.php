<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscription_type')->insertOrIgnore([
            [ 'id' => 1, 'type' => "subscription_live" ],
            [ 'id' => 2, 'type' => "subscription_periodic" ],
            [ 'id' => 3, 'type' => "contract" ]]);
    }
}
