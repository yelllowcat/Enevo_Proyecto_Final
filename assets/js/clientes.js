const CLIENTES_API = "../assets/app/controllers/ClientesController.php";
let clientesCache = [];

function escapeHtmlCliente(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}

// ============================================================================
// LISTAR Y FILTRAR CLIENTES
// ============================================================================
function cargarClientes() {
  fetch(`${CLIENTES_API}?accion=listar`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        clientesCache = Array.isArray(data.clientes) ? data.clientes : [];
        poblarFiltroNivelesClientes(clientesCache);
        aplicarFiltroClientes();
      } else {
        console.error("Error al cargar clientes:", data.message);
      }
    })
    .catch((error) => {
      console.error("Error en la petición:", error);
    });
}

function poblarFiltroNivelesClientes(clientes) {
  const select = document.getElementById("filtroNivelCliente");
  if (!select) return;

  const valorActual = select.value;
  const niveles = [...new Set(clientes.map((c) => (c.nivel_nombre || "").trim()).filter(Boolean))].sort();
  select.innerHTML =
    '<option value="">Todos los niveles</option>' +
    niveles.map((n) => `<option value="${escapeHtmlCliente(n)}">${escapeHtmlCliente(n)}</option>`).join("");

  if (niveles.includes(valorActual)) {
    select.value = valorActual;
  }
}

function aplicarFiltroClientes() {
  const queryEl = document.getElementById("buscarCliente");
  const nivelEl = document.getElementById("filtroNivelCliente");
  const query = queryEl ? queryEl.value.trim().toLowerCase() : "";
  const nivel = nivelEl ? nivelEl.value : "";

  const filtrados = clientesCache.filter((c) => {
    const idStr = String(c.id ?? "");
    const nombre = (c.nombre || "").toLowerCase();
    const email = (c.email || "").toLowerCase();
    const matchQuery = !query || idStr.includes(query) || nombre.includes(query) || email.includes(query);
    const matchNivel = !nivel || (c.nivel_nombre || "") === nivel;
    return matchQuery && matchNivel;
  });

  const countEl = document.getElementById("clientesCount");
  if (countEl) {
    countEl.textContent = `Mostrando ${filtrados.length} de ${clientesCache.length} clientes`;
  }

  mostrarClientes(filtrados);
}

function inicializarFiltrosClientes() {
  const queryEl = document.getElementById("buscarCliente");
  const nivelEl = document.getElementById("filtroNivelCliente");
  const limpiarBtn = document.getElementById("btnLimpiarClientes");

  if (queryEl) queryEl.addEventListener("input", aplicarFiltroClientes);
  if (nivelEl) nivelEl.addEventListener("change", aplicarFiltroClientes);
  if (limpiarBtn) {
    limpiarBtn.addEventListener("click", () => {
      if (queryEl) queryEl.value = "";
      if (nivelEl) nivelEl.value = "";
      aplicarFiltroClientes();
      if (queryEl) queryEl.focus();
    });
  }
}

function mostrarClientes(clientes) {
  const tbody = document.getElementById("clienteListado");

  if (!tbody) return;

  tbody.innerHTML = "";

  if (clientes.length === 0) {
    const msg = clientesCache.length === 0
      ? "No hay clientes registrados"
      : "No se encontraron clientes que coincidan con la búsqueda";
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">${msg}</td></tr>`;
    return;
  }

  clientes.forEach((cliente) => {
    const tr = document.createElement("tr");
    const safeNombre = escapeHtmlCliente(cliente.nombre);
    const safeNivel = escapeHtmlCliente(cliente.nivel_nombre);
    const safeEmail = escapeHtmlCliente(cliente.email);
    tr.innerHTML = `
            <td>${escapeHtmlCliente(String(cliente.id))}</td>
            <td>${safeNombre}</td>
            <td>${safeNivel}</td>
            <td>${safeEmail}</td>
            <td>
                <button type="button" class="btn-cliente-editar" onclick="irEditarCliente(${Number(cliente.id)})">Editar</button>
            </td>
            <td>
                <button type="button" class="btn-eliminar-cliente" data-id="${Number(cliente.id)}" data-nombre="${safeNombre}">Eliminar</button>
            </td>
        `;
    const delBtn = tr.querySelector(".btn-eliminar-cliente");
    if (delBtn) {
      delBtn.addEventListener("click", () => confirmarEliminar(Number(cliente.id), cliente.nombre || ""));
    }
    tbody.appendChild(tr);
  });
}

// ============================================================================
// AGREGAR CLIENTE
// ============================================================================
function agregarCliente() {
  const nombre = document.getElementById("nuevoNombre").value.trim();
  const email = document.getElementById("nuevoCorreo").value.trim();
  const password = document.getElementById("nuevoPass").value;
  const password2 = document.getElementById("nuevoPass2").value;

  if (!nombre || !email || !password || !password2) {
    showNotification({
      message: "Todos los campos son obligatorios",
      type: "error",
    });
    return;
  }

  if (password !== password2) {
    showNotification({
      message: "Las contraseñas no coinciden",
      type: "error",
    });
    return;
  }

  if (password.length < 6) {
    showNotification({
      message: "La contraseña debe tener al menos 6 caracteres",
      type: "error",
    });
    return;
  }

  const btnGuardar = document.querySelector(".btn-cliente-guardar");
  const originalText = btnGuardar ? btnGuardar.textContent : "";
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.setAttribute("aria-busy", "true");
    btnGuardar.textContent = "Agregando...";
  }

  const formData = new FormData();
  formData.append("accion", "crear");
  formData.append("nombre", nombre);
  formData.append("email", email);
  formData.append("password", password);

  fetch(CLIENTES_API, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification({
          message: "Cliente agregado exitosamente",
          type: "success",
          autoHide: 1500,
        });
        setTimeout(() => {
          window.location.href = "ClientesAdmin.php";
        }, 1400);
      } else {
        if (btnGuardar) {
          btnGuardar.disabled = false;
          btnGuardar.removeAttribute("aria-busy");
          btnGuardar.textContent = originalText;
        }
        showNotification({ message: "Error: " + data.message, type: "error" });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.removeAttribute("aria-busy");
        btnGuardar.textContent = originalText;
      }
      showNotification({
        message: "Error al agregar el cliente. Intenta nuevamente.",
        type: "error",
      });
    });
}

