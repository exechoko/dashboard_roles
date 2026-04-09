<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRagTematicasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rag_tematicas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                    // "Manual de Usuario"
            $table->string('coleccion')->unique();       // "manual_de_usuario" (slug para ChromaDB)
            $table->text('descripcion')->nullable();
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
        Schema::dropIfExists('rag_tematicas');
    }
}
