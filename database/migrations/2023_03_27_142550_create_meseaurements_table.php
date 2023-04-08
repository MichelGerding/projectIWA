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
        Schema::create('measurement', function (Blueprint $table) {
            $table->id();
            $table->string('stn', 10);

            $table->date('date');
            $table->time('time');

            // measurements
            $table->float('temp', 5, 2)->nullable();
            $table->float('dewp')->nullable();
            $table->float('stp')->nullable();
            $table->float('slp')->nullable();
            $table->float('visib')->nullable();
            $table->float('wdsp')->nullable();
            $table->float('prcp')->nullable();
            $table->float('sndp')->nullable();
            $table->float('frshtt')->nullable();
            $table->float('cldc')->nullable();
            $table->float('wnddir')->nullable();

            // foreign keys
            $table->foreign('stn')->references('name')->on('station');

            // indexes
            $table->index(['stn', 'date', 'time']);
            $table->index(['stn', 'date']);
            $table->index(['date', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement');
    }
};
