<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelevarAccesoriosInicial extends Command
{
    protected $signature = 'equipos:relevar-accesorios-inicial {--dry-run : Muestra qué se marcaría, sin escribir nada}';

    protected $description = 'Carga el relevamiento inicial de accesorios faltantes: los MDT400 sin antena RF y la flota HTT500, que no tiene antenas disponibles.';

    /**
     * Últimos 5 dígitos del TEI de los MDT400 relevados sin antena R.F.
     *
     * @var array<int, string>
     */
    private const MDT400_SIN_RF = [
        '13190', '14120', '57980', '14050', '14180', '13810',
        '13400', '13270', '13790', '57780', '13120', '13490',
    ];

    /**
     * Ese MDT400 además está sin frente remoto.
     */
    private const MDT400_SIN_FRENTE = '13490';

    private const NOTA_MDT400 = 'Falta antena RF (relevamiento inicial)';

    private const NOTA_HTT500 = 'Sin antena disponible para este modelo (relevamiento inicial)';

    public function handle(): int
    {
        $simulacion = (bool) $this->option('dry-run');

        if ($simulacion) {
            $this->warn('Modo simulación: no se escribe nada en la base.');
        }

        $mdt400 = $this->marcarMdt400($simulacion);
        $frente = $this->marcarFrenteRemoto($simulacion);
        $htt500 = $this->marcarHtt500($simulacion);

        $this->newLine();
        $this->table(['Relevamiento', 'Equipos'], [
            ['MDT400 sin antena R.F.', $mdt400],
            ['MDT400 sin frente remoto', $frente],
            ['HTT500 sin antena R.F.', $htt500],
        ]);

        if ($simulacion) {
            $this->info('Nada se escribió. Volvé a correr sin --dry-run para aplicarlo.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Listo. Operativos: %d · Degradados: %d.',
            Equipo::query()->disponible()->count(),
            Equipo::query()->degradado()->count()
        ));

        return self::SUCCESS;
    }

    /**
     * Marca los MDT400 relevados sin antena R.F., buscándolos por el final del TEI.
     */
    private function marcarMdt400(bool $simulacion): int
    {
        $ids = Equipo::query()
            ->whereIn(DB::raw('RIGHT(tei, 5)'), self::MDT400_SIN_RF)
            ->whereNull('rf')
            ->pluck('id');

        $this->avisarTeisNoEncontrados();

        return $this->aplicar($ids, ['rf' => 0, 'desc_rf' => self::NOTA_MDT400], $simulacion);
    }

    private function marcarFrenteRemoto(bool $simulacion): int
    {
        $ids = Equipo::query()
            ->whereRaw('RIGHT(tei, 5) = ?', [self::MDT400_SIN_FRENTE])
            ->whereNull('frente_remoto')
            ->pluck('id');

        return $this->aplicar($ids, [
            'frente_remoto' => 0,
            'desc_frente' => 'Falta frente remoto (relevamiento inicial)',
        ], $simulacion);
    }

    /**
     * Marca la flota HTT500 en estado operativo: no hay antenas para equiparlos, así
     * que aunque el estado diga que están bien no pueden salir a la calle.
     */
    private function marcarHtt500(bool $simulacion): int
    {
        $ids = Equipo::query()
            ->whereIn('estado_id', Equipo::operativoEstadoIds())
            ->whereNull('rf')
            ->whereHas('tipo_terminal', fn ($q) => $q->where('marca', 'Teltronic')->where('modelo', 'HTT500'))
            ->pluck('id');

        return $this->aplicar($ids, ['rf' => 0, 'desc_rf' => self::NOTA_HTT500], $simulacion);
    }

    /**
     * Avisa si algún TEI de la lista no existe en esta base, para no dar por hecho
     * un relevamiento que en realidad no se aplicó.
     */
    private function avisarTeisNoEncontrados(): void
    {
        $encontrados = Equipo::query()
            ->whereIn(DB::raw('RIGHT(tei, 5)'), self::MDT400_SIN_RF)
            ->pluck(DB::raw('RIGHT(tei, 5)'))
            ->all();

        $faltantes = array_diff(self::MDT400_SIN_RF, $encontrados);

        if ($faltantes !== []) {
            $this->warn('No encontré estos TEI (terminados en): ' . implode(', ', $faltantes));
        }
    }

    /**
     * Escribe el relevamiento salteando los equipos que ya lo tienen cargado, para
     * que el comando se pueda correr más de una vez sin pisar nada.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $ids
     * @param  array<string, mixed>  $valores
     */
    private function aplicar($ids, array $valores, bool $simulacion): int
    {
        if ($ids->isEmpty()) {
            return 0;
        }

        if ($simulacion) {
            return $ids->count();
        }

        return DB::table('equipos')
            ->whereIn('id', $ids)
            ->update($valores + ['updated_at' => now()]);
    }
}
