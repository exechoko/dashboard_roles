<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Para MySQL/MariaDB, necesitamos modificar la columna ENUM
        DB::statement("ALTER TABLE `importaciones` MODIFY COLUMN `estado` ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'procesando'");
    }

    public function down(): void
    {
        // Revertir al estado anterior (sin 'pendiente')
        DB::statement("ALTER TABLE `importaciones` MODIFY COLUMN `estado` ENUM('procesando', 'completado', 'error') DEFAULT 'procesando'");
    }
};
