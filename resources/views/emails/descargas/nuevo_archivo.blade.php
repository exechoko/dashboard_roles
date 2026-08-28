<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nuevo archivo disponible</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .file-info { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        .footer { text-align: center; padding: 15px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Nuevo archivo disponible</h2>
        </div>
        <div class="content">
            <p>Hola {{ $usuario->name }},</p>
            <p>Se ha cargado un nuevo archivo en la Plataforma de Descargas que está disponible para tu rol.</p>

            <div class="file-info">
                <h3>{{ $archivo->nombre_original }}</h3>
                <p><strong>Categoría:</strong> {{ $archivo->categoria->nombre }}</p>
                <p><strong>Tamaño:</strong> {{ $archivo->tamano_humano }}</p>
                @if($archivo->descripcion)
                    <p><strong>Descripción:</strong> {{ $archivo->descripcion }}</p>
                @endif
                @if($archivo->expira_at)
                    <p><strong>Expira:</strong> {{ $archivo->expira_at->format('d/m/Y H:i') }}</p>
                @endif
                <p><strong>Subido por:</strong> {{ $archivo->user->name ?? 'Sistema' }}</p>
            </div>

            <p style="text-align: center;">
                <a href="{{ route('descargas.show', $archivo) }}" class="btn">Ver y Descargar</a>
            </p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático de la Plataforma de Descargas.</p>
        </div>
    </div>
</body>
</html>
