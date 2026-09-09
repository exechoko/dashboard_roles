<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de compartir aprobada</title>
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
            background-color: #28a745;
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
            border-left: 4px solid #28a745;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #218838;
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
        .success-message {
            background-color: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Solicitud Aprobada</h1>
    </div>
    
    <div class="content">
        <p>Hola <strong>{{ $solicitud->usuarioSolicita->name }}</strong>,</p>
        
        <div class="success-message">
            <strong>¡Buenas noticias!</strong> Tu solicitud para compartir el archivo ha sido aprobada.
        </div>
        
        <div class="archivo-info">
            <h2 style="margin-top: 0; color: #28a745;">{{ $solicitud->archivo->nombre_original }}</h2>
            
            <div class="info-row">
                <span class="info-label">Categoría:</span> {{ $solicitud->archivo->categoria->nombre }}
            </div>
            
            <div class="info-row">
                <span class="info-label">Tamaño:</span> {{ number_format($solicitud->archivo->tamano_bytes / 1024 / 1024, 2) }} MB
            </div>
            
            <div class="info-row">
                <span class="info-label">Tipo:</span> {{ strtoupper($solicitud->archivo->extension) }}
            </div>
        </div>
        
        <div class="info-row">
            <span class="info-label">Compartido con:</span> {{ $solicitud->usuarioDestino->name }} ({{ $solicitud->usuarioDestino->email }})
        </div>
        
        <div class="info-row">
            <span class="info-label">Aprobado por:</span> {{ $solicitud->aprobadoPor->name }}
        </div>
        
        <div class="info-row">
            <span class="info-label">Fecha de aprobación:</span> {{ $solicitud->respondido_at->format('d/m/Y H:i') }}
        </div>
        
        <p style="text-align: center;">
            <a href="{{ route('descargas.show', $solicitud->archivo) }}" class="btn">Ver Archivo</a>
        </p>
        
        <div class="footer">
            <p>Este es un mensaje automático de la Plataforma de Descargas.</p>
            <p>El usuario {{ $solicitud->usuarioDestino->name }} ahora tiene acceso a este archivo.</p>
        </div>
    </div>
</body>
</html>
