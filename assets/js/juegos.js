// ============================================
// JUEGOS.JS - Catálogo dinámico desacoplado desde MySQL
// ============================================

let catalogoJuegos = [];

window.addEventListener("DOMContentLoaded", function () {
  cargarJuegos();
});

async function cargarJuegos() {
  const novedadesSection = document.querySelector(".novedades");
  if (!novedadesSection) return;

  try {
    const inViews = window.location.pathname.includes("/views/");
    const rutaController = inViews
      ? "../assets/app/controllers/JuegoController.php?action=getAll"
      : "assets/app/controllers/JuegoController.php?action=getAll";

    const response = await fetch(rutaController, {
      headers: { Accept: "application/json" },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const data = await response.json();

    const loadingEl = novedadesSection.querySelector(".catalogo-loading");
    if (loadingEl) loadingEl.remove();

    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
      catalogoJuegos = data.data;
      inicializarFiltrosCatalogo(catalogoJuegos);
      mostrarJuegos(catalogoJuegos);
    } else {
      mostrarMensaje("No hay juegos disponibles en el catálogo actualmente.");
    }
  } catch (error) {
    console.error("Error al cargar juegos:", error);
    const loadingEl = novedadesSection.querySelector(".catalogo-loading");
    if (loadingEl) loadingEl.remove();
    mostrarMensaje("El catálogo no está disponible temporalmente. Intenta más tarde.");
  }
}

function inicializarFiltrosCatalogo(juegos) {
  const novedadesSection = document.querySelector(".novedades");
  const tituloEl = novedadesSection ? novedadesSection.querySelector(".novedades-titulo") : null;
  if (!novedadesSection || !tituloEl || document.getElementById("catalogoFiltros")) return;

  const generos = [...new Set(juegos.map((j) => (j.genero || "").trim()).filter(Boolean))].sort();
  const plataformas = [...new Set(juegos.map((j) => (j.plataforma || "").trim()).filter(Boolean))].sort();

  const filtrosBox = document.createElement("div");
  filtrosBox.id = "catalogoFiltros";
  filtrosBox.className = "catalogo-filtros";
  filtrosBox.setAttribute("role", "search");
  filtrosBox.setAttribute("aria-label", "Filtrar catálogo de juegos");

  filtrosBox.innerHTML = `
    <div class="filtros-row-top">
      <input
        type="search"
        id="filtroBusqueda"
        class="filtro-control"
        placeholder="Buscar juego por nombre o descripción..."
        aria-label="Buscar juego"
      />
      <button type="button" id="btnLimpiarFiltros" class="btn-limpiar-filtros">Limpiar filtros</button>
    </div>
    <div class="filtros-row-bottom">
      <select id="filtroGenero" class="filtro-control" aria-label="Filtrar por género">
        <option value="">Todos los géneros</option>
        ${generos.map((g) => `<option value="${escapeHtml(g)}">${escapeHtml(g)}</option>`).join("")}
      </select>
      <select id="filtroPlataforma" class="filtro-control" aria-label="Filtrar por plataforma">
        <option value="">Todas las plataformas</option>
        ${plataformas.map((p) => `<option value="${escapeHtml(p)}">${escapeHtml(p)}</option>`).join("")}
      </select>
      <select id="filtroOrden" class="filtro-control" aria-label="Ordenar por precio">
        <option value="default">Orden predeterminado</option>
        <option value="precio_asc">Precio: menor a mayor</option>
        <option value="precio_desc">Precio: mayor a menor</option>
        <option value="nombre_asc">Nombre: A - Z</option>
      </select>
    </div>
    <p id="catalogoResultadosCount" class="catalogo-resultados-count" aria-live="polite"></p>
  `;

  tituloEl.insertAdjacentElement("afterend", filtrosBox);

  const busquedaEl = document.getElementById("filtroBusqueda");
  const generoEl = document.getElementById("filtroGenero");
  const plataformaEl = document.getElementById("filtroPlataforma");
  const ordenEl = document.getElementById("filtroOrden");
  const limpiarBtn = document.getElementById("btnLimpiarFiltros");

  const aplicarFiltros = () => {
    const query = (busquedaEl.value || "").trim().toLowerCase();
    const genero = generoEl.value;
    const plataforma = plataformaEl.value;
    const orden = ordenEl.value;

    let filtrados = catalogoJuegos.filter((j) => {
      const nombre = (j.nombre || "").toLowerCase();
      const desc = (j.descripcion || "").toLowerCase();
      const matchQuery = !query || nombre.includes(query) || desc.includes(query);
      const matchGenero = !genero || (j.genero || "") === genero;
      const matchPlataforma = !plataforma || (j.plataforma || "") === plataforma;
      return matchQuery && matchGenero && matchPlataforma;
    });

    if (orden === "precio_asc") {
      filtrados.sort((a, b) => (parseFloat(a.precio) || 0) - (parseFloat(b.precio) || 0));
    } else if (orden === "precio_desc") {
      filtrados.sort((a, b) => (parseFloat(b.precio) || 0) - (parseFloat(a.precio) || 0));
    } else if (orden === "nombre_asc") {
      filtrados.sort((a, b) => (a.nombre || "").localeCompare(b.nombre || "", "es"));
    }

    mostrarJuegos(filtrados);
  };

  busquedaEl.addEventListener("input", aplicarFiltros);
  generoEl.addEventListener("change", aplicarFiltros);
  plataformaEl.addEventListener("change", aplicarFiltros);
  ordenEl.addEventListener("change", aplicarFiltros);
  limpiarBtn.addEventListener("click", () => {
    busquedaEl.value = "";
    generoEl.value = "";
    plataformaEl.value = "";
    ordenEl.value = "default";
    aplicarFiltros();
    busquedaEl.focus();
  });
}

function mostrarJuegos(juegos) {
  const novedadesSection = document.querySelector(".novedades");
  if (!novedadesSection) return;

  novedadesSection
    .querySelectorAll(".card, .catalogo-loading, .catalogo-empty-msg")
    .forEach((el) => el.remove());

  const countEl = document.getElementById("catalogoResultadosCount");
  if (countEl) {
    countEl.textContent = `Mostrando ${juegos.length} de ${catalogoJuegos.length} juegos`;
  }

  if (juegos.length === 0) {
    mostrarMensaje("No se encontraron juegos con los filtros seleccionados.");
    return;
  }

  const inViews = window.location.pathname.includes("/views/");
  const isAdminView = window.location.pathname.includes("principalAdmin.php");
  const rutaImg = inViews ? "../assets/img/" : "assets/img/";

  juegos.forEach((juego) => {
    const precioNum = parseFloat(juego.precio) || 0;
    const puntos = Math.floor(precioNum / 10);

    const card = document.createElement("div");
    card.className = "card";

    const quickAddHtml = isAdminView
      ? ""
      : `<button type="button" class="btn-agregar-rapido" data-juego-id="${encodeURIComponent(juego.id)}" data-juego-nombre="${escapeHtml(juego.nombre)}">
          + Añadir al carrito
        </button>`;

    const metaTags = [juego.genero, juego.plataforma]
      .filter(Boolean)
      .map((tag) => `<span class="game-meta-tag">${escapeHtml(tag)}</span>`)
      .join("");

    const encodedId = encodeURIComponent(juego.id);
    card.innerHTML = `
      <picture>
        <source srcset="${rutaImg}juego_${encodedId}.webp" type="image/webp" />
        <img
          src="${rutaImg}juego_${encodedId}.jpg"
          alt="${escapeHtml(juego.nombre)}"
          loading="lazy"
          decoding="async"
          onerror="this.onerror=null;this.src='${rutaImg}LogoEnevo.png';"
        />
      </picture>
      <div class="info">
        <div>
          <h3>${escapeHtml(juego.nombre)}</h3>
          ${metaTags ? `<div class="game-meta-tags">${metaTags}</div>` : ""}
        </div>
        <p class="precio">
          <span>Desde ${precioNum.toFixed(2)} MXN</span><br>
          ${puntos} Puntos
        </p>
        <div class="card-actions">
          <button type="button" class="btn-mas-detalles" data-juego-id="${encodeURIComponent(juego.id)}">
            Más detalles
          </button>
          ${quickAddHtml}
        </div>
      </div>
    `;

    novedadesSection.appendChild(card);
  });

  agregarEventosDetalles();
  agregarEventosCarritoRapido();
}

function agregarEventosCarritoRapido() {
  const botones = document.querySelectorAll(".btn-agregar-rapido");
  const pathname = window.location.pathname;
  const inViews = pathname.includes("/views/");
  const isLoggedClient = pathname.includes("principal.php");
  const cartEndpoint = inViews
    ? "../assets/app/controllers/CarritoController.php?action=add"
    : "assets/app/controllers/CarritoController.php?action=add";
  const authEndpoint = inViews
    ? "../assets/app/controllers/AuthController.php?action=session_status"
    : "assets/app/controllers/AuthController.php?action=session_status";
  const loginHref = inViews ? "login.html" : "views/login.html";
  const registerHref = inViews ? "registro.html" : "views/registro.html";
  const cartHref = inViews ? "Carrito.php" : "views/Carrito.php";

  botones.forEach((btn) => {
    if (btn.dataset.hasCartListener) return;
    btn.dataset.hasCartListener = "true";

    btn.addEventListener("click", async function () {
      const juegoId = this.getAttribute("data-juego-id");
      const juegoNombre = this.getAttribute("data-juego-nombre") || "el juego";
      if (!juegoId) return;

      const originalText = this.textContent.trim();
      this.disabled = true;
      this.textContent = "Agregando...";

      try {
        if (!isLoggedClient) {
          const authRes = await fetch(authEndpoint, { credentials: "same-origin" });
          const authData = authRes.ok ? await authRes.json() : null;
          if (!authData || !authData.authenticated) {
            if (typeof window.showNotification === "function") {
              window.showNotification({
                message: `Inicia sesión o regístrate para añadir "${juegoNombre}" al carrito.`,
                primaryText: "Iniciar Sesión",
                primaryHref: loginHref,
                secondaryText: "Registrarse",
                secondaryHref: registerHref,
                type: "info",
              });
            }
            this.disabled = false;
            this.textContent = originalText;
            return;
          }
        }

        const formData = new FormData();
        formData.append("id_juego", juegoId);

        const response = await fetch(cartEndpoint, {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        });

        const data = await response.json();

        if (data.success) {
          this.textContent = "¡Agregado!";
          this.classList.add("added");

          if (typeof window.actualizarContadorCarritoGlobal === "function") {
            window.actualizarContadorCarritoGlobal();
          }

          if (typeof window.showNotification === "function") {
            window.showNotification({
              message: `"${juegoNombre}" se añadió a tu carrito.`,
              type: "success",
              autoHide: 3000,
              primaryText: "Ver carrito",
              primaryHref: cartHref,
            });
          }

          setTimeout(() => {
            this.disabled = false;
            this.textContent = originalText;
            this.classList.remove("added");
          }, 1500);
        } else {
          throw new Error(data.message || "No se pudo añadir al carrito");
        }
      } catch (err) {
        console.error("Error al añadir al carrito:", err);
        if (typeof window.showNotification === "function") {
          window.showNotification({
            message: "No se pudo añadir el juego al carrito. Intenta nuevamente.",
            type: "error",
          });
        }
        this.disabled = false;
        this.textContent = originalText;
      }
    });
  });
}

function agregarEventosDetalles() {
  const botones = document.querySelectorAll(".btn-mas-detalles");

  botones.forEach((btn) => {
    if (btn.dataset.hasListener) return;
    btn.dataset.hasListener = "true";

    btn.addEventListener("click", function () {
      const juegoId = this.getAttribute("data-juego-id");
      if (juegoId) {
        redirigirADetalles(juegoId);
      }
    });
  });
}

function redirigirADetalles(juegoId) {
  const pathname = window.location.pathname;
  const inViews = pathname.includes("/views/");

  if (pathname.includes("principalAdmin.php")) {
    window.location.href = `MasDetallesAdmin.php?id=${encodeURIComponent(juegoId)}`;
  } else if (pathname.includes("principal.php")) {
    window.location.href = `MasDetalles.php?id=${encodeURIComponent(juegoId)}`;
  } else {
    const target = inViews
      ? `MasDetallesNoLogin.html?id=${encodeURIComponent(juegoId)}`
      : `views/MasDetallesNoLogin.html?id=${encodeURIComponent(juegoId)}`;
    window.location.href = target;
  }
}

function mostrarMensaje(mensaje) {
  const novedadesSection = document.querySelector(".novedades");
  if (novedadesSection) {
    const p = document.createElement("p");
    p.className = "catalogo-empty-msg";
    p.style.textAlign = "center";
    p.style.color = "#c5c6c7";
    p.style.padding = "24px";
    p.style.fontSize = "16px";
    p.textContent = mensaje;
    novedadesSection.appendChild(p);
  }
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text ?? "";
  return div.innerHTML;
}
