<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
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
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin: 0 auto 12px;">
                <tr>
                    <td width="96" height="96" align="center" valign="middle" style="border-radius: 50%; border: 1px dashed rgba(0, 229, 255, 0.35);">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="82" height="82" align="center" valign="middle" style="border-radius: 50%; border: 1px solid rgba(139, 92, 246, 0.55);">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td width="70" height="70" align="center" valign="middle" bgcolor="#ffffff" style="border-radius: 50%; background-color: #ffffff; border: 1px solid rgba(0, 229, 255, 0.4); box-shadow: 0 0 18px rgba(0, 229, 255, 0.3); mso-padding-alt: 0;">
                                                <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="Sistema CAR911" width="56" height="56" style="display: block; width: 56px; height: 56px; object-fit: contain;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
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
