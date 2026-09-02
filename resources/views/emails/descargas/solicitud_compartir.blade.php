<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud para compartir archivo</title>
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
        .motivo {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔗 Nueva Solicitud para Compartir Archivo</h1>
    </div>
    
    <div class="content">
        <p>Hola <strong>Administrador</strong>,</p>
        
        <p>Un usuario ha solicitado compartir un archivo con otro usuario:</p>
        
        <div class="archivo-info">
            <h2 style="margin-top: 0; color: #007bff;">{{ $solicitud->archivo->nombre_original }}</h2>
            
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
            <span class="info-label">Solicitado por:</span> {{ $solicitud->usuarioSolicita->name }} ({{ $solicitud->usuarioSolicita->email }})
        </div>
        
        <div class="info-row">
            <span class="info-label">Compartir con:</span> {{ $solicitud->usuarioDestino->name }} ({{ $solicitud->usuarioDestino->email }})
        </div>
        
        @if($solicitud->motivo)
            <div class="motivo">
                <strong>Motivo de la solicitud:</strong><br>
                {{ $solicitud->motivo }}
            </div>
        @endif
        
        <p style="text-align: center;">
            <a href="{{ route('descargas.admin.solicitudes') }}" class="btn">Revisar Solicitud</a>
        </p>
        
        <div class="footer">
            <p>Este es un mensaje automático de la Plataforma de Descargas.</p>
            <p>Por favor, revisa y aprueba/rechaza esta solicitud desde el panel de administración.</p>
        </div>
    </div>
</body>
</html>
