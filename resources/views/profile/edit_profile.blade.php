<div id="EditProfileModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-cog mr-2"></i>Editar Perfil</h5>
                <button type="button" aria-label="Close" class="close outline-none" data-dismiss="modal">×</button>
            </div>
            <form method="POST" id="editProfileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="editProfileValidationErrorsBox"></div>
                    <input type="hidden" name="user_id" id="pfUserId">
                    {{csrf_field()}}
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Nombre: <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="pfName" class="form-control" required autofocus tabindex="1">
                        </div>
                        <div class="form-group col-sm-6 d-flex">
                            <div class="col-sm-5 pl-0 form-group">
                                <label>Imagen de perfil:</label><br>
                                <label class="image__file-upload btn btn-primary text-white btn-sm" tabindex="2">
                                    Elegir
                                    <input type="file" name="photo" id="pfImage" class="d-none">
                                </label>
                            </div>
                            <div class="col-sm-7 preview-image-video-container float-right mt-1">
                                <img id="edit_preview_photo"
                                    class="img-thumbnail user-img user-profile-img profilePicture"
                                    src="{{ asset('img/logo.png') }}" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-sm-6">
                            <label>Email: <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="pfEmail" class="form-control" required tabindex="3">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnPrEditSave"
                        data-loading-text="<span class='spinner-border spinner-border-sm'></span> Guardando..."
                        tabindex="4">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-light ml-1" data-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    $('#pfImage').on('change', function () {
        let reader = new FileReader();
        reader.onload = function (e) { $('#edit_preview_photo').attr('src', e.target.result); };
        reader.readAsDataURL(this.files[0]);
    });

    $('#EditProfileModal').on('show.bs.modal', function () {
        $('#pfUserId').val({{ auth()->id() }});
        $('#pfName').val({{ json_encode(auth()->user()->name) }});
        $('#pfEmail').val({{ json_encode(auth()->user()->email) }});
        @if(auth()->user()->photo)
            $('#edit_preview_photo').attr('src', '{{ asset(auth()->user()->photo) }}');
        @else
            $('#edit_preview_photo').attr('src', '{{ asset('img/logo.png') }}');
        @endif
    });

    $('#editProfileForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var btn = $('#btnPrEditSave');
        var originalText = btn.html();
        btn.prop('disabled', true).html(btn.data('loading-text'));
        $('#editProfileValidationErrorsBox').addClass('d-none').html('');

        $.ajax({
            url: '{{ route("profile.update") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('.profilePicture').attr('src', response.photo_url);
                    $('#EditProfileModal').modal('hide');
                    location.reload();
                }
            },
            error: function (xhr) {
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                var html = '<ul class="mb-0">';
                $.each(errors, function (k, v) { html += '<li>' + v[0] + '</li>'; });
                if (!Object.keys(errors).length) html += '<li>Error al guardar el perfil.</li>';
                html += '</ul>';
                $('#editProfileValidationErrorsBox').removeClass('d-none').html(html);
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

});
</script>
