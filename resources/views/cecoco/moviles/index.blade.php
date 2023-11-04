@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Moviles</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">


                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Recurso</th>
                                        <th style="color:#fff;">Latitud</th>
                                        <th style="color:#fff;">Longitud</th>
                                        <th style="color:#fff;">Velocidad</th>
                                        <th style="color: #fff;">Fecha</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($results as $movil)
                                            <tr>
                                                <td style="display: none;">{{ $movil->id }}</td>
                                                <td>{{ $movil->recurso }}</td>
                                                <td>{{ $movil->latitud }}</td>
                                                <td>{{ $movil->longitud }}</td>
                                                <td>{{ $movil->velocidad }}</td>
                                                <td>{{ $movil->fecha }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
