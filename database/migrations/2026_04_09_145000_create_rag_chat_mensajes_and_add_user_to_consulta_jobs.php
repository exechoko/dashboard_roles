<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRagChatMensajesAndAddUserToConsultaJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rag_chat_mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('coleccion');
            $table->enum('role', ['user', 'assistant']);
            $table->text('contenido');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'coleccion']);
        });

        Schema::table('rag_consulta_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rag_chat_mensajes');
        Schema::table('rag_consulta_jobs', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
}
