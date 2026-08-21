<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_mensajes', function (Blueprint $table) {
            $table->index(['buzon_id', 'fecha'], 'mail_mensajes_buzon_fecha_index');
            $table->index(['buzon_id', 'carpeta', 'fecha'], 'mail_mensajes_buzon_carpeta_fecha_index');
            $table->index(['buzon_id', 'de_email'], 'mail_mensajes_buzon_de_email_index');
            $table->index(['buzon_id', 'tamano_bytes'], 'mail_mensajes_buzon_tamano_index');
        });

        // asunto es VARCHAR(998) en utf8mb4: entra completo en el índice
        // fulltext, pero supera los 3072 bytes máximos de un índice btree
        // normal, así que acá se indexa solo un prefijo (alcanza para ORDER BY).
        DB::statement('ALTER TABLE mail_mensajes ADD INDEX mail_mensajes_buzon_asunto_index (buzon_id, asunto(191))');
    }

    public function down(): void
    {
        Schema::table('mail_mensajes', function (Blueprint $table) {
            $table->dropIndex('mail_mensajes_buzon_fecha_index');
            $table->dropIndex('mail_mensajes_buzon_carpeta_fecha_index');
            $table->dropIndex('mail_mensajes_buzon_de_email_index');
            $table->dropIndex('mail_mensajes_buzon_asunto_index');
            $table->dropIndex('mail_mensajes_buzon_tamano_index');
        });
    }
};
