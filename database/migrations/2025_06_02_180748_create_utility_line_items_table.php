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
        Schema::create('utility_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utility_id')->constrained('utility_facilities')->cascadeOnDelete();
            $table->foreignId('utility_item_id')->constrained('utility_items')->cascadeOnDelete();
            $table->enum('status', ['Okay', 'Needs Attention'])->default('Okay');
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
        Schema::dropIfExists('utility_line_items');
    }
};
