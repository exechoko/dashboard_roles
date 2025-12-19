<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCamaraInternaToPasswordVaultsSystemType extends Migration
{
    public function up()
    {
        $allowed = [
            'web',
            'vpn',
            'escritorio',
            'windows',
            'cecoco',
            'dss',
            'anydesk',
            'radmin',
            'policial',
            'personal',
            'servidor',
            'nms',
            'router',
            'remoto',
            'base_datos',
            'email',
            'ftp',
            'ssh',
            'camara_interna',
            'otro',
        ];

        $columns = DB::select("SHOW COLUMNS FROM `password_vaults` LIKE 'system_type'");
        if (!$columns) {
            return;
        }

        $type = $columns[0]->Type ?? '';
        if (is_string($type) && strpos($type, "'camara_interna'") !== false) {
            return;
        }

        // Normalizar valores existentes inválidos (incluye valores vacíos generados por inserts viejos)
        DB::table('password_vaults')
            ->whereNull('system_type')
            ->orWhere('system_type', '')
            ->orWhereNotIn('system_type', $allowed)
            ->update(['system_type' => 'otro']);

        DB::statement(
            "ALTER TABLE `password_vaults` MODIFY `system_type` ENUM('web','vpn','escritorio','windows','cecoco','dss','anydesk','radmin','policial','personal','servidor','nms','router','remoto','base_datos','email','ftp','ssh','camara_interna','otro') NOT NULL"
        );
    }

    public function down()
    {
        $allowed = [
            'web',
            'vpn',
            'escritorio',
            'windows',
            'cecoco',
            'dss',
            'anydesk',
            'radmin',
            'policial',
            'personal',
            'servidor',
            'nms',
            'router',
            'remoto',
            'base_datos',
            'email',
            'ftp',
            'ssh',
            'otro',
        ];

        $columns = DB::select("SHOW COLUMNS FROM `password_vaults` LIKE 'system_type'");
        if (!$columns) {
            return;
        }

        $type = $columns[0]->Type ?? '';
        if (!is_string($type) || strpos($type, "'camara_interna'") === false) {
            return;
        }

        // Evitar error al remover el ENUM si existen registros con ese valor
        DB::table('password_vaults')
            ->where('system_type', 'camara_interna')
            ->update(['system_type' => 'otro']);

        // Normalizar valores existentes inválidos (incluye valores vacíos generados por inserts viejos)
        DB::table('password_vaults')
            ->whereNull('system_type')
            ->orWhere('system_type', '')
            ->orWhereNotIn('system_type', $allowed)
            ->update(['system_type' => 'otro']);

        DB::statement(
            "ALTER TABLE `password_vaults` MODIFY `system_type` ENUM('web','vpn','escritorio','windows','cecoco','dss','anydesk','radmin','policial','personal','servidor','nms','router','remoto','base_datos','email','ftp','ssh','otro') NOT NULL"
        );
    }
}
