const listaReportes = document.getElementById('lista-reportes');
let reportes = [];

async function solicitarApi(urlConParams) {
    const respuesta = await fetch(urlConParams, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });

    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = 'Login.html';
        return;
    }

    if (!respuesta.ok || (!resultado.success && !resultado.succes)) {
        throw new Error(resultado.mensaje || resultado.message || "Error en la solicitud");
    }

    return resultado;
}

// 1. Cargar las estadísticas de las tarjetas
async function cargarResumen() {
    try {
        const resultado = await solicitarApi('../Modelo/gestionarReportes.php?tipo=resumen');
        const r = resultado.resumen;

        document.getElementById('kpiPrestado').textContent = Number(r.totalPrestado).toLocaleString("es-CO", { style: "currency", currency: "COP" });
        document.getElementById('kpiRecaudado').textContent = Number(r.totalRecaudado).toLocaleString("es-CO", { style: "currency", currency: "COP" });
        document.getElementById('kpiPendiente').textContent = Number(r.saldoPendiente).toLocaleString("es-CO", { style: "currency", currency: "COP" });
        document.getElementById('kpiAsociados').textContent = r.totalAsociados;
    } catch (error) {
        console.error("Error al cargar resumen:", error.message);
    }
}

// 2. Cargar listado con validación de fechas
async function cargarReportes() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const documento = document.getElementById('documento').value.trim();
    const hoy = new Date().toISOString().split('T')[0];
    const fechaMinima = '2000-01-01';

    if (fechaInicio && fechaInicio > hoy) {
        alert("La fecha inicial no puede ser una fecha futura.");
        return;
    }
    if (fechaFin && fechaFin > hoy) {
        alert("La fecha final no puede ser una fecha futura.");
        return;
    }
    if (fechaInicio && fechaInicio < fechaMinima) {
        alert("La fecha inicial no puede ser anterior al año 2000.");
        return;
    }
    if (fechaFin && fechaFin < fechaMinima) {
        alert("La fecha final no puede ser anterior al año 2000.");
        return;
    }
    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
        alert("La fecha inicial no puede ser mayor que la fecha final.");
        return;
    }

    try {
        const params = new URLSearchParams({
            tipo: 'general',
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            documento: documento
        });

        const resultado = await solicitarApi(`../Modelo/gestionarReportes.php?${params.toString()}`);
        reportes = resultado.reportes;
        mostrarReportes();
    } catch (error) {
        alert("Error al cargar reportes: " + error.message);
    }
}

// 3. Pintar en la tabla con consecutivo 1, 2, 3...
function mostrarReportes() {
    listaReportes.replaceChildren();

    const contador = document.getElementById("contadorReportes");
    if (contador) {
        contador.textContent = `${reportes.length} registro(s)`;
    }

    if (reportes.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 8;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No se encontraron reportes con los criterios de búsqueda.";
        fila.append(celda);
        listaReportes.append(fila);
        return;
    }

    reportes.forEach((rep, index) => {
        const fila = document.createElement("tr");

        const prestado = Number(rep.valorPrestamo);
        const recaudado = Number(rep.totalRecaudado);
        const pendiente = Number(rep.saldoRestante);
        const estaAlDia = pendiente <= 0;

        // Celda del estado con badge
        const celdaEstado = document.createElement("td");
        celdaEstado.className = "text-center";
        const badge = document.createElement("span");
        badge.className = estaAlDia ? "badge bg-success" : "badge bg-warning text-dark";
        badge.textContent = estaAlDia ? "Paz y Salvo" : "Con Saldo";
        celdaEstado.append(badge);

        // Celda de N° consecutivo (1, 2, 3...)
        const celdaNumero = crearCelda(index + 1);
        celdaNumero.className = "text-center fw-bold text-muted";

        fila.append(
            celdaNumero,
            crearCelda(rep.fechaPrestamo),
            crearCelda(rep.documento),
            crearCelda(rep.nombreAsociado),
            crearCelda(prestado.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(recaudado.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(pendiente.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            celdaEstado
        );

        listaReportes.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

document.addEventListener('DOMContentLoaded', () => {
    const hoy = new Date().toISOString().split('T')[0];
    const inputInicio = document.getElementById('fechaInicio');
    const inputFin = document.getElementById('fechaFin');

    if (inputInicio) inputInicio.max = hoy;
    if (inputFin) inputFin.max = hoy;

    cargarResumen();
    cargarReportes();
});

const formFiltros = document.getElementById('formFiltros');
if (formFiltros) {
    formFiltros.addEventListener('submit', (e) => {
        e.preventDefault();
        cargarReportes();
    });
}

const btnLimpiar = document.getElementById('btnLimpiar');
if (btnLimpiar) {
    btnLimpiar.addEventListener('click', () => {
        document.getElementById('formFiltros').reset();
        cargarReportes();
    });
}