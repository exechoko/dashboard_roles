<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            // 'movil' | 'escritorio': determina a qué URL abre la notificación
            // (la ficha liviana de /movil/chat o la vista de escritorio).
            $table->string('plataforma')->default('movil')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn('plataforma');
        });
    }
};
