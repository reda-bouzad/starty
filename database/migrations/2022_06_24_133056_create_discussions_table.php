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
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['user1']);
            $table->dropForeign(['user2']);
            $table->dropColumn(['user1','user2']);
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('receiver');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chats',function(Blueprint $table){
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->dropMorphs('receiver');
        });
    }
};
