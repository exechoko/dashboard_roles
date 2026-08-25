<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llamadas_central_telefonica', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 40)->unique();
            $table->dateTime('calldate');
            $table->string('ani', 30)->nullable();
            $table->string('dialed_number', 30)->nullable();
            $table->string('final_dnis', 30)->nullable();
            $table->string('forwarded_to', 30)->nullable();
            $table->unsignedInteger('duration');
            $table->unsignedInteger('bill_duration');
            $table->boolean('es_interna')->default(false);
            $table->boolean('atendida')->default(false);
            $table->string('periodo', 7)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->unsignedTinyInteger('mes')->nullable();
            $table->string('archivo_origen', 255)->nullable();
            $table->timestamps();

            $table->index('calldate');
            $table->index('es_interna');
            $table->index('atendida');
            $table->index('periodo');
            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llamadas_central_telefonica');
    }
};
