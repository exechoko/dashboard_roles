<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirmanteDestinoIdToPatrimonioCargosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patrimonio_cargos', function (Blueprint $table) {
            $table->unsignedBigInteger('firmante_destino_id')->nullable()->after('firmante_legajo');
            
            $table->foreign('firmante_destino_id')
                ->references('id')
                ->on('destino')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patrimonio_cargos', function (Blueprint $table) {
            $table->dropForeign(['firmante_destino_id']);
            $table->dropColumn('firmante_destino_id');
        });
    }
}
