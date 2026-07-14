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
        if (!Schema::hasColumn('arma_retenciones', 'motivo_eliminacion')) {
            Schema::table('arma_retenciones', function (Blueprint $table) {
                $table->text('motivo_eliminacion')->nullable()->after('deleted_at');
                $table->foreignId('eliminado_por')->nullable()->after('motivo_eliminacion')->constrained('users')->nullOnDelete();
                $table->timestamp('eliminado_en')->nullable()->after('eliminado_por');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arma_retenciones', function (Blueprint $table) {
            $table->dropForeign(['eliminado_por']);
            $table->dropColumn(['motivo_eliminacion', 'eliminado_por', 'eliminado_en']);
        });
    }
};
