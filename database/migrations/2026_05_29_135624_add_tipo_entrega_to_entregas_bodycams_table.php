<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entregas_bodycams', function (Blueprint $table) {
            $table->enum('tipo_entrega', ['normal', 'recambio_tecnologico'])
                ->default('normal')
                ->after('hora_entrega')
                ->comment('Tipo de entrega de bodycams');

            $table->index(['tipo_entrega']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entregas_bodycams', function (Blueprint $table) {
            $table->dropIndex(['tipo_entrega']);
            $table->dropColumn('tipo_entrega');
        });
    }
};
