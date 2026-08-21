<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los filtros "De" y "Para/CC" usaban LIKE '%texto%', que con comodín al
 * inicio nunca puede usar un índice normal: en un buzón de ~40k mensajes
 * tardaban varios segundos por búsqueda. Se agregan índices FULLTEXT para
 * poder resolverlos con MATCH AGAINST, igual que ya se hace con el buscador
 * de texto libre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Separados (no un solo índice con las 4 columnas) porque "De" solo
        // debe buscar en remitente y "Para" solo en destinatarios/CC: un
        // único FULLTEXT combinado haría que un nombre en el CC de un mensaje
        // apareciera como falso positivo al buscar por remitente.
        DB::statement('ALTER TABLE mail_mensajes ADD FULLTEXT INDEX mail_mensajes_fulltext_de (de_nombre, de_email)');
        DB::statement('ALTER TABLE mail_mensajes ADD FULLTEXT INDEX mail_mensajes_fulltext_para (para, cc)');
        DB::statement('ALTER TABLE mail_mensajes ADD FULLTEXT INDEX mail_mensajes_fulltext_asunto (asunto)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE mail_mensajes DROP INDEX mail_mensajes_fulltext_de');
        DB::statement('ALTER TABLE mail_mensajes DROP INDEX mail_mensajes_fulltext_para');
        DB::statement('ALTER TABLE mail_mensajes DROP INDEX mail_mensajes_fulltext_asunto');
    }
};
