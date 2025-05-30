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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('factory_id')->constrained('factories')->onDelete('cascade');
            $table->enum('transaction_type', ['IN', 'OUT', 'ADJUSTMENT', 'RETURN']); // e.g., 'IN', 'OUT', 'ADJUSTMENT', 'RETURN'
            $table->integer('quantity_changed');
            $table->dateTime('transaction_date')->useCurrent();
            $table->string('source_destination')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
