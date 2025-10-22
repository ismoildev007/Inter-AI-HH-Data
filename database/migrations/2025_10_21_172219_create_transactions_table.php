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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->nullable();
            $table->string('payment_status')->nullable();
            $table->enum('payment_method', ['payme', 'click'])->default('payme')->nullable();
            $table->integer('state')->default(0); // 0 = created, 1 = performed, 2 = cancelled
            $table->bigInteger('amount');
            $table->bigInteger('create_time')->nullable();
            $table->bigInteger('perform_time')->nullable();
            $table->bigInteger('cancel_time')->nullable();
            $table->integer('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
