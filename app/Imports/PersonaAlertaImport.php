<?php

namespace App\Imports;

use App\Services\AlertaVideoService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Solo procesa la hoja "Personas" del archivo subido; el resto de las
 * hojas (Vehiculos, Hoja1, Hoja2, Hoja3, etc.) se ignoran.
 */
class PersonaAlertaImport implements WithMultipleSheets
{
    private PersonaAlertaSheetImport $sheetImport;

    public function __construct(AlertaVideoService $service)
    {
        $this->sheetImport = new PersonaAlertaSheetImport($service);
    }

    /**
     * @return array<string, PersonaAlertaSheetImport>
     */
    public function sheets(): array
    {
        return [
            'Personas' => $this->sheetImport,
        ];
    }

    public function getCreated(): int
    {
        return $this->sheetImport->getCreated();
    }

    public function getOmitidos(): int
    {
        return $this->sheetImport->getOmitidos();
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->sheetImport->getErrors();
    }
}
