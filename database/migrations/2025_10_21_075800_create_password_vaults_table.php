<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasswordVaultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('password_vaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('system_name');
            $table->enum('system_type', ['web', 'vpn', 'escritorio','windows', 'cecoco', 'dss', 'anydesk', 'radmin', 'policial', 'personal','servidor','nms', 'router', 'remoto', 'base_datos', 'email', 'ftp', 'ssh','otro']);
            $table->string('url')->nullable();
            $table->string('username');
            $table->text('password'); // Encriptado
            $table->text('notes')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('favorite')->default(false);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'system_type']);
            $table->index('favorite');
        });

        // Tabla para compartir contraseñas entre usuarios
        Schema::create('password_vault_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('password_vault_id')->constrained()->onDelete('cascade');
            $table->foreignId('shared_with_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shared_by_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['password_vault_id', 'shared_with_user_id'], 'vault_share_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('password_vault_shares');
        Schema::dropIfExists('password_vaults');
    }
}
