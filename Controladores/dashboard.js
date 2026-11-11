document.addEventListener('DOMContentLoaded', () => {
    const logueado = localStorage.getItem('logueado');
    if (logueado !== 'true') {
        window.location.href = '../Vista/login.html';
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

async function cerrarSesion() {
    try{
const respuesta= await fetch("../Modelo/CerrarSesion.php",{method:'POST'});
if(!respuesta.ok){
    throw new Error("Error al cerrar sesión");
}
    
    
    localStorage.clear();
    window.location.href = '../Vista/login.html';
    }catch(error){
    }
}