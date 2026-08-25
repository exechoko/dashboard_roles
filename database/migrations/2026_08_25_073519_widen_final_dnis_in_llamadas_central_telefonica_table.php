<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->string('final_dnis', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('llamadas_central_telefonica', function (Blueprint $table) {
            $table->string('final_dnis', 30)->nullable()->change();
        });
    }
};
