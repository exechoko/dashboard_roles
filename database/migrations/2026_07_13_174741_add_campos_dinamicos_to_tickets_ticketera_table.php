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
        Schema::table('tickets_ticketera', function (Blueprint $table) {
            $table->string('oficina', 160)->nullable()->after('dependencia');
            $table->timestamp('fecha_inicio_falla')->nullable()->after('problema_detectado');
            $table->timestamp('fecha_fin_falla')->nullable()->after('fecha_inicio_falla');
            $table->foreignId('recurso_id')->nullable()->after('movil')->constrained('recursos')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable()->after('recurso_id')->constrained('equipos')->nullOnDelete();
            $table->foreignId('tipo_terminal_id')->nullable()->after('equipo_id')->constrained('tipo_terminales')->nullOnDelete();
            $table->json('camaras_afectadas')->nullable()->after('subsistema');
            $table->unsignedInteger('cantidad_items')->nullable()->after('camaras_afectadas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets_ticketera', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurso_id');
            $table->dropConstrainedForeignId('equipo_id');
            $table->dropConstrainedForeignId('tipo_terminal_id');
            $table->dropColumn([
                'oficina',
                'fecha_inicio_falla',
                'fecha_fin_falla',
                'camaras_afectadas',
                'cantidad_items',
            ]);
        });
    }
};
