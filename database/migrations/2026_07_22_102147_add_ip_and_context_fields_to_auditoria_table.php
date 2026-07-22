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
        Schema::table('auditoria', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('accion');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->index('user_id');
            $table->index('nombre_tabla');
            $table->index('accion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['nombre_tabla']);
            $table->dropIndex(['accion']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
