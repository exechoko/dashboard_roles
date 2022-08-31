<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDivisionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('divisiones', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('direccion_id')->nullable();
            $table->unsignedBigInteger('departamental_id')->nullable();
            $table->string('nombre')->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('ubicacion')->nullable();
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
        Schema::dropIfExists('divisiones');
    }
}
