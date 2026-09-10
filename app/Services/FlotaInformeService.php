<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class FlotaInformeService
{
    private const FONT = 'Arial';
    private const MARGIN = 1000;

    public function generarEstadoFlota(Collection $recursos, $destino): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $phpWord = $this->crearDocumento();
        $section = $phpWord->addSection($this->layoutPortrait());
        $fechaFormateada = now()->locale('es')->isoFormat('DD [de] MMMM [de] YYYY');

        $this->addTitulo($section, 'POLICÍA DE ENTRE RÍOS');
        $this->addTitulo($section, mb_strtoupper($destino->nombre, 'UTF-8'));
        $section->addTextBreak(1);
        $this->addTitulo($section, 'ESTADO DE FLOTA ACTUAL');
        $this->addSubtitulo($section, 'Fecha: ' . $fechaFormateada);
        $section->addTextBreak(1);

        $table = $section->addTable($this->estiloTabla($phpWord, 'tablaFlota'));
        $table->addRow(400);
        foreach (['Móvil', 'Dominio', 'Tipo / Marca / Modelo', 'Estado', 'Última novedad de bitácora'] as $h) {
            $table->addCell(null, ['bgColor' => '2C3E50'])->addText(
                $h, ['bold' => true, 'size' => 9, 'color' => 'FFFFFF', 'name' => self::FONT], ['alignment' => 'center']
            );
        }

        foreach ($recursos as $recurso) {
            $vehiculo = $recurso->vehiculoActual();
            $estado = $recurso->estadoSeccion;
            $estadoLabel = $estado ? ($estado->label ?? $estado->estado) : 'En servicio';
            $estadoBg = match ($estado?->estado) {
                'fuera_de_servicio' => 'FADBD8',
                'en_taller'         => 'FEF9E7',
                'baja_provisional'  => 'EAECEE',
                default             => 'EAFAF1',
            };

            $ultima = $recurso->ultimaBitacora;
            $novedades = $ultima
                ? $ultima->fecha_hora->format('d/m/Y') . ' — ' . $ultima->categoriaLabel()
                    . ($recurso->bitacoraAbiertas->isNotEmpty() ? ' (en taller)' : '')
                    . "\n" . \Illuminate\Support\Str::limit($ultima->descripcion, 90)
                : '—';

            $tipoMarcaModelo = $vehiculo ? implode(' / ', array_filter([
                $vehiculo->tipo_vehiculo,
                $vehiculo->marca,
                $vehiculo->modelo,
            ])) : '—';

            $table->addRow(300);
            $table->addCell(1200)->addText($recurso->nombre, ['size' => 9, 'name' => self::FONT]);
            $table->addCell(1200)->addText($vehiculo?->dominio ?? '—', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            $table->addCell(2500)->addText($tipoMarcaModelo, ['size' => 9, 'name' => self::FONT]);
            $table->addCell(1500, ['bgColor' => $estadoBg])->addText($estadoLabel, ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
            $table->addCell(3000)->addText($novedades, ['size' => 9, 'name' => self::FONT]);
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
            'borderSize'       => 6,
            'borderColor'      => 'CCCCCC',
            'cellMarginLeft'   => 80,
            'cellMarginRight'  => 80,
            'cellMarginTop'    => 40,
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
        $section->addText('Paraná, ' . $fechaFormateada, ['size' => 9, 'name' => self::FONT], ['alignment' => 'right']);
        $section->addTextBreak(3);
        $section->addText('___________________________________', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
        $section->addText('Firma y Sello', ['size' => 9, 'name' => self::FONT], ['alignment' => 'center']);
    }

    private function descargar(PhpWord $phpWord, string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
}
