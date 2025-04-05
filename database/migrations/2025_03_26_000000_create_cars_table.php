<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarsTable extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('country');
            $table->string('banner_url')->nullable();
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('model');
            $table->year('year');
            $table->foreignUuid('brand_id')->constrained('brands')->onDelete('cascade');
            $table->string('color');
            $table->decimal('price', 15, 2)->unsigned();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid']);
            $table->enum('availability', ['in_stock', 'pre_order', 'out_of_stock'])->default('in_stock');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cars');
        Schema::dropIfExists('brands');
    }
}
