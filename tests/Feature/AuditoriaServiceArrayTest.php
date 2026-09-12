<?php

namespace Tests\Feature;

use App\Services\AuditoriaService;
use ReflectionClass;
use Tests\TestCase;

class AuditoriaServiceArrayTest extends TestCase
{
    /**
     * AuditoriaService::valorLegible() hacía (string) $valor sin chequear
     * is_array(). getOriginal() de un atributo casteado a array (ej.
     * RecursoInformePreferencia::recurso_ids, ParteDiarioNovedades::contenido)
     * SÍ puede llegar como array a esta función (a diferencia de getChanges(),
     * que devuelve el valor crudo/string) — reventaba con "Array to string
     * conversion" (ErrorException, promovida desde el E_WARNING de PHP) y,
     * como el error se atrapa en el try/catch de registrarEventoModelo(), el
     * registro de auditoría de ese evento se perdía en silencio.
     */
    private function valorLegible(mixed $valor): string
    {
        $metodo = (new ReflectionClass(AuditoriaService::class))->getMethod('valorLegible');
        $metodo->setAccessible(true);

        return $metodo->invoke(null, $valor);
    }

    public function test_valor_legible_no_revienta_con_un_array(): void
    {
        $resultado = $this->valorLegible([1, 2, 3]);

        $this->assertSame('[1,2,3]', $resultado);
    }

    public function test_valor_legible_sigue_andando_para_los_demas_tipos(): void
    {
        $this->assertSame('S/D', $this->valorLegible(null));
        $this->assertSame('true', $this->valorLegible(true));
        $this->assertSame('false', $this->valorLegible(false));
        $this->assertSame('texto', $this->valorLegible('texto'));
        $this->assertSame('42', $this->valorLegible(42));
    }
}
