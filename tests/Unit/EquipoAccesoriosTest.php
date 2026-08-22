<?php

namespace Tests\Unit;

use App\Models\Equipo;
use PHPUnit\Framework\TestCase;

class EquipoAccesoriosTest extends TestCase
{
    public function test_lista_los_accesorios_que_le_faltan_al_equipo(): void
    {
        $equipo = new Equipo();
        $equipo->rf = false;
        $equipo->frente_remoto = false;
        $equipo->gps = true;

        $this->assertSame(['Antena R.F.', 'Frente remoto'], $equipo->accesoriosFaltantes());
    }

    public function test_un_equipo_sin_relevar_no_figura_con_accesorios_faltantes(): void
    {
        $equipo = new Equipo();

        $this->assertSame([], $equipo->accesoriosFaltantes());
    }

    public function test_un_equipo_completo_no_figura_con_accesorios_faltantes(): void
    {
        $equipo = new Equipo();
        $equipo->rf = true;
        $equipo->frente_remoto = true;
        $equipo->gps = true;
        $equipo->kit_inst = true;

        $this->assertSame([], $equipo->accesoriosFaltantes());
    }

    public function test_cada_accesorio_tiene_su_columna_de_descripcion(): void
    {
        foreach (array_keys(Equipo::ACCESORIOS) as $accesorio) {
            $this->assertArrayHasKey($accesorio, Equipo::ACCESORIOS_DESCRIPCION);
            $this->assertContains(Equipo::descripcionCampo($accesorio), (new Equipo())->getFillable());
        }
    }

    public function test_los_campos_de_accesorio_son_asignables_y_se_castean_a_booleano(): void
    {
        $equipo = new Equipo();

        foreach (array_keys(Equipo::ACCESORIOS) as $accesorio) {
            $this->assertContains($accesorio, $equipo->getFillable());
            $this->assertSame('boolean', $equipo->getCasts()[$accesorio] ?? null);
        }
    }

    public function test_el_modelo_no_declara_columnas_inexistentes(): void
    {
        $this->assertNotContains('operativo', (new Equipo())->getFillable());
    }
}
