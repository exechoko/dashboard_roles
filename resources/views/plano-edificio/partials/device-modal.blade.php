<!-- Modal Dispositivo -->
<div class="modal fade" id="deviceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <div class="d-flex align-items-center">
                        <div id="device-icon-preview" class="device-icon-preview">
                            <i class="fas fa-cube"></i>
                        </div>
                        <div>
                            <span id="modal-title-text">Nuevo Dispositivo</span>
                            <br>
                            <small class="text-muted" id="modal-subtitle-text">Complete los datos del dispositivo</small>
                        </div>
                    </div>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="deviceForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="form-group required">
                                <label class="col-form-label">Tipo de Dispositivo</label>
                                <select name="tipo" id="device-tipo" class="form-control" required>
                                    <option value="">Seleccionar...</option>
                                    @php
                                        $tipos = \App\Models\DispositivoEdificio::getTiposDispositivos();
                                    @endphp
                                    @foreach($tipos as $tipo => $info)
                                        @php
                                            $requiresSO = in_array($tipo, \App\Models\DispositivoEdificio::TIPOS_CON_SO, true);
                                            $requiresPuertos = in_array($tipo, \App\Models\DispositivoEdificio::TIPOS_CON_PUERTOS, true);
                                        @endphp
                                        <option value="{{ $tipo }}"
                                                data-icon="{{ $info['icon'] }}"
                                                data-color="{{ $info['color'] }}"
                                                data-requires-so="{{ $requiresSO ? '1' : '0' }}"
                                                data-requires-puertos="{{ $requiresPuertos ? '1' : '0' }}">
                                            {{ $info['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group required">
                                <label class="col-form-label">Nombre</label>
                                <input type="text" name="nombre" id="device-nombre" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Dirección IP</label>
                                <input type="text" name="ip" id="device-ip" class="form-control" placeholder="192.168.1.1">
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Dirección MAC</label>
                                <input type="text" name="mac" id="device-mac" class="form-control"
                                       placeholder="00:1A:2B:3C:4D:5E">
                            </div>

                            <div class="form-group required">
                                <label class="col-form-label">Oficina</label>
                                <input type="text" name="oficina" id="device-oficina" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Piso</label>
                                <select name="piso" id="device-piso" class="form-control">
                                    <option value="">Seleccionar...</option>
                                    <option value="PB">Planta Baja</option>
                                    <option value="1">Piso 1</option>
                                    <option value="2">Piso 2</option>
                                    <option value="3">Piso 3</option>
                                    <option value="4">Piso 4</option>
                                </select>
                            </div>
                        </div>

                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">Marca</label>
                                <input type="text" name="marca" id="device-marca" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Modelo</label>
                                <input type="text" name="modelo" id="device-modelo" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="col-form-label">Número de Serie</label>
                                <input type="text" name="serie" id="device-serie" class="form-control">
                            </div>

                            <!-- Campos condicionales -->
                            <div class="form-group condicional-field" id="so-field">
                                <label class="col-form-label">Sistema Operativo</label>
                                <select name="sistema_operativo" id="device-so" class="form-control">
                                    @php
                                        $sos = \App\Models\DispositivoEdificio::getSistemasOperativos();
                                    @endphp
                                    @foreach($sos as $so => $label)
                                        <option value="{{ $so }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group condicional-field" id="puertos-field">
                                <label class="col-form-label">Cantidad de Puertos</label>
                                <input type="number" name="puertos" id="device-puertos" class="form-control"
                                       min="1" max="48">
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="device-activo" checked>
                                    <label class="custom-control-label" for="device-activo">
                                        Dispositivo Activo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Credenciales -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-key"></i> Credenciales de Acceso
                                        <small class="text-muted">(Opcional)</small>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-form-label">Usuario</label>
                                                <input type="text" name="username" id="device-username" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="col-form-label">Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" name="password" id="device-password"
                                                           class="form-control">
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                                onclick="togglePasswordVisibility()">
                                                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary"
                                                                onclick="generatePassword()">
                                                            <i class="fas fa-dice"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="col-form-label">Observaciones</label>
                                <textarea name="observaciones" id="device-observaciones" class="form-control"
                                          rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Posición (solo lectura, se establece al arrastrar) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">Posición X</label>
                                <input type="number" name="posicion_x" id="device-posicion-x"
                                       class="form-control" readonly step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">Posición Y</label>
                                <input type="number" name="posicion_y" id="device-posicion-y"
                                       class="form-control" readonly step="0.01">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <input type="hidden" name="device_id" id="device-id">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    @can('credenciales-plano-edificio')
                        <button type="button" class="btn btn-info" id="test-connection" style="display: none;">
                            <i class="fas fa-plug"></i> Probar Conexión
                        </button>
                    @endcan
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentDeviceId = null;

// Event listeners para el modal
document.addEventListener('DOMContentLoaded', function() {
    // Cambio de tipo de dispositivo
    document.getElementById('device-tipo').addEventListener('change', function() {
        updateDeviceIconPreview(this.value);
        toggleConditionalFields(this.value);
    });

    // Submit del formulario
    document.getElementById('deviceForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveDevice();
    });

    // Resetear modal al cerrar (Bootstrap 4)
    if (window.jQuery) {
        window.jQuery('#deviceModal').on('hidden.bs.modal', function() {
            resetDeviceForm();
        });
    } else {
        document.getElementById('deviceModal').addEventListener('hidden.bs.modal', function() {
            resetDeviceForm();
        });
    }
});

function updateDeviceIconPreview(tipo) {
    const select = document.getElementById('device-tipo');
    const option = select.querySelector(`option[value="${tipo}"]`);
    const preview = document.getElementById('device-icon-preview');

    if (option && option.dataset.icon && option.dataset.color) {
        preview.innerHTML = `<i class="${option.dataset.icon}"></i>`;
        preview.style.backgroundColor = option.dataset.color;
    } else {
        preview.innerHTML = '<i class="fas fa-cube"></i>';
        preview.style.backgroundColor = '#6c757d';
    }
}

function toggleConditionalFields(tipo) {
    const soField = document.getElementById('so-field');
    const puertosField = document.getElementById('puertos-field');
    const select = document.getElementById('device-tipo');
    const option = select ? select.querySelector(`option[value="${tipo}"]`) : null;

    const requiresSO = option && option.dataset.requiresSo === '1';
    const requiresPuertos = option && option.dataset.requiresPuertos === '1';

    // Sistema operativo
    if (requiresSO) {
        soField.classList.add('show');
        document.getElementById('device-so').required = true;
    } else {
        soField.classList.remove('show');
        document.getElementById('device-so').required = false;
        document.getElementById('device-so').value = '';
    }

    // Puertos
    if (requiresPuertos) {
        puertosField.classList.add('show');
        document.getElementById('device-puertos').required = true;
    } else {
        puertosField.classList.remove('show');
        document.getElementById('device-puertos').required = false;
        document.getElementById('device-puertos').value = '';
    }
}

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('device-password');
    const toggleIcon = document.getElementById('password-toggle-icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function generatePassword() {
    const length = 12;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';

    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }

    document.getElementById('device-password').value = password;
}

function resetDeviceForm() {
    document.getElementById('deviceForm').reset();
    document.getElementById('device-id').value = '';
    currentDeviceId = null;

    // Resetear campos condicionales
    document.querySelectorAll('.condicional-field').forEach(field => {
        field.classList.remove('show');
    });

    // Resetear preview
    updateDeviceIconPreview('');

    // Ocultar botón de prueba de conexión
    const testBtn = document.getElementById('test-connection');
    if (testBtn) {
        testBtn.style.display = 'none';
    }
}

function abrirModalCrear(posicionX = null, posicionY = null) {
    resetDeviceForm();

    // Establecer posición si se proporciona
    if (posicionX !== null && posicionY !== null) {
        document.getElementById('device-posicion-x').value = posicionX;
        document.getElementById('device-posicion-y').value = posicionY;
    }

    document.getElementById('modal-title-text').textContent = 'Nuevo Dispositivo';
    document.getElementById('modal-subtitle-text').textContent = 'Complete los datos del dispositivo';

    if (window.jQuery) {
        window.jQuery('#deviceModal').modal('show');
    }
}

function abrirModalEditar(deviceId) {
    currentDeviceId = deviceId;

    // Cargar datos del dispositivo
    fetch(`/api/plano-edificio/devices/${deviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const device = data.data;

                // Llenar formulario
                document.getElementById('device-id').value = device.id;
                document.getElementById('device-tipo').value = device.tipo;
                document.getElementById('device-nombre').value = device.nombre;
                document.getElementById('device-ip').value = device.ip || '';
                document.getElementById('device-mac').value = device.mac || '';
                document.getElementById('device-marca').value = device.marca || '';
                document.getElementById('device-modelo').value = device.modelo || '';
                document.getElementById('device-serie').value = device.serie || '';
                document.getElementById('device-oficina').value = device.oficina;
                document.getElementById('device-piso').value = device.piso || '';
                document.getElementById('device-so').value = device.sistema_operativo || '';
                document.getElementById('device-puertos').value = device.puertos || '';
                document.getElementById('device-observaciones').value = device.observaciones || '';
                document.getElementById('device-username').value = device.username || '';
                document.getElementById('device-password').value = device.password || '';
                document.getElementById('device-activo').checked = device.activo;
                document.getElementById('device-posicion-x').value = device.posicion_x || '';
                document.getElementById('device-posicion-y').value = device.posicion_y || '';

                // Actualizar preview y campos condicionales
                updateDeviceIconPreview(device.tipo);
                toggleConditionalFields(device.tipo);

                // Mostrar botón de prueba de conexión si tiene credenciales
                const testBtn = document.getElementById('test-connection');
                if (testBtn) {
                    testBtn.style.display = (device.username && device.password) ? 'block' : 'none';
                }

                document.getElementById('modal-title-text').textContent = 'Editar Dispositivo';
                document.getElementById('modal-subtitle-text').textContent = 'Modifique los datos del dispositivo';

                if (window.jQuery) {
                    window.jQuery('#deviceModal').modal('show');
                }
            } else {
                showToast('Error al cargar dispositivo', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error al cargar dispositivo', 'error');
        });
}
</script>
