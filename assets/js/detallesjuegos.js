// ============================================
// DETALLES JUEGO - Cargar info desde BD + Carrito
// ============================================

window.addEventListener("DOMContentLoaded", function () {
    // Ejecutar solo en páginas de detalles
    if (window.location.pathname.includes("MasDetalles")) {
        console.log("📄 Página de detalles detectada");
        cargarDetalleJuego();
    }
    
    // Actualizar contador del carrito si está en una página de usuario logueado
    if (!window.location.pathname.includes("NoLogin") && 
        !window.location.pathname.includes("Admin")) {
        actualizarContadorCarrito();
    }

    // Interceptar clicks al carrito dentro de la página pública (NoLogin)
    const isNoLoginView = window.location.pathname.includes("NoLogin");
    const loginHrefQuick = window.location.pathname.includes('/views/') ? 'login.html' : 'views/login.html';
    if (isNoLoginView) {
        document.body.addEventListener('click', function(e){
            const clicked = e.target.closest('.btn-carrito, a[href*="carrito"], a[href*="Carrito"], .add-to-cart');
            if (!clicked) return;
            e.preventDefault();
            showNotification({
                message: 'Debes iniciar sesión para acceder al carrito.',
                primaryText: 'Aceptar',
                primaryHref: loginHrefQuick,
                type: 'info'
            });
        });
    }
});

async function cargarDetalleJuego() {
    try {
        // Obtener ID de juego desde la URL
        const urlParams = new URLSearchParams(window.location.search);
        let juegoId = urlParams.get("id");

        if (!juegoId) {
            mostrarError("No se especificó un juego");
            return;
        }

        const slugMap = {
            darksouls: 1,
            darksouls_p: 1,
            expedition33: 2,
            expedition33_p: 2,
            risk2: 3,
            risk2_p: 3
        };
        if (slugMap[juegoId]) {
            juegoId = slugMap[juegoId];
        }

        console.log("🎮 Cargando detalles del juego ID:", juegoId);

        const response = await fetch("../assets/app/controllers/JuegoController.php?action=getAll");
        const data = await response.json();

        if (data.success && data.data.length > 0) {
            const juego = data.data.find(j => j.id == juegoId);

            if (juego) {
                mostrarDetalles(juego);
                activarCarrito(juego);
            } else {
                mostrarError("Juego no encontrado");
            }
        } else {
            mostrarError("No se pudieron cargar los detalles");
        }
    } catch (error) {
        console.error("Error al cargar detalles:", error);
        mostrarError("Error al cargar la información del juego");
    }
}

function mostrarDetalles(juego) {
    const precioNum = parseFloat(juego.precio) || 0;
    const puntosGanados = Math.floor(precioNum / 10);
    const puntosCanje = Math.floor(precioNum / 50);
    const isNoLogin = window.location.pathname.includes("NoLogin");
    const prefix = isNoLogin ? "nl-" : "";

    const titulo = document.getElementById(`game-${prefix}title`);
    const img = document.getElementById(`game-${prefix}img`);
    const precio = document.getElementById(`game-${prefix}price`);
    const buyTitle = document.getElementById(`buy-${prefix}title`);
    const platformEl = document.getElementById(`game-${prefix}platform`);
    const descripcion = document.getElementById(`game-${prefix}desc`);
    const requisitos = document.getElementById(`req-${prefix}list`);

    if (titulo) titulo.textContent = juego.nombre;

    if (img) {
        const basePath = `../assets/img/juego_${juego.id}`;
        img.decoding = "async";
        img.setAttribute("fetchpriority", "high");
        img.alt = juego.nombre || "Juego";
        img.onerror = () => {
            if (img.src.endsWith(".webp")) {
                img.src = `${basePath}.jpg`;
            } else {
                img.onerror = null;
                img.src = "../assets/img/LogoEnevo.png";
            }
        };
        img.src = `${basePath}.webp`;
    }

    if (precio) {
        precio.textContent = `Mex$ ${precioNum.toFixed(2)} · Ganas ${puntosGanados} pts (Canje: ${puntosCanje} pts)`;
    }

    if (buyTitle) buyTitle.textContent = `Comprar ${juego.nombre}`;

    if (platformEl) {
        platformEl.textContent = juego.plataforma || "Multi-plataforma";
    }

    if (descripcion)
        descripcion.textContent = juego.descripcion || "Sin descripción disponible";

    // Requisitos
    if (requisitos) {
        requisitos.innerHTML = "";
        const reqs = [
            `Plataforma: ${juego.plataforma || "PC"}`,
            `Género: ${juego.genero || "Acción"}`,
            "SO: Windows 10 64-bit",
            "RAM: 8 GB",
            "GPU: GTX 1060 o superior"
        ];

        reqs.forEach(r => {
            const li = document.createElement("li");
            li.textContent = r;
            requisitos.appendChild(li);
        });
    }
}

