<?php

namespace Tests\Unit;

use App\Services\SanitizadorHtmlWeb;
use PHPUnit\Framework\TestCase;

class SanitizadorHtmlWebTest extends TestCase
{
    public function test_conserva_tags_y_atributos_permitidos(): void
    {
        $html = '<p style="margin:0;">Hola <strong>negrita</strong> y <a href="https://ok.com" target="_blank" rel="noopener">enlace</a></p>';

        $this->assertSame($html, SanitizadorHtmlWeb::limpiar($html));
    }

    public function test_conserva_listas_anidadas(): void
    {
        $html = '<ul><li>Uno <strong>x</strong></li><li>Dos</li></ul>';

        $this->assertSame($html, SanitizadorHtmlWeb::limpiar($html));
    }

    public function test_elimina_scripts_por_completo(): void
    {
        $limpio = SanitizadorHtmlWeb::limpiar('<p>Texto</p><script>alert(1)</script>');

        $this->assertSame('<p>Texto</p>', $limpio);
    }

    public function test_elimina_iframes_por_completo(): void
    {
        $limpio = SanitizadorHtmlWeb::limpiar('<p>Texto</p><iframe src="https://malo"></iframe>');

        $this->assertSame('<p>Texto</p>', $limpio);
    }

    public function test_elimina_handlers_de_eventos(): void
    {
        $limpio = SanitizadorHtmlWeb::limpiar('<p onclick="robar()">Texto</p>');

        $this->assertSame('<p>Texto</p>', $limpio);
    }

    public function test_elimina_href_javascript_pero_conserva_el_texto(): void
    {
        $limpio = SanitizadorHtmlWeb::limpiar('<a href="javascript:alert(1)">click</a>');

        $this->assertSame('<a>click</a>', $limpio);
    }

    public function test_desenvuelve_tags_no_permitidos_conservando_texto(): void
    {
        $limpio = SanitizadorHtmlWeb::limpiar('<section><p>Hola</p></section>');

        $this->assertSame('<p>Hola</p>', $limpio);
    }

    public function test_cadena_vacia_devuelve_vacio(): void
    {
        $this->assertSame('', SanitizadorHtmlWeb::limpiar('   '));
    }
}
