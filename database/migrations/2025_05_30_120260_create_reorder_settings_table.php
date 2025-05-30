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
        Schema::create('reorder_settings', function (Blueprint $table) {
            $table->foreignId('product_id')->primary()->constrained('products');
            $table->integer('reorder_point');
            $table->integer('reorder_quantity');
            $table->foreignId('preferred_supplier_id')->nullable()->constrained('suppliers');
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reorder_settings');
    }
};
