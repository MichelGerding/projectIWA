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
        Schema::create("subscription_type", function (Blueprint $table) {
            $table->id();
            $table->string("type", 32);
        });

        // create the minimal subscription table
        Schema::create("subscription", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("subscription_type");
            $table->string("apikey", 120)->unique();

            $table->foreign("subscription_type")->references("id")->on("subscription_type");

            $table->index(["apikey"]);
        });

        Schema::create("subscription_station", function(Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("subscription");
            $table->string("station");


            $table->foreign("subscription")->references("id")->on("subscription");
            $table->foreign("station")->references("name")->on("station");
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_type');
        Schema::dropIfExists('subscription');
        Schema::dropIfExists('subscription_station');
    }
};
