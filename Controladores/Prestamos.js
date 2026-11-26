const listaPrestamos = document.getElementById('lista-prestamos');
const formPrestamo = document.getElementById('formPrestamo');
let prestamos = [];
let modalPrestamoInstancia = null;

// 1. Petición al servidor (idéntica a Actividades)
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

    const respuesta = await fetch('../Modelo/gestionarPrestamos.php', opciones);
    const resultado = await respuesta.json();

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la solicitud");
    }

    return resultado;
}

// 2. Cargar lista de préstamos
async function cargarPrestamos() {
    try {
        const resultado = await solicitarApi("GET");
        prestamos = resultado.prestamos;
        mostrarPrestamos();
    } catch (error) {
        alert("Error al cargar los préstamos: " + error.message);
    }
}

// 3. Pintar la tabla
function mostrarPrestamos() {
    listaPrestamos.replaceChildren();

    const contador = document.getElementById("contadorPrestamos");
    if (contador) {
        contador.textContent = `${prestamos.length} préstamo(s)`;
    }

    if (prestamos.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 8; // CAMBIO OBLIGATORIO: 7 -> 8 (nueva columna Estado)
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay préstamos registrados aún.";
        fila.append(celda);
        listaPrestamos.append(fila);
        return;
    }

    prestamos.forEach((prestamo) => {
        const fila = document.createElement("tr");

        const valorFormato = Number(prestamo.valorPrestamo).toLocaleString("es-CO", {
            style: "currency",
            currency: "COP",
            maximumFractionDigits: 0
        });

        // AGREGADO: celda de Estado con color
        const celdaEstado = document.createElement("td");
        celdaEstado.className = "text-center";
        const badgeEstado = document.createElement("span");
        const coloresEstado = { Pendiente: "bg-warning", Aprobado: "bg-success", Rechazado: "bg-danger" };
        badgeEstado.className = "badge " + (coloresEstado[prestamo.estado] || "bg-secondary");
        badgeEstado.textContent = prestamo.estado;
        celdaEstado.append(badgeEstado);
        // FIN AGREGADO

        const celdaAcciones = document.createElement("td");
        celdaAcciones.className = "text-nowrap text-center";

        // AGREGADO: Aprobar / Rechazar solo si está Pendiente
        if (prestamo.estado === "Pendiente") {
            const botonAprobar = document.createElement("button");
            botonAprobar.type = "button";
            botonAprobar.className = "btn btn-sm btn-success me-2";
            botonAprobar.innerHTML = '<i class="bi bi-check-lg"></i> Aprobar';
            botonAprobar.addEventListener("click", () => cambiarEstado(prestamo, "Aprobado"));

            const botonRechazar = document.createElement("button");
            botonRechazar.type = "button";
            botonRechazar.className = "btn btn-sm btn-outline-danger me-2";
            botonRechazar.innerHTML = '<i class="bi bi-x-lg"></i> Rechazar';
            botonRechazar.addEventListener("click", () => cambiarEstado(prestamo, "Rechazado"));

            celdaAcciones.append(botonAprobar, botonRechazar);
        }
        // FIN AGREGADO

        const botonEditar = document.createElement("button");
        botonEditar.type = "button";
        botonEditar.className = "btn btn-sm btn-outline-primary me-2";
        botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i> Editar';
        botonEditar.addEventListener("click", () => abrirEdicion(prestamo));

        const botonEliminar = document.createElement("button");
        botonEliminar.type = "button";
        botonEliminar.className = "btn btn-sm btn-outline-danger";
        botonEliminar.innerHTML = '<i class="bi bi-trash-square"></i> Eliminar';
        botonEliminar.addEventListener("click", () => eliminarPrestamo(prestamo));

        celdaAcciones.append(botonEditar, botonEliminar);

        fila.append(
            crearCelda(`#${prestamo.idPrestamo}`),
            crearCelda(`${prestamo.idAsociado} - ${prestamo.nombreAsociado || ''}`),
            crearCelda(valorFormato),
            crearCelda(`${prestamo.tasaInteres}%`),
            crearCelda(`${prestamo.numeroCuotas} cuotas`),
            crearCelda(prestamo.fechaPrestamo),
            celdaEstado, // AGREGADO
            celdaAcciones
        );

        listaPrestamos.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

// 4. Modal de Bootstrap
function obtenerModalPrestamo() {
    const modalEl = document.getElementById("modalPrestamo");
    if (!modalEl || typeof bootstrap === "undefined") return null;
    if (!modalPrestamoInstancia) {
        modalPrestamoInstancia = new bootstrap.Modal(modalEl);
    }
    return modalPrestamoInstancia;
}

// 5. Abrir para nuevo préstamo
function abrirNuevoPrestamo() {
    formPrestamo.reset();
    document.getElementById("idPrestamo").value = "";
    document.getElementById("codigo").value = "";
    document.getElementById("tituloModalPrestamo").textContent = "Registrar nuevo préstamo";
    obtenerModalPrestamo()?.show();
}

// 6. Abrir para editar préstamo
function abrirEdicion(prestamo) {
    formPrestamo.reset();
    document.getElementById("idPrestamo").value = prestamo.idPrestamo;
    document.getElementById("codigo").value = `#${prestamo.idPrestamo}`;
    document.getElementById("documento").value = prestamo.idAsociado;
    document.getElementById("valor").value = prestamo.valorPrestamo;
    document.getElementById("fechaPrestamo").value = prestamo.fechaPrestamo;
    document.getElementById("tasa").value = prestamo.tasaInteres;
    document.getElementById("cuotas").value = prestamo.numeroCuotas;

    document.getElementById("tituloModalPrestamo").textContent = `Editar préstamo #${prestamo.idPrestamo}`;
    obtenerModalPrestamo()?.show();
}

// 7. Guardar (Crear o Editar)
formPrestamo.addEventListener("submit", async (evento) => {
    evento.preventDefault();

    const idPrestamoVal = document.getElementById("idPrestamo").value;
    const datos = {
        documento: Number(document.getElementById("documento").value),
        valor: Number(document.getElementById("valor").value),
        fechaPrestamo: document.getElementById("fechaPrestamo").value,
        tasa: Number(document.getElementById("tasa").value),
        cuotas: Number(document.getElementById("cuotas").value)
    };

    if (datos.valor > 80000000) {
        alert("El valor máximo del préstamo es de 80 millones de pesos.");
        return;
    }

    // AGREGADO: validaciones nuevas (el servidor las repite)
    if (datos.valor < 100000) {
        alert("El valor mínimo del préstamo es de $100.000.");
        return;
    }
    if (datos.tasa < 0.5 || datos.tasa > 3) {
        alert("La tasa de interés debe estar entre 0,5 % y 3 % mensual.");
        return;
    }
    if (!Number.isInteger(datos.cuotas) || datos.cuotas < 1 || datos.cuotas > 60) {
        alert("El número de cuotas debe ser un entero entre 1 y 60.");
        return;
    }
    if (datos.fechaPrestamo > new Date().toISOString().split("T")[0]) {
        alert("La fecha del préstamo no puede ser futura.");
        return;
    }
    // FIN AGREGADO

    try {
        if (idPrestamoVal) {
            datos.codigo = Number(idPrestamoVal);
            await solicitarApi("PUT", datos);
        } else {
            await solicitarApi("POST", datos);
        }

        obtenerModalPrestamo()?.hide();
        await cargarPrestamos();
        alert(idPrestamoVal ? "Préstamo actualizado correctamente" : "Préstamo registrado correctamente");
    } catch (error) {
        alert(error.message);
    }
});

// 8. Eliminar
async function eliminarPrestamo(prestamo) {
    const confirmar = window.confirm(`¿Deseas eliminar el préstamo #${prestamo.idPrestamo} del asociado ${prestamo.idAsociado}?`);
    if (!confirmar) return;

    try {
        await solicitarApi("DELETE", { idPrestamo: prestamo.idPrestamo });
        await cargarPrestamos();
        alert("Préstamo eliminado correctamente");
    } catch (error) {
        alert(error.message);
    }
}

// AGREGADO: aprobar o rechazar una solicitud pendiente
async function cambiarEstado(prestamo, nuevoEstado) {
    const accion = nuevoEstado === "Aprobado" ? "aprobar" : "rechazar";
    const confirmar = window.confirm(`¿Deseas ${accion} el préstamo #${prestamo.idPrestamo} del asociado ${prestamo.idAsociado}? Tasa actual: ${prestamo.tasaInteres}% (si no es la correcta, cancela y usa Editar primero).`);
    if (!confirmar) return;

    try {
        await solicitarApi("PATCH", { idPrestamo: prestamo.idPrestamo, estado: nuevoEstado });
        await cargarPrestamos();
        alert(`Préstamo ${nuevoEstado.toLowerCase()} correctamente`);
    } catch (error) {
        alert(error.message);
    }
}
// FIN AGREGADO

// Escuchar clic en botón Registrar Préstamo
document.getElementById("btnNuevoPrestamo")?.addEventListener("click", abrirNuevoPrestamo);

// Cargar la lista al entrar
cargarPrestamos();