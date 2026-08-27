@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; PCs Policiales</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    @include('infraestructura.partials.grid', [
                        'grupo' => 'pcs',
                        'titulo' => 'PCs Policiales',
                        'icono' => 'fas fa-desktop',
                    ])
                </div>
            </div>
        </div>
    </section>
@endsection
