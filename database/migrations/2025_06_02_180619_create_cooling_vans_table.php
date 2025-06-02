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
        Schema::create('cooling_vans', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name');
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->decimal('total', 20, 2)->nullable();
            $table->decimal('fuel_total', 20, 2)->nullable();
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
        Schema::dropIfExists('cooling_vans');
    }
};