// =================================================
// CARRITO SEGÚN TIPO DE VISTA (Login / NoLogin / Admin)
// =================================================

function activarCarrito(juego) {
    const isNoLogin = window.location.pathname.includes("NoLogin");
    const isAdmin = window.location.pathname.includes("Admin");

    // En MasDetalles.php la sesión ya fue validada por SessionSecurity en el servidor;
    // en MasDetallesNoLogin.html el usuario no tiene sesión activa.
    const isLogged = !isNoLogin;

    if (!isLogged) {
        // Si no está logueado, interceptar el botón de 'añadir al carrito' y los enlaces al carrito
        let btn = document.getElementById("addCartBtn") || document.querySelector(".btn-buy") || document.querySelector("button[class*='cart'], button[class*='buy']");
        if (btn) {
            btn.addEventListener("click", function(e){
                e.preventDefault();
                const loginHref = window.location.pathname.includes('/views/') ? 'login.html' : 'views/login.html';
                showNotification({
                    message: 'Debes iniciar sesión para agregar al carrito.',
                    primaryText: 'Aceptar',
                    primaryHref: loginHref,
                    type: 'info'
                });
            });
        }

        const carritoBtns = document.querySelectorAll('.btn-carrito, a[href*="carrito"], .add-to-cart');
        carritoBtns.forEach(cb => {
            cb.addEventListener('click', function(e){
                e.preventDefault();
                const loginHref = window.location.pathname.includes('/views/') ? 'login.html' : 'views/login.html';
                showNotification({
                    message: 'Debes iniciar sesión para acceder al carrito.',
                    primaryText: 'Aceptar',
                    primaryHref: loginHref,
                    type: 'info'
                });
            });
        });

        return;
    }

    // ADMIN: no puede comprar
    if (isAdmin) return;

    // CLIENTE NORMAL: agregar al carrito REAL
    // Buscar botón por diferentes selectores
    let btn = document.getElementById("addCartBtn");
    
    if (!btn) {
        btn = document.querySelector(".btn-buy");
    }
    
    if (!btn) {
        btn = document.querySelector("button[class*='cart'], button[class*='buy']");
    }
    
    if (!btn) {
        console.error(" No se encontró el botón para agregar al carrito");
        return;
    }

    console.log(" Botón encontrado:", btn);
    
    // AGREGAR EVENT LISTENER
    btn.addEventListener("click", async function() {
        console.log(" Botón clickeado, ID del juego:", juego.id);
        
        // Guardar estado original
        const originalText = btn.textContent;
        const originalBg = btn.style.backgroundColor;
        
        // Deshabilitar y cambiar apariencia temporalmente
        btn.disabled = true;
        btn.textContent = " Agregando...";
        btn.style.opacity = "0.8";
        btn.style.cursor = "wait";
        
        try {
            // Crear FormData con el ID del juego
            const formData = new FormData();
            formData.append("id_juego", juego.id);
            
            console.log(" Enviando datos al servidor...");
            
            // Hacer petición al controlador CORREGIDO
            const response = await fetch("../assets/app/controllers/CarritoController.php?action=add", {
                method: "POST",
                body: formData,
            });
            
            // Verificar si la respuesta es válida
            const responseText = await response.text();
            console.log(" Respuesta en texto plano:", responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
                console.log(" JSON parseado correctamente:", data);
            } catch (jsonError) {
                console.error(" Error parseando JSON:", jsonError);
                console.log(" Respuesta cruda (primeros 500 chars):", responseText.substring(0, 500));
                
                // Si hay error de JSON pero la petición fue exitosa (código 200)
                if (response.ok) {
                    // Forzar éxito (modo de respaldo)
                    data = { success: true, message: "¡Juego agregado al carrito!" };
                    
                    // Intentar agregar al carrito de todos modos
                    if (!sessionStorage.getItem('carrito_local')) {
                        sessionStorage.setItem('carrito_local', JSON.stringify([]));
                    }
                    let carritoLocal = JSON.parse(sessionStorage.getItem('carrito_local'));
                    carritoLocal.push({
                        id: juego.id,
                        nombre: juego.nombre,
                        precio: juego.precio,
                        fecha: new Date().toISOString()
                    });
                    sessionStorage.setItem('carrito_local', JSON.stringify(carritoLocal));
                } else {
                    throw new Error("Respuesta no válida del servidor");
                }
            }
            
            // MOSTRAR RESULTADO
            if (data.success) {
                // ÉXITO
                btn.textContent = " ¡Agregado!";
                btn.style.backgroundColor = "#269b26ff";
                btn.style.color = "white";
                
                // Mensaje al usuario
                showNotification({
                    message: (data.message || "¡Juego agregado al carrito exitosamente!"),
                    type: 'success',
                    autoHide: 2500,
                    primaryText: 'Ver carrito',
                    primaryHref: 'Carrito.php'
                });
                
                // Actualizar contador del carrito
                actualizarContadorCarrito();
                
                // Log del carrito actual
                setTimeout(async () => {
                    try {
                        const carritoResp = await fetch("../assets/app/controllers/CarritoController.php?action=get");
                        const carritoData = await carritoResp.json();
                        console.log(" Carrito actual:", carritoData);
                    } catch (e) {
                        console.log("No se pudo verificar carrito:", e.message);
                    }
                }, 500);
                
            } else {
                // ERROR del servidor
                btn.textContent = " Error";
                btn.style.backgroundColor = "#f44336";
                btn.style.color = "white";
                
                showNotification({ message: (data.message || "No se pudo agregar al carrito. Intenta nuevamente."), type: 'error' });
            }
            
        } catch (error) {
            // ERROR de conexión o procesamiento
            console.error(" Error en la petición:", error);
            
            btn.textContent = " Error conexión";
            btn.style.backgroundColor = "#FF9800";
            btn.style.color = "white";
            
            showNotification({ message: "Error de conexión: " + error.message + "\n\nEl producto podría haberse agregado. Verifica tu carrito.", type: 'error' });
            
        } finally {
            // Restaurar botón después de 1.5 segundos
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = originalText;
                btn.style.backgroundColor = originalBg;
                btn.style.opacity = "1";
                btn.style.cursor = "pointer";
                btn.style.color = ""; 
            }, 1500);
        }
    });
}

