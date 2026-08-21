<?php

namespace App\Exports;

use App\Models\MailMensaje;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class MailsExport implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    public function __construct(
        private int $buzonId,
        private array $filtros = []
    ) {
    }

    public function collection()
    {
        $query = MailMensaje::query()->where('buzon_id', $this->buzonId);

        if (!empty($this->filtros['texto'])) {
            $query->whereRaw(
                'MATCH(asunto, cuerpo_texto, adjuntos_nombres) AGAINST (? IN BOOLEAN MODE)',
                [$this->prepararModoBooleano($this->filtros['texto'])]
            );
        }

        if (!empty($this->filtros['de'])) {
            $query->whereRaw('MATCH(de_nombre, de_email) AGAINST (? IN BOOLEAN MODE)', [$this->prepararModoBooleano($this->filtros['de'])]);
        }

        if (!empty($this->filtros['para'])) {
            $query->whereRaw('MATCH(para, cc) AGAINST (? IN BOOLEAN MODE)', [$this->prepararModoBooleano($this->filtros['para'])]);
        }

        if (!empty($this->filtros['asunto'])) {
            $query->whereRaw('MATCH(asunto) AGAINST (? IN BOOLEAN MODE)', [$this->prepararModoBooleano($this->filtros['asunto'])]);
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha', '>=', Carbon::parse($this->filtros['fecha_desde'])->startOfDay());
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha', '<=', Carbon::parse($this->filtros['fecha_hasta'])->endOfDay());
        }

        if (!empty($this->filtros['adjuntos'])) {
            $query->where('tiene_adjuntos', $this->filtros['adjuntos'] === 'con');
        }

        if (!empty($this->filtros['carpeta'])) {
            $query->where('carpeta', $this->filtros['carpeta']);
        }

        $mensajes = $query->orderBy('fecha', 'desc')->limit(10000)->get();

        return new Collection($mensajes->map(fn (MailMensaje $m) => [
            'fecha' => $m->fecha?->format('d/m/Y H:i'),
            'de' => trim(($m->de_nombre ?? '').' <'.$m->de_email.'>'),
            'para' => $m->para,
            'asunto' => $m->asunto,
            'carpeta' => MailMensaje::CARPETAS[$m->carpeta] ?? $m->carpeta,
            'adjuntos' => $m->cantidad_adjuntos,
            'tamano_kb' => round($m->tamano_bytes / 1024, 1),
        ]));
    }

    public function headings(): array
    {
        return ['Fecha', 'De', 'Para', 'Asunto', 'Carpeta', 'Adjuntos', 'Tamaño (KB)'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $headerRange = 'A1:'.$sheet->getHighestColumn().'1';
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->setAutoFilter($headerRange);
                $sheet->freezePane('A2');
            },
        ];
    }

    /**
     * Separa por cualquier caracter no alfanumérico (no solo espacios) para
     * que buscar un email completo ("nombre@dominio.com") arme un término
     * por cada parte en vez de pegotearlas al sacarle el "@" y el ".".
     */
    private function prepararModoBooleano(string $texto): string
    {
        $palabras = array_filter(preg_split('/[^\p{L}\p{N}]+/u', trim($texto)) ?: []);

        $terminos = array_map(fn (string $palabra) => '+'.$palabra.'*', $palabras);

        return implode(' ', array_filter($terminos)) ?: $texto;
    }
}
