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
            'División Policia Adicional',
            'División Minoridad Y Violencia Familiar',
            'División Secretaria General',
            'División Comunicaciones',
            'División Bomberos Zapadores',
            'División Guardia Infanteria Adiestrada',
            'División Alcaidía Tribunales',
            'División Seguridad Urbana Y Bancaria',
            'División Custodia Gubernamental',
            'División Compañía de Operaciones Especiales',
            'División Seguridad Deportiva',
            'División 911 y Videovigilancia',
            'División Montada Y Canes',
            'División Custodia de Autoridades Gubernamentales',
            'División Planeamiento y Desarrollo',
        ];

        $divisionesAyudantia = [
            'División Informática',
            'División Secretaría General',
            'División Relaciones Publicas Prensa Y Ceremonial',
            'División Servicio Médico Sanitario',
            'División Asesoría Letrada',
            'División Relaciones Culturales',
            'División Convenio Policial',
            'División Odontologia',
            'División Proyectos Tecnológicos y CCTV',
        ];

        $divisionesInstituto = [
            'División Secretaría General y Personal',
            'División Capellania',
            'División Logística',
            'División Escuela Superior De Oficiales',
            'División Instrucción y Capacitación',
            'División Escuela Policial De Formacion Profesional',
            'División Escuela De Agentes',
            'División Escuela De Suboficiales',
            'División Coordinación Universitaria',
        ];

        $divisionesLogistica = [
            'División Secretaría General y Personal',
            'División Abastecimiento y Servicio Integral del Automotor',
            'División Arquitectura',
            'División Finanzas',
            'División Licitaciones y Compras',
            'División Servicios',
            'División Tesorería',
            'División Control De Gestion y Rendición De Cuentas',
        ];

        $divisionesPersonal = [
            'División Administración de Recursos Humanos',
            'División Asuntos Previsionales y Sociales',
            'División Despacho',
            'División Junta Médica Superior',
            'División Higiene y Seguridad en el Trabajo',
            'División Armas y Municiones',
        ];

        $divisionesCrimin = [
            'División Accidentologia Vial',
            'División Quimica Forense y Toxicologia',
            'División Rastros',
            'División Scopometría',
            'División Secretaria General y Personal',
            'División Antecedentes Personales',
            'División Registro Provincial Armas',
        ];

        $divisionesIntelig = [
            'División Despacho',
            'División Busqueda y Reunión de la Informacion',
            'División Técnica E Informatica',
        ];

        $divisionesPrevVial = [
            'División Operaciones Viales',
            'División Logistica',
            'División Educación Vial',
            'División Secretaria General',
            'División Asesoria Juridica y Ejecuciones Fiscales',
        ];

        $divisionesAsuntosInt = [
            'División Sumarios Administrativos',
            'División Inteligencia e Investigaciones Internas',
            'División Secretaria General y Personal',
        ];

        $divisionesInvest = [
            'División Secretaría General y Personal',
            'División Robos y Hurtos',
            'División Homicidios',
            'División Trata de Personas',
            'División Sustracción Automotores',
            'División Delitos Económicos',
            'División Asuntos Jurídicos',
        ];

        $divisionesIntCrim = [
            'División Despacho y Relaciones Institucionales',
            'División Tecnicas Especiales y Desarrollo Informático',
            'División Delitos Complejos',
            'División Cibercrimen',
            'División Capacitacion Y Relaciones Institucionales',
        ];

        $divisionesToxic = [
            'División Inteligencia',
            'División Operaciones Tecnicas y Pericias Tecnologicas',
            'División Prevención Conductas Adictivas',
            'División Secretaría General',
            'División Operaciones',
        ];

        $divisionesDelRur = [
            'División Secretaria General y Personal',
            'División Operaciones y Seguridad',
            'División Logística',
            'División Inter. Forestales y E.Soc.',
            'División Investigaciones',
        ];

        //Faltan las divisiones por departamental
        //Colon
        $divisionesColon = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //concordia
        $divisionesConcordia = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //diamante
        $divisionesDiamante = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //federacion
        $divisionesFederacion = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //federal
        $divisionesFederal = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //feliciano
        $divisionesFeliciano = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //gualeguay
        $divisionesGualeguay = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //gualeguaychu
        $divisionesGualeguaychu = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //islas del ibicuy
        $divisionesIslas = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //la paz
        $divisionesLaPaz = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //nogoya
        $divisionesNogoya = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //parana
        $divisionesParana = [
            'División Secretaria General y Personal',
            'División Operaciones y Seguridad',
            'División Logística',
        ];
        //san salvador
        $divisionesSanSalvador = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //tala
        $divisionesTala = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
        ];
        //Uruguay
        $divisionesUruguay = [
            'División Secretaria General y Personal',
            'División Operaciones y Seguridad',
            'División Logística',
            'División Inter. Forestales y E.Soc.',
            'División Investigaciones',
        ];
        //victoria
        $divisionesVictoria = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
        ];
        //villaguay
        $divisionesVillaguay = [
            'División Operaciones Y Seguridad',
            'División Investigaciones',
            'División Logística',
            'División Secretaria General y Personal',
            'División Toxicologia',
            'División Criminalística',
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
