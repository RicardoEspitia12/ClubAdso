const formulario = document.getElementById("formRegistro");

formulario.addEventListener("submit", async function (event) {
    // Evitar recargar la página al enviar el formulario
    event.preventDefault();

    const botonGuardar = formulario.querySelector('button[type="submit"]');

    // Lee los campos del formulario y los convierte en un objeto
    const asociado = Object.fromEntries(new FormData(formulario).entries());

    // Validación de longitud mínima de contraseña (8 caracteres)
    if (asociado.password.length < 8) {
        document.getElementById("mensajeregistro").textContent = "La contraseña debe tener al menos 8 caracteres.";
        return;
    }

    // Validación de que las contraseñas coincidan
    if (asociado.password !== asociado.confirmarPassword) {
        document.getElementById("mensajeregistro").textContent = "Las contraseñas no coinciden.";
        return;
    }
    delete asociado.confirmarPassword; // Elimina el campo de confirmación antes de enviarlo al servidor

    botonGuardar.disabled = true; // Deshabilita el botón mientras se procesa la solicitud
    document.getElementById("mensajeregistro").textContent = "Guardando...";
    document.getElementById("mensajeregistro").dataset.tipo = "";

    try {
        const respuesta = await fetch(formulario.action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(asociado)
        });

        // Pasar la respuesta Json a un objeto de JavaScript
        const resultado = await respuesta.json();

        document.getElementById("mensajeregistro").textContent = resultado.mensaje;

        if (respuesta.ok && resultado.success) {
            formulario.reset(); // Limpiar el formulario si la solicitud fue exitosa
        }

    } catch (error) {
        console.error("Error al enviar el formulario:", error);
        document.getElementById("mensajeregistro").textContent = "Ocurrió un error al enviar el formulario. Por favor, inténtalo de nuevo.";
    } finally {
        botonGuardar.disabled = false; // Habilitar el botón después de procesar la solicitud
    }
});