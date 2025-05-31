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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('product_type', ['raw_material', 'finished_good'])->default('raw_material');
            $table->integer('quantity_on_hand')->nullable();
            $table->decimal('cost', 20, 2)->default(0.00);
            $table->decimal('price', 20, 2)->default(0.00);
            $table->string('description')->nullable();
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->decimal('stock_value', 20, 2)->virtualAs(DB::raw('cost * quantity_on_hand'));
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