// =================================================
// FUNCIONES AUXILIARES
// =================================================

async function actualizarContadorCarrito() {
    if (typeof window.actualizarContadorCarritoGlobal === "function") {
        await window.actualizarContadorCarritoGlobal();
    }
}

function mostrarNotificacion(mensaje, tipo = "success") {
    // Eliminar notificación anterior si existe
    const notifAnterior = document.querySelector(".notificacion-flotante");
    if (notifAnterior) {
        notifAnterior.remove();
    }
    
    // Crear nueva notificación
    const notificacion = document.createElement("div");
    notificacion.className = `notificacion-flotante ${tipo}`;
    notificacion.textContent = mensaje;
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    // Color según tipo
    if (tipo === "success") {
        notificacion.style.backgroundColor = "#414141ff";
        notificacion.style.borderLeft = "5px solid #7a7a7aff";
    } else if (tipo === "error") {
        notificacion.style.backgroundColor = "#f44336";
        notificacion.style.borderLeft = "5px solid #c62828";
    } else {
        notificacion.style.backgroundColor = "#2196F3";
        notificacion.style.borderLeft = "5px solid #0D47A1";
    }
    
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 3 segundos
    setTimeout(() => {
        notificacion.style.animation = "slideOut 0.3s ease-in";
        setTimeout(() => notificacion.remove(), 300);
    }, 3000);
}

function mostrarError(mensaje) {
    const main = document.querySelector("main");
    if (main) {
        main.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <h2 style="color: #f44;"> ${mensaje}</h2>
                <p>
                    <a href="../index.html" style="color: #00a8e8;">← Volver a la tienda</a>
                </p>
            </div>
        `;
    }
}

// Añadir estilos CSS para las animaciones
const style = document.createElement("style");
style.textContent = `
    @keyframes slideIn {
        from { 
            transform: translateX(100%); 
            opacity: 0; 
        }
        to { 
            transform: translateX(0); 
            opacity: 1; 
        }
    }
    
    @keyframes slideOut {
        from { 
            transform: translateX(0); 
            opacity: 1; 
        }
        to { 
            transform: translateX(100%); 
            opacity: 0; 
        }
    }
    
    button:disabled {
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    #cart-count {
        transition: all 0.3s ease;
    }
    
    #cart-count.pulse {
        animation: pulse 0.5s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);

// Función para verificar estado del carrito al cargar
function verificarEstadoInicialCarrito() {
    console.log(" Verificando estado inicial del carrito...");
    
    // Verificar si hay carrito en localStorage (respaldo)
    const carritoLocal = sessionStorage.getItem('carrito_local');
    if (carritoLocal) {
        console.log(" Carrito local encontrado:", JSON.parse(carritoLocal));
    }
    
    // Intentar cargar del servidor
    setTimeout(() => {
        if (!window.location.pathname.includes("NoLogin") && 
            !window.location.pathname.includes("Admin")) {
            actualizarContadorCarrito();
        }
    }, 1000);
}

// Ejecutar verificación al cargar
verificarEstadoInicialCarrito();