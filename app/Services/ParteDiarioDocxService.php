<?php

namespace App\Services;

use App\Models\ParteDiario;
use App\Models\ParteDiarioNovedades;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

/**
 * Genera los partes diarios de Flota 911 en formato oficio:
 *  - Parte de móviles (Sección Patrulla): dotaciones por zona + hoja NOVEDADES.
 *  - Parte de motos (Sección Patrulla Motorizada): nómina jerárquica + asignación de servicios.
 */
class ParteDiarioDocxService
{
    private const FONT = 'Arial';
    private const MARGIN = 1000;

    /** Abreviaturas de jerarquía como se escriben en el oficio. */
    private const JERARQUIA_ABREV = [
        'Crio. General'   => 'CRIO. GRAL.',
        'Crio. Mayor'     => 'CRIO. MY.',
        'Crio. Principal' => 'CRIO. PPAL.',
        'Crio. Inspector' => 'CRIO. INSP.',
        'Comisario'       => 'CRIO.',
        'Subcomisario'    => 'SUB CRIO.',
        'Of. Principal'   => 'OF. PPAL.',
        'Of. Inspector'   => 'OF. INSP.',
        'Of. SubInsp.'    => 'OF. SUB INSP.',
        'Of. Ayudante'    => 'OF. AYTE.',
        'Subof. Mayor'    => 'SUBOF. MY.',
        'Subof. Ppal.'    => 'SUBOF. PPAL.',
        'Sgto. Ayudante'  => 'SGTO. AYTE.',
        'Sgto. Primero'   => 'SGTO. 1°',
        'Sargento'        => 'SGTO.',
        'Cabo Primero'    => 'CABO 1°',
        'Cabo'            => 'CABO',
        'Agente'          => 'AGTE.',
    ];

    public function generar(
        ParteDiario $parte,
        ?ParteDiarioNovedades $novedades
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        return $parte->esMotos()
            ? $this->generarMotos($parte, $novedades)
            : $this->generarMoviles($parte, $novedades);
    }

    // ─── Parte de móviles ────────────────────────────────────────────────────

    private function generarMoviles(
        ParteDiario $parte,
        ?ParteDiarioNovedades $novedades
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $phpWord = $this->crearDocumento();
        $s = $phpWord->addSection($this->layout());

        $this->membrete($s, config('flota911.membrete.moviles'));
        $this->encabezadoOficio($s, $parte, 'Informar novedad.');
        $this->parrafo($s,
            'Cumplo en dirigirme a Ud., a los fines de llevar a su conocimiento la nómina de dotaciones '
            . 'correspondiente a cada móvil de esta sección, que cumplirán servicio de guardia desde las '
            . $parte->fecha_inicio->format('H:i') . ' hs, hasta las ' . $parte->fecha_fin->format('H:i')
            . ' hs. del día ' . $this->fechaLarga($parte->fecha) . ', a saber:'
        );
        $s->addTextBreak(1);

        $dotacionesPorRecurso = $parte->dotaciones->groupBy('recurso_id');
        $conduccion = [];
        $porZona = [1 => [], 2 => [], 3 => [], 4 => [], 0 => []];

        foreach ($parte->estadosDiarios as $estado) {
            $tripulacion = ($dotacionesPorRecurso->get($estado->recurso_id) ?? collect())->sortBy('orden');
            $estadoDia = $estado->estado_dia ?? 'circula';

            // Un móvil que "circula" sin dotación cargada no se reporta.
            if ($estadoDia === 'circula' && $tripulacion->isEmpty()) {
                continue;
            }

            $rol = $this->rolDeTripulacion($tripulacion);
            $fila = ['estado' => $estado, 'tripulacion' => $tripulacion, 'rol' => $rol];

            if ($rol !== null) {
                $conduccion[] = $fila;
            } else {
                $porZona[$estado->zona ?: 0][] = $fila;
            }
        }

        foreach ($conduccion as $fila) {
            $this->lineaMovil($s, $fila, $this->etiquetaRol($fila['rol']));
        }
        if ($conduccion !== []) {
            $s->addTextBreak(1);
        }

        foreach ([1, 2, 3, 4, 0] as $zona) {
            if ($porZona[$zona] === []) {
                continue;
            }
            $this->titulo($s, $zona === 0 ? 'SIN ZONA ASIGNADA' : 'ZONA ' . $zona, 11);
            foreach ($porZona[$zona] as $fila) {
                $this->lineaMovil($s, $fila);
            }
            $s->addTextBreak(1);
        }

        $this->pieFirma($s);

        // Dorso: NOVEDADES
        $sn = $phpWord->addSection($this->layout());
        $this->membrete($sn, config('flota911.membrete.novedades'));
        $this->titulo($sn, 'NOVEDADES', 12, true);
        $sn->addTextBreak(1);
        foreach (($novedades?->rubrosCompletos() ?? $this->rubrosVacios()) as $rubro) {
            $texto = $sn->addTextRun();
            $texto->addText($rubro['etiqueta'] . ': ', ['bold' => true, 'size' => 10, 'name' => self::FONT]);
            $texto->addText($rubro['valor'], ['size' => 10, 'name' => self::FONT]);
        }
        $this->pieFirma($sn);

        return $this->descargar($phpWord, 'Parte_Moviles_' . $parte->fecha_inicio->format('Ymd_Hi') . '.docx');
    }

