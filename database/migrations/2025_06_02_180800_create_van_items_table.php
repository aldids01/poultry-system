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
        Schema::create('van_items', function (Blueprint $table) {
            $table->id();
            $table->time('departure_time');
            $table->string('amount_products_carried');
            $table->string('delivery_location');
            $table->time('return_time')->nullable();
            $table->decimal('fuel_level')->default(0);
            $table->longText('remarks')->nullable();
            $table->foreignId('cooling_id')->constrained('cooling_vans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vam_items');
    }
};
