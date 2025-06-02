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
        Schema::create('bird_receiveds', function (Blueprint $table) {
            $table->id();
            $table->time('time_of_arrival')->nullable();
            $table->string('batch_number')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->string('vehicle_number')->nullable();
            $table->integer('birds_delivered')->nullable();
            $table->integer('birds_dea')->nullable();
            $table->integer('birds_accepted')->nullable();
            $table->foreignId('recovery_officer_id')->nullable()->constrained('users');
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
        Schema::dropIfExists('bird_receiveds');
    }
};
