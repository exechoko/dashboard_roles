<div id="changePasswordModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lock mr-2"></i>Cambiar contraseña</h5>
                <button type="button" aria-label="Close" class="close outline-none" data-dismiss="modal">×</button>
            </div>
            <form method="POST" id="changePasswordForm">
                <div class="modal-body">
                    <div class="alert alert-success d-none" id="cpSuccess"></div>
                    <div class="alert alert-danger d-none" id="cpError"></div>
                    {{csrf_field()}}
                    <div class="form-group">
                        <label>Contraseña actual: <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input class="form-control" id="cpCurrentPassword" type="password"
                                   name="current_password" required autocomplete="current-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary cpToggle" data-target="cpCurrentPassword" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nueva contraseña: <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input class="form-control" id="cpNewPassword" type="password"
                                   name="new_password" required minlength="6" autocomplete="new-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary cpToggle" data-target="cpNewPassword" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Confirmar nueva contraseña: <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input class="form-control" id="cpConfirmPassword" type="password"
                                   name="confirm_new_password" required minlength="6" autocomplete="new-password">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary cpToggle" data-target="cpConfirmPassword" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <small id="cpMatchFeedback"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" id="btnPrPasswordEditSave"
                            data-loading-text="<span class='spinner-border spinner-border-sm'></span> Guardando...">
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

    $('#changePasswordModal').on('hidden.bs.modal', function () {
        $('#changePasswordForm')[0].reset();
        $('#cpSuccess, #cpError').addClass('d-none').html('');
        $('#cpMatchFeedback').html('');
        $('.cpToggle').each(function () {
            var input = document.getElementById($(this).data('target'));
            input.type = 'password';
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        });
    });

    $('.cpToggle').on('click', function () {
        var input = document.getElementById($(this).data('target'));
        var icon = $(this).find('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    function checkPasswordsMatch() {
        var newPwd = $('#cpNewPassword').val();
        var confirmPwd = $('#cpConfirmPassword').val();
        var feedback = $('#cpMatchFeedback');

        if (confirmPwd === '') {
            feedback.html('');
            return;
        }
        if (newPwd === confirmPwd) {
            feedback.html('<i class="fas fa-check-circle text-success"></i> <span class="text-success">Las contraseñas coinciden</span>');
        } else {
            feedback.html('<i class="fas fa-times-circle text-danger"></i> <span class="text-danger">Las contraseñas no coinciden</span>');
        }
    }
    $('#cpNewPassword, #cpConfirmPassword').on('input', checkPasswordsMatch);

    $('#changePasswordForm').on('submit', function (e) {
        e.preventDefault();

        var btn = $('#btnPrPasswordEditSave');
        var originalText = btn.html();
        $('#cpSuccess, #cpError').addClass('d-none').html('');

        if ($('#cpNewPassword').val() !== $('#cpConfirmPassword').val()) {
            $('#cpError').removeClass('d-none').text('Las contraseñas nuevas no coinciden.');
            return;
        }

        btn.prop('disabled', true).html(btn.data('loading-text'));

        $.ajax({
            url: '{{ route("profile.updatePassword") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.success) {
                    $('#cpSuccess').removeClass('d-none').html(
                        '<i class="fas fa-check-circle mr-1"></i> ' + response.message
                    );
                    $('#changePasswordForm')[0].reset();
                    setTimeout(function () { $('#changePasswordModal').modal('hide'); }, 1200);
                }
            },
            error: function (xhr) {
                var msg = 'Error al actualizar la contraseña.';
                try {
                    var data = xhr.responseJSON;
                    if (data && data.errors) {
                        var first = Object.values(data.errors)[0];
                        msg = Array.isArray(first) ? first[0] : first;
                    } else if (data && data.message) {
                        msg = data.message;
                    }
                } catch (e) {}
                $('#cpError').removeClass('d-none').text(msg);
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

});
</script>
