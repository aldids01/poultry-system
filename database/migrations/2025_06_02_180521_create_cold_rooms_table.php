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
        Schema::create('cold_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique()->nullable();
            $table->time('time_in')->nullable();
            $table->longText('product_description')->nullable();
            $table->string('quality')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->string('blast_freezer_temperature')->nullable();
            $table->string('cold_room_temperature')->nullable();
            $table->foreignId('transferred_by_id')->nullable()->constrained('users');
            $table->longText('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cold_rooms');
    }
};
