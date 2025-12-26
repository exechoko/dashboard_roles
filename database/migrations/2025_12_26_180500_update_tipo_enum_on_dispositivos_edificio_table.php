<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tipos = [
        'pc',
        'puesto_cecoco',
        'puesto_video',
        'router',
        'switch',
        'camara_interna',
        'servidor',
        'servidor_cecoco',
        'servidor_nebula',
        'grabador_nebula',
        'nvr',
        'access_point',
    ];

    public function up(): void
    {
        $tipos = "'" . implode("','", $this->tipos) . "'";
        DB::statement("ALTER TABLE `dispositivos_edificio` MODIFY COLUMN `tipo` ENUM($tipos) NOT NULL");
    }

    public function down(): void
    {
        $tiposOriginales = "'pc','puesto_cecoco','puesto_video','router','switch','camara_interna'";
        DB::statement("ALTER TABLE `dispositivos_edificio` MODIFY COLUMN `tipo` ENUM($tiposOriginales) NOT NULL");
    }
};
