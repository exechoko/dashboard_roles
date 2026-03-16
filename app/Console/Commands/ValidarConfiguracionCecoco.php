<?php

namespace App\Console\Commands;

use App\Services\CecocoExpedienteService;
use Illuminate\Console\Command;

class ValidarConfiguracionCecoco extends Command
{
    protected $signature = 'cecoco:validar-config';

    protected $description = 'Valida la configuración de integración con CECOCO para expedientes';

    public function handle(CecocoExpedienteService $service)
    {
        $this->info('🔍 Validando configuración de CECOCO Expedientes...');
        $this->newLine();

        $validacion = $service->validarConfiguracion();

        $this->table(
            ['Configuración', 'Valor'],
            [
                ['Python Path', $validacion['configuracion']['python_path']],
                ['Script Path', $validacion['configuracion']['script_path']],
                ['Output Path', $validacion['configuracion']['output_path']],
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
