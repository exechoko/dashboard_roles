@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Nueva tarjeta de historia</h3>
        </div>

        <div class="section-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>¡Revise los campos!</strong>
                    @foreach ($errors->all() as $error)
                        <span class="badge badge-light">{{ $error }}</span>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <form action="{{ route('web-historia.store') }}" method="POST">
                @csrf
                @include('web-historia._form', ['card' => null])

                <div class="text-right mb-4">
                    <a href="{{ route('web-historia.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar y publicar</button>
                </div>
            </form>
        </div>
    </section>
@endsection
