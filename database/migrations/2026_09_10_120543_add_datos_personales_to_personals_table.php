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
        Schema::table('personals', function (Blueprint $table) {
            $table->string('direccion')->nullable()->after('observaciones_personal911');
            $table->string('telefono')->nullable()->after('direccion');
            $table->string('email')->nullable()->after('telefono');
            $table->string('estado_civil', 50)->nullable()->after('email');
            $table->date('fecha_nacimiento')->nullable()->after('estado_civil');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personals', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'telefono', 'email', 'estado_civil', 'fecha_nacimiento']);
        });
    }
};