    /**
     * @param  array{estado: mixed, tripulacion: \Illuminate\Support\Collection, rol: ?string}  $fila
     */
    private function lineaMovil(Section $s, array $fila, ?string $etiqueta = null): void
    {
        $estado = $fila['estado'];
        $ht = $estado->ht ? ' (HT ' . $estado->ht . ')' : '';
        $encabezado = $this->nombreMovil($estado->recurso?->nombre) . $ht . ':';

        $run = $s->addTextRun();
        $run->addText($encabezado, ['bold' => true, 'size' => 10, 'name' => self::FONT]);
        if ($etiqueta) {
            $run->addText('   (' . $etiqueta . ')', ['bold' => true, 'size' => 10, 'name' => self::FONT]);
        }

        if ($fila['tripulacion']->isEmpty()) {
            $s->addText('   Sin dotación cargada.', ['italic' => true, 'size' => 9, 'name' => self::FONT]);
        }

        foreach ($fila['tripulacion'] as $dot) {
            $sufijo = $dot->es_chofer ? ' (chofer)' : '';
            $s->addText('   ' . $this->nombre($dot->personal) . $sufijo, ['size' => 10, 'name' => self::FONT]);
        }

        $estadoDia = $estado->estado_dia ?? 'circula';
        if ($estadoDia !== 'circula') {
            $label = \App\Models\RecursoEstadoDiario::$estados[$estadoDia] ?? $estadoDia;
            $motivo = $estado->motivo ? ' — ' . $estado->motivo : '';
            $s->addText('   [' . $label . $motivo . ']', ['size' => 9, 'italic' => true, 'name' => self::FONT]);
        }
    }

    // ─── Parte de motos ──────────────────────────────────────────────────────

    private function generarMotos(
        ParteDiario $parte,
        ?ParteDiarioNovedades $novedades
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $phpWord = $this->crearDocumento();
        $s = $phpWord->addSection($this->layout());

        $this->membrete($s, config('flota911.membrete.motos'));
        $this->encabezadoOficio($s, $parte, 'informar.-');
        $this->parrafo($s,
            'Cumplo en dirigirme a Ud. a fines de llevar a su conocimiento la nómina del personal que integra la '
            . $this->guardiaLabel($parte->guardia) . ' de ésta Sección Patrulla Motorizada-911, como así también '
            . 'de las Moto Patrullas en servicio y Choferes a cargo, para desarrollar tareas de prevención en el día '
            . 'de la fecha desde las ' . $parte->fecha_inicio->format('H:i') . 'hs hasta las '
            . $parte->fecha_fin->format('H:i') . 'hs, a saber:'
        );
        $s->addTextBreak(1);

        $this->titulo($s, mb_strtoupper($this->guardiaLabel($parte->guardia), 'UTF-8'), 11);
        $s->addTextBreak(1);

        $recursoPorId = $parte->estadosDiarios->keyBy('recurso_id');
        $dotaciones = $parte->dotaciones->map(function ($dot) use ($recursoPorId) {
            $estado = $recursoPorId->get($dot->recurso_id);
            $rol = $this->rolDeFuncion($dot->personal?->funcion_personal911);

            return (object) [
                'personal'  => $dot->personal,
                'recurso'   => $estado?->recurso?->nombre ?? '—',
                'ht'       => $estado?->ht,
                'es_chofer' => (bool) $dot->es_chofer,
                'rol'       => $rol,
                'recurso_id' => $dot->recurso_id,
            ];
        });

        $recursosDeMando = $dotaciones->filter(fn ($d) => in_array($d->rol, ['jefe', 'segundo_jefe'], true))
            ->pluck('recurso_id')->unique()->all();

        $apartados = $dotaciones->filter(
            fn ($d) => in_array($d->rol, ['jefe', 'segundo_jefe'], true)
                || ($d->es_chofer && in_array($d->recurso_id, $recursosDeMando, true))
        );
        $nomina = $dotaciones->reject(fn ($d) => $apartados->contains($d))
            ->sortBy(fn ($d) => sprintf('%03d|%s', Personal::pesoJerarquia($d->personal?->jerarquia), Str::lower((string) $d->personal?->apellido)))
            ->values();

        foreach ($apartados->sortBy(fn ($d) => $d->rol === 'jefe' ? 0 : ($d->rol === 'segundo_jefe' ? 1 : 2)) as $d) {
            $s->addText($this->nombre($d->personal) . '   —   ' . $d->recurso, ['bold' => true, 'size' => 10, 'name' => self::FONT]);
        }
        if ($apartados->isNotEmpty()) {
            $s->addTextBreak(1);
        }

        foreach ($nomina as $i => $d) {
            $ht = $d->ht ? ' / ' . $d->ht : '';
            $s->addText(($i + 1) . '. ' . $this->nombre($d->personal) . '   —   ' . $d->recurso . $ht, ['size' => 10, 'name' => self::FONT]);
        }
        $s->addTextBreak(1);

        $this->lineaEtiquetada($s, 'Guardia', $parte->guardia_interna);
        $this->lineaEtiquetada($s, 'Personal de Licencia O.', $parte->licencia_ordinaria);
        $s->addTextBreak(1);

        $asignaciones = $parte->asignaciones
            ->filter(fn ($a) => trim((string) $a->asignacion_texto) !== '')
            ->values();

        if ($asignaciones->isNotEmpty()) {
            $this->titulo($s, 'Asignación de servicios:', 11);
            $this->grillaAsignaciones($s, $phpWord, $asignaciones);
            $s->addTextBreak(1);
        }

        $pie = trim((string) $parte->novedades_pie);
        if ($pie !== '') {
            $this->lineaEtiquetada($s, 'NOVEDADES', $pie);
        }

        $this->pieFirma($s);

        return $this->descargar($phpWord, 'Parte_Motos_' . $parte->fecha_inicio->format('Ymd_Hi') . '.docx');
    }

