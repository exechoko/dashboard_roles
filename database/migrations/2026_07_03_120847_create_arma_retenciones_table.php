<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArmaRetencionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arma_retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_id')->constrained('personals');
            $table->string('numeracion_arma', 50);
            $table->string('nro_chaleco', 50)->nullable();
            $table->enum('tipo', ['RETENCIÓN', 'REGULACIÓN', 'RESGUARDO']);
            $table->foreignId('motivo_id')->constrained('arma_motivos');
            $table->date('fecha_posesion');
            $table->unsignedInteger('dias_restantes')->nullable();
            $table->date('fecha_elevacion')->nullable();
            $table->date('fecha_devolucion')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['EN_ARMERIA', 'EN_JEF_CENTRAL', 'DEVUELTA'])->default('EN_ARMERIA');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('personal_id');
            $table->index('motivo_id');
            $table->index('estado');
            $table->index('fecha_posesion');
            $table->index('numeracion_arma');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('arma_retenciones');
    }
}
