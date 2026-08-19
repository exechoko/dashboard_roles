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
        Schema::table('chat_participantes', function (Blueprint $table) {
            $table->timestamp('aviso_no_leido_enviado_at')->nullable()->after('ultimo_leido_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_participantes', function (Blueprint $table) {
            $table->dropColumn('aviso_no_leido_enviado_at');
        });
    }
};
