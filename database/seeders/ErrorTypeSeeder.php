<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ErrorTypeSeeder extends Seeder
{
    /**
     * Generate the different types of errors
     */
    public function run(): void
    {
        DB::table('error_type')->insertOrIgnore([
            [ 'id' => 1, 'name' => "missing" ],
            [ 'id' => 2, 'name' => "invalid" ]]);
    }
}
