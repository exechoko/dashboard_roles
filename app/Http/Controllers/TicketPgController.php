<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnviarTicketPgRequest;
use App\Http\Requests\StoreTicketPgRequest;
use App\Models\Camara;
use App\Models\DispositivoEdificio;
use App\Models\Equipo;
use App\Models\FlotaGeneral;
use App\Models\Recurso;
use App\Models\TicketTicketera;
use App\Models\TipoTerminal;
use App\Services\IAService;
use App\Services\RedactorTicketPgService;
use App\Services\SecuenciaTicketeraService;
use App\Services\TicketeraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class TicketPgController extends Controller
{
    public function __construct(
        private SecuenciaTicketeraService $secuencias,
        private RedactorTicketPgService $redactor
    ) {
    }

    public function index(): View
    {
        $tickets = TicketTicketera::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('incidencias.tickets-pg.index', compact('tickets'));
    }

    public function create(): View
    {
        $codigoSugerido = $this->secuencias->previsualizarCodigo();

        return view('incidencias.tickets-pg.create', array_merge(
            ['codigoSugerido' => $codigoSugerido],
            $this->datosParaFormulario()
        ));
    }

    public function store(StoreTicketPgRequest $request, TicketeraService $ticketera): RedirectResponse
    {
        $datosTicket = $this->datosValidados($request);
        $datosTicket['codigo_interno'] = $this->secuencias->generarCodigo();
        $datosTicket = $this->completarRedaccion($datosTicket);

        $ticket = TicketTicketera::create($datosTicket);

        if ($request->input('accion') === 'enviar') {
            return $this->enviarTicket($ticket, $ticketera);
        }

        return redirect()
            ->route('incidencias.tickets-pg.show', $ticket)
            ->with('success', "Ticket {$ticket->codigo_interno} guardado como borrador.");
    }

    public function show(TicketTicketera $ticket): View
    {
        return view('incidencias.tickets-pg.show', compact('ticket'));
    }

    public function edit(TicketTicketera $ticket): View
    {
        return view('incidencias.tickets-pg.edit', array_merge(
            ['ticket' => $ticket],
            $this->datosParaFormulario()
        ));
    }

    public function update(StoreTicketPgRequest $request, TicketTicketera $ticket): RedirectResponse
    {
        if ($ticket->estaEnviado()) {
            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('error', 'El ticket ya fue enviado. No se modifica el texto enviado para mantener trazabilidad.');
        }

        $datosTicket = $this->datosValidados($request);
        $datosTicket['codigo_interno'] = $ticket->codigo_interno;
        $ticket->update($this->completarRedaccion($datosTicket));

        return redirect()
            ->route('incidencias.tickets-pg.show', $ticket)
            ->with('success', "Ticket {$ticket->codigo_interno} actualizado.");
    }

    public function enviar(EnviarTicketPgRequest $request, TicketTicketera $ticket, TicketeraService $ticketera): RedirectResponse
    {
        return $this->enviarTicket($ticket, $ticketera);
    }

    public function mejorarRedaccion(EnviarTicketPgRequest $request, TicketTicketera $ticket, IAService $iaService): RedirectResponse
    {
        if ($ticket->estaEnviado()) {
            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('error', 'El ticket ya fue enviado. No se modifica el texto enviado.');
        }

        try {
            $textoMejorado = trim($iaService->generarTexto($this->promptMejora($ticket)));

            if ($textoMejorado === '') {
                return redirect()
                    ->route('incidencias.tickets-pg.show', $ticket)
                    ->with('error', 'La IA no devolvio una redaccion util.');
            }

            $ticket->update(['texto_enviado' => $textoMejorado]);

            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('success', 'Redaccion mejorada con IA. Revisala antes de enviar.');
        } catch (Throwable $e) {
            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('error', 'No se pudo mejorar con IA: ' . $e->getMessage());
        }
    }

    private function enviarTicket(TicketTicketera $ticket, TicketeraService $ticketera): RedirectResponse
    {
        if ($ticket->estaEnviado()) {
            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('error', 'El ticket ya figura como enviado.');
        }

        try {
            $respuestaTicketera = $ticketera->crearTicket($ticket->toArray());

            $ticket->update([
                'codigo_ticketera' => $respuestaTicketera['codigo_ticketera'],
                'url_seguimiento'  => $respuestaTicketera['url_seguimiento'],
                'estado_envio'     => 'enviado',
                'estado_ticketera' => 'creado',
                'enviado_en'       => now(),
                'ultimo_error'     => null,
            ]);

            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('success', "Ticket {$ticket->codigo_interno} enviado a la ticketera.");
        } catch (Throwable $e) {
            $ticket->update([
                'estado_envio' => 'error',
                'ultimo_error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('incidencias.tickets-pg.show', $ticket)
                ->with('error', 'No se pudo enviar a la ticketera: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidados(StoreTicketPgRequest $request): array
    {
        $datosTicket = $request->validated();
        unset($datosTicket['accion']);
        $datosTicket['aplica_calculo'] = $request->boolean('aplica_calculo', true);

        $this->aplicarDatosDeCategoria($datosTicket, $request);

        return $datosTicket;
    }

    /**
     * Resuelve los datos estructurados según la categoría: cámaras seleccionadas,
     * móvil (recurso), TEI del equipo y modelo del terminal, dejando snapshots
     * legibles para el texto enviado y la exportación a Excel.
     *
     * @param array<string, mixed> $datos
     */
    private function aplicarDatosDeCategoria(array &$datos, StoreTicketPgRequest $request): void
    {
        $idsCamaras = array_filter((array) $request->input('camaras', []));
        unset($datos['camaras']);

        if ($idsCamaras !== []) {
            $camaras = Camara::query()
                ->with('tipoCamara:id,tipo')
                ->whereIn('id', $idsCamaras)
                ->get(['id', 'nombre', 'ip', 'tipo_camara_id']);
            $datos['camaras_afectadas'] = $camaras->map(fn (Camara $camara): array => [
                'id'     => $camara->id,
                'nombre' => $camara->nombre,
                'ip'     => $camara->ip,
                'tipo'   => $camara->tipoCamara?->tipo,
            ])->all();
            $datos['cantidad_items'] = $camaras->count();
        } else {
            $datos['camaras_afectadas'] = null;
            $datos['cantidad_items'] = null;
        }

        if (!empty($datos['recurso_id'])) {
            $recurso = Recurso::query()->find($datos['recurso_id']);
            if ($recurso !== null) {
                $datos['movil'] = $recurso->nombre;
            }
        }

        if (!empty($datos['equipo_id'])) {
            $equipo = Equipo::query()->find($datos['equipo_id']);
            if ($equipo !== null) {
                $datos['tei'] = $equipo->tei;
            }
        }

        if (!empty($datos['tipo_terminal_id'])) {
            $terminal = TipoTerminal::query()->find($datos['tipo_terminal_id']);
            if ($terminal !== null) {
                $datos['modelo_equipo'] = trim($terminal->marca . ' ' . $terminal->modelo);
            }
        }
    }

    /**
     * Datasets compartidos por los formularios de alta y edición.
     *
     * @return array<string, mixed>
     */
    private function datosParaFormulario(): array
    {
        return [
            'prioridades'             => ['Critico', 'Alto', 'Medio', 'Bajo'],
            'categorias'              => config('ticketera_categorias.categorias'),
            'subsistemas'             => config('ticketera_categorias.subsistemas'),
            'camposPorCategoria'      => config('ticketera_categorias.campos'),
            'subsistemaPorCategoria'  => config('ticketera_categorias.subsistema_por_categoria'),
            'camaras'                 => Camara::query()->with('tipoCamara:id,tipo')->orderBy('nombre')->get(['id', 'nombre', 'ip', 'tipo_camara_id']),
            'recursos'                => Recurso::query()->orderBy('nombre')->get(['id', 'nombre']),
            'recursosEquipos'         => $this->mapaRecursosEquipos(),
            'tipoTerminales'          => TipoTerminal::query()->orderBy('marca')->orderBy('modelo')->get(),
            'oficinas'                => DispositivoEdificio::query()
                ->whereNotNull('oficina')
                ->where('oficina', '!=', '')
                ->distinct()
                ->orderBy('oficina')
                ->pluck('oficina'),
        ];
    }

    /**
     * Mapa recurso_id => lista de equipos TETRA activos asignados, para
     * autocompletar TEI y modelo en el formulario sin volver al servidor.
     *
     * @return array<int, array<int, array{equipo_id: int, tei: string|null, tipo_terminal_id: int|null, modelo: string|null}>>
     */
    private function mapaRecursosEquipos(): array
    {
        return FlotaGeneral::query()
            ->whereNull('fecha_desasignacion')
            ->whereNotNull('equipo_id')
            ->with('equipo.tipo_terminal')
            ->get()
            ->groupBy('recurso_id')
            ->map(function ($asignaciones) {
                return $asignaciones
                    ->map(function (FlotaGeneral $asignacion): ?array {
                        $equipo = $asignacion->equipo;
                        if ($equipo === null) {
                            return null;
                        }
                        $terminal = $equipo->tipo_terminal;

                        return [
                            'equipo_id'        => $equipo->id,
                            'tei'              => $equipo->tei,
                            'tipo_terminal_id' => $terminal?->id,
                            'modelo'           => $terminal !== null ? trim($terminal->marca . ' ' . $terminal->modelo) : null,
                        ];
                    })
                    ->filter()
                    ->values();
            })
            ->toArray();
    }

    /**
     * @param array<string, mixed> $datosTicket
     * @return array<string, mixed>
     */
    private function completarRedaccion(array $datosTicket): array
    {
        if (empty($datosTicket['asunto'])) {
            $datosTicket['asunto'] = $this->redactor->asunto($datosTicket);
        }

        if (empty($datosTicket['texto_enviado'])) {
            $datosTicket['texto_enviado'] = $this->redactor->redactar($datosTicket);
        }

        return $datosTicket;
    }

    private function promptMejora(TicketTicketera $ticket): string
    {
        return <<<PROMPT
Mejora la redaccion del siguiente ticket tecnico manteniendo estilo breve, formal y operativo de Patagonia Green.
No agregues datos no informados. Conserva el codigo interno {$ticket->codigo_interno}. Devuelve solo el texto final.

Texto actual:
{$ticket->texto_enviado}
PROMPT;
    }
}
