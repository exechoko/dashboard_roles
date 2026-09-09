<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('descarga_archivos', function (Blueprint $table) {
            $table->string('job_id', 255)->nullable()->after('user_id');
            $table->enum('estado_proceso', ['pendiente', 'procesando', 'completado', 'error'])
                  ->default('pendiente')
                  ->after('job_id');
            $table->unsignedTinyInteger('progreso')->default(0)->after('estado_proceso');
            $table->text('error_proceso')->nullable()->after('progreso');
            $table->timestamp('procesado_at')->nullable()->after('error_proceso');
            
            $table->index(['estado_proceso', 'job_id']);
        });
    }

    public function down(): void
    {
        Schema::table('descarga_archivos', function (Blueprint $table) {
            $table->dropIndex(['estado_proceso', 'job_id']);
            $table->dropColumn([
                'job_id',
                'estado_proceso',
                'progreso',
                'error_proceso',
                'procesado_at',
            ]);
        });
    }
};
