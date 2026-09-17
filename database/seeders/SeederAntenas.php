<?php

namespace Database\Seeders;

use App\Models\Antena;
use Illuminate\Database\Seeder;

/**
 * Carga las antenas (SBS) que antes estaban hardcodeadas en
 * MapaController::antenasFijas(), con la altura de cada mástil.
 *
 * Los datos de Paraná (SBS1/2/3) fueron actualizados con la información
 * exacta de docs/varios/Sitios_TETRA_Parana.md (memorias "Conforme a Obra"
 * Daxa Argentina, nov. 2012 y memorias de cálculo Carvajal S.A.I.C., dic. 2011).
 *
 * Ejecutar: php artisan db:seed --class=SeederAntenas
 */
class SeederAntenas extends Seeder
{
    public function run(): void
    {
        $antenas = [
            [
                'nombre' => 'SBS 1',
                'localidad' => 'Paraná',
                'ubicacion' => 'Calle Córdoba entre Méjico y Narciso Laprida, Paraná',
                'latitud' => -31.726550,
                'longitud' => -60.533008,
                'altura' => 54,
                'activa' => true,
                'observaciones' => "Sitio: Jefatura.\n"
                    . "Altura estructura sin antena (brazo RX): 50,7 m. Altura antena TX: 46,3 m.\n"
                    . "Antenas: 3 omnidireccionales RX en diversidad 3 (triángulo de 4,5 m de lado) + 1 omni TX. Modelo 738192, VPol Omni 806-894 MHz, 360°, 11 dBi, 500 W máx., 3,237 m de largo.\n"
                    . "Trunking: 2 portadoras TETRA BSR75 (8 canales). Portadora 1: Rx 821,0875 / Tx 866,0875 MHz. Portadora 2: Rx 822,0875 / Tx 867,0875 MHz.\n"
                    . "Red IP (Nebula): 172.16.1.0/24, Gateway 172.16.1.130, BSR1 172.16.1.1, BSR2 172.16.1.2, LSC 172.16.1.127, SNI 172.16.1.130, I/O PLC 172.16.1.200, Switch 172.20.201.100, PS 172.16.1.216.\n"
                    . 'Energía: grupo electrógeno monofásico 6 KVA + TTA (FENK), UPS Borry RT-3000 3 KVA, rectificador PSI AC 3000 EM (2 módulos PSIM 2000).',
            ],
            [
                'nombre' => 'SBS 2',
                'localidad' => 'Paraná',
                'ubicacion' => 'Intersección Lola Mora y Mario Monti S/N, Paraná',
                'latitud' => -31.750794,
                'longitud' => -60.485525,
                'altura' => 36,
                'activa' => true,
                'observaciones' => "Sitio: Comisaría 12.\n"
                    . "Altura estructura sin antena (brazo RX): 32,7 m. Altura antena TX: 28,3 m.\n"
                    . "Antenas: 3 omnidireccionales RX en diversidad 3 (triángulo de 4,5 m de lado) + 1 omni TX. Modelo 738192, VPol Omni 806-894 MHz, 360°, 11 dBi, 500 W máx., 3,237 m de largo.\n"
                    . "Trunking: 2 portadoras TETRA BSR75 (8 canales). Portadora 1: Rx 821,3375 / Tx 866,3375 MHz. Portadora 2: Rx 822,3375 / Tx 867,3375 MHz.\n"
                    . "Red IP (Nebula): 172.16.2.0/24, Gateway 172.16.2.130, BSR1 172.16.2.1, BSR2 172.16.2.2, LSC 172.16.2.127, SNI 172.16.2.130, I/O PLC 172.16.2.200, Switch 172.20.202.100, PS 172.16.2.216.\n"
                    . 'Energía: grupo electrógeno monofásico 6 KVA + TTA (FENK), UPS Borry RT-3000 3 KVA, rectificador PSI AC 3000 EM (2 módulos PSIM 2000).',
            ],
            [
                'nombre' => 'SBS 3',
                'localidad' => 'Paraná',
                'ubicacion' => 'Juan Báez 50, Paraná',
                'latitud' => -31.770872,
                'longitud' => -60.524775,
                'altura' => 48,
                'activa' => true,
                'observaciones' => "Sitio: Comisaría 13.\n"
                    . "Altura estructura sin antena (brazo RX): 44,7 m. Altura antena TX: 40,3 m.\n"
                    . "Antenas: 3 omnidireccionales RX en diversidad 3 (triángulo de 4,5 m de lado) + 1 omni TX. Modelo 738192, VPol Omni 806-894 MHz, 360°, 11 dBi, 500 W máx., 3,237 m de largo.\n"
                    . "Trunking: 2 portadoras TETRA BSR75 (8 canales). Portadora 1: Rx 821,5875 / Tx 866,5875 MHz. Portadora 2: Rx 822,5875 / Tx 867,5875 MHz.\n"
                    . "Red IP (Nebula): 172.16.3.0/24, Gateway 172.16.3.130, BSR1 172.16.3.1, BSR2 172.16.3.2, LSC 172.16.3.127, SNI 172.16.3.130, I/O PLC 172.16.3.200, Switch 172.20.203.100, PS 172.16.3.216.\n"
                    . 'Energía: grupo electrógeno monofásico 6 KVA + TTA (FENK), UPS Borry RT-3000 3 KVA, rectificador PSI AC 3000 EM (2 módulos PSIM 2000).',
            ],
            [
                'nombre' => 'SBS 11',
                'localidad' => 'Concordia',
                'latitud' => -31.324043,
                'longitud' => -58.012072,
                'altura' => null,
                'activa' => true,
            ],
            [
                'nombre' => 'SBS 12',
                'localidad' => 'Concordia',
                'latitud' => -31.391542,
                'longitud' => -58.032703,
                'altura' => null,
                'activa' => true,
            ],
        ];

        foreach ($antenas as $antena) {
            Antena::updateOrCreate(
                ['nombre' => $antena['nombre'], 'localidad' => $antena['localidad']],
                $antena
            );
        }

        $this->command->info('Antenas (SBS) cargadas correctamente.');
    }
}
