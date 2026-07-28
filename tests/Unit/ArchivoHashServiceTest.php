<?php

namespace Tests\Unit;

use App\Services\ArchivoHashService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class ArchivoHashServiceTest extends TestCase
{
    public function test_calcula_el_sha256_de_un_archivo_sin_importar_su_extension(): void
    {
        $archivo = UploadedFile::fake()->createWithContent(
            'evidencia.formato-desconocido',
            'The quick brown fox jumps over the lazy dog'
        );

        $hash = (new ArchivoHashService())->calcularSha256($archivo);

        $this->assertSame(
            'd7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592',
            $hash
        );
    }

    public function test_calcula_el_sha256_de_un_archivo_vacio(): void
    {
        $archivo = UploadedFile::fake()->createWithContent('vacio.bin', '');

        $hash = (new ArchivoHashService())->calcularSha256($archivo);

        $this->assertSame(
            'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            $hash
        );
    }
}
