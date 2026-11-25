const listaMisPrestamos = document.getElementById('lista-mis-prestamos');
let datosAsociadoActual = null;

// Misma función estándar que usas en todo tu proyecto
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

    const respuesta = await fetch('../Modelo/gestionarPerfil.php', opciones);
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

// 1. Cargar la información del perfil y sus préstamos
async function cargarPerfil() {
    try {
        const resultado = await solicitarApi("GET");
        datosAsociadoActual = resultado.asociado;
        const prestamos = resultado.prestamos;

        mostrarDatosAsociado(datosAsociadoActual);
        mostrarPrestamos(prestamos);

    } catch (error) {
        alert("Error al cargar perfil: " + error.message);
    }
}

// 2. Pintar los datos personales en la tarjeta izquierda
function mostrarDatosAsociado(asoc) {
    document.getElementById('perfilNombre').textContent = `${asoc.nombre} ${asoc.apellidos}`;
    document.getElementById('perfilDocumento').textContent = asoc.documento;
    document.getElementById('perfilTelefono').textContent = asoc.telefono || 'Sin registrar';
    document.getElementById('perfilEmail').textContent = asoc.email || 'Sin registrar';
    document.getElementById('perfilUbicacion').textContent = `${asoc.direccionResidencia || ''} ${asoc.ciudad ? '(' + asoc.ciudad + ')' : ''}`.trim() || 'Sin registrar';
    document.getElementById('perfilFechaNac').textContent = asoc.fechaNacimiento || 'Sin registrar';

    // Rellenar campos del modal de edición
    document.getElementById('editTelefono').value = asoc.telefono || '';
    document.getElementById('editEmail').value = asoc.email || '';
    document.getElementById('editDireccion').value = asoc.direccionResidencia || '';
    document.getElementById('editCiudad').value = asoc.ciudad || '';
}

// 3. Pintar los préstamos en la tabla con consecutivo 1, 2, 3...
function mostrarPrestamos(prestamos) {
    listaMisPrestamos.replaceChildren();

    let saldoTotal = 0;
    document.getElementById('kpiMisPrestamos').textContent = prestamos.length;

    if (prestamos.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 7;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No tienes préstamos registrados actualmente.";
        fila.append(celda);
        listaMisPrestamos.append(fila);
        document.getElementById('kpiMiSaldo').textContent = "$ 0";
        return;
    }

    prestamos.forEach((p, index) => {
        const monto = Number(p.valorPrestamo);
        const pagado = Number(p.totalPagado);
        const saldo = Number(p.saldoPendiente);
        saldoTotal += saldo;

        const estaAlDia = saldo <= 0;

        const fila = document.createElement("tr");

        const celdaEstado = document.createElement("td");
        celdaEstado.className = "text-center";
        const badge = document.createElement("span");
        badge.className = estaAlDia ? "badge bg-success" : "badge bg-warning text-dark";
        badge.textContent = estaAlDia ? "Paz y Salvo" : "Con Saldo";
        celdaEstado.append(badge);

        const celdaNumero = crearCelda(index + 1);
        celdaNumero.className = "text-center fw-bold text-muted";

        fila.append(
            celdaNumero,
            crearCelda(p.fechaPrestamo),
            crearCelda(monto.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(`${p.numeroCuotas} cuotas`),
            crearCelda(pagado.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(saldo.toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            celdaEstado
        );

        listaMisPrestamos.append(fila);
    });

    document.getElementById('kpiMiSaldo').textContent = saldoTotal.toLocaleString("es-CO", { style: "currency", currency: "COP" });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

// 4. Guardar cambios de contacto
const formContacto = document.getElementById('formContacto');
if (formContacto) {
    formContacto.addEventListener('submit', async (e) => {
        e.preventDefault();

        const datos = {
            telefono: document.getElementById('editTelefono').value.trim(),
            email: document.getElementById('editEmail').value.trim(),
            direccion: document.getElementById('editDireccion').value.trim(),
            ciudad: document.getElementById('editCiudad').value.trim()
        };

        try {
            const resp = await solicitarApi("PUT", datos);
            alert(resp.mensaje || "Datos actualizados");

            // Cerrar modal
            const modalEl = document.getElementById('modalEditarContacto');
            const modalInstancia = bootstrap.Modal.getInstance(modalEl);
            if (modalInstancia) modalInstancia.hide();

            await cargarPerfil();
        } catch (error) {
            alert(error.message);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    cargarPerfil();
});