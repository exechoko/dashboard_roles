<?php

namespace App\Console\Commands;

use App\Services\CecocoExpedienteService;
use Illuminate\Console\Command;

class ValidarConfiguracionCecoco extends Command
{
    protected $signature = 'cecoco:validar-config';

    protected $description = 'Valida la configuración de integración PHP nativa con CECOCO para expedientes (sin Python)';

    public function handle(CecocoExpedienteService $service)
    {
        $this->info('🔍 Validando configuración de CECOCO Expedientes...');
        $this->newLine();

        $validacion = $service->validarConfiguracion();

        $this->table(
            ['Configuración', 'Valor'],
            [
                ['CECOCO URL', $validacion['configuracion']['cecoco_url']],
                ['Usuario', $validacion['configuracion']['cecoco_user']],
                ['Timeout', $validacion['configuracion']['timeout'] . ' segundos'],
            ]
        );

        $this->newLine();

        if ($validacion['valido']) {
            $this->info('✅ Configuración válida - El sistema está listo para consultar expedientes CECOCO');
            return Command::SUCCESS;
        }

        $this->error('❌ Se encontraron errores en la configuración:');
        $this->newLine();

        foreach ($validacion['errores'] as $error) {
            $this->line("  • {$error}");
        }

        $this->newLine();
        $this->warn('💡 Revisa el archivo .env y asegúrate de que las rutas sean correctas.');
        $this->warn('💡 Consulta INTEGRACION_CECOCO_EXPEDIENTES.md para más información.');

        return Command::FAILURE;
    }
}
