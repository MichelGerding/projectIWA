<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("contract", function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("subscription");
            $table->json("station_selection_json");

            $table->foreign("subscription")->references("id")->on("subscription");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("contract");
    }
};
