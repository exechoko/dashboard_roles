<?php

namespace App\Observers;

use App\Models\Equipo;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class EquipoObserver
{
    /**
     * Handle the Equipo "created" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function created(Equipo $equipo)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'equipos_modificado_id' => $equipo->id,
                'nombre_tabla' => 'equipos',
                'accion' => 'CREAR EQUIPO',
            ]);
            $changes = sprintf(
                'Equipo creado: ID: %d - ISSI: %s - TEI: %s - ESTADO: %s - TIPO_TERMINAL: %s',
                $equipo->id,
                $equipo->issi,
                $equipo->tei,
                $equipo->estado->nombre ?? 'N/A',
                $equipo->tipo_terminal->tipo_uso ?? 'N/A'
            );
            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Equipo "updated" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function updated(Equipo $equipo)
    {
        DB::beginTransaction();
        try {
            $changes = [];
            $dirty = $equipo->getDirty();
            $original = $equipo->getOriginal();

            // Solo proceder si hay cambios reales
            if (empty($dirty)) {
                return;
            }

            // Mapear los nombres de campos para mostrar
            $fieldNames = [
                'issi' => 'ISSI',
                'tei' => 'TEI',
                'tipo_terminal_id' => 'Tipo Terminal',
                'estado_id' => 'Estado',
                'fecha_estado' => 'Fecha Estado',
                'gps' => 'GPS',
                'desc_gps' => 'Descripción GPS',
                'frente_remoto' => 'Frente Remoto',
                'desc_frente' => 'Descripción Frente',
                'rf' => 'RF',
                'desc_rf' => 'Descripción RF',
                'kit_inst' => 'Kit Instalación',
                'desc_kit_inst' => 'Descripción Kit Instalación',
                'operativo' => 'Operativo',
                'propietario' => 'Propietario',
                'condicion' => 'Condición',
                'con_garantia' => 'Con Garantía',
                'fecha_venc_garantia' => 'Fecha Vencimiento Garantía',
                'observaciones' => 'Observaciones'
            ];

            foreach ($dirty as $field => $newValue) {
                $oldValue = $original[$field] ?? null;
                $fieldLabel = $fieldNames[$field] ?? $field;

                // Manejar campos especiales con relaciones
                if ($field === 'tipo_terminal_id') {
                    $oldTerminal = $oldValue ? \App\Models\TipoTerminal::find($oldValue)?->tipo_uso : 'N/A';
                    $newTerminal = $newValue ? \App\Models\TipoTerminal::find($newValue)?->tipo_uso : 'N/A';
                    $changes[] = sprintf('%s: "%s" → "%s"', $fieldLabel, $oldTerminal, $newTerminal);
                } elseif ($field === 'estado_id') {
                    $oldEstado = $oldValue ? \App\Models\Estado::find($oldValue)?->nombre : 'N/A';
                    $newEstado = $newValue ? \App\Models\Estado::find($newValue)?->nombre : 'N/A';
                    $changes[] = sprintf('%s: "%s" → "%s"', $fieldLabel, $oldEstado, $newEstado);
                } elseif (in_array($field, ['gps', 'frente_remoto', 'rf', 'kit_inst', 'operativo', 'con_garantia'])) {
                    // Campos booleanos - convertir a texto legible
                    $oldText = $oldValue ? 'Sí' : 'No';
                    $newText = $newValue ? 'Sí' : 'No';
                    $changes[] = sprintf('%s: "%s" → "%s"', $fieldLabel, $oldText, $newText);
                } elseif (in_array($field, ['fecha_estado', 'fecha_venc_garantia'])) {
                    // Campos de fecha - formato legible
                    $oldDate = $oldValue ? date('d/m/Y', strtotime($oldValue)) : 'N/A';
                    $newDate = $newValue ? date('d/m/Y', strtotime($newValue)) : 'N/A';
                    $changes[] = sprintf('%s: "%s" → "%s"', $fieldLabel, $oldDate, $newDate);
                } else {
                    // Campos de texto normales
                    $changes[] = sprintf('%s: "%s" → "%s"', $fieldLabel, $oldValue ?? 'N/A', $newValue ?? 'N/A');
                }
            }

            if (!empty($changes)) {
                $aud = new Auditoria([
                    'user_id' => auth()->id(),
                    'equipos_modificado_id' => $equipo->id,
                    'nombre_tabla' => 'equipos',
                    'accion' => 'ACTUALIZAR EQUIPO',
                ]);

                $changeText = sprintf(
                    'Equipo actualizado (ID: %d - ISSI: %s - TEI: %s): %s',
                    $equipo->id,
                    $equipo->issi,
                    $equipo->tei,
                    implode(', ', $changes)
                );

                $aud->cambios = $changeText;
                $aud->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Equipo "deleted" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function deleted(Equipo $equipo)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'equipos_modificado_id' => $equipo->id,
                'nombre_tabla' => 'equipos',
                'accion' => 'ELIMINAR EQUIPO',
            ]);

            $changes = sprintf(
                'Equipo eliminado: ID: %d - ISSI: %s - TEI: %s - ESTADO: %s - TIPO_TERMINAL: %s - PROPIETARIO: %s',
                $equipo->id,
                $equipo->issi,
                $equipo->tei,
                $equipo->estado->nombre ?? 'N/A',
                $equipo->tipo_terminal->tipo_uso ?? 'N/A',
                $equipo->propietario ?? 'N/A'
            );

            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Equipo "restored" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function restored(Equipo $equipo)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'equipos_modificado_id' => $equipo->id,
                'nombre_tabla' => 'equipos',
                'accion' => 'RESTAURAR EQUIPO',
            ]);

            $changes = sprintf(
                'Equipo restaurado: ID: %d - ISSI: %s - TEI: %s - ESTADO: %s - TIPO_TERMINAL: %s - PROPIETARIO: %s',
                $equipo->id,
                $equipo->issi,
                $equipo->tei,
                $equipo->estado->nombre ?? 'N/A',
                $equipo->tipo_terminal->tipo_uso ?? 'N/A',
                $equipo->propietario ?? 'N/A'
            );

            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Handle the Equipo "force deleted" event.
     *
     * @param  \App\Models\Equipo  $equipo
     * @return void
     */
    public function forceDeleted(Equipo $equipo)
    {
        DB::beginTransaction();
        try {
            $aud = new Auditoria([
                'user_id' => auth()->id(),
                'equipos_modificado_id' => $equipo->id,
                'nombre_tabla' => 'equipos',
                'accion' => 'ELIMINAR PERMANENTE EQUIPO',
            ]);

            $changes = sprintf(
                'Equipo eliminado permanentemente: ID: %d - ISSI: %s - TEI: %s - ESTADO: %s - TIPO_TERMINAL: %s - PROPIETARIO: %s',
                $equipo->id,
                $equipo->issi,
                $equipo->tei,
                $equipo->estado->nombre ?? 'N/A',
                $equipo->tipo_terminal->tipo_uso ?? 'N/A',
                $equipo->propietario ?? 'N/A'
            );

            $aud->cambios = $changes;
            $aud->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
