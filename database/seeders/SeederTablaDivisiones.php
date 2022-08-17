<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class SeederTablaDivisiones extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $divisionesOper = [
            'Policia Adicional',
            'Minoridad Y Violencia Familiar',
            'Secretaria General',
            'Comunicaciones',
            'Bomberos Zapadores',
            'Guardia Infanteria Adiestrada',
            'Alcaidía Tribunales',
            'Seguridad Urbana Y Bancaria',
            'Custodia Gubernamental',
            'Compañía de Operaciones Especiales',
            'Seguridad Deportiva',
            '911 y Videovigilancia',
            'Montada Y Canes',
            'Custodia de Autoridades Gubernamentales',
            'Planeamiento y Desarrollo',
        ];

        $divisionesAyudantia = [
            'Informática',
            'Secretaría General',
            'Relaciones Publicas Prensa Y Ceremonial',
            'Servicio Médico Sanitario',
            'Asesoría Letrada',
            'Relaciones Culturales',
            'Convenio Policial',
            'Odontologia',
            'Proyectos Tecnológicos y CCTV',
        ];

        $divisionesInstituto = [
            'Secretaría General y Personal',
            'Capellania',
            'Logística',
            'Escuela Superior De Oficiales',
            'Instrucción y Capacitación',
            'Escuela Policial De Formacion Profesional',
            'Escuela De Agentes',
            'Escuela De Suboficiales',
            'Coordinación Universitaria',
        ];

        $divisionesLogistica = [
            'Secretaría General y Personal',
            'Abastecimiento y Servicio Integral del Automotor',
            'Arquitectura',
            'Finanzas',
            'Licitaciones y Compras',
            'Servicios',
            'Tesorería',
            'Control De Gestion y Rendición De Cuentas',
        ];

        $divisionesPersonal = [
            'Administración de Recursos Humanos',
            'Asuntos Previsionales y Sociales',
            'Despacho',
            'Junta Médica Superior',
            'Higiene y Seguridad en el Trabajo',
            'Armas y Municiones',
        ];

        $divisionesCrimin = [
            'Accidentologia Vial',
            'Quimica Forense y Toxicologia',
            'Rastros',
            'Scopometría',
            'Secretaria General y Personal',
            'Antecedentes Personales',
            'Registro Provincial Armas',
        ];

        $divisionesIntelig = [
            'Despacho',
            'Busqueda y Reunión de la Informacion',
            'Técnica E Informatica',
        ];

        $divisionesPrevVial = [
            'Operaciones Viales',
            'Logistica',
            'Educación Vial',
            'Secretaria General',
            'Asesoria Juridica y Ejecuciones Fiscales',
        ];

        $divisionesAsuntosInt = [
            'Sumarios Administrativos',
            'Inteligencia e Investigaciones Internas',
            'Secretaria General y Personal',
        ];

        $divisionesInvest = [
            'Secretaría General y Personal',
            'Robos y Hurtos',
            'Homicidios',
            'Trata de Personas',
            'Sustracción Automotores',
            'Delitos Económicos',
            'Asuntos Jurídicos',
        ];

        $divisionesIntCrim = [
            'Despacho y Relaciones Institucionales',
            'Tecnicas Especiales y Desarrollo Informático',
            'Delitos Complejos',
            'Cibercrimen',
            'Capacitacion Y Relaciones Institucionales',
        ];

        $divisionesToxic = [
            'Inteligencia',
            'Operaciones Tecnicas y Pericias Tecnologicas',
            'Prevención Conductas Adictivas',
            'Secretaría General',
            'Operaciones',
        ];

        $divisionesDelRur = [
            'Secretaria General y Personal',
            'Operaciones y Seguridad',
            'Logística',
            'Inter. Forestales y E.Soc.',
            'Investigaciones',
        ];

        //Faltan las divisiones por departamental
        //Colon
        $divisionesColon = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //concordia
        $divisionesConcordia = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //diamante
        $divisionesDiamante = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //federacion
        $divisionesFederacion = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //federal
        $divisionesFederal = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //feliciano
        $divisionesFeliciano = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //gualeguay
        $divisionesGualeguay = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //gualeguaychu
        $divisionesGualeguaychu = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //islas del ibicuy
        $divisionesIslas = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //la paz
        $divisionesLaPaz = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //nogoya
        $divisionesNogoya = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //parana
        $divisionesParana = [
            'Secretaria General y Personal',
            'Operaciones y Seguridad',
            'Logística',
        ];
        //san salvador
        $divisionesSanSalvador = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //tala
        $divisionesTala = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
        ];
        //Uruguay
        $divisionesUruguay = [
            'Secretaria General y Personal',
            'Operaciones y Seguridad',
            'Logística',
            'Inter. Forestales y E.Soc.',
            'Investigaciones',
        ];
        //victoria
        $divisionesVictoria = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];
        //villaguay
        $divisionesVillaguay = [
            'Operaciones Y Seguridad',
            'Investigaciones',
            'Logística',
            'Secretaria General y Personal',
            'Toxicologia',
            'Criminalística',
        ];


        foreach($divisionesOper as $division){
            Division::create([
                'direccion_id' => '1',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesAyudantia as $division){
            Division::create([
                'direccion_id' => '2',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesInstituto as $division){
            Division::create([
                'direccion_id' => '3',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesLogistica as $division){
            Division::create([
                'direccion_id' => '4',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesPersonal as $division){
            Division::create([
                'direccion_id' => '5',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesCrimin as $division){
            Division::create([
                'direccion_id' => '6',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesIntelig as $division){
            Division::create([
                'direccion_id' => '7',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesPrevVial as $division){
            Division::create([
                'direccion_id' => '8',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesAsuntosInt as $division){
            Division::create([
                'direccion_id' => '9',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesInvest as $division){
            Division::create([
                'direccion_id' => '10',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesIntCrim as $division){
            Division::create([
                'direccion_id' => '11',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesToxic as $division){
            Division::create([
                'direccion_id' => '12',
                'nombre' => $division,
            ]);
        }

        foreach($divisionesDelRur as $division){
            Division::create([
                'direccion_id' => '13',
                'nombre' => $division,
            ]);
        }

        //dependientes de Departamentales
        foreach($divisionesColon as $division){
            Division::create([
                'departamental_id' => '1',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesConcordia as $division){
            Division::create([
                'departamental_id' => '2',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesDiamante as $division){
            Division::create([
                'departamental_id' => '3',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesFederacion as $division){
            Division::create([
                'departamental_id' => '4',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesFederal as $division){
            Division::create([
                'departamental_id' => '5',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesFeliciano as $division){
            Division::create([
                'departamental_id' => '6',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesGualeguay as $division){
            Division::create([
                'departamental_id' => '7',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesGualeguaychu as $division){
            Division::create([
                'departamental_id' => '8',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesIslas as $division){
            Division::create([
                'departamental_id' => '9',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesLaPaz as $division){
            Division::create([
                'departamental_id' => '10',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesNogoya as $division){
            Division::create([
                'departamental_id' => '11',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesParana as $division){
            Division::create([
                'departamental_id' => '12',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesSanSalvador as $division){
            Division::create([
                'departamental_id' => '13',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesTala as $division){
            Division::create([
                'departamental_id' => '14',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesUruguay as $division){
            Division::create([
                'departamental_id' => '15',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesVictoria as $division){
            Division::create([
                'departamental_id' => '16',
                'nombre' => $division,
            ]);
        }
        foreach($divisionesVillaguay as $division){
            Division::create([
                'departamental_id' => '17',
                'nombre' => $division,
            ]);
        }
    }
}
