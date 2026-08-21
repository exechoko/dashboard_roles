<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $mensaje->asunto ?: '(sin asunto)' }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #222; margin: 24px; }
        h1 { font-size: 16px; margin: 0 0 12px; }
        table.cabecera { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.cabecera th { text-align: left; width: 90px; padding: 2px 8px 2px 0; vertical-align: top; color: #555; font-weight: normal; }
        table.cabecera td { padding: 2px 0; }
        .adjuntos { margin-bottom: 12px; font-size: 12px; color: #555; }
        hr { border: none; border-top: 1px solid #ddd; margin: 16px 0; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <h1>{{ $mensaje->asunto ?: '(sin asunto)' }}</h1>

    <table class="cabecera">
        <tr>
            <th>De</th>
            <td>{{ $mensaje->de_nombre }} @if ($mensaje->de_email) &lt;{{ $mensaje->de_email }}&gt; @endif</td>
        </tr>
        <tr>
            <th>Para</th>
            <td>{{ $mensaje->para ?: '-' }}</td>
        </tr>
        @if ($mensaje->cc)
            <tr>
                <th>CC</th>
                <td>{{ $mensaje->cc }}</td>
            </tr>
        @endif
        <tr>
            <th>Fecha</th>
            <td>{{ $mensaje->fecha?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    @if (!empty($adjuntos))
        <div class="adjuntos">
            <strong>Adjuntos:</strong> {{ collect($adjuntos)->pluck('nombre')->filter()->implode(', ') }}
        </div>
    @endif

    <hr>

    {!! $cuerpoHtml !!}

    <script nonce="{{ $nonce }}">
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
