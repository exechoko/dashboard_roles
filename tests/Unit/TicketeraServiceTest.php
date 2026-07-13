<?php

namespace Tests\Unit;

use App\Services\TicketeraService;
use ReflectionMethod;
use Tests\TestCase;

class TicketeraServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ticketera.url'         => 'https://ticketera.patagoniagreen.com',
            'services.ticketera.usuario'     => 'Tecnica911',
            'services.ticketera.password'    => 'secreto',
            'services.ticketera.customer_id' => '77',
            'services.ticketera.owner_id'    => '3',
        ]);
    }

    public function test_dry_run_no_envia_y_devuelve_codigo_simulado(): void
    {
        config(['services.ticketera.dry_run' => true]);

        $service = new TicketeraService();

        $this->assertTrue($service->esDryRun());

        $respuesta = $service->crearTicket([
            'codigo_interno' => 'PG/26-001',
            'tipo_equipo'    => 'Tetra',
            'prioridad'      => 'Alto',
            'asunto'         => 'Prueba',
            'texto_enviado'  => 'Texto de prueba',
        ]);

        $this->assertStringStartsWith('DRYRUN-', (string) $respuesta['codigo_ticketera']);
        $this->assertNull($respuesta['url_seguimiento']);
        $this->assertStringContainsString('DRY-RUN', $respuesta['html']);
    }

    public function test_mapea_categoria_y_prioridad_a_valores_hesk(): void
    {
        $service = new TicketeraService();

        $categoria = new ReflectionMethod($service, 'categoriaHesk');
        $categoria->setAccessible(true);
        $prioridad = new ReflectionMethod($service, 'prioridadHesk');
        $prioridad->setAccessible(true);

        $this->assertSame(9, $categoria->invoke($service, ['tipo_equipo' => 'Tetra']));
        $this->assertSame(4, $categoria->invoke($service, ['tipo_equipo' => 'Cámara']));
        $this->assertSame(18, $categoria->invoke($service, ['tipo_equipo' => 'Aire Acondicionado']));
        $this->assertSame(1, $categoria->invoke($service, ['tipo_equipo' => 'inexistente']));

        // HESK invierte la escala: 0 = Crítica ... 3 = Baja.
        $this->assertSame(0, $prioridad->invoke($service, ['prioridad' => 'Critico']));
        $this->assertSame(1, $prioridad->invoke($service, ['prioridad' => 'Alto']));
        $this->assertSame(3, $prioridad->invoke($service, ['prioridad' => 'Bajo']));
    }

    public function test_arma_campos_del_ticket_con_customer_id_y_owner(): void
    {
        $service = new TicketeraService();

        $campos = new ReflectionMethod($service, 'camposTicket');
        $campos->setAccessible(true);

        $resultado = $campos->invoke($service, [
            'asunto'        => 'Falla PTT',
            'texto_enviado' => 'Cuerpo del ticket',
            'prioridad'     => 'Alto',
        ], 9);

        $this->assertSame('9', $resultado['category']);
        $this->assertSame('CUSTOMER', $resultado['customer_type']);
        $this->assertSame('77', $resultado['customer_id']);
        $this->assertSame('3', $resultado['owner']);
        $this->assertSame('1', $resultado['priority']);
        $this->assertSame('Falla PTT', $resultado['subject']);
        $this->assertArrayNotHasKey('name', $resultado);
    }

    public function test_extrae_token_oculto_y_action_del_formulario_hesk(): void
    {
        $service = new TicketeraService();
        $html = <<<'HTML'
        <form method="post" class="form" action="admin_submit_ticket.php" enctype="multipart/form-data">
            <input type="hidden" name="token" value="6ebb976c61dc16e0998665704414c324a721cb88">
            <input type="hidden" name="category" value="9">
            <input type="hidden" name="customer_type" value="CUSTOMER">
            <input type="text" name="subject" value="">
        </form>
        HTML;

        $ocultos = new ReflectionMethod($service, 'extraerCamposOcultos');
        $ocultos->setAccessible(true);
        $campos = $ocultos->invoke($service, $html);

        $this->assertSame('6ebb976c61dc16e0998665704414c324a721cb88', $campos['token']);
        $this->assertSame('9', $campos['category']);
        $this->assertArrayNotHasKey('subject', $campos, 'los inputs no-hidden no deben incluirse');

        $action = new ReflectionMethod($service, 'extraerActionFormulario');
        $action->setAccessible(true);
        $this->assertSame('admin/admin_submit_ticket.php', $action->invoke($service, $html, 'admin/admin_submit_ticket.php'));
    }

    public function test_extrae_codigo_y_url_de_seguimiento_de_la_respuesta(): void
    {
        $service = new TicketeraService();
        $html = '<a href="../admin_ticket.php?track=ABC-123-4567">Ver ticket</a>';

        $this->assertSame('ABC-123-4567', $service->extraerCodigoTicketera($html));
        $this->assertSame(
            'https://ticketera.patagoniagreen.com/admin_ticket.php?track=ABC-123-4567',
            $service->extraerUrlSeguimiento($html)
        );
    }
}