    /**
     * Grilla de "Asignación de servicios" en 4 columnas: por cada bloque de hasta
     * 4 consignas, una fila con el grupo (si es común), una con los nombres y otra
     * con las asignaciones — como en el parte de motos.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ParteDiarioAsignacion>  $asignaciones
     */
    private function grillaAsignaciones(Section $s, PhpWord $phpWord, Collection $asignaciones): void
    {
        $tabla = $s->addTable($this->estiloTabla($phpWord));
        $anchoCelda = (int) floor(9600 / 4);

        foreach ($asignaciones->chunk(4) as $bloque) {
            $bloque = $bloque->values();
            $n = $bloque->count();
            $grupos = $bloque->pluck('grupo')->map(fn ($g) => trim((string) $g))->unique();

            if ($grupos->count() === 1 && $grupos->first() !== '') {
                $tabla->addRow(260);
                $tabla->addCell($anchoCelda * $n, ['gridSpan' => $n, 'bgColor' => 'EEEEEE'])
                    ->addText($grupos->first(), ['bold' => true, 'size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            }

            $tabla->addRow(260);
            foreach ($bloque as $a) {
                $tabla->addCell($anchoCelda, ['bgColor' => '2C3E50'])
                    ->addText($a->nombre, ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => self::FONT], ['alignment' => 'center']);
            }

            $tabla->addRow(300);
            foreach ($bloque as $a) {
                $tabla->addCell($anchoCelda)
                    ->addText($a->asignacion_texto ?: '—', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            }
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function rolDeFuncion(?string $funcion): ?string
    {
        $f = Str::lower(trim((string) $funcion));
        if ($f === '') {
            return null;
        }
        if (str_contains($f, 'oficial de calle')) {
            return 'jefe_calle';
        }
        if (str_contains($f, 'jefe patrulla')) {
            return 'jefe_patrulla';
        }
        if (str_contains($f, 'jefe secci') && str_contains($f, 'motor')) {
            return preg_match('/(^2|2[°º]|2do|segundo)/u', $f) === 1 ? 'segundo_jefe' : 'jefe';
        }

        return null;
    }

    private function rolDeTripulacion(Collection $tripulacion): ?string
    {
        foreach ($tripulacion as $dot) {
            $rol = $this->rolDeFuncion($dot->personal?->funcion_personal911);
            if (in_array($rol, ['jefe_patrulla', 'jefe_calle'], true)) {
                return $rol;
            }
        }

        return null;
    }

    private function etiquetaRol(?string $rol): ?string
    {
        return match ($rol) {
            'jefe_patrulla' => 'JEFE DE PATRULLA',
            'jefe_calle'    => 'JEFE DE CALLE',
            default         => null,
        };
    }

    private function nombre(?Personal $p): string
    {
        if (! $p) {
            return '—';
        }

        $jerarquia = self::JERARQUIA_ABREV[trim((string) $p->jerarquia)]
            ?? mb_strtoupper(trim((string) $p->jerarquia), 'UTF-8');

        return trim($jerarquia . ' ' . mb_strtoupper(trim("{$p->apellido} {$p->nombre}"), 'UTF-8'));
    }

    private function nombreMovil(?string $nombre): string
    {
        $nombre = trim((string) $nombre) ?: 'Móvil';
        $prefijo = trim((string) config('flota911.prefijo_movil_patrulla', ''));

        if ($prefijo !== '' && preg_match('/^(ex\s+)?m[óo]vil\s+(.+)$/iu', $nombre, $m) === 1) {
            $nombre = ($m[1] ? 'Ex ' : '') . 'Móvil ' . $prefijo . ' ' . $m[2];
        }

        return mb_strtoupper($nombre, 'UTF-8');
    }

    private function guardiaLabel(string $guardia): string
    {
        return 'Guardia N° ' . preg_replace('/\D+/', '', $guardia);
    }

    private function fechaLarga(Carbon $fecha): string
    {
        $mes = Str::ucfirst($fecha->locale('es')->isoFormat('MMMM'));

        return $fecha->format('d') . ' de ' . $mes . ' de ' . $fecha->format('Y');
    }

    private function membrete(Section $s, array $lineas): void
    {
        foreach ($lineas as $linea) {
            $s->addText($linea, ['bold' => true, 'size' => 11, 'name' => self::FONT], ['alignment' => 'center']);
        }
        $s->addTextBreak(1);
    }

    private function encabezadoOficio(Section $s, ParteDiario $parte, string $objeto): void
    {
        $s->addText('PARANÁ: ' . $this->fechaLarga($parte->fecha), ['size' => 10, 'name' => self::FONT], ['alignment' => 'right']);
        $s->addText('OBJETO: ' . $objeto, ['size' => 10, 'name' => self::FONT], ['alignment' => 'right']);
        $s->addTextBreak(1);
        foreach (config('flota911.destinatario') as $linea) {
            $s->addText($linea, ['bold' => true, 'size' => 10, 'name' => self::FONT]);
        }
        $s->addText('SU ________ // ________ DESPACHO:', ['size' => 10, 'name' => self::FONT]);
        $s->addTextBreak(1);
    }

    private function parrafo(Section $s, string $texto): void
    {
        $s->addText($texto, ['size' => 10, 'name' => self::FONT], ['alignment' => 'both', 'spaceAfter' => 0]);
    }

    private function titulo(Section $s, string $texto, int $size, bool $center = true): void
    {
        $s->addText($texto, ['bold' => true, 'size' => $size, 'name' => self::FONT], $center ? ['alignment' => 'center'] : []);
    }

    private function lineaEtiquetada(Section $s, string $etiqueta, ?string $valor): void
    {
        $run = $s->addTextRun();
        $run->addText($etiqueta . ': ', ['bold' => true, 'size' => 10, 'name' => self::FONT]);
        $run->addText(trim((string) $valor) !== '' ? $valor : 'Sin Novedad', ['size' => 10, 'name' => self::FONT]);
    }

    private function pieFirma(Section $s): void
    {
        $s->addTextBreak(4);
        $s->addText('_______________________________', ['size' => 10, 'name' => self::FONT], ['alignment' => 'center']);
        $s->addText('Firma y aclaración', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
    }

    /**
     * @return array<string, array{etiqueta: string, valor: string}>
     */
    private function rubrosVacios(): array
    {
        $salida = [];
        foreach (ParteDiarioNovedades::RUBROS as $clave => $etiqueta) {
            $salida[$clave] = ['etiqueta' => $etiqueta, 'valor' => ParteDiarioNovedades::SIN_NOVEDAD];
        }

        return $salida;
    }

    private function crearDocumento(): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(10);

        return $phpWord;
    }

    /**
     * @return array<string, int>
     */
    private function layout(): array
    {
        return [
            'marginTop'    => self::MARGIN,
            'marginBottom' => self::MARGIN,
            'marginLeft'   => self::MARGIN,
            'marginRight'  => self::MARGIN,
        ];
    }

    private function estiloTabla(PhpWord $phpWord): string
    {
        $phpWord->addTableStyle('tablaAsignaciones', [
            'borderSize'  => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin'  => 60,
        ]);

        return 'tablaAsignaciones';
    }

    private function descargar(PhpWord $phpWord, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        IOFactory::createWriter($phpWord, 'Word2007')->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
