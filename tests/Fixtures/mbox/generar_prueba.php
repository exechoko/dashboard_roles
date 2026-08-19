<?php

/**
 * Genera tests/Fixtures/mbox/prueba.mbox con casos reales de Gmail Takeout:
 * acentos (RFC 2047), multipart/alternative con HTML, un adjunto en base64,
 * una línea de cuerpo que empieza con "From " (debe llegar escapada con
 * ">From " en el archivo) y una etiqueta de Gmail para probar la carpeta
 * derivada. Se corre una sola vez a mano; el resultado se versiona en git.
 */

function encodedWord(string $texto): string
{
    return '=?UTF-8?B?'.base64_encode($texto).'?=';
}

$mensajes = [];

// 1) Texto plano, acentos en asunto y remitente, con una línea de cuerpo que
//    debe quedar escapada (">From ") por empezar con "From ".
$asunto1 = encodedWord('Prueba con acentos: áéíóú ñ');
$de1 = encodedWord('Juan Pérez').' <juan.perez@example.com>';
$mensajes[] = "From juan.perez@example.com Mon Jan 01 09:00:00 2024\n"
    ."From: {$de1}\n"
    ."To: mesa@example.com\n"
    ."Subject: {$asunto1}\n"
    ."Date: Mon, 01 Jan 2024 09:00:00 -0300\n"
    ."Message-ID: <msg1@example.com>\n"
    ."X-Gmail-Labels: Inbox\n"
    ."Content-Type: text/plain; charset=UTF-8\n"
    ."\n"
    ."Hola, este es un mensaje de prueba con acentos: áéíóú.\n"
    .">From este punto la reunión sigue en otra sala.\n"
    ."Saludos.\n";

// 2) multipart/alternative: texto + HTML.
$boundary2 = 'boundary-2-alt';
$mensajes[] = "From secretaria@example.com Tue Jan 02 10:00:00 2024\n"
    ."From: Secretaría <secretaria@example.com>\n"
    ."To: destinatario@example.com\n"
    ."Cc: copia@example.com\n"
    ."Subject: Notificacion con HTML\n"
    ."Date: Tue, 02 Jan 2024 10:00:00 -0300\n"
    ."Message-ID: <msg2@example.com>\n"
    ."X-Gmail-Labels: Inbox\n"
    ."Content-Type: multipart/alternative; boundary=\"{$boundary2}\"\n"
    ."\n"
    ."--{$boundary2}\n"
    ."Content-Type: text/plain; charset=UTF-8\n"
    ."\n"
    ."Version en texto plano del aviso.\n"
    ."\n"
    ."--{$boundary2}\n"
    ."Content-Type: text/html; charset=UTF-8\n"
    ."\n"
    ."<html><body><p>Version en <b>HTML</b> del aviso.</p></body></html>\n"
    ."\n"
    ."--{$boundary2}--\n";

// 3) multipart/mixed con un adjunto PDF (contenido ficticio) en base64.
$boundary3 = 'boundary-3-mixed';
$pdfFalso = "%PDF-1.4 contenido de prueba, no es un PDF valido, solo bytes para el test.";
$pdfBase64 = chunk_split(base64_encode($pdfFalso), 76, "\n");
$mensajes[] = "From otro@example.com Wed Jan 03 11:00:00 2024\n"
    ."From: Otro Remitente <otro@example.com>\n"
    ."To: destinatario@example.com\n"
    ."Subject: Mensaje con adjunto\n"
    ."Date: Wed, 03 Jan 2024 11:00:00 -0300\n"
    ."Message-ID: <msg3@example.com>\n"
    ."X-Gmail-Labels: Inbox\n"
    ."Content-Type: multipart/mixed; boundary=\"{$boundary3}\"\n"
    ."\n"
    ."--{$boundary3}\n"
    ."Content-Type: text/plain; charset=UTF-8\n"
    ."\n"
    ."Te mando el documento adjunto.\n"
    ."\n"
    ."--{$boundary3}\n"
    ."Content-Type: application/pdf; name=\"documento.pdf\"\n"
    ."Content-Disposition: attachment; filename=\"documento.pdf\"\n"
    ."Content-Transfer-Encoding: base64\n"
    ."\n"
    .$pdfBase64
    ."\n"
    ."--{$boundary3}--\n";

// 4) Enviado (X-Gmail-Labels: Sent) -> debe derivar carpeta = enviados.
$mensajes[] = "From yo@example.com Thu Jan 04 12:00:00 2024\n"
    ."From: Yo <yo@example.com>\n"
    ."To: destinatario2@example.com\n"
    ."Subject: Mensaje enviado de prueba\n"
    ."Date: Thu, 04 Jan 2024 12:00:00 -0300\n"
    ."Message-ID: <msg4@example.com>\n"
    ."X-Gmail-Labels: Sent\n"
    ."X-GM-THRID: 1111111111111111111\n"
    ."Content-Type: text/plain; charset=UTF-8\n"
    ."\n"
    ."Este mensaje deberia quedar clasificado como enviado.\n";

// 5) Caso mínimo: sin Subject, sin adjuntos, cuerpo corto.
$mensajes[] = "From minimo@example.com Fri Jan 05 13:00:00 2024\n"
    ."From: minimo@example.com\n"
    ."To: destinatario@example.com\n"
    ."Date: Fri, 05 Jan 2024 13:00:00 -0300\n"
    ."Message-ID: <msg5@example.com>\n"
    ."Content-Type: text/plain; charset=UTF-8\n"
    ."\n"
    ."Mensaje minimo sin asunto.\n";

$contenido = implode("\n", $mensajes);

file_put_contents(__DIR__.'/prueba.mbox', $contenido);

echo 'Generado: '.__DIR__.'/prueba.mbox ('.strlen($contenido)." bytes, ".count($mensajes)." mensajes)\n";
