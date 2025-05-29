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
        Schema::create('telegrams', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();

            $table->string('nick')->nullable();
            $table->string('phone')->nullable();
            $table->string('name')->nullable();
            $table->string('surename')->nullable();
            $table->foreignId('object_id')->nullable(); // объект стройки
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegrams');
    }
};
