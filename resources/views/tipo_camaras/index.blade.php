@extends('layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h3 class="page__heading">Cámaras - Tipos de Cámaras</h3>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="">
                                @can('crear-tipo-camara')
                                    <a class="btn btn-success" href="{{ route('tipo-camara.create') }}">Nuevo</a>
                                @endcan
                                <label class="alert alert-dark mb-0" style="float: right;">Registros:
                                    {{ $tipoCamaras->total() }}</label>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped mt-2">
                                    <thead style="background: linear-gradient(45deg,#6777ef, #35199a)">
                                        <th style="display: none;">ID</th>
                                        <th style="color:#fff;">Tipo</th>
                                        <th style="color:#fff;">Marca</th>
                                        <th style="color:#fff;">Modelo</th>
                                        <th style="color: #fff;">Imagen</th>
                                        <th style="color:#fff;">Acciones</th>
                                    </thead>
                                    <tbody>
                                        @foreach ($tipoCamaras as $tipoCam)
                                            <tr>
                                                <td style="display: none;">{{ $tipoCam->id }}</td>
                                                <td>{{ $tipoCam->tipo }}</td>
                                                <td>{{ $tipoCam->marca }}</td>
                                                <td>{{ $tipoCam->modelo }}</td>
                                                <td><img alt="" width="70px" id="myImg"
                                                        src="{{ asset($tipoCam->imagen) }}"
                                                        class="img-fluid img-thumbnail"></td>
                                                <td>
                                                    <form action="{{ route('tipo-camara.destroy', $tipoCam->id) }}"
                                                        method="POST">
                                                        @can('editar-tipo-camara')
                                                            <a class="btn btn-info"
                                                                href="{{ route('tipo-camara.edit', $tipoCam->id) }}">Editar</a>
                                                        @endcan

                                                        @csrf
                                                        @method('DELETE')
                                                        @can('borrar-tipo-camara')
                                                            <button type="submit" onclick="return confirm('Está seguro que desea borrar el tipo de cámara')"
                                                                class="btn btn-danger">Borrar</button>
                                                        @endcan
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Ubicamos la paginacion a la derecha -->
                            <div class="pagination justify-content-end">
                                {!! $tipoCamaras->links() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- The Modal >
    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="img01">
        <div id="caption"></div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById('myModal');

        // Get the image and insert it inside the modal - use its "alt" text as a caption
        var img = document.getElementById('myImg');
        var modalImg = document.getElementById("img01");
        var captionText = document.getElementById("caption");
        img.onclick = function() {
            modal.style.display = "block";
            modalImg.src = this.src;
            captionText.innerHTML = this.alt;
        }

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }
    </script-->
@endsection
