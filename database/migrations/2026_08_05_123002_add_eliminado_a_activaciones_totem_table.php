<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activaciones_totem', function (Blueprint $table) {
            $table->foreignId('eliminado_por')->nullable()->after('observaciones')->constrained('users')->nullOnDelete();
            $table->dateTime('fecha_eliminado')->nullable()->after('eliminado_por');
        });
    }

    public function down(): void
    {
        Schema::table('activaciones_totem', function (Blueprint $table) {
            $table->dropConstrainedForeignId('eliminado_por');
            $table->dropColumn('fecha_eliminado');
        });
    }
};
