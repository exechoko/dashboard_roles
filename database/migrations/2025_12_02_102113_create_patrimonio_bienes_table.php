<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatrimonioBienesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patrimonio_bienes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_bien_id')->constrained('patrimonio_tipos_bien')->onDelete('restrict');
            $table->foreignId('destino_id')->nullable()->constrained('destino')->onDelete('set null');
            $table->string('ubicacion', 150)->nullable();
            $table->string('siaf', 100)->nullable()->index();
            $table->text('descripcion');
            $table->string('numero_serie', 255)->nullable()->index();
            $table->enum('estado', ['activo', 'baja', 'transferido', 'desuso', 'rotura'])->default('activo')->index();
            $table->date('fecha_alta');
            $table->string('tabla_origen', 100)->nullable();
            $table->unsignedBigInteger('id_origen')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índice compuesto para búsqueda de origen
            $table->index(['tabla_origen', 'id_origen'], 'idx_origen');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patrimonio_bienes');
    }
}
