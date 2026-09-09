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
        Schema::table('descarga_archivos', function (Blueprint $table) {
            $table->foreignId('compartido_por_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->boolean('es_compartido')->default(false)->after('compartido_por_user_id');
            
            $table->index(['es_compartido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('descarga_archivos', function (Blueprint $table) {
            $table->dropIndex(['es_compartido']);
            $table->dropForeign(['compartido_por_user_id']);
            $table->dropColumn(['compartido_por_user_id', 'es_compartido']);
        });
    }
};
