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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('factory_id')->constrained('factories')->cascadeOnDelete();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('order_date');
            $table->dateTime('delivery_date');
            $table->dateTime('approved_date')->nullable();
            $table->longText('remarks')->nullable();
            $table->decimal('total', 20, 2)->default(0);
            $table->enum('status', ['Pending', 'Approved', 'Received', 'Posted', 'Cancelled'])->default('Pending');
            $table->enum('payment', ['Pending', 'Processing', 'Completed', 'Cancelled'])->default('Pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
