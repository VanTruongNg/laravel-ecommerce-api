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
        Schema::create('cart_car', function (Blueprint $table) {
            $table->uuid('car_id');
            $table->uuid('cart_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->primary(['car_id', 'cart_id']);
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('cascade');
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_car');
    }
};
