
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
        Schema::create('reports', function (Blueprint $table) {
           $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
        });

         Schema::create('model_report', function (Blueprint $table) {
           $table->bigIncrements('id');
           $table->unsignedBigInteger('model_id');
           $table->unsignedBigInteger('report_id');
           $table->unsignedInteger('user_id');
           $table->string('model_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
         Schema::dropIfExists('model_report');
    }
};
