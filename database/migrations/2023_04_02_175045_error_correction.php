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

        Schema::create('error_type', function (Blueprint $table) {
            $table->id();
            $table->string('name', 10)->unique();
        });
        Schema::create('error', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('measurement');
            $table->unsignedBigInteger('error_type');
            $table->float('value')->nullable();
            $table->string('measurement_type', 10);

            // foreign keys
            $table->foreign('measurement')->references('id')->on('measurement');
            $table->foreign('error_type')->references('id')->on('error_type');
            
            // indexes
            $table->index(['measurement', 'measurement_type', 'error_type']);
            $table->index(['measurement', 'error_type']);
            $table->index(['measurement_type', 'error_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error');
        Schema::dropIfExists('error_type');
    }
};
