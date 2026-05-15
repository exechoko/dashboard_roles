<div id="MasterPasswordModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-lock mr-2"></i> Contraseña Maestra del Gestor
                </h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <i class="fas fa-info-circle mr-1"></i>
                    La contraseña maestra protege el acceso al <strong>Gestor de Contraseñas</strong>.
                    Se pedirá cada vez que intente ingresar al gestor.
                </div>

                <div class="alert alert-success d-none" id="mpSetupSuccess"></div>
                <div class="alert alert-danger d-none" id="mpSetupError"></div>

                <div class="form-group">
                    <label>
                        Nueva contraseña maestra
                        @if(auth()->user()->master_password)
                            <span class="badge badge-success ml-1"><i class="fas fa-check-circle"></i> Configurada</span>
                        @else
                            <span class="badge badge-secondary ml-1"><i class="fas fa-times-circle"></i> No configurada</span>
                        @endif
                    </label>
                    <div class="input-group">
                        <input type="password" id="mpNewPassword" class="form-control"
                            placeholder="{{ auth()->user()->master_password ? 'Nueva contraseña (opcional)' : 'Ingrese una contraseña maestra' }}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="mpToggle1" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña maestra</label>
                    <div class="input-group">
                        <input type="password" id="mpConfirmPassword" class="form-control" placeholder="Repita la contraseña maestra">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="mpToggle2" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->master_password)
                <div class="form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="mpClearCheck">
                        <label class="custom-control-label text-danger" for="mpClearCheck">
                            <i class="fas fa-trash-alt mr-1"></i> Eliminar la contraseña maestra
                        </label>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="mpSaveBtn">
                    <i class="fas fa-lock mr-1"></i> Guardar
                </button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {

    // Toggle visibilidad
    $('#mpToggle1').on('click', function () {
        var input = document.getElementById('mpNewPassword');
        var icon = $(this).find('i');
        if (input.type === 'password') { input.type = 'text'; icon.removeClass('fa-eye').addClass('fa-eye-slash'); }
        else { input.type = 'password'; icon.removeClass('fa-eye-slash').addClass('fa-eye'); }
    });
    $('#mpToggle2').on('click', function () {
        var input = document.getElementById('mpConfirmPassword');
        var icon = $(this).find('i');
        if (input.type === 'password') { input.type = 'text'; icon.removeClass('fa-eye').addClass('fa-eye-slash'); }
        else { input.type = 'password'; icon.removeClass('fa-eye-slash').addClass('fa-eye'); }
    });

    // Checkbox eliminar: deshabilita los campos
    $(document).on('change', '#mpClearCheck', function () {
        var checked = $(this).is(':checked');
        $('#mpNewPassword, #mpConfirmPassword').prop('disabled', checked).val('');
    });

    // Limpiar al cerrar
    $('#MasterPasswordModal').on('hidden.bs.modal', function () {
        $('#mpNewPassword, #mpConfirmPassword').prop('disabled', false).val('');
        if ($('#mpClearCheck').length) $('#mpClearCheck').prop('checked', false);
        $('#mpSetupSuccess, #mpSetupError').addClass('d-none').html('');
    });

    // Guardar
    $('#mpSaveBtn').on('click', function () {
        var mp     = $('#mpNewPassword').val();
        var cmp    = $('#mpConfirmPassword').val();
        var clear  = $('#mpClearCheck').is(':checked');
        var btn    = $(this);

        $('#mpSetupSuccess, #mpSetupError').addClass('d-none').html('');

        if (!clear) {
            if (mp.trim() === '') {
                $('#mpSetupError').removeClass('d-none').text('Ingrese una contraseña maestra o marque la opción de eliminar.');
                return;
            }
            if (mp.length < 4) {
                $('#mpSetupError').removeClass('d-none').text('La contraseña maestra debe tener al menos 4 caracteres.');
                return;
            }
            if (mp !== cmp) {
                $('#mpSetupError').removeClass('d-none').text('Las contraseñas no coinciden.');
                return;
            }
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...');

        $.ajax({
            url: '{{ route("profile.updateMasterPassword") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                master_password:         clear ? '' : mp,
                confirm_master_password: clear ? '' : cmp,
                clear_master_password:   clear ? 1 : 0
            },
            success: function (response) {
                if (response.success) {
                    $('#mpSetupSuccess').removeClass('d-none').html(
                        '<i class="fas fa-check-circle mr-1"></i> ' + response.message
                    );
                    setTimeout(function () { location.reload(); }, 1200);
                }
            },
            error: function (xhr) {
                var msg = 'Error al guardar la contraseña maestra.';
                try {
                    var data = xhr.responseJSON;
                    if (data && data.errors) {
                        var first = Object.values(data.errors)[0];
                        msg = Array.isArray(first) ? first[0] : first;
                    } else if (data && data.message) {
                        msg = data.message;
                    }
                } catch (e) {}
                $('#mpSetupError').removeClass('d-none').text(msg);
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-lock mr-1"></i> Guardar');
            }
        });
    });
});
</script>
