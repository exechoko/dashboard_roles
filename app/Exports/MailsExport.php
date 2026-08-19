<?php

namespace App\Exports;

use App\Models\MailMensaje;
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
            $palabras = array_filter(preg_split('/\s+/', trim($this->filtros['texto'])) ?: []);
            $terminos = implode(' ', array_map(
                fn (string $p) => '+'.preg_replace('/[+\-><()~*"@]+/', '', $p).'*',
                $palabras
            ));
            $query->whereRaw('MATCH(asunto, cuerpo_texto, adjuntos_nombres) AGAINST (? IN BOOLEAN MODE)', [$terminos ?: $this->filtros['texto']]);
        }

        if (!empty($this->filtros['de'])) {
            $de = $this->filtros['de'];
            $query->where(fn ($q) => $q->where('de_email', 'like', "%{$de}%")->orWhere('de_nombre', 'like', "%{$de}%"));
        }

        if (!empty($this->filtros['para'])) {
            $para = $this->filtros['para'];
            $query->where(fn ($q) => $q->where('para', 'like', "%{$para}%")->orWhere('cc', 'like', "%{$para}%"));
        }

        if (!empty($this->filtros['asunto'])) {
            $query->where('asunto', 'like', '%'.$this->filtros['asunto'].'%');
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $this->filtros['fecha_hasta']);
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
}
