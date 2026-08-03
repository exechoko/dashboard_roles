<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armeria_chalecos', function (Blueprint $table) {
            $table->id();
            $table->string('movil', 50)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 150)->nullable();
            $table->string('talle', 20)->nullable();
            $table->string('numero_serie', 50)->unique();
            $table->string('estado', 30)->default('EN_SERVICIO');
            $table->string('ubicacion', 30)->default('DIVISION_911');
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'ubicacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armeria_chalecos');
    }
};
