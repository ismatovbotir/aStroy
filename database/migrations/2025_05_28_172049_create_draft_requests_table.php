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
        Schema::create('draft_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_id');
            $table->foreign('telegram_id')->references('id')->on('telegrams')->onDelete('cascade');
            $table->text('content');
            $table->string("type")->nullable()->default('text'); // Тип контента: text, image, video и т.д.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_requests');
    }
};
