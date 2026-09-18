<?php

namespace App\Imports;

use App\Services\AlertaVideoService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Solo procesa la hoja "Vehiculos" del archivo subido; el resto de las
 * hojas (Personas, Hoja1, Hoja2, Hoja3, etc.) se ignoran.
 */
class DominioAlertaImport implements WithMultipleSheets
{
    private DominioAlertaSheetImport $sheetImport;

    public function __construct(AlertaVideoService $service)
    {
        $this->sheetImport = new DominioAlertaSheetImport($service);
    }

    /**
     * @return array<string, DominioAlertaSheetImport>
     */
    public function sheets(): array
    {
        return [
            'Vehiculos' => $this->sheetImport,
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
