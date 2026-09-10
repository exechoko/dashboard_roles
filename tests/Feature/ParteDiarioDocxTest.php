<?php

namespace Tests\Feature;

use App\Models\Destino;
use App\Models\ParteDiario;
use App\Models\ParteDiarioAsignacion;
use App\Models\ParteDiarioNovedades;
use App\Models\Personal;
use App\Models\Recurso;
use App\Models\RecursoDotacion;
use App\Models\RecursoEstadoDiario;
use App\Models\User;
use App\Services\ParteDiarioDocxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class ParteDiarioDocxTest extends TestCase
{
    use DatabaseTransactions;

    private ParteDiarioDocxService $docx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->docx = new ParteDiarioDocxService();
    }

    private function textoDelDocx($response): string
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($response->getFile()->getPathname()) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return strip_tags($xml ?: '');
    }

    private function parte(string $tipoNombre): ParteDiario
    {
        $seccion = Destino::where('parent_id', 42)
            ->where('nombre', 'like', $tipoNombre)
            ->firstOrFail();

        $recursos = Recurso::query()
            ->where('destino_id', $seccion->id)
            ->whereNotNull('vehiculo_id')
            ->take(2)->get();

        $user = User::factory()->create();
        $fi = Carbon::parse('2099-06-15 06:15');
        $tipo = Str::contains(Str::lower($seccion->nombre), 'motor')
            ? ParteDiario::TIPO_MOTOS
            : ParteDiario::TIPO_MOVILES;

        $parte = ParteDiario::create([
            'destino_id'   => $seccion->id,
            'tipo'         => $tipo,
            'fecha'        => '2099-06-15',
            'guardia'      => 'guardia_3',
            'horario'      => '06_18',
            'fecha_inicio' => $fi,
            'fecha_fin'    => '2099-06-15 18:15',
            'guardia_interna' => 'Sub Of Ppal Herrera Gabriel',
            'user_id'      => $user->id,
        ]);

        [$p1, $p2] = Personal::query()->take(2)->get()->all();
        foreach ($recursos as $i => $recurso) {
            RecursoEstadoDiario::create([
                'parte_diario_id' => $parte->id,
                'recurso_id'      => $recurso->id,
                'guardia'         => 'guardia_3',
                'horario'         => '06_18',
                'zona'            => $i + 1,
                'ht'             => 'HT 2' . $i,
                'fecha_inicio'    => $fi,
                'fecha_fin'       => '2099-06-15 18:15',
                'estado_dia'      => 'circula',
                'user_id'         => $user->id,
            ]);
            RecursoDotacion::create([
                'parte_diario_id' => $parte->id, 'recurso_id' => $recurso->id, 'personal_id' => $i === 0 ? $p1->id : $p2->id,
                'es_chofer' => true, 'orden' => 0, 'guardia' => 'guardia_3', 'horario' => '06_18',
                'fecha_inicio' => $fi, 'fecha_fin' => '2099-06-15 18:15', 'user_id' => $user->id,
            ]);
        }

        return $parte->fresh(['seccion', 'estadosDiarios.recurso.vehiculo', 'dotaciones.personal', 'asignaciones']);
    }

    public function test_el_parte_de_moviles_tiene_oficio_zonas_y_hoja_novedades(): void
    {
        $parte = $this->parte('%Patrulla%');
        $novedades = new ParteDiarioNovedades(['contenido' => ['sala_armas' => 'SGTO. TESTIGO']]);

        $texto = $this->textoDelDocx($this->docx->generar($parte, $novedades));

        $this->assertStringContainsString('OBJETO: Informar novedad.', $texto);
        $this->assertStringContainsString('DESPACHO:', $texto);
        $this->assertStringContainsString('ZONA 1', $texto);
        $this->assertStringContainsString('ZONA 2', $texto);
        $this->assertStringContainsString('(chofer)', $texto);
        $this->assertStringContainsString('NOVEDADES', $texto);
        $this->assertStringContainsString('PERSONAL SALA DE ARMAS: SGTO. TESTIGO', $texto);
        $this->assertStringContainsString('MÓVIL DE TRASLADO: Sin Novedad', $texto);
    }

    public function test_el_parte_de_motos_tiene_guardia_nomina_y_asignacion_de_servicios(): void
    {
        $parte = $this->parte('%Motorizada%');
        ParteDiarioAsignacion::create([
            'parte_diario_id' => $parte->id, 'grupo' => 'Microcentro', 'nombre' => 'Sector 2',
            'asignacion_texto' => '31 ht 05', 'orden' => 0,
        ]);
        ParteDiarioAsignacion::create([
            'parte_diario_id' => $parte->id, 'grupo' => null, 'nombre' => 'Costanera',
            'asignacion_texto' => '', 'orden' => 1,
        ]);
        $parte->load('asignaciones');

        $texto = $this->textoDelDocx($this->docx->generar($parte, null));

        $this->assertStringContainsString('OBJETO: informar.-', $texto);
        $this->assertStringContainsString('GUARDIA N° 3', $texto);
        $this->assertStringContainsString('Asignación de servicios:', $texto);
        $this->assertStringContainsString('Microcentro', $texto);
        $this->assertStringContainsString('31 ht 05', $texto);
        $this->assertStringContainsString('Guardia: Sub Of Ppal Herrera Gabriel', $texto);
        $this->assertStringNotContainsString('Costanera', $texto, 'Las consignas sin asignación no se listan.');
        $this->assertMatchesRegularExpression('/\b(SGTO\.|CABO|AGTE\.|OF\.|SUBOF\.|CRIO\.)/', $texto, 'La jerarquía debe ir abreviada.');
    }

    public function test_los_titulos_se_mayusculizan_conservando_acentos(): void
    {
        $parte = $this->parte('%Motorizada%');
        $texto = $this->textoDelDocx($this->docx->generar($parte, null));

        $this->assertStringContainsString('MINISTERIO DE SEGURIDAD Y JUSTICIA', $texto);
        $this->assertStringContainsString('PARANÁ:', $texto);
        $this->assertStringContainsString(' de Junio de 2099', $texto, 'El mes debe ir capitalizado.');
    }
}
