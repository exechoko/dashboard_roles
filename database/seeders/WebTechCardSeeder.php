<?php

namespace Database\Seeders;

use App\Models\WebTechCard;
use Illuminate\Database\Seeder;

/**
 * Importa las cards de tecnologia.html a la BD.
 * Ejecutar una sola vez: php artisan db:seed --class=WebTechCardSeeder
 */
class WebTechCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            ['SALA DE DESPACHO - OPERADORES DE RADIO', 'blue', 'Área responsable de coordinar y asignar los recursos policiales ante emergencias. Desde aquí se gestionan los móviles en servicio, se realiza el seguimiento de los eventos en curso y se mantiene comunicación permanente con el personal en la vía pública.'],
            ['SALA DE TELEFONISTAS', 'green', 'Espacio donde se reciben las llamadas al 911. Los operadores recopilan información clave del incidente, brindan contención al ciudadano y cargan los datos en el sistema para su inmediata derivación y despacho.'],
            ['SALA DE VIDEOVIGILANCIA', 'purple', 'Sector encargado del monitoreo en tiempo real de las cámaras de seguridad ubicadas en distintos puntos estratégicos. Su función es detectar situaciones sospechosas, aportar apoyo visual a los operativos y resguardar material probatorio.'],
            ['SECCIÓN DE GÉNERO', 'pink', 'Área especializada en la atención de situaciones vinculadas a violencia de género. Brinda asistencia, orientación y coordinación con organismos judiciales y sociales, priorizando la protección de las víctimas.'],
            ['SECCIÓN PATRULLA', 'indigo', 'Unidad encargada de la prevención y respuesta en territorio mediante recorridas permanentes. Interviene en incidentes, brinda presencia policial disuasiva y colabora con otras dependencias en operativos de seguridad.'],
            ['SECCIÓN PATRULLA MOTORIZADA', 'orange', 'Equipo operativo que utiliza motocicletas para una respuesta rápida en zonas urbanas y de difícil acceso. Su movilidad permite reducir tiempos de llegada ante emergencias y reforzar tareas de prevención y control.'],
            ['UNIDAD OPERATIVA MÓVIL', 'red', "La Unidad Operativa Móvil es un recurso estratégico diseñado para desplegar un puesto de comando y control en el lugar donde se requiera. Equipada con sistemas de comunicaciones TETRA, antenas de transmisión y acceso remoto al sistema de videovigilancia, permite coordinar operativos en tiempo real desde el terreno.\n\nEste vehículo facilita la gestión de eventos de gran magnitud, operativos de seguridad, situaciones de emergencia o contingencias, garantizando comunicación segura, monitoreo de cámaras y enlace directo con el Centro de Operaciones. Su capacidad de movilidad y autonomía tecnológica lo convierten en una herramienta clave para reforzar la prevención, optimizar la respuesta policial y mejorar la coordinación interinstitucional."],
        ];

        $orden = 0;
        foreach ($cards as [$titulo, $color, $texto]) {
            WebTechCard::updateOrCreate(
                ['titulo' => $titulo],
                ['texto' => $texto, 'color' => $color, 'orden' => ++$orden],
            );
        }

        $this->command->info('Cards de tecnología importadas a web_tech_cards.');
    }
}
