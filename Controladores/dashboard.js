document.addEventListener('DOMContentLoaded', () => {
    const logueado = localStorage.getItem('logueado');
    if (logueado !== 'true') {
        window.location.href = 'login.html';
        return;
    }
    
    const nombre = localStorage.getItem('nombre');
    const rol = localStorage.getItem('rol');

    const labelNombre = document.getElementById('nombreUsuario');
    if (labelNombre) {
        labelNombre.textContent = nombre ? `Bienvenido, ${nombre}` : '';
    }

    const seccionAdmin = document.getElementById('tarjetasAdministrador');
    const seccionAsociado = document.getElementById('tarjetasAsociado');

    // Comparamos en minúsculas para evitar problemas si viene como 'admin' o 'Administrador'
    if (rol && rol.toLowerCase() === 'admin') {
        if (seccionAdmin) seccionAdmin.classList.remove('d-none');
        if (seccionAsociado) seccionAsociado.classList.add('d-none');
    } else {
        if (seccionAdmin) seccionAdmin.classList.add('d-none');
        if (seccionAsociado) seccionAsociado.classList.remove('d-none');
    }
});

function cerrarSesion() {
    localStorage.clear();
    window.location.href = 'login.html'; // Ajusta la ruta de salida si es necesario
}