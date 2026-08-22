<?php

namespace Tests\Unit;

use App\Services\OpenCodeService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenCodeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.opencode.url' => 'http://opencode.test:4096',
            'services.opencode.username' => 'chatbot',
            'services.opencode.password' => 'secret-test',
            'services.opencode.agent' => 'ayuda-sistema',
            'services.opencode.model' => 'opencode-go/deepseek-v4-flash',
            'services.opencode.fallback_model' => '',
        ]);
    }

    public function test_crea_una_sesion_y_envia_un_mensaje_autenticado(): void
    {
        Http::fake([
            'http://opencode.test:4096/session' => Http::response(['id' => 'ses_123'], 200),
            'http://opencode.test:4096/session/ses_123/message' => Http::response([
                'info' => ['id' => 'msg_1'],
                'parts' => [
                    ['type' => 'reasoning', 'text' => 'No exponer'],
                    ['type' => 'text', 'text' => 'Abrí el módulo de equipos.'],
                ],
            ], 200),
        ]);

        $service = $this->service();
        $session = $service->createSession('Ayuda');
        $answer = $service->sendMessage($session['id'], '¿Dónde están los equipos?');

        $this->assertSame('ses_123', $session['id']);
        $this->assertSame('Abrí el módulo de equipos.', $answer);

        Http::assertSent(function ($request): bool {
            return $request->hasHeader('Authorization', 'Basic ' . base64_encode('chatbot:secret-test'));
        });

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://opencode.test:4096/session/ses_123/message'
                && $request['agent'] === 'ayuda-sistema'
                && $request['model']['providerID'] === 'opencode-go'
                && $request['model']['modelID'] === 'deepseek-v4-flash'
                && $request['parts'][0]['type'] === 'text';
        });
    }

    public function test_rechaza_una_respuesta_sin_partes_de_texto(): void
    {
        Http::fake([
            'http://opencode.test:4096/session/ses_123/message' => Http::response([
                'parts' => [['type' => 'reasoning', 'text' => 'interno']],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('respuesta vacía');

        $this->service()->sendMessage('ses_123', 'Consulta');
    }

    public function test_detecta_un_error_del_proveedor_incluido_en_una_respuesta_exitosa(): void
    {
        Http::fake([
            'http://opencode.test:4096/session/ses_123/message' => Http::response([
                'info' => [
                    'error' => [
                        'name' => 'APIError',
                        'data' => ['message' => 'Invalid API key.'],
                    ],
                ],
                'parts' => [],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no pudo ejecutar el modelo');

        $this->service()->sendMessage('ses_123', 'Consulta');
    }

    public function test_reintenta_con_el_modelo_alternativo_si_falla_el_principal(): void
    {
        config(['services.opencode.fallback_model' => 'opencode/laguna-s-2.1-free']);
        $attempt = 0;

        Http::fake(function ($request) use (&$attempt) {
            $attempt++;

            if ($attempt === 1) {
                return Http::response([
                    'info' => ['error' => ['name' => 'APIError']],
                    'parts' => [],
                ], 200);
            }

            return Http::response([
                'info' => ['id' => 'msg_2'],
                'parts' => [['type' => 'text', 'text' => 'Respuesta alternativa']],
            ], 200);
        });

        $answer = $this->service()->sendMessage('ses_123', 'Consulta');

        $this->assertSame('Respuesta alternativa', $answer);
        Http::assertSent(function ($request): bool {
            return isset($request['model'])
                && $request['model']['providerID'] === 'opencode'
                && $request['model']['modelID'] === 'laguna-s-2.1-free';
        });
    }

    public function test_informa_un_error_si_no_puede_crear_la_sesion(): void
    {
        Http::fake([
            'http://opencode.test:4096/session' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se pudo iniciar una sesión');

        $this->service()->createSession('Ayuda');
    }

    protected function service(): OpenCodeService
    {
        return new OpenCodeService();
    }
}
