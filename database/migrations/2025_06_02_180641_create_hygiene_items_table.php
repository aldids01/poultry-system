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
        Schema::create('hygiene_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->enum('status', ['Clean', 'Dirty'])->default('Clean');
            $table->longText('remarks')->nullable();
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
        Schema::dropIfExists('hygiene_items');
    }
};
