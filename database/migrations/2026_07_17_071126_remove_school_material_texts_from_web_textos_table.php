<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('web_textos')
            ->whereIn('clave', [
                'educacion.tarjeta7_titulo',
                'educacion.tarjeta7_texto',
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $ahora = now();

        DB::table('web_textos')->insertOrIgnore([
            [
                'clave' => 'educacion.tarjeta7_titulo',
                'valor' => 'Material para Escuelas',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            [
                'clave' => 'educacion.tarjeta7_texto',
                'valor' => 'Programas educativos, material descargable, videos didácticos y actividades para el aula.',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }
};