// ============================================================================
// EDITAR CLIENTE
// ============================================================================
function cargarDatosCliente() {
  const urlParams = new URLSearchParams(window.location.search);
  const clienteId = urlParams.get("id");

  if (!clienteId) {
    showNotification({ message: "ID de cliente no válido", type: "error" });
    setTimeout(() => {
      window.location.href = "ClientesAdmin.php";
    }, 1200);
    return;
  }

  fetch(`${CLIENTES_API}?accion=obtener&id=${clienteId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        mostrarDatosClienteParaEditar(data);
      } else {
        showNotification({
          message: "Error al cargar datos del cliente",
          type: "error",
        });
        setTimeout(() => {
          window.location.href = "ClientesAdmin.php";
        }, 1200);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification({
        message: "Error al cargar datos del cliente",
        type: "error",
      });
    });
}

function mostrarDatosClienteParaEditar(data) {
  const cliente = data.cliente;
  const siguienteNivel = data.siguiente_nivel;
  const comprasFaltantes = data.compras_faltantes;

  document.getElementById("idClienteTexto").textContent = cliente.id;
  document.getElementById("nivelTexto").textContent = cliente.nivel_nombre;

  if (siguienteNivel) {
    document.getElementById("sigNivelTexto").textContent =
      siguienteNivel.nombre;
    document.getElementById("faltantesTexto").textContent = comprasFaltantes;
  } else {
    document.getElementById("sigNivelTexto").textContent =
      "Nivel máximo alcanzado";
    document.getElementById("faltantesTexto").textContent = "0";
  }

  document.getElementById("clienteId").value = cliente.id;
  document.getElementById("editarNombre").value = cliente.nombre;
  document.getElementById("editarCorreo").value = cliente.email;
}

function guardarCambios() {
  const id = document.getElementById("clienteId").value;
  const nombre = document.getElementById("editarNombre").value.trim();
  const email = document.getElementById("editarCorreo").value.trim();

  if (!nombre || !email) {
    showNotification({
      message: "El nombre y el correo son obligatorios",
      type: "error",
    });
    return;
  }

  const btnGuardar = document.querySelector(".btn-guardar");
  const originalText = btnGuardar ? btnGuardar.textContent : "";
  if (btnGuardar) {
    btnGuardar.disabled = true;
    btnGuardar.setAttribute("aria-busy", "true");
    btnGuardar.textContent = "Guardando...";
  }

  const formData = new FormData();
  formData.append("accion", "actualizar");
  formData.append("id", id);
  formData.append("nombre", nombre);
  formData.append("email", email);

  fetch(CLIENTES_API, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification({
          message: "Cliente actualizado exitosamente",
          type: "success",
          autoHide: 1400,
        });
        setTimeout(() => {
          window.location.href = "ClientesAdmin.php";
        }, 1300);
      } else {
        if (btnGuardar) {
          btnGuardar.disabled = false;
          btnGuardar.removeAttribute("aria-busy");
          btnGuardar.textContent = originalText;
        }
        showNotification({ message: "Error: " + data.message, type: "error" });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      if (btnGuardar) {
        btnGuardar.disabled = false;
        btnGuardar.removeAttribute("aria-busy");
        btnGuardar.textContent = originalText;
      }
      showNotification({
        message: "Error al actualizar el cliente",
        type: "error",
      });
    });
}

// ============================================================================
// ELIMINAR CLIENTE
// ============================================================================
function confirmarEliminar(id, nombre) {
  showNotification({
    message: `¿Está seguro de eliminar al cliente "${nombre}"?\n\nEsta acción también eliminará:\n- Su usuario asociado\n- Todo su historial de compras\n\nEsta acción no se puede deshacer`,
    type: "info",
    primaryText: "Eliminar",
    onPrimary: function () {
      eliminarCliente(id);
    },
    secondaryText: "Cancelar",
  });
}

function eliminarCliente(id) {
  const formData = new FormData();
  formData.append("accion", "eliminar");
  formData.append("id", id);

  fetch(CLIENTES_API, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification({
          message: "Cliente eliminado exitosamente",
          type: "success",
          autoHide: 1200,
        });
        cargarClientes();
      } else {
        showNotification({ message: "Error: " + data.message, type: "error" });
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification({
        message: "Error al eliminar el cliente",
        type: "error",
      });
    });
}

// ============================================================================
// NAVEGACIÓN
// ============================================================================
function irAgregarCliente() {
  window.location.href = "AgregarCliente.php";
}

function irEditarCliente(id) {
  window.location.href = `EditarCliente.php?id=${id}`;
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================
document.addEventListener("DOMContentLoaded", function () {
  const path = window.location.pathname;

  if (path.includes("ClientesAdmin.php")) {
    inicializarFiltrosClientes();
    cargarClientes();
  } else if (path.includes("EditarCliente.php")) {
    cargarDatosCliente();
  }
});
