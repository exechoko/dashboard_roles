@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar Mapeo CECOCO-CAR911</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('cecoco.recursos-alias.update', $alias) }}" method="POST">
                                @csrf
                                @method('PUT')
                                @include('cecoco.recursos-alias._form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
