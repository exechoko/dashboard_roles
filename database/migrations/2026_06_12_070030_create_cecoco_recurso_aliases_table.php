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
        Schema::create('cecoco_recurso_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_cecoco', 50)->unique();
            $table->foreignId('recurso_id')->nullable()->constrained('recursos')->nullOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->boolean('activo')->default(true)->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cecoco_recurso_aliases');
    }
};
