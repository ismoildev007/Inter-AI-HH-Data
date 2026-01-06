<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('about')->nullable();
            $table->unsignedInteger('salary_expectation')->nullable();
            $table->float('experience')->nullable(); // in years
            $table->string('specialization')->nullable();
            $table->json('skills')->nullable();
            $table->json('contact_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('candidates');
    }
};
