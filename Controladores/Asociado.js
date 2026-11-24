const listaAsociados = document.getElementById('lista-asociados');
let asociados = [];
let modalAsociadoInstancia = null;
let esModoEdicion = false;

const formAsociado = document.getElementById("formAsociado");

// Petición al servidor (idéntica a solicitarApi de Actividades)
async function solicitarApi(metodo, datos) {
    const opciones = {
        method: metodo,
        headers: {
            'Content-Type': 'application/json'
        },
    };

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch('../Modelo/gestionarAsociados.php', opciones);
    const resultado = await respuesta.json();

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la solicitud");
    }

    return resultado;
}

// Cargar asociados desde la base de datos
async function cargarAsociados() {
    try {
        const resultado = await solicitarApi("GET");
        asociados = resultado.asociados;
        mostrarAsociados();
    } catch (error) {
        alert("Error al cargar los asociados: " + error.message);
    }
}

// Pintar la tabla
function mostrarAsociados() {
    listaAsociados.replaceChildren();

    const contador = document.getElementById("contadorAsociados");
    if (contador) {
        contador.textContent = `${asociados.length} asociado(s)`;
    }

    if (asociados.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 7;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay asociados disponibles.";
        fila.append(celda);
        listaAsociados.append(fila);
        return;
    }

    asociados.forEach((asociado) => {
        const fila = document.createElement("tr");

        // Celda de Rol con color
        const celdaRol = document.createElement("td");
        const badge = document.createElement("span");
        badge.className = asociado.rol === 'admin' ? "badge bg-danger" : "badge bg-info text-dark";
        badge.textContent = asociado.rol === 'admin' ? "Administrador" : "Asociado";
        celdaRol.append(badge);

        // Botones de acción
        const celdaAcciones = document.createElement("td");
        celdaAcciones.className = "text-nowrap text-center";

        const botonEditar = document.createElement("button");
        botonEditar.type = "button";
        botonEditar.className = "btn btn-sm btn-outline-primary me-2";
        botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i> Editar';
        botonEditar.addEventListener("click", () => abrirEdicion(asociado));

        const botonEliminar = document.createElement("button");
        botonEliminar.type = "button";
        botonEliminar.className = "btn btn-sm btn-outline-danger";
        botonEliminar.innerHTML = '<i class="bi bi-trash-square"></i> Eliminar';
        botonEliminar.addEventListener("click", () => eliminarAsociado(asociado));

        celdaAcciones.append(botonEditar, botonEliminar);

        fila.append(
            crearCelda(asociado.documento),
            crearCelda(`${asociado.nombre} ${asociado.apellidos}`),
            crearCelda(asociado.telefono),
            crearCelda(asociado.email),
            crearCelda(asociado.ciudad),
            celdaRol,
            celdaAcciones
        );

        listaAsociados.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

function obtenerModalAsociado() {
    const modalEl = document.getElementById("modalAsociado");
    if (!modalEl || typeof bootstrap === "undefined") return null;
    if (!modalAsociadoInstancia) {
        modalAsociadoInstancia = new bootstrap.Modal(modalEl);
    }
    return modalAsociadoInstancia;
}

// Abrir modal para crear
function abrirNuevoAsociado() {
    esModoEdicion = false;
    formAsociado.reset();
    document.getElementById("documento").disabled = false;
    document.getElementById("grupoPassword").classList.remove("d-none");
    document.getElementById("tituloModalAsociado").textContent = "Nuevo asociado";
    obtenerModalAsociado()?.show();
}

// Abrir modal para editar
function abrirEdicion(asociado) {
    esModoEdicion = true;
    formAsociado.reset();

    document.getElementById("documento").value = asociado.documento;
    document.getElementById("documento").disabled = true; // No se edita la llave primaria
    document.getElementById("fechaNacimiento").value = asociado.fechaNacimiento;
    document.getElementById("nombre").value = asociado.nombre;
    document.getElementById("apellidos").value = asociado.apellidos;
    document.getElementById("direccionResidencia").value = asociado.direccionResidencia;
    document.getElementById("ciudad").value = asociado.ciudad;
    document.getElementById("telefono").value = asociado.telefono;
    document.getElementById("email").value = asociado.email;
    document.getElementById("rol").value = asociado.rol;

    // Seleccionar el género
    const radioGenero = document.querySelector(`input[name="genero"][value="${asociado.genero}"]`);
    if (radioGenero) radioGenero.checked = true;

    // Ocultar campo de contraseña al editar para no obligar a cambiarla
    document.getElementById("grupoPassword").classList.add("d-none");

    document.getElementById("tituloModalAsociado").textContent = "Editar asociado y rol";
    obtenerModalAsociado()?.show();
}

// Guardar (Crear o Editar)
async function guardarAsociado(event) {
    event.preventDefault();

    const generoSeleccionado = document.querySelector('input[name="genero"]:checked')?.value;

    const datos = {
        documento: Number(document.getElementById("documento").value),
        nombre: document.getElementById("nombre").value.trim(),
        apellidos: document.getElementById("apellidos").value.trim(),
        fechaNacimiento: document.getElementById("fechaNacimiento").value,
        genero: generoSeleccionado,
        direccionResidencia: document.getElementById("direccionResidencia").value.trim(),
        ciudad: document.getElementById("ciudad").value,
        telefono: document.getElementById("telefono").value.trim(),
        email: document.getElementById("email").value.trim(),
        rol: document.getElementById("rol").value
    };

    try {
        if (esModoEdicion) {
            await solicitarApi("PUT", datos);
        } else {
            datos.password = document.getElementById("password").value;
            if (datos.password.length > 0 && datos.password.length < 8) {
                alert("La contraseña debe tener al menos 8 caracteres.");
                return;
            }
            await solicitarApi("POST", datos);
        }

        obtenerModalAsociado()?.hide();
        await cargarAsociados();
        alert(esModoEdicion ? "Asociado actualizado correctamente" : "Asociado registrado correctamente");
    } catch (error) {
        alert(error.message);
    }
}

// Eliminar
async function eliminarAsociado(asociado) {
    const confirmar = window.confirm(`¿Deseas eliminar al asociado "${asociado.nombre} ${asociado.apellidos}"?`);

    if (!confirmar) return;

    try {
        await solicitarApi("DELETE", { documento: asociado.documento });
        await cargarAsociados();
        alert("Asociado eliminado correctamente");
    } catch (error) {
        alert(error.message);
    }
}

// Configurar botón nuevo y envío del formulario
document.getElementById("nuevoAsociado")?.addEventListener("click", abrirNuevoAsociado);

if (formAsociado) {
    formAsociado.addEventListener("submit", guardarAsociado);
}

// Iniciar cargando la tabla
cargarAsociados();