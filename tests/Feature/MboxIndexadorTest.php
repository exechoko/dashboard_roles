<?php

namespace Tests\Feature;

use App\Models\MailArchivo;
use App\Models\MailBuzon;
use App\Models\MailMensaje;
use App\Services\Mbox\MboxIndexador;
use App\Services\Mbox\MboxLector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MboxIndexadorTest extends TestCase
{
    use DatabaseTransactions;

    private function crearArchivoDePrueba(): MailArchivo
    {
        $ruta = base_path('tests/Fixtures/mbox/prueba.mbox');

        $buzon = MailBuzon::create([
            'nombre' => 'Buzón de Prueba',
            'carpeta' => 'test_'.uniqid(),
            'activo' => true,
        ]);

        return MailArchivo::create([
            'buzon_id' => $buzon->id,
            'nombre_archivo' => basename($ruta),
            'ruta_absoluta' => $ruta,
            'tamano_bytes' => filesize($ruta),
            'estado' => 'pendiente',
        ]);
    }

    public function test_indexa_los_cinco_mensajes_del_fixture(): void
    {
        $archivo = $this->crearArchivoDePrueba();
        $indexador = app(MboxIndexador::class);

        $resultado = $indexador->indexar($archivo);

        $this->assertSame(5, $resultado['mensajes_total']);
        $this->assertSame(5, $resultado['mensajes_nuevos']);
        $this->assertSame(5, MailMensaje::where('buzon_id', $archivo->buzon_id)->count());
    }

    public function test_decodifica_acentos_y_detecta_la_linea_from_escapada_en_el_cuerpo(): void
    {
        $archivo = $this->crearArchivoDePrueba();
        app(MboxIndexador::class)->indexar($archivo);

        $mensaje = MailMensaje::where('buzon_id', $archivo->buzon_id)
            ->where('message_id', 'msg1@example.com')
            ->firstOrFail();

        $this->assertSame('Prueba con acentos: áéíóú ñ', $mensaje->asunto);
        $this->assertSame('Juan Pérez', $mensaje->de_nombre);
        $this->assertSame('juan.perez@example.com', $mensaje->de_email);
        $this->assertStringContainsString('áéíóú', $mensaje->cuerpo_texto);

        $crudo = app(MboxLector::class)->leerCrudo($mensaje);
        $this->assertStringContainsString("\nFrom este punto la reunión sigue en otra sala.\n", $crudo);
        $this->assertStringNotContainsString("\n>From este punto", $crudo);
    }

    public function test_detecta_el_adjunto_y_la_carpeta_derivada_de_la_etiqueta(): void
    {
        $archivo = $this->crearArchivoDePrueba();
        app(MboxIndexador::class)->indexar($archivo);

        $conAdjunto = MailMensaje::where('buzon_id', $archivo->buzon_id)
            ->where('message_id', 'msg3@example.com')
            ->firstOrFail();

        $this->assertTrue($conAdjunto->tiene_adjuntos);
        $this->assertSame(1, $conAdjunto->cantidad_adjuntos);
        $this->assertSame('documento.pdf', $conAdjunto->adjuntos_json[0]['nombre']);
        $this->assertStringContainsString('documento.pdf', $conAdjunto->adjuntos_nombres);

        $enviado = MailMensaje::where('buzon_id', $archivo->buzon_id)
            ->where('message_id', 'msg4@example.com')
            ->firstOrFail();

        $this->assertSame('enviados', $enviado->carpeta);
    }

    public function test_reindexar_no_duplica_mensajes(): void
    {
        $archivo = $this->crearArchivoDePrueba();
        $indexador = app(MboxIndexador::class);

        $primero = $indexador->indexar($archivo);
        $archivo->refresh();
        $segundo = $indexador->indexar($archivo->fresh(), reiniciar: true);

        $this->assertSame(5, $primero['mensajes_total']);
        $this->assertSame(5, $segundo['mensajes_total']);
        $this->assertSame(0, $segundo['mensajes_nuevos'], 'Al reindexar, ningún mensaje debería contar como nuevo.');
        $this->assertSame(5, MailMensaje::where('buzon_id', $archivo->buzon_id)->count());
    }
}
