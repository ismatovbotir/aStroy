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
        Schema::create('requests', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('telegram_id');
    $table->foreign('telegram_id')->references('id')->on('telegrams')->onDelete('cascade');
    $table->foreignId('object_id')->nullable();
    $table->text('content');
    $table->enum('status', ['new', 'accepted', 'rejected', 'done'])->default('new');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
