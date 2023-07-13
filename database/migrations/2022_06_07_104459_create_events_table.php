<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->integer('nb_participants');
            $table->enum('type', ['public', 'private'])->default('public');
            $table->boolean('pricy')->default(false);
            $table->float('price')->default(0.0);
            $table->point('location')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('events');
    }
};
