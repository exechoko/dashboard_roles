<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el campo errores de TEXT a LONGTEXT para soportar mensajes de error muy largos
        DB::statement("ALTER TABLE `importaciones` MODIFY COLUMN `errores` LONGTEXT NULL");
    }

    public function down(): void
    {
        // Revertir a TEXT
        DB::statement("ALTER TABLE `importaciones` MODIFY COLUMN `errores` TEXT NULL");
    }
};
