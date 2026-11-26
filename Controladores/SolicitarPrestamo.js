const listaSolicitudes = document.getElementById('lista-solicitudes');
const formSolicitud = document.getElementById('formSolicitud');
let solicitudes = [];

// Misma función solicitarApi estándar de tu proyecto
async function solicitarApi(metodo, datos) {
    const opciones = {
        method: metodo,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch('../Modelo/gestionarSolicitudes.php', opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = 'Login.html';
        return;
    }

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la solicitud");
    }

    return resultado;
}

// 1. Cargar mis solicitudes
async function cargarSolicitudes() {
    try {
        const resultado = await solicitarApi("GET");
        solicitudes = resultado.solicitudes;
        mostrarSolicitudes();
    } catch (error) {
        console.error("Error al cargar solicitudes:", error.message);
    }
}

// 2. Pintar la tabla y las tarjetas
function mostrarSolicitudes() {
    listaSolicitudes.replaceChildren();

    const contador = document.getElementById("contadorSolicitudes");
    if (contador) contador.textContent = `${solicitudes.length} solicitud(es)`;

    document.getElementById("kpiPendientes").textContent = solicitudes.filter(s => s.estado === "Pendiente").length;
    document.getElementById("kpiAprobados").textContent = solicitudes.filter(s => s.estado === "Aprobado").length;

    if (solicitudes.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 6;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "Aún no has hecho ninguna solicitud.";
        fila.append(celda);
        listaSolicitudes.append(fila);
        return;
    }

    const colores = { Pendiente: "bg-warning text-dark", Aprobado: "bg-success", Rechazado: "bg-danger" };

    solicitudes.forEach((s) => {
        const fila = document.createElement("tr");

        const celdaCodigo = crearCelda(`#${s.idPrestamo}`);
        celdaCodigo.className = "text-center fw-semibold text-primary";

        const celdaValor = crearCelda(Number(s.valorPrestamo).toLocaleString("es-CO", {
            style: "currency", currency: "COP", maximumFractionDigits: 0
        }));
        celdaValor.className = "text-end fw-bold";

        const celdaTasa = crearCelda(s.estado === "Pendiente" ? "Por definir" : `${s.tasaInteres}%`);
        celdaTasa.className = "text-center";

        const celdaCuotas = crearCelda(s.numeroCuotas);
        celdaCuotas.className = "text-center";

        const celdaEstado = document.createElement("td");
        celdaEstado.className = "text-center";
        const badge = document.createElement("span");
        badge.className = "badge " + (colores[s.estado] || "bg-secondary");
        badge.textContent = s.estado;
        celdaEstado.append(badge);

        fila.append(celdaCodigo, crearCelda(s.fechaPrestamo), celdaValor, celdaTasa, celdaCuotas, celdaEstado);
        listaSolicitudes.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

// 3. Enviar solicitud
if (formSolicitud) {
    formSolicitud.addEventListener("submit", async (e) => {
        e.preventDefault();

        const datos = {
            valor: Number(document.getElementById("valorSolicitud").value),
            cuotas: Number(document.getElementById("cuotasSolicitud").value)
        };

        if (datos.valor < 100000 || datos.valor > 80000000) {
            alert("El valor debe estar entre $100.000 y $80.000.000.");
            return;
        }
        if (!Number.isInteger(datos.cuotas) || datos.cuotas < 1 || datos.cuotas > 60) {
            alert("Las cuotas deben ser un entero entre 1 y 60.");
            return;
        }

        try {
            const resp = await solicitarApi("POST", datos);
            alert(resp.mensaje || "Solicitud enviada");
            formSolicitud.reset();
            document.getElementById("fechaSolicitud").value = new Date().toISOString().split("T")[0];
            await cargarSolicitudes();
        } catch (error) {
            alert(error.message);
        }
    });
}

// Evita que la rueda del mouse cambie los números
document.addEventListener("wheel", () => {
    const el = document.activeElement;
    if (el && el.type === "number") el.blur();
}, { passive: true });

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("fechaSolicitud").value = new Date().toISOString().split("T")[0];
    cargarSolicitudes();
});