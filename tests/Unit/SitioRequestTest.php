<?php

namespace Tests\Unit;

use App\Http\Requests\SitioRequest;
use App\Models\Sitio;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SitioRequestTest extends TestCase
{
    /**
     * @dataProvider energizadoPorPermitidos
     */
    public function test_acepta_los_proveedores_de_energia_permitidos(string $energizadoPor): void
    {
        $validator = Validator::make($this->datos($energizadoPor), (new SitioRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rechaza_un_proveedor_de_energia_no_permitido(): void
    {
        $validator = Validator::make($this->datos('Proveedor no válido'), (new SitioRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('energizado_por', $validator->errors()->toArray());
    }

    /**
     * @return array<int, array{string}>
     */
    public static function energizadoPorPermitidos(): array
    {
        return array_map(
            static fn (string $energizadoPor): array => [$energizadoPor],
            Sitio::ENERGIZADO_POR
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(string $energizadoPor): array
    {
        return [
            'nombre' => 'Sitio de prueba',
            'localidad' => 'Paraná',
            'destino_id' => 1,
            'activo' => true,
            'energizado_por' => $energizadoPor,
        ];
    }
}
