const listaPagos = document.getElementById('lista-pagos');
let misPagos = [];

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

    const respuesta = await fetch('../Modelo/gestionarPagos.php', opciones);
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

// 1. Cargar historial de abonos
async function cargarPagos() {
    try {
        const resultado = await solicitarApi("GET");
        misPagos = resultado.pagos;
        mostrarPagos();
    } catch (error) {
        console.error("Error al cargar pagos:", error.message);
    }
}

// 2. Pintar los abonos en la tabla con tu función crearCelda
function mostrarPagos() {
    listaPagos.replaceChildren();

    const contador = document.getElementById("contadorPagos");
    if (contador) {
        contador.textContent = `${misPagos.length} abono(s)`;
    }

    if (misPagos.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 5;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "Aún no has registrado ningún abono.";
        fila.append(celda);
        listaPagos.append(fila);
        return;
    }

    misPagos.forEach((p, index) => {
        const valor = Number(p.valorCuota);
        const fila = document.createElement("tr");

        const celdaNumero = crearCelda(index + 1);
        celdaNumero.className = "text-center fw-bold text-muted";

        const celdaPrestamo = crearCelda(`#${p.idPrestamo}`);
        celdaPrestamo.className = "text-center fw-semibold text-primary";

        const celdaMonto = crearCelda(valor.toLocaleString("es-CO", { style: "currency", currency: "COP" }));
        celdaMonto.className = "text-end fw-bold text-success";

        const celdaEstado = document.createElement("td");
        celdaEstado.className = "text-center";
        celdaEstado.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success">Aplicado</span>`;

        fila.append(
            celdaNumero,
            crearCelda(p.fechaPago),
            celdaPrestamo,
            celdaMonto,
            celdaEstado
        );

        listaPagos.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

// 3. Registrar abono desde tu formulario original
const formPago = document.getElementById("formPago");
if (formPago) {
    formPago.addEventListener("submit", async (e) => {
        e.preventDefault();

        const codigoPrestamo = document.getElementById("codigoPrestamo").value.trim();
        const fechaPago = document.getElementById("fechaPago").value;
        const valorPago = parseFloat(document.getElementById("valorPago").value);
        const hoy = new Date().toISOString().split("T")[0];

        // Validación: fecha no futura
        if (fechaPago > hoy) {
            alert("La fecha de pago no puede ser una fecha futura.");
            return;
        }

        // Validación: monto mayor a 0
        if (isNaN(valorPago) || valorPago <= 0) {
            alert("El valor pagado debe ser mayor a cero.");
            return;
        }

        const datos = {
            codigoPrestamo: codigoPrestamo,
            fechaPago: fechaPago,
            valorPago: valorPago
        };

        try {
            const resp = await solicitarApi("POST", datos);
            alert(resp.mensaje || "Abono registrado con éxito");
            formPago.reset();
            // Mantener la fecha máxima bloqueada a hoy
            document.getElementById("fechaPago").max = hoy;
            await cargarPagos();
        } catch (error) {
            alert(error.message);
        }
    });
}

// Configurar fecha máxima en el calendario para evitar fechas futuras
document.addEventListener("DOMContentLoaded", () => {
    const inputFecha = document.getElementById("fechaPago");
    if (inputFecha) {
        const hoy = new Date().toISOString().split("T")[0];
        inputFecha.max = hoy;
        inputFecha.value = hoy;
    }
    cargarPagos();
});