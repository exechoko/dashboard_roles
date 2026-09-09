<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->boolean('es_chofer')->default(false)->after('personal_id');
            $table->unsignedSmallInteger('orden')->default(0)->after('es_chofer');
            $table->unsignedBigInteger('parte_diario_id')->nullable()->after('id');

            $table->foreign('parte_diario_id')->references('id')->on('partes_diarios')->nullOnDelete();
            $table->index('parte_diario_id');
        });
    }

    public function down(): void
    {
        Schema::table('recurso_dotaciones', function (Blueprint $table) {
            $table->dropForeign(['parte_diario_id']);
            $table->dropIndex(['parte_diario_id']);
            $table->dropColumn(['es_chofer', 'orden', 'parte_diario_id']);
        });
    }
};
