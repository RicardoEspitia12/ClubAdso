const formulario = document.getElementById("formRegistro");

formulario.addEventListener("submit", async function (event) {
    //Evita recargar la página al enviar el formulario
    event.preventDefault();

    const botonGuardar = formulario.querySelector('button[type="submit"]');

    //Lee los campos del formulario y los convierte en un objeto
    const asociado = Object.fromEntries(new FormData(formulario).entries());

    botonGuardar.disabled = true; // Deshabilita el botón para evitar múltiples envíos

    try{
        const respuesta = await fetch(formulario.action,{method:'POST',headers:{'Content-Type':'application/json'}),body:JSON.stringify(asociado)});
        //pasar la respuesta Json a un objeto de JavaScript
        const resultado = await respuesta.json();
        document.getElementById("mensajeregistro").textContent = "Guardando...";
        document.getElementById("mensajeRegistro").dataset,tipo='';



        document.getElementById("mensajeregistro").textContent = resultado.mensaje;

        if(respuesta.ok && resultado.success){
            formulario.reset();
            }


    } catch (error) {
     console.error("Error al enviar el formulario:", error); 
     document.getElementById("mensajeregistro").textContent = "Error al enviar el formulario. Por favor, inténtelo de nuevo.";
    } finally {
        botonGuardar.disabled = false; // Habilitar el botón despues de procesar la solicitud
    }
    });