<?php

namespace Database\Seeders;

use App\Models\WebHistoriaCard;
use Illuminate\Database\Seeder;

/**
 * Importa las tarjetas de la línea de tiempo de historia.html a la BD.
 * Ejecutar una sola vez: php artisan db:seed --class=WebHistoriaCardSeeder
 */
class WebHistoriaCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            ['2012', 'Nace el Sistema 911 en Paraná', 'En octubre de 2012 se pone en marcha el Sistema 911 y la videovigilancia en la ciudad de Paraná, con 75 cámaras y un Centro de Atención y Gestión de Emergencias. La Resolución 26 de la Policía de Entre Ríos establece su función institucional: coordinar la atención de primera respuesta a emergencias de Policía, Bomberos y Emergencias Médicas.', 'Hito fundacional'],
            ['2013', 'Primeros resultados medibles', 'El balance del primer año arroja cifras contundentes: 10.200 intervenciones con cámaras de videovigilancia, 626 detenidos, 27 secuestros de armas y reducción verificable de los hechos delictivos en la zona de cobertura del sistema.', 'Impacto operativo'],
            ['2012 - 2020', 'Consolidación y crecimiento sostenido', 'Durante sus primeros años, el 911 se consolida como pilar de la seguridad provincial. Se incorporan sistemas de comunicación bajo estándar TETRA, se expande la red de cámaras y se fortalece la coordinación con las comisarías de Paraná y localidades del área metropolitana.', 'Expansión'],
            ['2021', 'Nuevo Sistema Integral de Seguridad (911 SIS)', 'El Gobierno de Entre Ríos licita y adjudica el nuevo 911 SIS para Paraná y área metropolitana. Se incorporan tecnología LTE, cámaras corporales 4G, software de lectura de patentes y reconocimiento facial, 300 km de fibra óptica, y se proyecta alcanzar 400 cámaras. La cobertura se extiende a Oro Verde, Colonia Avellaneda y San Benito.', 'Modernización tecnológica'],
            ['2022', 'El 911 avanza hacia Concordia', 'Se licita la ampliación edilicia de la Jefatura de Policía Departamental de Concordia para albergar el servicio de la División 911 y Videovigilancia, dando inicio a la expansión del sistema a la segunda ciudad más importante de la provincia.', 'Expansión provincial'],
            ['2024', 'Nuevos agentes y tótems de seguridad', 'Egresan 61 nuevos agentes tras un curso intensivo de 6 semanas en atención telefónica y videovigilancia, fortaleciendo las dotaciones de toda la provincia. En octubre se instalan los primeros tótems de seguridad con botón antipánico y comunicación directa al 911 en Paraná. El primer prototipo (Galán y José María Paz) permite la detención de un ilícito a días de su activación.', 'Innovación y formación'],
            ['2025', 'Integración con el Túnel Subfluvial', 'Técnicos de la División 911 inspeccionan el sistema de monitoreo del Túnel Subfluvial, evaluando la integración de las capacidades de videovigilancia policial con la infraestructura de la conexión vial más importante de la región.', 'Cooperación interinstitucional'],
            ['Hoy', 'Compromiso en evolución', 'La División 911 y Videovigilancia continúa incorporando nuevas herramientas tecnológicas, capacitando a su personal y optimizando los protocolos de respuesta. Con más de 400 funcionarios, 400 cámaras y una red de tótems en expansión, la División mantiene su compromiso de brindar seguridad y tranquilidad a toda la comunidad, las 24 horas, los 365 días del año.', 'Presente y futuro'],
        ];

        $orden = 0;
        foreach ($cards as [$anio, $titulo, $texto, $tag]) {
            WebHistoriaCard::updateOrCreate(
                ['anio' => $anio, 'titulo' => $titulo],
                ['texto' => $texto, 'tag' => $tag, 'orden' => ++$orden],
            );
        }

        $this->command->info('Tarjetas de historia importadas a web_historia_cards.');
    }
}
