document.addEventListener("DOMContentLoaded", function () {
  const inViews = window.location.pathname.includes("/views/");
  const authBase = inViews
    ? "../assets/app/controllers/AuthController.php"
    : "assets/app/controllers/AuthController.php";

  // Limpiar cualquier parámetro técnico residual en la URL (0 parámetros expuestos)
  if (window.location.search && (document.querySelector(".login-box") || document.querySelector(".register-box"))) {
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  // Eliminar cualquier bandera insegura previa en almacenamiento del navegador
  localStorage.removeItem("usuarioLogeado");
  localStorage.removeItem("user_rol");
  sessionStorage.removeItem("Usuario");

  // ===========================================================
  //   MENSAJES CONTROLADOS DESDE SESIÓN EN SERVIDOR (SIN ?error=)
  // ===========================================================
  function renderAuthAlert(type, message) {
    const box = document.querySelector(".login-box") || document.querySelector(".register-box");
    if (!box || !message) return;

    const alertDiv = document.createElement("div");
    alertDiv.className = type === "success" ? "alert-success" : "alert-error";

    const span = document.createElement("span");
    span.textContent = message;

    const closeBtn = document.createElement("button");
    closeBtn.className = "alert-close";
    closeBtn.type = "button";
    closeBtn.innerHTML = "&times;";

    alertDiv.appendChild(span);
    alertDiv.appendChild(closeBtn);
    box.insertBefore(alertDiv, box.firstChild);

    closeBtn.addEventListener("click", () => alertDiv.remove());
    setTimeout(() => {
      if (alertDiv.parentElement) alertDiv.remove();
    }, 7000);
  }

  if (document.querySelector(".login-box") || document.querySelector(".register-box")) {
    fetch(`${authBase}?action=flash`, { credentials: "same-origin" })
      .then((res) => (res.ok ? res.json() : null))
      .then((data) => {
        if (data && data.success && data.flash && data.flash.message) {
          renderAuthAlert(data.flash.type, data.flash.message);
        }
      })
      .catch(() => {});
  }

  // ===========================================================
  //                     VALIDACIÓN LOGIN
  // ===========================================================
  const loginForm = document.querySelector(
    '.login-box form[action*="AuthController.php"][method="POST"]'
  );

  if (loginForm && loginForm.querySelector('input[name="action"][value="login"]')) {
    const emailInput = loginForm.querySelector('input[type="email"]');
    const passInput = loginForm.querySelector('input[type="password"]');

    function createErrorEl(text) {
      const el = document.createElement("div");
      el.className = "input-error";
      el.innerText = text;
      return el;
    }

    function removeError(input) {
      const next = input.nextElementSibling;
      if (next && next.classList.contains("input-error")) next.remove();
      input.classList.remove("has-error");
    }

    function showError(input, text) {
      removeError(input);
      input.classList.add("has-error");
      input.insertAdjacentElement("afterend", createErrorEl(text));
    }

    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    [emailInput, passInput].forEach((input) =>
      input.addEventListener("input", () => removeError(input))
    );

    loginForm.addEventListener("submit", function (e) {
      let hasError = false;

      if (!emailInput.value.trim()) {
        showError(emailInput, "El correo es obligatorio.");
        hasError = true;
      } else if (!isValidEmail(emailInput.value.trim())) {
        showError(emailInput, "El formato del correo no es válido.");
        hasError = true;
      }

      if (!passInput.value) {
        showError(passInput, "La contraseña es obligatoria.");
        hasError = true;
      }

      if (hasError) e.preventDefault();
    });
  }

  // ===========================================================
  //                 VALIDACIÓN REGISTRO
  // ===========================================================
  const registerForm = document.querySelector(
    '.register-box form[action*="AuthController.php"][method="POST"]'
  );

  if (registerForm && registerForm.querySelector('input[name="action"][value="register"]')) {
    const nameInput =
      registerForm.querySelector('input[name="nombre"]') ||
      registerForm.querySelector('input[name="name"]');
    const emailInput = registerForm.querySelector('input[name="email"]');
    const passInput = registerForm.querySelector('input[name="password"]');
    const confirmInput = registerForm.querySelector('input[name="confirm_password"]');

    function createErrorEl(text) {
      const el = document.createElement("div");
      el.className = "input-error";
      el.innerText = text;
      return el;
    }

    function removeError(input) {
      const next = input.nextElementSibling;
      if (next && next.classList.contains("input-error")) next.remove();
      input.classList.remove("has-error");
    }

    function showError(input, text) {
      removeError(input);
      input.classList.add("has-error");
      input.insertAdjacentElement("afterend", createErrorEl(text));
    }

    function isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    [nameInput, emailInput, passInput, confirmInput].forEach((input) => {
      if (input) input.addEventListener("input", () => removeError(input));
    });

    registerForm.addEventListener("submit", function (e) {
      let hasError = false;

      if (!nameInput.value.trim()) {
        showError(nameInput, "El nombre es obligatorio.");
        hasError = true;
      }

      if (!emailInput.value.trim()) {
        showError(emailInput, "El correo es obligatorio.");
        hasError = true;
      } else if (!isValidEmail(emailInput.value.trim())) {
        showError(emailInput, "El formato del correo no es válido.");
        hasError = true;
      }

      if (!passInput.value) {
        showError(passInput, "La contraseña es obligatoria.");
        hasError = true;
      } else if (passInput.value.length < 6) {
        showError(passInput, "Debe tener al menos 6 caracteres.");
        hasError = true;
      }

      if (!confirmInput.value) {
        showError(confirmInput, "Debes confirmar tu contraseña.");
        hasError = true;
      } else if (confirmInput.value !== passInput.value) {
        showError(confirmInput, "Las contraseñas no coinciden.");
        hasError = true;
      }

      if (hasError) e.preventDefault();
    });
  }

  // ===========================================================
  //                     CERRAR SESIÓN
  // ===========================================================
  const logoutBtn = document.getElementById("logoutBtn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (e) {
      e.preventDefault();
      window.location.href = inViews
        ? "../assets/app/controllers/LogoutController.php"
        : "assets/app/controllers/LogoutController.php";
    });
  }

  // ===========================================================
  //           MODAL GLOBAL DE NOTIFICACIÓN
  // ===========================================================
  (function insertGlobalNotification() {
    if (document.getElementById("globalNotification")) return;

    const modal = document.createElement("div");
    modal.id = "globalNotification";
    modal.className = "modal oculto";
    modal.innerHTML = `
      <div class="modal-content notification-content">
        <p id="globalNotificationMessage">Mensaje de notificación</p>
        <div class="notification-actions">
          <a id="globalPrimaryBtn" class="btn-comprar btn-aceptar" href="#">Aceptar</a>
          <a id="globalSecondaryBtn" class="btn-vaciar" href="#" style="display:none;">Registrar</a>
          <button id="globalCloseBtn" class="btn-vaciar">Cerrar</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    document.getElementById("globalCloseBtn").addEventListener("click", function () {
      document.getElementById("globalNotification").classList.add("oculto");
    });
  })();

  window.showNotification = function (options) {
    const modal = document.getElementById("globalNotification");
    const msg = document.getElementById("globalNotificationMessage");
    const primary = document.getElementById("globalPrimaryBtn");
    const secondary = document.getElementById("globalSecondaryBtn");

    msg.textContent = options.message || "";

    if (options.primaryText) {
      primary.textContent = options.primaryText;
      if (options.primaryHref) {
        primary.setAttribute("href", options.primaryHref);
        primary.onclick = null;
      } else if (typeof options.onPrimary === "function") {
        primary.setAttribute("href", "#");
        primary.onclick = function (e) {
          e.preventDefault();
          options.onPrimary();
        };
      } else {
        primary.setAttribute("href", "#");
        primary.onclick = function (e) {
          e.preventDefault();
          modal.classList.add("oculto");
        };
      }
      primary.style.display = "";
    } else {
      primary.style.display = "none";
    }

    if (options.secondaryText) {
      secondary.textContent = options.secondaryText;
      if (options.secondaryHref) {
        secondary.setAttribute("href", options.secondaryHref);
        secondary.onclick = null;
      } else {
        secondary.setAttribute("href", "#");
        secondary.onclick = function (e) {
          e.preventDefault();
          modal.classList.add("oculto");
        };
      }
      secondary.style.display = "";
    } else {
      secondary.style.display = "none";
    }

    const content = modal.querySelector(".modal-content");
    content.classList.remove("info", "success", "error");
    if (options.type) content.classList.add(options.type);

    modal.classList.remove("oculto");

    if (options.autoHide) {
      setTimeout(() => {
        modal.classList.add("oculto");
      }, options.autoHide);
    }
  };

  // Verificar sesión real en servidor al pulsar el botón público de carrito
  document.body.addEventListener("click", async function (e) {
    const target = e.target.closest(".btn-carrito");
    if (!target) return;

    e.preventDefault();
    try {
      const res = await fetch(`${authBase}?action=session_status`, {
        credentials: "same-origin",
      });
      const data = res.ok ? await res.json() : null;
      if (data && data.authenticated) {
        window.location.href = inViews ? "Carrito.php" : "views/Carrito.php";
        return;
      }
    } catch (_) {}

    const loginHref = inViews ? "login.html" : "views/login.html";
    showNotification({
      message: "Por favor inicia sesión o regístrate para acceder al carrito.",
      primaryText: "Iniciar Sesión",
      primaryHref: loginHref,
      type: "info",
    });
  });
});
