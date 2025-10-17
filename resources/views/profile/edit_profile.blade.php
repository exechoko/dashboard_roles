<div id="EditProfileModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Perfil</h5>
                <button type="button" aria-label="Close" class="close outline-none" data-dismiss="modal">×</button>
            </div>
            <form method="POST" id="editProfileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="editProfileValidationErrorsBox"></div>
                    <input type="hidden" name="user_id" id="pfUserId">
                    <input type="hidden" name="is_active" value="1">
                    {{csrf_field()}}
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Nombre:</label><span class="required">*</span>
                            <input type="text" name="name" id="pfName" class="form-control" required autofocus
                                tabindex="1">
                        </div>
                        <div class="form-group col-sm-6 d-flex">
                            <div class="col-sm-4 col-md-6 pl-0 form-group">
                                <label>Imagen de perfil:</label>
                                <br>
                                <label class="image__file-upload btn btn-primary text-white" tabindex="2"> Elegir
                                    <input type="file" name="photo" id="pfImage" class="d-none">
                                </label>
                            </div>
                            <div class="col-sm-3 preview-image-video-container float-right mt-1">
                                <img id='edit_preview_photo'
                                    class="img-thumbnail user-img user-profile-img profilePicture"
                                    src="{{asset('img/logo.png')}}" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Email:</label><span class="required">*</span>
                            <input type="text" name="email" id="pfEmail" class="form-control" required tabindex="3">
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary" id="btnPrEditSave"
                            data-loading-text="<span class='spinner-border spinner-border-sm'></span> Procesando..."
                            tabindex="5">Guardar
                        </button>
                        <button type="button" class="btn btn-light ml-1 edit-cancel-margin margin-left-5"
                            data-dismiss="modal">Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        // Preview de la imagen antes de subir
        $('#pfImage').on('change', function () {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('#edit_preview_photo').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        });

        // Submit del formulario
        $('#editProfileForm').on('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            let submitBtn = $('#btnPrEditSave');
            let originalText = submitBtn.html();

            // Deshabilitar botón y mostrar loading
            submitBtn.prop('disabled', true);
            submitBtn.html(submitBtn.data('loading-text'));

            // Ocultar errores previos
            $('#editProfileValidationErrorsBox').addClass('d-none').html('');

            $.ajax({
                url: '{{ route("profile.update") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        // Actualizar imagen en la navbar o donde la muestres
                        $('.profilePicture').attr('src', response.photo_url);

                        // Cerrar modal
                        $('#EditProfileModal').modal('hide');

                        // Mostrar mensaje de éxito (opcional - usar SweetAlert o similar)
                        alert('Perfil actualizado correctamente');

                        // Recargar página (opcional)
                        // location.reload();
                    }
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorHtml = '<ul class="mb-0">';

                    $.each(errors, function (key, value) {
                        errorHtml += '<li>' + value[0] + '</li>';
                    });

                    errorHtml += '</ul>';

                    $('#editProfileValidationErrorsBox').removeClass('d-none').html(errorHtml);
                },
                complete: function () {
                    // Rehabilitar botón
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                }
            });
        });

        // Cargar datos del usuario al abrir el modal
        $('#EditProfileModal').on('show.bs.modal', function () {
            let userId = {{ auth()->id() }};
            $('#pfUserId').val(userId);
            $('#pfName').val('{{ auth()->user()->name }}');
            $('#pfEmail').val('{{ auth()->user()->email }}');

            @if(auth()->user()->photo)
                $('#edit_preview_photo').attr('src', '{{ asset(auth()->user()->photo) }}');
            @else
                $('#edit_preview_photo').attr('src', '{{ asset('img/logo.png') }}');
            @endif
    });
    });
</script>
