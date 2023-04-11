<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table("event_participants", function (Blueprint $table) {
            $table
                ->enum("is_visible", ["visible", "hidden"])
                ->default("visible");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table("event_participants", function (Blueprint $table) {
            $table->dropColumn("is_visible");
        });
    }
};
