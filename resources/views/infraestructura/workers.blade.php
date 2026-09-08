@extends('layouts.app')

@section('css')
@include('infraestructura._workers_status_styles')
@stop

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Infraestructura &mdash; Workers y Bases de Datos</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    @include('infraestructura._workers_status_card')
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@include('infraestructura._workers_status_scripts')
@endpush
