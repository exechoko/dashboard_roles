{{-- Helpers JS compartidos de la Plataforma de Descargas (SweetAlert2, ya cargado
     globalmente en layouts.app), para reemplazar los alert()/confirm() nativos. --}}
<script>
    function descargasToast(message, icon = 'success') {
        Swal.fire({
            icon: icon,
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }

    function descargasConfirmar({ titulo, texto = '', confirmText = 'Sí, continuar', icon = 'warning' }) {
        return Swal.fire({
            title: titulo,
            text: texto,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar',
        });
    }

    function descargasErrorAjax(xhr, fallback = 'Ocurrió un error inesperado.') {
        const response = xhr && xhr.responseJSON;
        descargasToast(response?.message || fallback, 'error');
    }
</script>
