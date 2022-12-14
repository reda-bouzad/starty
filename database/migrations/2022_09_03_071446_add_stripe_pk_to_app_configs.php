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
        Schema::table('app_configs', function (Blueprint $table) {
            $table->text('stripe_pk')->nullable();
            $table->unsignedInteger('android_build')->default(0);
            $table->unsignedInteger('ios_build')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_configs', function (Blueprint $table) {
            $table->dropColumn(['stripe_pk','android_build','ios_build']);
        });
    }
};
