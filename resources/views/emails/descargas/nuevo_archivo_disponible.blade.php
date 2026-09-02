<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo archivo disponible</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .archivo-info {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📥 Nuevo Archivo Disponible</h1>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $usuario->name }}</strong>,</p>
        
        <p>Se ha cargado un nuevo archivo en la Plataforma de Descargas al cual tienes acceso:</p>
        
        <div class="archivo-info">
            <h2 style="margin-top: 0; color: #007bff;">{{ $archivo->nombre_original }}</h2>
            
            <div class="info-row">
                <span class="info-label">Categoría:</span> {{ $archivo->categoria->nombre }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Tamaño:</span> {{ number_format($archivo->tamano_bytes / 1024 / 1024, 2) }} MB
            </div>
            
            <div class="info-row">
                <span class="info-label">Tipo:</span> {{ strtoupper($archivo->extension) }}
            </div>
            
            @if($archivo->descripcion)
                <div class="info-row">
                    <span class="info-label">Descripción:</span> {{ $archivo->descripcion }}
                </div>
            @endif
            
            <div class="info-row">
                <span class="info-label">Subido por:</span> {{ $archivo->user->name }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Fecha:</span> {{ $archivo->created_at->format('d/m/Y H:i') }}
            </div>
            
            @if($archivo->expira_at)
                <div class="info-row">
                    <span class="info-label">Expira:</span> {{ $archivo->expira_at->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
        
        <p style="text-align: center;">
            <a href="{{ route('descargas.show', $archivo) }}" class="btn">Ver y Descargar Archivo</a>
        </p>
        
        <div class="footer">
            <p>Este es un mensaje automático de la Plataforma de Descargas.</p>
            <p>Si no deseas recibir más notificaciones, contacta al administrador del sistema.</p>
        </div>
    </div>
</body>
</html>
