<?php

namespace Tests\Unit;

use App\Services\ChatbotContentSanitizer;
use PHPUnit\Framework\TestCase;

class ChatbotContentSanitizerTest extends TestCase
{
    private ChatbotContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new ChatbotContentSanitizer();
    }

    public function test_oculta_credenciales_en_la_consulta_del_usuario(): void
    {
        $content = $this->sanitizer->sanitizeInput(
            'Mi api_key=sk-live-123456789012 y password: SuperSecreta123'
        );

        $this->assertStringNotContainsString('sk-live-123456789012', $content);
        $this->assertStringNotContainsString('SuperSecreta123', $content);
        $this->assertStringContainsString('[CREDENCIAL OCULTA]', $content);
    }

    public function test_convierte_una_respuesta_json_con_texto_en_respuesta_normal(): void
    {
        $content = $this->sanitizer->sanitizeOutput(
            '```json{"respuesta":"Abrí el módulo de equipos."}```'
        );

        $this->assertSame('Abrí el módulo de equipos.', $content);
    }

    public function test_reemplaza_json_sin_una_respuesta_util_por_un_mensaje_neutro(): void
    {
        $content = $this->sanitizer->sanitizeOutput('{"modelo":"interno","api_key":"secreto"}');

        $this->assertSame(
            'No pude generar una respuesta en un formato legible. Reformulá la consulta.',
            $content
        );
    }

    public function test_oculta_modelos_proveedores_y_tokens_en_la_respuesta(): void
    {
        $content = $this->sanitizer->sanitizeOutput(
            'Uso opencode-go/deepseek-v4-flash y Authorization: Bearer abcdefghijklmnop'
        );

        $this->assertStringNotContainsString('deepseek-v4-flash', $content);
        $this->assertStringNotContainsString('abcdefghijklmnop', $content);
        $this->assertStringContainsString('[MODELO INTERNO]', $content);
        $this->assertStringContainsString('[CREDENCIAL OCULTA]', $content);
    }

    public function test_extrae_content_de_un_mensaje_json_anidado(): void
    {
        $content = $this->sanitizer->sanitizeOutput(
            '{"message":{"content":"Seguí los pasos documentados."}}'
        );

        $this->assertSame('Seguí los pasos documentados.', $content);
    }

    public function test_oculta_rutas_internas_de_documentacion_y_windows(): void
    {
        $content = $this->sanitizer->sanitizeOutput(
            'Consulté `docs/sistema/` en C:\\IA\\chatbot-CAR911\\docs\\sistema\\indice.md.'
        );

        $this->assertStringNotContainsString('docs/sistema', $content);
        $this->assertStringNotContainsString('C:\\IA', $content);
        $this->assertStringContainsString('documentación aprobada', $content);
        $this->assertStringContainsString('[RUTA INTERNA]', $content);
    }
}
