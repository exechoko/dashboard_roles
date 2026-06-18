@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Editar card de tecnología</h3>
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

            <form action="{{ route('web-tecnologia.update', $card) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('web-tecnologia._form')

                <div class="text-right mb-4">
                    <a href="{{ route('web-tecnologia.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </section>
@endsection
