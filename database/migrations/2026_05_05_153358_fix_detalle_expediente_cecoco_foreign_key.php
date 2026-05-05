<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the broken FK that points to evento_cecoco_old
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->dropForeign('detalle_expediente_cecoco_evento_cecoco_id_foreign');
        });

        // Recreate it pointing to the correct table
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->foreign('evento_cecoco_id')
                ->references('id')
                ->on('evento_cecoco')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->dropForeign(['evento_cecoco_id']);
        });

        Schema::table('detalle_expediente_cecoco', function (Blueprint $table) {
            $table->foreign('evento_cecoco_id')
                ->references('id')
                ->on('evento_cecoco_old')
                ->cascadeOnDelete();
        });
    }
};
