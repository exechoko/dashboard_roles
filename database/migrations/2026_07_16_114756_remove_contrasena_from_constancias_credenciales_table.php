<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('constancias_credenciales', function (Blueprint $table) {
            $table->dropColumn('contrasena');
        });
    }

    public function down(): void
    {
        Schema::table('constancias_credenciales', function (Blueprint $table) {
            $table->string('contrasena')->after('email');
        });
    }
};
