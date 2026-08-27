@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Routers / Switches</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    @include('infraestructura.partials.grid', [
                        'grupo' => 'red',
                        'titulo' => 'Routers / Switches',
                        'icono' => 'fas fa-project-diagram',
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection
