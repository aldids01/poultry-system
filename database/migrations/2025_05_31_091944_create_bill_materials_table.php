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
        Schema::create('bill_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_good_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity_needed', 20, 2);
            $table->decimal('cost', 20, 2);
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->unique(['finished_good_id', 'raw_material_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_materials');
    }
};
