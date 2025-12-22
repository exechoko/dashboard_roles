<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositivos_edificio', function (Blueprint $table) {
            if (!Schema::hasColumn('dispositivos_edificio', 'username')) {
                $table->string('username')->nullable()->after('puertos');
            }
            if (!Schema::hasColumn('dispositivos_edificio', 'password')) {
                $table->text('password')->nullable()->after('username');
            }
        });

        // Migrar credenciales desde password_vaults (si existía relación)
        if (Schema::hasColumn('dispositivos_edificio', 'password_vault_id')) {
            DB::statement(
                "UPDATE `dispositivos_edificio` d "
                . "JOIN `password_vaults` p ON p.id = d.password_vault_id "
                . "SET d.username = COALESCE(d.username, p.username), d.password = COALESCE(d.password, p.password) "
                . "WHERE d.password_vault_id IS NOT NULL"
            );
        }

        Schema::table('dispositivos_edificio', function (Blueprint $table) {
            if (Schema::hasColumn('dispositivos_edificio', 'password_vault_id')) {
                $table->dropForeign(['password_vault_id']);
                $table->dropColumn('password_vault_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispositivos_edificio', function (Blueprint $table) {
            if (!Schema::hasColumn('dispositivos_edificio', 'password_vault_id')) {
                $table->foreignId('password_vault_id')->nullable()->constrained('password_vaults');
            }

            if (Schema::hasColumn('dispositivos_edificio', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('dispositivos_edificio', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};
