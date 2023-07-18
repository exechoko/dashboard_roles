<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditoriaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable(); //Usuario que realizo la modificacion
            $table->integer('usuario_modificado_id')->nullable();
            $table->integer('flota_modificado_id')->nullable();
            $table->integer('act_pol_modificado_id')->nullable();
            $table->integer('camara_modificado_id')->nullable();
            $table->integer('comisaria_modificado_id')->nullable();
            $table->integer('departamental_modificado_id')->nullable();
            $table->integer('destino_modificado_id')->nullable();
            $table->integer('direccion_modificado_id')->nullable();
            $table->integer('division_modificado_id')->nullable();
            $table->integer('empresa_sop_modificado_id')->nullable();
            $table->integer('equipo_modificado_id')->nullable();
            $table->integer('historico_modificado_id')->nullable();
            $table->integer('recurso_modificado_id')->nullable();
            $table->integer('seccion_modificado_id')->nullable();
            $table->integer('vehiculo_modificado_id')->nullable();
            $table->string('nombre_tabla');
            $table->longText('cambios')->nullable();
            $table->string('accion');
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
        Schema::dropIfExists('auditoria');
    }
}
