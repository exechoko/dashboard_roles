<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #1a3a5c; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .header h2 { margin: 0; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
        .info-box { background: #e7f3ff; border-left: 4px solid #0066cc; padding: 15px; margin: 15px 0; }
        .footer { background: #1a3a5c; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 8px 8px; }
        .warn-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; font-size: 13px; }
        .contact-box { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin: 15px 0; }
        .contact-box p { margin: 4px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="Sistema CAR911" style="height: 70px; margin-bottom: 10px;">
            <h2>Sistema CAR911</h2>
        </div>

        <div class="content">
            <p>Estimado/a <strong>{{ $nombreUsuario }}</strong>,</p>

            <p>Le informamos que se ha creado su cuenta de acceso al <strong>Sistema CAR911</strong>.</p>

            <div class="info-box">
                <p><strong>Su credencial de acceso es su correo electrónico: {{ $emailUsuario }}</strong></p>
                <p>Puede acceder al sistema a través del siguiente enlace:</p>
                <p style="text-align: center;">
                    <a href="{{ $sistemaURL }}" style="display: inline-block; background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                        Acceder al Sistema CAR911
                    </a>
                </p>
            </div>

            <div class="warn-box">
                <p><strong>Importante:</strong> En caso de olvidar sus credenciales de acceso o tener inconvenientes para ingresar al sistema, deberá comunicarse con la <strong>Sección Técnica de la División 911 y Video Vigilancia.</strong></p>
            </div>

            <div class="contact-box">
                <p><strong>Contacto ante inconvenientes:</strong></p>
                <p>📧 Email: <a href="mailto:tecnica911per@gmail.com">tecnica911per@gmail.com</a></p>
                <p>📱 WhatsApp: 3434708413</p>
                <p>☎️ Fijo: 3434420004</p>
            </div>

            <p>Atentamente,<br>
            <strong>Sección Técnica - División 911 y Video Vigilancia.</strong></p>
        </div>

        <div class="footer">
            <p>Este mensaje ha sido generado automáticamente por el Sistema CAR911.</p>
            <p>División 911 y Video Vigilancia - Policía de Entre Ríos</p>
        </div>
    </div>
</body>
</html>
