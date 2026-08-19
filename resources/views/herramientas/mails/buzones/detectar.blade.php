@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Detectar Oficinas</h3>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">
                        Carpetas encontradas en <code>{{ $raiz }}</code>. Tildá las que todavía no son
                        buzón para darlas de alta; después asignale un rol a cada una desde "Editar".
                    </p>

                    @if (empty($oficinas))
                        <div class="alert alert-warning">
                            No se encontró ninguna subcarpeta en <code>{{ $raiz }}</code>. Verificá que la
                            unidad esté disponible y que <code>MBOX_PATH</code> apunte al lugar correcto.
                        </div>
                    @else
                        <form method="POST" action="{{ route('herramientas.mails.buzones.registrar-oficinas') }}">
                            @csrf
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:40px"></th>
                                        <th>Carpeta</th>
                                        <th>Nombre sugerido</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($oficinas as $oficina)
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="carpetas[]" value="{{ $oficina['carpeta'] }}"
                                                       {{ $oficina['ya_registrado'] ? 'disabled' : '' }}>
                                            </td>
                                            <td><code>{{ $oficina['carpeta'] }}</code></td>
                                            <td>{{ $oficina['nombre_sugerido'] }}</td>
                                            <td>
                                                @if ($oficina['ya_registrado'])
                                                    <span class="badge badge-success">Ya es buzón</span>
                                                @else
                                                    <span class="badge badge-light">Sin registrar</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Dar de alta las tildadas
                            </button>
                            <a href="{{ route('herramientas.mails.buzones.index') }}" class="btn btn-secondary">Cancelar</a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
