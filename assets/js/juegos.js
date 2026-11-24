// ============================================
// JUEGOS.JS - Catálogo dinámico desacoplado desde MySQL
// ============================================

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
      mostrarJuegos(data.data);
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

function mostrarJuegos(juegos) {
  const novedadesSection = document.querySelector(".novedades");
  if (!novedadesSection) return;

  novedadesSection.querySelectorAll(".card, .catalogo-loading").forEach((el) => el.remove());

  const inViews = window.location.pathname.includes("/views/");
  const rutaImg = inViews ? "../assets/img/" : "assets/img/";

  juegos.forEach((juego) => {
    const precioNum = parseFloat(juego.precio) || 0;
    const puntos = Math.floor(precioNum / 10);

    const card = document.createElement("div");
    card.className = "card";

    card.innerHTML = `
      <img
        src="${rutaImg}juego_${encodeURIComponent(juego.id)}.jpg"
        alt="${escapeHtml(juego.nombre)}"
        onerror="this.onerror=null;this.src='${rutaImg}LogoEnevo.png';"
      />
      <div class="info">
        <h3>${escapeHtml(juego.nombre)}</h3>
        <p class="precio">
          <span>Desde ${precioNum.toFixed(2)} MXN</span><br>
          ${puntos} Puntos
        </p>
        <button type="button" class="btn-mas-detalles" data-juego-id="${encodeURIComponent(juego.id)}">
          Más detalles
        </button>
      </div>
    `;

    novedadesSection.appendChild(card);
  });

  agregarEventosDetalles();
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
    p.style.textAlign = "center";
    p.style.color = "#999";
    p.style.padding = "20px";
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
