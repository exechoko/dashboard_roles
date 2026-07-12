<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->string('numeracion_arma', 50)->nullable()->after('jerarquia');
            $table->foreignId('arma_tipo_id')->nullable()->after('numeracion_arma')->constrained('arma_tipos')->nullOnDelete();
            $table->string('nro_chaleco', 50)->nullable()->after('arma_tipo_id');
        });

        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->dropColumn(['numeracion_arma', 'nro_chaleco']);
        });
    }

    public function down(): void
    {
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->string('numeracion_arma', 50)->nullable();
            $table->string('nro_chaleco', 50)->nullable();
        });

        Schema::table('personals', function (Blueprint $table) {
            $table->dropForeign(['arma_tipo_id']);
            $table->dropColumn(['numeracion_arma', 'arma_tipo_id', 'nro_chaleco']);
        });
    }
};
