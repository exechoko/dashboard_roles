<?php

namespace App\Console\Commands;

use App\Services\CecocoExpedienteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CecocoTamanoRestauraciones extends Command
{
    protected $signature = 'cecoco:tamano-restauraciones {--show-cache : Sólo muestra el valor cacheado, sin consultar CECOCO}';

    protected $description = 'Consulta CECOCO Históricos > Gestión > Restauraciones y guarda el tamaño de la BD en caché. Útil para diagnosticar producción.';

    public function handle(CecocoExpedienteService $service)
    {
        $this->info('🔍 Diagnóstico tamaño BD restauraciones CECOCO');
        $this->newLine();

        // 1. Mostrar configuración efectiva
        $this->table(
            ['Variable', 'Valor'],
            [
                ['CECOCO_URL',             config('cecoco.url') ?: '(vacío)'],
                ['CECOCO_USER',            config('cecoco.user') ?: '(vacío)'],
                ['CECOCO_USER_MONITOR',    config('cecoco.user_monitor') ?: '(vacío - usará CECOCO_USER)'],
                ['CECOCO_PASSWORD_MONITOR', config('cecoco.password_monitor') ? '✓ definida' : '(vacío - usará CECOCO_PASSWORD)'],
                ['CECOCO_TIMEOUT',         config('cecoco.timeout') . ' s'],
                ['Cache driver',           config('cache.default')],
            ]
        );
        $this->newLine();

        // 2. Mostrar valor cacheado actual
        $cached = $service->obtenerTamanoBaseRestauraciones();
        if ($cached) {
            $this->info("📦 Cache actual: {$cached['mb']} MB (consultado {$cached['consultado_en']})");
        } else {
            $this->warn('📦 Cache vacío (aún no se consultó CECOCO o expiró)');
        }
        $this->newLine();

        if ($this->option('show-cache')) {
            return Command::SUCCESS;
        }

        // 3. Llamar al servicio en vivo (login JSF + dispatch + parseo)
        $this->info('🌐 Consultando CECOCO en vivo (login JSF completo)...');
        try {
            $resultado = $service->actualizarCacheTamanoBaseRestauraciones();
            $this->newLine();
            $this->info('✅ Consulta exitosa');
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Tamaño',        $resultado['mb'] . ' MB'],
                    ['Consultado en', $resultado['consultado_en']],
                    ['Umbral alerta', '4000 MB'],
                    ['Estado',        $resultado['mb'] > 4000 ? '⚠️  SUPERA EL UMBRAL' : '✅ OK'],
                ]
            );
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('❌ Falló la consulta:');
            $this->error('   ' . $e->getMessage());
            $this->newLine();
            $this->line('<fg=yellow>Pistas comunes:</>');
            $this->line('  • Si dice "Usuario en sesión": el CECOCO_USER_MONITOR está abierto en otro navegador.');
            $this->line('  • Si dice "no se pudo conectar"/"cURL": producción no llega al host CECOCO (firewall/red).');
            $this->line('  • Si dice "ViewState"/"login rechazado": cambiaron las credenciales o el formulario.');
            $this->line('  • Si dice "Bean no inicializado": login fue OK pero el dispatch falló (revisar perfil del usuario).');
            return Command::FAILURE;
        }
    }
}
