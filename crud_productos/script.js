/**
 * script.js
 * Maneja toda la lógica del lado del cliente:
 *  - Captura de eventos (clic en botones, input en búsqueda)
 *  - Validaciones en cliente antes de enviar
 *  - Comunicación asíncrona con registrar.php mediante fetch + FormData
 *  - Procesamiento de respuestas JSON
 *  - Alertas con SweetAlert2
 *  - Renderizado dinámico de la tabla
 */

"use strict";

// ── URL del backend ───────────────────────────────────────────────────
const URL_BACKEND = "registrar.php";

// ── Estado global: "Guardar" | "Modificar" ───────────────────────────
let modoActual = "Guardar";

// ═════════════════════════════════════════════════════════════════════
// FUNCIÓN PRINCIPAL: Guardar o Modificar según modoActual
// ═════════════════════════════════════════════════════════════════════
async function accionFormulario() {
    // Recolectar valores del formulario
    const id       = document.getElementById("id").value.trim();
    const codigo   = document.getElementById("codigo").value.trim();
    const producto = document.getElementById("producto").value.trim();
    const precio   = document.getElementById("precio").value.trim();
    const cantidad = document.getElementById("cantidad").value.trim();

    // ── VALIDACIÓN EN CLIENTE ─────────────────────────────────────
    const erroresCliente = validarCamposCliente(codigo, producto, precio, cantidad, modoActual);
    if (erroresCliente.length > 0) {
        mostrarErrores(erroresCliente, modoActual);
        return;
    }

    // ── switch en JavaScript para definir la Accion ───────────────
    let accion;
    switch (modoActual) {
        case "Guardar":
            accion = "Guardar";
            break;
        case "Modificar":
            accion = "Modificar";
            break;
        default:
            accion = "Guardar";
    }

    // ── CONSTRUIR FormData ────────────────────────────────────────
    const formData = new FormData();
    formData.append("Accion",   accion);
    formData.append("id",       id);
    formData.append("codigo",   codigo);
    formData.append("producto", producto);
    formData.append("precio",   precio);
    formData.append("cantidad", cantidad);

    // ── Deshabilitar botón mientras procesa ───────────────────────
    const btnGuardar = document.getElementById("btnGuardar");
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Procesando…`;

    try {
        // ── PETICIÓN FETCH ────────────────────────────────────────
        const response = await fetch(URL_BACKEND, {
            method: "POST",
            body:   formData
        });

        // Verificar que la respuesta HTTP sea correcta
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
        }

        // Parsear JSON
        const data = await response.json();

        // ── PROCESAR RESPUESTA con switch ─────────────────────────
        switch (data.accion) {
            case "Guardar":
                if (data.success) {
                    await Swal.fire({
                        icon:  "success",
                        title: "¡Guardado!",
                        text:  data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    limpiarFormulario();
                    ListarProductos();
                } else {
                    mostrarErroresServidor(data);
                }
                break;

            case "Modificar":
                if (data.success) {
                    await Swal.fire({
                        icon:  "success",
                        title: "¡Actualizado!",
                        text:  data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    cancelarEdicion();
                    ListarProductos();
                } else {
                    mostrarErroresServidor(data);
                }
                break;

            default:
                Swal.fire("Error", data.message || "Respuesta inesperada del servidor.", "error");
        }

    } catch (error) {
        // Error de red o parseo JSON
        Swal.fire({
            icon:  "error",
            title: "Error de Conexión",
            text:  `No se pudo comunicar con el servidor: ${error.message}`,
        });
        console.error("Fetch error:", error);
    } finally {
        // Restaurar botón
        btnGuardar.disabled = false;
        actualizarBotonesFormulario();
    }
}

// ═════════════════════════════════════════════════════════════════════
// LISTAR PRODUCTOS — carga la tabla completa
// ═════════════════════════════════════════════════════════════════════
async function ListarProductos() {
    const formData = new FormData();
    formData.append("Accion", "Listar");

    try {
        const response = await fetch(URL_BACKEND, { method: "POST", body: formData });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();

        if (data.success) {
            renderizarTabla(data.productos);
        } else {
            mostrarTablaVacia("No se pudo cargar la lista de productos.");
        }
    } catch (error) {
        mostrarTablaVacia("Error al conectar con el servidor.");
        console.error("ListarProductos error:", error);
    }
}

// ═════════════════════════════════════════════════════════════════════
// BUSCAR PRODUCTOS — búsqueda en tiempo real
// ═════════════════════════════════════════════════════════════════════
async function buscarProductos(termino) {
    // Si está vacío, volver a listar todos
    if (termino.trim() === "") {
        ListarProductos();
        return;
    }

    const formData = new FormData();
    formData.append("Accion",  "Buscar");
    formData.append("termino", termino);

    try {
        const response = await fetch(URL_BACKEND, { method: "POST", body: formData });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        renderizarTabla(data.productos || []);

    } catch (error) {
        console.error("buscarProductos error:", error);
    }
}

// ═════════════════════════════════════════════════════════════════════
// EDITAR — carga los datos en el formulario
// ═════════════════════════════════════════════════════════════════════
async function editarProducto(id) {
    const formData = new FormData();
    formData.append("Accion", "BuscarId");
    formData.append("id",     id);

    try {
        const response = await fetch(URL_BACKEND, { method: "POST", body: formData });
        const data     = await response.json();

        if (data.success) {
            const p = data.producto;

            // Rellenar formulario
            document.getElementById("id").value       = p.id;
            document.getElementById("codigo").value   = p.codigo;
            document.getElementById("producto").value = p.producto;
            document.getElementById("precio").value   = p.precio;
            document.getElementById("cantidad").value = p.cantidad;

            // Cambiar modo a Modificar y ajustar cantidad mínima a 0
            modoActual = "Modificar";
            document.getElementById("cantidad").min = "0";
            actualizarBotonesFormulario();

            // Scroll al formulario
            document.getElementById("codigo").scrollIntoView({ behavior: "smooth" });
            document.getElementById("codigo").focus();

        } else {
            Swal.fire("No encontrado", data.message, "warning");
        }
    } catch (error) {
        Swal.fire("Error", "No se pudo cargar el producto.", "error");
        console.error("editarProducto error:", error);
    }
}

// ═════════════════════════════════════════════════════════════════════
// CANCELAR EDICIÓN — vuelve al modo Guardar
// ═════════════════════════════════════════════════════════════════════
function cancelarEdicion() {
    modoActual = "Guardar";
    limpiarFormulario();
    actualizarBotonesFormulario();
}

// ═════════════════════════════════════════════════════════════════════
// RENDERIZAR TABLA
// ═════════════════════════════════════════════════════════════════════
function renderizarTabla(productos) {
    const tbody = document.getElementById("tbodyProductos");
    const label = document.getElementById("lblTotal");

    if (!productos || productos.length === 0) {
        mostrarTablaVacia("No se encontraron productos.");
        return;
    }

    label.textContent = `${productos.length} producto(s)`;

    const filas = productos.map(p => {
        const stockBadge = parseInt(p.cantidad) > 0
            ? `<span class="badge badge-stock-ok">${p.cantidad}</span>`
            : `<span class="badge badge-stock-zero">Agotado</span>`;

        const precio = parseFloat(p.precio).toFixed(2);

        return `
        <tr>
            <td class="text-muted small">${p.id}</td>
            <td><code>${p.codigo}</code></td>
            <td class="text-start">${p.producto}</td>
            <td>$${precio}</td>
            <td>${stockBadge}</td>
            <td>
                <button
                    class="btn btn-sm btn-outline-primary"
                    onclick="editarProducto(${p.id})"
                    title="Editar"
                >
                    <i class="bi bi-pencil-square"></i>
                </button>
            </td>
        </tr>`;
    }).join("");

    tbody.innerHTML = filas;
}

// ═════════════════════════════════════════════════════════════════════
// HELPERS
// ═════════════════════════════════════════════════════════════════════

/** Limpia todos los campos del formulario */
function limpiarFormulario() {
    document.getElementById("id").value       = "0";
    document.getElementById("codigo").value   = "";
    document.getElementById("producto").value = "";
    document.getElementById("precio").value   = "";
    document.getElementById("cantidad").value = "";
    document.getElementById("cantidad").min   = "1"; // restaurar mínimo
}

/** Actualiza texto y visibilidad de botones según el modo */
function actualizarBotonesFormulario() {
    const lblBoton    = document.getElementById("lblBoton");
    const btnGuardar  = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelar");

    switch (modoActual) {
        case "Guardar":
            lblBoton.textContent       = "Registrar";
            btnGuardar.className       = "btn btn-primary flex-fill";
            btnCancelar.style.display  = "none";
            break;
        case "Modificar":
            lblBoton.textContent       = "Actualizar";
            btnGuardar.className       = "btn btn-warning flex-fill";
            btnCancelar.style.display  = "inline-block";
            break;
    }
}

/** Muestra fila vacía en la tabla con un mensaje */
function mostrarTablaVacia(mensaje) {
    document.getElementById("tbodyProductos").innerHTML =
        `<tr><td colspan="6" class="text-muted py-4">
            <i class="bi bi-inbox me-2"></i>${mensaje}
         </td></tr>`;
    document.getElementById("lblTotal").textContent = "0 producto(s)";
}

// ─── VALIDACIONES EN CLIENTE ──────────────────────────────────────────

/**
 * Valida los campos del formulario antes de enviar
 * @returns {string[]} Lista de errores; vacía si es válido
 */
function validarCamposCliente(codigo, producto, precio, cantidad, modo) {
    const errores = [];

    if (!codigo)   errores.push("El código es obligatorio.");
    if (!producto) errores.push("El nombre del producto es obligatorio.");

    const precioNum = parseFloat(precio);
    if (isNaN(precioNum) || precioNum <= 0) {
        errores.push("El precio debe ser mayor a 0.");
    }

    const cantidadNum = parseInt(cantidad);
    if (isNaN(cantidadNum)) {
        errores.push("La cantidad debe ser un número entero.");
    } else {
        const minCantidad = (modo === "Guardar") ? 1 : 0;
        if (cantidadNum < minCantidad) {
            errores.push(
                modo === "Guardar"
                    ? "La cantidad debe ser al menos 1 al registrar."
                    : "La cantidad no puede ser negativa."
            );
        }
    }

    return errores;
}

/** Muestra alerta de errores del cliente con SweetAlert2 */
function mostrarErrores(errores, accion) {
    const lista = errores.map(e => `<li>${e}</li>`).join("");
    Swal.fire({
        icon:  "warning",
        title: `Errores al ${accion}`,
        html:  `<ul class="text-start">${lista}</ul>`,
    });
}

/** Muestra errores devueltos por el servidor con SweetAlert2 */
function mostrarErroresServidor(data) {
    const errores = data.errors || [];
    if (errores.length > 0) {
        mostrarErrores(errores, data.accion);
    } else {
        Swal.fire({
            icon:  "error",
            title: "Error",
            text:  data.message || "Error desconocido en el servidor.",
        });
    }
}
