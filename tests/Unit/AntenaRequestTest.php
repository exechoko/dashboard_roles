<?php

namespace Tests\Unit;

use App\Http\Requests\AntenaRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AntenaRequestTest extends TestCase
{
    public function test_acepta_datos_validos(): void
    {
        $validator = Validator::make($this->datos(), (new AntenaRequest())->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_requiere_el_nombre(): void
    {
        $validator = Validator::make($this->datos(['nombre' => '']), (new AntenaRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nombre', $validator->errors()->toArray());
    }

    public function test_rechaza_una_latitud_fuera_de_rango(): void
    {
        $validator = Validator::make($this->datos(['latitud' => 200]), (new AntenaRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitud', $validator->errors()->toArray());
    }

    public function test_rechaza_una_longitud_fuera_de_rango(): void
    {
        $validator = Validator::make($this->datos(['longitud' => -200]), (new AntenaRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitud', $validator->errors()->toArray());
    }

    public function test_acepta_coordenadas_nulas(): void
    {
        $validator = Validator::make(
            $this->datos(['latitud' => null, 'longitud' => null]),
            (new AntenaRequest())->rules()
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function datos(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'SBS 1',
            'localidad' => 'Paraná',
            'ubicacion' => 'Plaza 1° de Mayo',
            'latitud' => -31.72652,
            'longitud' => -60.53293,
            'altura' => 30,
            'activa' => true,
            'observaciones' => 'Sin novedades',
        ], $overrides);
    }
}
