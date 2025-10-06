<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->string('username')->nullable()->unique()->index();
            $table->string('channel_id')->unique();
           // $table->string('title')->nullable();
            $table->boolean('is_source')->default(true)->index();
            $table->boolean('is_target')->default(false)->index();
            $table->unsignedBigInteger('last_message_id')->nullable();
           // $table->json('raw_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_channels');
    }
};

