<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Trae, en vivo contra `personal911`, todos los campos del funcionario que
 * `Personal911ImportService` no persiste localmente (grupo sanguíneo, sexo,
 * CUIL, legajo contable, cuerpo, función D.P.3, domicilio laboral, fecha y
 * norma de ingreso a la División 911). No se guarda nada en `personals`:
 * es solo para la vista de detalle, así no hace falta tocar el import
 * existente (usado también por Armería) ni duplicar todo el padrón.
 */
class Personal911DetalleService
{
    public function obtener(int $personal911Id): ?object
    {
        try {
            return DB::connection('personal911')
                ->table('funcionarios as f')
                ->leftJoin('funciones as fn', 'fn.Id_Funcion', '=', 'f.Funcion')
                ->leftJoin('funciones_dp3 as fdp3', 'fdp3.Id_Funcion_DP3', '=', 'f.Funcion_DP3')
                ->leftJoin('jerarquias as j', 'j.Id_Jerarquia', '=', 'f.IdJerarquia_Func')
                ->leftJoin('cuerpos as c', 'c.Id_Cuerpo', '=', 'f.IdCuerpo_Func')
                ->leftJoin('sexo_func as sx', 'sx.Id_SexoFunc', '=', 'f.Sexo_Func')
                ->leftJoin('estcivil as ec', 'ec.Id_ECivil', '=', 'f.Estado_Civil_Func')
                ->leftJoin('tipo_armas as ta', 'ta.Id_TipoArma', '=', 'f.Tipo_Arma_Func')
                ->leftJoin('tipo_estados as te', 'te.Id_TipoEstado', '=', 'f.Id_Estado')
                ->leftJoin('domicilio_laboral as dl', 'dl.Id_DomLab', '=', 'f.Dom_FuncLab')
                ->leftJoin('lugares as lg', 'lg.Id_lugar', '=', 'f.Lugar_Func')
                ->leftJoin('gruposang as gs', 'gs.Id_GrupoSang', '=', 'f.GS_Func')
                ->where('f.Id_Func', $personal911Id)
                ->select([
                    'f.Ape_Func', 'f.Nom_Func', 'f.Doc_Func', 'f.Dom_Func',
                    'f.FecNac_Func', 'gs.Nom_GrupoSang', 'f.Telefono1_Func', 'f.Telefono2_Func',
                    'f.Cuil_Func', 'f.Email_Func',
                    'f.LgjC_Func', 'f.FecIng_Func', 'f.Nro_Arma_Func', 'f.Obs_Func',
                    'f.Fec_Estado', 'f.Obs_Estado', 'f.Fec_Ing911', 'f.Norma_Ing911',
                    'fn.Nom_Funcion',
                    'fdp3.Nombre_Funcion as funcion_dp3',
                    'j.Nom_Jerarquia', 'j.Nom_JerarquiaNueva',
                    'c.Nom_Cuerpo',
                    'sx.Nombre_SexoFunc',
                    'ec.Nom_ECivil',
                    'ta.Nombre_TipoArma',
                    'te.Nom_Estado',
                    'dl.Nombre_DomLab',
                    'lg.Nom_Lugar',
                ])
                ->first();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
