const listaActividades = document.getElementById('lista-actividades');
async function solicitarApi(metodo,datos){
    const opciones = {
        method: metodo,
        headers: {
            'Content-Type': 'application/json'},
    }

    id(datos){
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch('../Modelo/gestionarActividades.php',opciones);
    const resultado = await respuesta.json();
    if (respuesta.status ===401) {
        window.location.href = 'login.html;
    }
        if (!respuesta.ok || !resultado.succes) {
            throw new Error(resultado.mensaje || "Error en la solicitud");
        
    }
    return resultado;
   }