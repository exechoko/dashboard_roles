<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->unsignedTinyInteger('zona')->nullable()->after('horario');
            $table->string('ht', 50)->nullable()->after('zona');
            $table->unsignedBigInteger('parte_diario_id')->nullable()->after('id');

            $table->foreign('parte_diario_id')->references('id')->on('partes_diarios')->nullOnDelete();
            $table->index('parte_diario_id');
        });
    }

    public function down(): void
    {
        Schema::table('recurso_estado_diario', function (Blueprint $table) {
            $table->dropForeign(['parte_diario_id']);
            $table->dropIndex(['parte_diario_id']);
            $table->dropColumn(['zona', 'ht', 'parte_diario_id']);
        });
    }
};
