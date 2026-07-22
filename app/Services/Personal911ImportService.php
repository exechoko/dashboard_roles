<?php

namespace App\Services;

use App\Models\Arma;
use App\Models\ArmaTipo;
use App\Models\Chaleco;
use App\Models\InventarioConflicto;
use App\Models\InventarioDiscrepancia;
use App\Models\Personal;
use App\Models\PersonalArmaAsignacion;
use App\Models\PersonalChalecoAsignacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Personal911ImportService
{
    /**
     * @return array{procesados: int, armas: int, chalecos: int, conflictos_armas: array<int, array{numero: string, funcionarios: string}>, conflictos_chalecos: array<int, array{numero: string, funcionarios: string}>, observaciones_sin_interpretar: array<int, array{lp: string, observacion: string}>}
     */
    public function importar(): array
    {
        $funcionarios = DB::connection('personal911')
            ->table('funcionarios as f')
            ->leftJoin('tipo_armas as ta', 'ta.Id_TipoArma', '=', 'f.Tipo_Arma_Func')
            ->leftJoin('jerarquias as j', 'j.Id_Jerarquia', '=', 'f.IdJerarquia_Func')
            ->select([
                'f.Id_Func',
                'f.Ape_Func',
                'f.Nom_Func',
                'f.LgjP_Func',
                'f.Doc_Func',
                'f.Nro_Arma_Func',
                'f.Obs_Func',
                'ta.Nombre_TipoArma',
                'j.Nom_JerarquiaNueva',
                'j.Nom_Jerarquia',
            ])
            ->where('f.Id_Estado', 2)
            ->orderBy('f.Id_Func')
            ->get();

        if ($funcionarios->isEmpty()) {
            throw new \RuntimeException('personal911 no devolvió funcionarios activos; se canceló la sincronización.');
        }

        $armasDuplicadas = $funcionarios
            ->filter(fn ($funcionario): bool => trim((string) $funcionario->Nro_Arma_Func) !== '')
            ->groupBy(fn ($funcionario): string => Str::upper(trim((string) $funcionario->Nro_Arma_Func)))
            ->filter(fn ($grupo): bool => $grupo->count() > 1);

        $chalecosPorFuncionario = $funcionarios->mapWithKeys(
            fn ($funcionario): array => [
                $funcionario->Id_Func => $this->extraerChaleco((string) $funcionario->Obs_Func),
            ]
        );
        $chalecosDuplicados = $funcionarios
            ->filter(fn ($funcionario): bool => $chalecosPorFuncionario->get($funcionario->Id_Func) !== null)
            ->groupBy(fn ($funcionario): string => $chalecosPorFuncionario->get($funcionario->Id_Func)['numero_serie'])
            ->filter(fn ($grupo): bool => $grupo->count() > 1);

        $resultado = [
            'procesados' => 0,
            'armas' => 0,
            'chalecos' => 0,
            'conflictos_armas' => [],
            'conflictos_chalecos' => [],
            'observaciones_sin_interpretar' => [],
        ];

        DB::transaction(function () use ($funcionarios, $armasDuplicadas, $chalecosPorFuncionario, $chalecosDuplicados, &$resultado): void {
            $idsPersonal911 = $funcionarios->pluck('Id_Func')->all();
            $this->desactivarPersonalFueraDeServicio($idsPersonal911);
            $this->sincronizarConflictos($armasDuplicadas, $chalecosDuplicados);

            foreach ($funcionarios as $funcionario) {
                $lp = trim((string) $funcionario->LgjP_Func);

                if ($lp === '') {
                    continue;
                }

                $personal = Personal::withTrashed()->where('personal911_id', $funcionario->Id_Func)->first();

                if ($personal === null) {
                    $personal = Personal::withTrashed()->where('lp', $lp)->firstOrNew();
                    if ($personal->personal911_id !== null && $personal->personal911_id !== $funcionario->Id_Func) {
                        throw new \RuntimeException("El L.P. {$lp} ya está vinculado a otro funcionario de personal911.");
                    }
                }

                $doc = trim((string) $funcionario->Doc_Func);

                $personal->fill([
                    'personal911_id' => $funcionario->Id_Func,
                    'lp' => $lp,
                    'nombre' => trim((string) $funcionario->Nom_Func),
                    'apellido' => trim((string) $funcionario->Ape_Func),
                    'dni' => $doc !== '' ? $doc : $personal->dni,
                    'jerarquia' => trim((string) ($funcionario->Nom_Jerarquia ?: $funcionario->Nom_JerarquiaNueva ?: 'Sin jerarquía')),
                ]);
                $personal->deleted_at = null;
                $personal->save();

                $numeroArma = trim((string) $funcionario->Nro_Arma_Func);
                $omitirSincronizacionArma = false;

                if ($numeroArma !== '') {
                    $tipo = $this->obtenerTipoArma((string) $funcionario->Nombre_TipoArma);
                    $arma = Arma::firstOrNew(['numero' => $numeroArma]);
                    $arma->arma_tipo_id = $tipo?->id;
                    if (!$arma->exists) {
                        $arma->origen = 'personal911';
                    }
                    $arma->save();
                    $armaDuplicada = $armasDuplicadas->has(Str::upper($numeroArma));

                    if ($personal->arma_importacion_bloqueada) {
                        $this->sincronizarDiscrepanciaInventario(
                            $personal,
                            InventarioDiscrepancia::TIPO_ARMA,
                            $this->descripcionArma($personal->numeracion_arma, $personal->arma_tipo_id),
                            $this->descripcionArma($numeroArma, $tipo?->id, $tipo?->nombre),
                            [
                                'arma_local' => $personal->numeracion_arma,
                                'arma_personal911' => $numeroArma,
                                'tipo_local_id' => $personal->arma_tipo_id,
                                'tipo_personal911_id' => $tipo?->id,
                            ]
                        );

                        if (!$this->armaCoincide($personal, $numeroArma, $tipo?->id) || $armaDuplicada) {
                            $omitirSincronizacionArma = true;
                        } else {
                            $this->desbloquearImportacion($personal, InventarioDiscrepancia::TIPO_ARMA);
                        }
                    }

                    if (!$omitirSincronizacionArma) {
                        if ($armaDuplicada) {
                            PersonalArmaAsignacion::query()
                                ->where('arma_id', $arma->id)
                                ->where('activa', true)
                                ->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);

                            $personal->armaAsignacionActual()->update([
                                'fecha_hasta' => now()->toDateString(),
                                'activa' => null,
                            ]);

                            if (!collect($resultado['conflictos_armas'])->contains('numero', $numeroArma)) {
                                $resultado['conflictos_armas'][] = [
                                    'numero' => $numeroArma,
                                    'funcionarios' => $armasDuplicadas[Str::upper($numeroArma)]
                                        ->map(fn ($item): string => trim((string) $item->LgjP_Func).' '.trim((string) $item->Ape_Func))
                                        ->implode(' | '),
                                ];
                            }
                        } else {
                            $this->asignarArma($personal, $arma);
                        }

                        $personal->forceFill($armaDuplicada ? [
                            'numeracion_arma' => null,
                            'arma_tipo_id' => null,
                        ] : [
                            'numeracion_arma' => $arma->numero,
                            'arma_tipo_id' => $arma->arma_tipo_id,
                        ])->save();
                    }

                    $resultado['armas']++;
                } else {
                    if ($personal->arma_importacion_bloqueada) {
                        $this->sincronizarDiscrepanciaInventario(
                            $personal,
                            InventarioDiscrepancia::TIPO_ARMA,
                            $this->descripcionArma($personal->numeracion_arma, $personal->arma_tipo_id),
                            null,
                            ['arma_local' => $personal->numeracion_arma, 'arma_personal911' => null]
                        );

                        if (!$this->valorVacio($personal->numeracion_arma)) {
                            $omitirSincronizacionArma = true;
                        } else {
                            $this->desbloquearImportacion($personal, InventarioDiscrepancia::TIPO_ARMA);
                        }
                    }

                    if (!$omitirSincronizacionArma) {
                        $personal->armaAsignacionActual()->update([
                            'fecha_hasta' => now()->toDateString(),
                            'activa' => null,
                        ]);
                        $personal->forceFill(['numeracion_arma' => null, 'arma_tipo_id' => null])->save();
                    }
                }

                $observacion = trim((string) $funcionario->Obs_Func);
                if (Str::contains(Str::lower($observacion), 'chaleco')) {
                    $datosChaleco = $chalecosPorFuncionario->get($funcionario->Id_Func);

                    if ($datosChaleco === null) {
                        $resultado['observaciones_sin_interpretar'][] = [
                            'lp' => $lp,
                            'observacion' => $observacion,
                        ];
                    } else {
                        $chaleco = Chaleco::updateOrCreate(
                            ['numero_serie' => $datosChaleco['numero_serie']],
                            array_filter(array_merge($datosChaleco, [
                                'observacion_origen' => $observacion,
                            ]), fn ($valor): bool => $valor !== null)
                        );

                        if ($chaleco->wasRecentlyCreated) {
                            $chaleco->update(['origen' => 'personal911']);
                        }
                        $chalecoDuplicado = $chalecosDuplicados->has($chaleco->numero_serie);

                        if ($personal->chaleco_importacion_bloqueada) {
                            $this->sincronizarDiscrepanciaInventario(
                                $personal,
                                InventarioDiscrepancia::TIPO_CHALECO,
                                $this->descripcionChaleco($personal->nro_chaleco),
                                $this->descripcionChaleco($chaleco->numero_serie),
                                [
                                    'chaleco_local' => $personal->nro_chaleco,
                                    'chaleco_personal911' => $chaleco->numero_serie,
                                ]
                            );

                            if (!$this->valoresIguales($personal->nro_chaleco, $chaleco->numero_serie) || $chalecoDuplicado) {
                                $resultado['chalecos']++;
                                $resultado['procesados']++;
                                continue;
                            }

                            $this->desbloquearImportacion($personal, InventarioDiscrepancia::TIPO_CHALECO);
                        }

                        if ($chalecoDuplicado) {
                            PersonalChalecoAsignacion::query()
                                ->where('chaleco_id', $chaleco->id)
                                ->where('activa', true)
                                ->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);
                            $personal->chalecoAsignacionActual()->update([
                                'fecha_hasta' => now()->toDateString(),
                                'activa' => null,
                            ]);
                            $personal->forceFill(['nro_chaleco' => null])->save();

                            if (!collect($resultado['conflictos_chalecos'])->contains('numero', $chaleco->numero_serie)) {
                                $resultado['conflictos_chalecos'][] = [
                                    'numero' => $chaleco->numero_serie,
                                    'funcionarios' => $this->nombresFuncionarios($chalecosDuplicados[$chaleco->numero_serie]),
                                ];
                            }
                        } else {
                            $this->asignarChaleco($personal, $chaleco);
                            $personal->forceFill(['nro_chaleco' => $chaleco->numero_serie])->save();
                        }
                        $resultado['chalecos']++;
                    }
                } else {
                    if ($personal->chaleco_importacion_bloqueada) {
                        $this->sincronizarDiscrepanciaInventario(
                            $personal,
                            InventarioDiscrepancia::TIPO_CHALECO,
                            $this->descripcionChaleco($personal->nro_chaleco),
                            null,
                            ['chaleco_local' => $personal->nro_chaleco, 'chaleco_personal911' => null]
                        );

                        if (!$this->valorVacio($personal->nro_chaleco)) {
                            $resultado['procesados']++;
                            continue;
                        }

                        $this->desbloquearImportacion($personal, InventarioDiscrepancia::TIPO_CHALECO);
                    }

                    $personal->chalecoAsignacionActual()->update([
                        'fecha_hasta' => now()->toDateString(),
                        'activa' => null,
                    ]);
                    $personal->forceFill(['nro_chaleco' => null])->save();
                }

                $resultado['procesados']++;
            }
        });

        return $resultado;
    }

    /**
     * @return array{numero_serie: string, marca: ?string, modelo: ?string, talle: ?string, nivel: ?string, lote: ?string}|null
     */
    public function extraerChaleco(?string $observacion): ?array
    {
        $texto = trim((string) $observacion);

        if ($texto === '' || !Str::contains(Str::lower($texto), 'chaleco')) {
            return null;
        }

        $patronesNumero = [
            '/(?:Nro\.?\s*Serie|Serie)\s*:?[\sN°ºro.]*([A-Z0-9.-]+)/iu',
            '/Chaleco(?:\s+Antibalas?|\s+Bal[ií]stico)?(?:\s+ABPC)?\s*:?\s*(?:N[°ºo.]?\s*)?([0-9][A-Z0-9.-]*)/iu',
            '/Chaleco.*?(?:nN|N)[°º]\s*([A-Z0-9.-]+)/isu',
        ];

        $numeroSerie = null;
        foreach ($patronesNumero as $patron) {
            if (preg_match($patron, $texto, $coincidencias) === 1) {
                $numeroSerie = trim($coincidencias[1], " .-\t\n\r\0\x0B");
                break;
            }
        }

        if ($numeroSerie === null || $numeroSerie === '') {
            return null;
        }

        return [
            'numero_serie' => Str::upper($numeroSerie),
            'marca' => $this->extraer($texto, '/\b(ABPC|SEATLE)\b/iu'),
            'modelo' => $this->extraer($texto, '/\b(FORCE\s*-?\s*\d+[A-Z]?)\b/iu'),
            'talle' => $this->extraer($texto, '/\bTalle\s*:?\s*([A-Z0-9]+)/iu'),
            'nivel' => $this->extraer($texto, '/\b(?:Nivel\s*)?(R[VB]3)\b/iu'),
            'lote' => $this->extraer($texto, '/\bLote\s*:?\s*([A-Z0-9-]+)/iu'),
        ];
    }

    private function obtenerTipoArma(string $nombre): ?ArmaTipo
    {
        $nombreNormalizado = $this->normalizar($nombre);

        if ($nombreNormalizado === '' || $nombreNormalizado === 'no posee') {
            return null;
        }

        $tipo = ArmaTipo::all()->first(
            fn (ArmaTipo $armaTipo): bool => $this->normalizar($armaTipo->nombre) === $nombreNormalizado
        );

        return $tipo ?? ArmaTipo::create(['nombre' => trim($nombre), 'activo' => true]);
    }

    private function asignarArma(Personal $personal, Arma $arma): void
    {
        PersonalArmaAsignacion::query()
            ->where('activa', true)
            ->where(function ($query) use ($personal, $arma) {
                $query->where('personal_id', $personal->id)
                    ->orWhere('arma_id', $arma->id);
            })
            ->where(function ($query) use ($personal, $arma) {
                $query->where('personal_id', '<>', $personal->id)
                    ->orWhere('arma_id', '<>', $arma->id);
            })
            ->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);

        PersonalArmaAsignacion::firstOrCreate([
            'personal_id' => $personal->id,
            'arma_id' => $arma->id,
            'activa' => true,
        ], [
            'fecha_desde' => now()->toDateString(),
            'origen' => 'personal911',
        ]);
    }

    private function asignarChaleco(Personal $personal, Chaleco $chaleco): void
    {
        PersonalChalecoAsignacion::query()
            ->where('activa', true)
            ->where(function ($query) use ($personal, $chaleco) {
                $query->where('personal_id', $personal->id)
                    ->orWhere('chaleco_id', $chaleco->id);
            })
            ->where(function ($query) use ($personal, $chaleco) {
                $query->where('personal_id', '<>', $personal->id)
                    ->orWhere('chaleco_id', '<>', $chaleco->id);
            })
            ->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);

        PersonalChalecoAsignacion::firstOrCreate([
            'personal_id' => $personal->id,
            'chaleco_id' => $chaleco->id,
            'activa' => true,
        ], [
            'fecha_desde' => now()->toDateString(),
            'origen' => 'personal911',
        ]);
    }

    private function sincronizarConflictos(Collection $armasDuplicadas, Collection $chalecosDuplicados): void
    {
        $conflictosActuales = [];

        foreach ([
            InventarioConflicto::TIPO_ARMA => $armasDuplicadas,
            InventarioConflicto::TIPO_CHALECO => $chalecosDuplicados,
        ] as $tipo => $grupos) {
            foreach ($grupos as $identificador => $funcionarios) {
                $identificador = (string) $identificador;
                $conflictosActuales[] = $tipo.'|'.$identificador;
                $conflicto = InventarioConflicto::firstOrNew([
                    'tipo' => $tipo,
                    'identificador' => $identificador,
                ]);

                if (!$conflicto->exists) {
                    $conflicto->detectado_en = now();
                }

                $conflicto->fill([
                    'estado' => InventarioConflicto::ESTADO_ACTIVO,
                    'detalles' => [
                        'funcionarios' => $funcionarios->map(fn ($funcionario): array => [
                            'personal911_id' => (int) $funcionario->Id_Func,
                            'lp' => trim((string) $funcionario->LgjP_Func),
                            'apellido' => trim((string) $funcionario->Ape_Func),
                            'nombre' => trim((string) $funcionario->Nom_Func),
                            'jerarquia' => trim((string) ($funcionario->Nom_Jerarquia ?: $funcionario->Nom_JerarquiaNueva ?: 'Sin jerarquía')),
                        ])->values()->all(),
                    ],
                    'ultima_deteccion_en' => now(),
                    'resuelto_en' => null,
                ]);
                $conflicto->save();
            }
        }

        InventarioConflicto::where('estado', InventarioConflicto::ESTADO_ACTIVO)
            ->get()
            ->reject(fn (InventarioConflicto $conflicto): bool => in_array(
                $conflicto->tipo.'|'.$conflicto->identificador,
                $conflictosActuales,
                true
            ))
            ->each->update([
                'estado' => InventarioConflicto::ESTADO_RESUELTO,
                'resuelto_en' => now(),
            ]);
    }

    private function nombresFuncionarios(Collection $funcionarios): string
    {
        return $funcionarios
            ->map(fn ($funcionario): string => trim((string) $funcionario->LgjP_Func).' '.trim((string) $funcionario->Ape_Func))
            ->implode(' | ');
    }

    private function sincronizarDiscrepanciaInventario(Personal $personal, string $tipo, ?string $valorLocal, ?string $valorImportado, array $detalles = []): void
    {
        if ($this->valoresIguales($valorLocal, $valorImportado)) {
            $this->resolverDiscrepanciaInventario($personal, $tipo);

            return;
        }

        $discrepancia = InventarioDiscrepancia::firstOrNew([
            'personal_id' => $personal->id,
            'tipo' => $tipo,
        ]);

        if (!$discrepancia->exists || $discrepancia->estado === InventarioDiscrepancia::ESTADO_RESUELTA) {
            $discrepancia->detectado_en = now();
        }

        $discrepancia->fill([
            'personal911_id' => $personal->personal911_id,
            'estado' => InventarioDiscrepancia::ESTADO_ACTIVA,
            'valor_local' => $valorLocal,
            'valor_importado' => $valorImportado,
            'detalles' => array_merge($detalles, [
                'funcionario' => [
                    'lp' => $personal->lp,
                    'apellido' => $personal->apellido,
                    'nombre' => $personal->nombre,
                    'jerarquia' => $personal->jerarquia,
                ],
            ]),
            'ultima_deteccion_en' => now(),
            'resuelto_en' => null,
            'corregido_por' => $personal->inventario_bloqueado_por,
            'motivo' => $personal->inventario_bloqueo_motivo,
        ]);
        $discrepancia->save();
    }

    private function resolverDiscrepanciaInventario(Personal $personal, string $tipo): void
    {
        InventarioDiscrepancia::where('personal_id', $personal->id)
            ->where('tipo', $tipo)
            ->where('estado', InventarioDiscrepancia::ESTADO_ACTIVA)
            ->update([
                'estado' => InventarioDiscrepancia::ESTADO_RESUELTA,
                'ultima_deteccion_en' => now(),
                'resuelto_en' => now(),
            ]);
    }

    private function desbloquearImportacion(Personal $personal, string $tipo): void
    {
        $campo = $tipo === InventarioDiscrepancia::TIPO_ARMA
            ? 'arma_importacion_bloqueada'
            : 'chaleco_importacion_bloqueada';

        $personal->forceFill([$campo => false])->save();

        if (!$personal->arma_importacion_bloqueada && !$personal->chaleco_importacion_bloqueada) {
            $personal->forceFill([
                'inventario_bloqueado_por' => null,
                'inventario_bloqueado_en' => null,
                'inventario_bloqueo_motivo' => null,
            ])->save();
        }
    }

    private function armaCoincide(Personal $personal, ?string $numero, ?int $tipoId): bool
    {
        $tipoLocal = $personal->arma_tipo_id === null ? null : (int) $personal->arma_tipo_id;
        $tipoImportado = $tipoId === null ? null : (int) $tipoId;

        return $this->valoresIguales($personal->numeracion_arma, $numero) && $tipoLocal === $tipoImportado;
    }

    private function descripcionArma(?string $numero, ?int $tipoId, ?string $tipoNombre = null): ?string
    {
        if ($this->valorVacio($numero)) {
            return null;
        }

        $tipoNombre ??= $tipoId !== null ? ArmaTipo::find($tipoId)?->nombre : null;

        return $tipoNombre !== null && trim($tipoNombre) !== ''
            ? trim((string) $numero).' - '.trim($tipoNombre)
            : trim((string) $numero);
    }

    private function descripcionChaleco(?string $numero): ?string
    {
        return $this->valorVacio($numero) ? null : trim((string) $numero);
    }

    private function valoresIguales(?string $primero, ?string $segundo): bool
    {
        if ($this->valorVacio($primero) && $this->valorVacio($segundo)) {
            return true;
        }

        return Str::upper(trim((string) $primero)) === Str::upper(trim((string) $segundo));
    }

    private function valorVacio(?string $valor): bool
    {
        return $valor === null || trim($valor) === '';
    }

    /**
     * @param array<int, int> $idsPersonal911
     */
    private function desactivarPersonalFueraDeServicio(array $idsPersonal911): void
    {
        Personal::query()
            ->whereNotNull('personal911_id')
            ->whereNotIn('personal911_id', $idsPersonal911)
            ->each(function (Personal $personal): void {
                $personal->armaAsignacionActual()->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);
                $personal->chalecoAsignacionActual()->update(['fecha_hasta' => now()->toDateString(), 'activa' => null]);
                $personal->forceFill([
                    'numeracion_arma' => null,
                    'arma_tipo_id' => null,
                    'nro_chaleco' => null,
                ])->save();
                $personal->delete();
            });
    }

    private function extraer(string $texto, string $patron): ?string
    {
        if (preg_match($patron, $texto, $coincidencias) !== 1) {
            return null;
        }

        return Str::upper(trim(preg_replace('/\s+/', ' ', $coincidencias[1])));
    }

    private function normalizar(string $valor): string
    {
        return Str::lower(trim(preg_replace('/\s+/', ' ', Str::ascii($valor))));
    }
}
