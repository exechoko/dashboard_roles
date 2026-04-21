<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoricoMovilProcesadosTable extends Migration
{
    public function up()
    {
        Schema::create('historico_movil_procesados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('nombre_archivo');
            $table->string('recurso')->nullable();
            $table->string('fecha_inicio')->nullable();
            $table->string('fecha_fin')->nullable();
            $table->unsignedInteger('posiciones')->default(0);
            $table->float('velocidad_maxima')->default(45);
            $table->unsignedInteger('umbral_naranja')->default(30); // minutos
            $table->unsignedInteger('umbral_rojo')->default(45);    // minutos
            $table->json('metadata');
            $table->longText('registros_json');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('historico_movil_procesados');
    }
}
