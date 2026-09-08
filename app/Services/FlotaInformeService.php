<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Converter;

class FlotaInformeService
{
    private const FONT = 'Arial';
    private const MARGIN = 1000;

    public function generarParteDiario(Collection $secciones, string $fecha, ?string $novedadesGenerales): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $phpWord = $this->crearDocumento();
        $section = $phpWord->addSection($this->layoutPortrait());
        $fechaFormateada = \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('DD [de] MMMM [de] YYYY');

        // Encabezado
        $this->addTitulo($section, 'POLICÍA DE ENTRE RÍOS');
        $this->addTitulo($section, 'DIVISIÓN 911 Y VIDEOVIGILANCIA');
        $section->addTextBreak(1);
        $this->addTitulo($section, 'PARTE DIARIO DE VEHÍCULOS');
        $this->addSubtitulo($section, 'Fecha: ' . $fechaFormateada);
        $section->addTextBreak(1);

        foreach ($secciones as $seccion) {
            $recursos = $seccion->recursos->filter(fn($r) => $r->vehiculo !== null);
            if ($recursos->isEmpty()) {
                continue;
            }

            // Título de sección
            $this->addSeccionTitulo($section, strtoupper($seccion->nombre));
            $section->addTextBreak(1);

            // Vehículos que circulan
            $circulan = $recursos->filter(
                fn($r) => ($r->vehiculo->estadoDiario->first()?->estado_dia ?? 'circula') === 'circula'
            );

            if ($circulan->isNotEmpty()) {
                $this->addSubtitulo($section, 'Vehículos que circulan:');
                $table = $section->addTable($this->estiloTabla($phpWord, 'tablaCirculan' . $seccion->id));

                $table->addRow(400);
                foreach (['Móvil', 'Dominio', 'Tipo', 'Dotación'] as $h) {
                    $table->addCell(null, ['bgColor' => '2C3E50'])->addText(
                        $h, ['bold' => true, 'size' => 9, 'color' => 'FFFFFF', 'name' => self::FONT], ['alignment' => 'center']
                    );
                }

                foreach ($circulan as $recurso) {
                    $v = $recurso->vehiculo;
                    $dotacion = $v->dotaciones
                        ->filter(fn($d) => $d->fecha?->toDateString() === $fecha)
                        ->map(fn($d) => $d->personal?->getNombreCompletoAttribute())
                        ->filter()
                        ->join("\n");

                    $table->addRow(300);
                    $table->addCell(1200)->addText($recurso->nombre, ['size' => 9, 'name' => self::FONT]);
                    $table->addCell(1200)->addText($v->dominio ?? '—', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
                    $table->addCell(1200)->addText($v->tipo_vehiculo ?? '—', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
                    $table->addCell(5000)->addText($dotacion ?: '—', ['size' => 9, 'name' => self::FONT]);
                }

                $section->addTextBreak(1);
            }

            // Vehículos en reserva
            $reserva = $recursos->filter(
                fn($r) => ($r->vehiculo->estadoDiario->first()?->estado_dia ?? 'circula') === 'reserva'
            );

            if ($reserva->isNotEmpty()) {
                $this->addSubtitulo($section, 'Vehículos en reserva:');
                foreach ($reserva as $recurso) {
                    $v = $recurso->vehiculo;
                    $motivo = $v->estadoDiario->first()?->motivo ?? '';
                    $texto = "{$recurso->nombre} ({$v->dominio})" . ($motivo ? " — {$motivo}" : '');
                    $section->addText('• ' . $texto, ['size' => 9, 'name' => self::FONT]);
                }
                $section->addTextBreak(1);
            }

            // Fuera de servicio / otro
            $fueraDeServicio = $recursos->filter(
                fn($r) => in_array($r->vehiculo->estadoDiario->first()?->estado_dia ?? 'circula', ['fuera_de_servicio', 'otro'])
            );

            if ($fueraDeServicio->isNotEmpty()) {
                $this->addSubtitulo($section, 'Vehículos fuera de servicio:');
                foreach ($fueraDeServicio as $recurso) {
                    $v = $recurso->vehiculo;
                    $motivo = $v->estadoDiario->first()?->motivo ?? '';
                    $texto = "{$recurso->nombre} ({$v->dominio})" . ($motivo ? " — {$motivo}" : '');
                    $section->addText('• ' . $texto, ['size' => 9, 'name' => self::FONT]);
                }
                $section->addTextBreak(1);
            }
        }

        // Novedades generales
        if ($novedadesGenerales) {
            $this->addSeccionTitulo($section, 'NOVEDADES GENERALES');
            $section->addText($novedadesGenerales, ['size' => 9, 'name' => self::FONT]);
            $section->addTextBreak(1);
        }

        // Pie de firma
        $this->addPieFirma($section, $fechaFormateada);

        $filename = 'Parte_Diario_' . str_replace('-', '', $fecha) . '.docx';
        return $this->descargar($phpWord, $filename);
    }

    public function generarEstadoFlota(Collection $vehiculos, $destino): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $phpWord = $this->crearDocumento();
        $section = $phpWord->addSection($this->layoutPortrait());
        $fechaFormateada = now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY');

        $this->addTitulo($section, 'POLICÍA DE ENTRE RÍOS');
        $this->addTitulo($section, strtoupper($destino->nombre));
        $section->addTextBreak(1);
        $this->addTitulo($section, 'ESTADO DE FLOTA ACTUAL');
        $this->addSubtitulo($section, 'Fecha: ' . $fechaFormateada);
        $section->addTextBreak(1);

        // Tabla principal
        $table = $section->addTable($this->estiloTabla($phpWord, 'tablaFlota'));
        $table->addRow(400);
        foreach (['Móvil', 'Dominio', 'Tipo / Marca / Modelo', 'Estado', 'Novedades pendientes'] as $h) {
            $table->addCell(null, ['bgColor' => '2C3E50'])->addText(
                $h, ['bold' => true, 'size' => 9, 'color' => 'FFFFFF', 'name' => self::FONT], ['alignment' => 'center']
            );
        }

        foreach ($vehiculos as $vehiculo) {
            $estado = $vehiculo->estadoSeccion;
            $estadoLabel = $estado ? ($estado->label ?? $estado->estado) : 'En servicio';
            $estadoBg = match ($estado?->estado) {
                'fuera_de_servicio' => 'FADBD8',
                'en_taller'         => 'FEF9E7',
                'baja_provisional'  => 'EAECEE',
                default             => 'EAFAF1',
            };

            $novedades = $vehiculo->novedadesPendientes
                ->map(fn($n) => '• ' . \Illuminate\Support\Str::limit($n->descripcion, 80))
                ->join("\n");

            $recurso = $vehiculo->recurso->first();
            $nombreMovil = $recurso?->nombre ?? '—';
            $tipoMarcaModelo = implode(' / ', array_filter([
                $vehiculo->tipo_vehiculo,
                $vehiculo->marca,
                $vehiculo->modelo,
            ]));

            $table->addRow(300);
            $table->addCell(1200)->addText($nombreMovil, ['size' => 9, 'name' => self::FONT]);
            $table->addCell(1200)->addText($vehiculo->dominio ?? '—', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            $table->addCell(2500)->addText($tipoMarcaModelo, ['size' => 9, 'name' => self::FONT]);
            $table->addCell(1500, ['bgColor' => $estadoBg])->addText($estadoLabel, ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            $table->addCell(3000)->addText($novedades ?: '—', ['size' => 9, 'name' => self::FONT]);
        }

        $section->addTextBreak(2);
        $this->addPieFirma($section, $fechaFormateada);

        $filename = 'Estado_Flota_' . \Illuminate\Support\Str::slug($destino->nombre) . '_' . now()->format('Ymd') . '.docx';
        return $this->descargar($phpWord, $filename);
    }

    private function crearDocumento(): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName(self::FONT);
        $phpWord->setDefaultFontSize(10);
        return $phpWord;
    }

    private function layoutPortrait(): array
    {
        return [
            'marginTop'    => self::MARGIN,
            'marginBottom' => self::MARGIN,
            'marginLeft'   => self::MARGIN,
            'marginRight'  => self::MARGIN,
        ];
    }

    private function estiloTabla(PhpWord $phpWord, string $nombre): string
    {
        $phpWord->addTableStyle($nombre, [
            'borderSize'      => 6,
            'borderColor'     => 'CCCCCC',
            'cellMarginLeft'  => 80,
            'cellMarginRight' => 80,
            'cellMarginTop'   => 40,
            'cellMarginBottom' => 40,
        ]);
        return $nombre;
    }

    private function addTitulo($section, string $texto): void
    {
        $section->addText($texto, ['bold' => true, 'size' => 12, 'name' => self::FONT], ['alignment' => 'center']);
    }

    private function addSubtitulo($section, string $texto): void
    {
        $section->addText($texto, ['bold' => true, 'size' => 10, 'name' => self::FONT]);
    }

    private function addSeccionTitulo($section, string $texto): void
    {
        $section->addText($texto, ['bold' => true, 'size' => 11, 'name' => self::FONT, 'color' => '2C3E50']);
        $section->addText(str_repeat('─', 60), ['size' => 8, 'color' => 'AAAAAA', 'name' => self::FONT]);
    }

    private function addPieFirma($section, string $fechaFormateada): void
    {
        $section->addTextBreak(2);
        $section->addText(
            'Paraná, ' . $fechaFormateada,
            ['size' => 9, 'name' => self::FONT],
            ['alignment' => 'right']
        );
        $section->addTextBreak(3);
        $section->addText(
            '___________________________________',
            ['size' => 9, 'name' => self::FONT],
            ['alignment' => 'center']
        );
        $section->addText(
            'Firma y Sello',
            ['size' => 9, 'name' => self::FONT],
            ['alignment' => 'center']
        );
    }

    private function descargar(PhpWord $phpWord, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
