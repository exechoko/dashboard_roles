<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('localidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('cp', 20)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
