<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión de Histórico</title>
    <!-- Incluir Font Awesome desde CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }

        .print-toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .print-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .close-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 6px 8px;
            text-align: left;
        }

        thead tr {
            background-color: #888888;
            color: white;
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Barra de herramientas flotante -->
    <div class="print-toolbar">
        <button class="print-btn" onclick="imprimirDocumento()">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button class="close-btn" onclick="cerrarVentana()">
            <i class="fas fa-times"></i> Cerrar
        </button>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        @yield('content')
    </div>

    <script>
        // Función para imprimir
        function imprimirDocumento() {
            console.log('Ejecutando función de impresión...');
            window.print();
        }

        // Función mejorada para cerrar la ventana
        function cerrarVentana() {
            // Intenta cerrar la pestaña actual
            window.open('', '_self').close();

            // Si falla (por políticas del navegador), redirige como alternativa
            setTimeout(() => {
                if (!window.closed) {
                    window.location.href = '/';
                }
            }, 500);
        }
        // Forzar la recarga de la página si los botones no funcionan
        document.addEventListener('DOMContentLoaded', function () {
            console.log('Documento cargado correctamente');

            // Verificar si los botones responden
            document.querySelector('.print-btn').addEventListener('click', function () {
                console.log('Botón Imprimir clickeado');
            });

            document.querySelector('.close-btn').addEventListener('click', function () {
                console.log('Botón Cerrar clickeado');
            });
        });
    </script>
</body>

</html>
