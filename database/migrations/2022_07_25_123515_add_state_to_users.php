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
        Schema::table('users', function (Blueprint $table) {
            $table->json("blocked_by")->nullable();
            $table->json("blocked_user")->nullable();
            $table->json("blocked_event")->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->json("blocked_by")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['blocked_by','blocked_user','blocked_event']);
        });
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('blocked_by');
        });
    }
};
