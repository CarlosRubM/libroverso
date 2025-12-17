<?php
// 1. INICIAR LA SESIÓN
session_start();

// 2. VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 3. VERIFICAR QUE SEA UN CLIENTE (no admin)
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'administrador') {
    header('Location: admin/index.php');
    exit;
}

// 4. INCLUIR LA CONFIGURACIÓN DE LA BD
require_once __DIR__ . '/../src/config/config.php';

// ========== OBTENER INFORMACIÓN DEL ÚLTIMO LOGIN ==========
$ultimo_login_info = null;
$fecha_ultimo_login = null; 

if (isset($_COOKIE['ultimo_login'])) {
    $ultimo_login_info = json_decode($_COOKIE['ultimo_login'], true);
    try {
        if (!empty($ultimo_login_info['fecha'])) {
            $fecha_ultimo_login = new DateTime($ultimo_login_info['fecha']);
        }
    } catch (Exception $e) {
        $fecha_ultimo_login = null;
    }
}

// 5. OBTENER LOS LIBROS (CON FILTRO DE BÚSQUEDA)
$libros = [];
$error_db = '';
$busqueda = ''; 

if (isset($_GET['q'])) {
    $busqueda = trim($_GET['q']);
}

try {
    if (!empty($busqueda)) {
        $sql = "SELECT * FROM libros WHERE titulo LIKE ? OR autor LIKE ? ORDER BY titulo ASC";
        $stmt = $pdo->prepare($sql);
        $param = "%" . $busqueda . "%";
        $stmt->execute([$param, $param]);
    } else {
        $sql = "SELECT * FROM libros ORDER BY titulo ASC";
        $stmt = $pdo->query($sql);
    }
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_db = "Error al cargar los libros: " . $e->getMessage();
}

// 6. INCLUIR LA CABECERA
include_once __DIR__ . '/../src/templates/header.php';
?>

<!-- === ESTILOS CSS === -->
<style>
    /* Estilos del Buscador */
    .search-container { max-width: 600px; margin: 0 auto 30px auto; padding: 0 15px; }
    .search-form { display: flex; gap: 10px; background: #fff; padding: 10px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
    .search-input { flex: 1; border: none; padding: 10px 15px; font-size: 1rem; outline: none; border-radius: 50px; }
    .btn-search { background-color: #007bff; color: white; border: none; padding: 10px 25px; border-radius: 50px; cursor: pointer; transition: background 0.3s; }
    .btn-search:hover { background-color: #0056b3; }
    .btn-clear { display: inline-flex; align-items: center; text-decoration: none; color: #666; padding: 0 15px; font-weight: bold; border-left: 1px solid #ddd; }
    .btn-clear:hover { color: #dc3545; }

    /* Estilos de Botones en Tarjeta */
    .card-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
    .btn-comprar-ya { display: block; text-align: center; background-color: #28a745; color: white; padding: 8px; border-radius: 4px; text-decoration: none; font-weight: bold; transition: background 0.3s; }
    .btn-comprar-ya:hover { background-color: #218838; }
    .btn-agregar-ajax { display: block; width: 100%; background-color: #fff; color: #007bff; border: 1px solid #007bff; padding: 8px; border-radius: 4px; cursor: pointer; font-weight: bold; transition: all 0.3s; }
    .btn-agregar-ajax:hover { background-color: #e7f1ff; }

    /* Cursor pointer para elementos que abren el modal */
    .clickable-book { cursor: pointer; transition: opacity 0.2s; }
    .clickable-book:hover { opacity: 0.8; }

    /* === ESTILOS DEL MODAL (VENTANA EMERGENTE) === */
    .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border: none; width: 90%; max-width: 900px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; animation: slideDown 0.3s ease-out; overflow: hidden; }
    @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    
    .close-modal { color: #888; position: absolute; right: 20px; top: 15px; font-size: 30px; font-weight: bold; cursor: pointer; z-index: 10; transition: color 0.2s; }
    .close-modal:hover { color: #333; }

    .modal-body-flex { display: flex; flex-wrap: wrap; }
    
    /* Lado Izquierdo del Modal (Imagen) */
    .modal-img-container { flex: 1; min-width: 300px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; padding: 30px; }
    .modal-img-display { max-width: 100%; max-height: 450px; box-shadow: 0 8px 16px rgba(0,0,0,0.15); border-radius: 4px; object-fit: cover; }

    /* Lado Derecho del Modal (Info) */
    .modal-info-container { flex: 1.5; min-width: 300px; padding: 40px; display: flex; flex-direction: column; }
    .modal-title { margin: 0 0 10px 0; font-size: 2rem; color: #333; line-height: 1.2; }
    .modal-author { font-size: 1.2rem; color: #666; margin-bottom: 20px; font-style: italic; }
    .modal-meta { display: flex; gap: 15px; margin-bottom: 20px; font-size: 0.9rem; color: #888; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .modal-desc { font-size: 1rem; line-height: 1.6; color: #444; margin-bottom: 30px; flex-grow: 1; max-height: 200px; overflow-y: auto; }
    .modal-price { font-size: 2.5rem; font-weight: 800; color: #28a745; margin-bottom: 25px; }
    
    .modal-buttons { display: flex; gap: 15px; }
    .modal-btn { flex: 1; padding: 15px; border-radius: 8px; font-size: 1.1rem; text-align: center; cursor: pointer; font-weight: bold; transition: transform 0.1s; }
    .modal-btn:active { transform: scale(0.98); }

    /* Notificación flotante (Toast) */
    #toast-notification { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 4px; padding: 16px; position: fixed; z-index: 3000; left: 50%; bottom: 30px; transform: translateX(-50%); font-size: 17px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.5s, bottom 0.5s; }
    #toast-notification.show { visibility: visible; opacity: 1; bottom: 50px; }
    
    /* Scrollbar personalizado para la descripción */
    .modal-desc::-webkit-scrollbar { width: 6px; }
    .modal-desc::-webkit-scrollbar-track { background: #f1f1f1; }
    .modal-desc::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }

    /* === ESTILOS PUBLICIDAD (MEJORADOS) === */
    .ad-container {
        margin: 40px auto; 
        text-align: center; 
        max-width: 900px; 
        padding: 0 20px;
    }
    .ad-banner-img {
        width: 100%;
        height: auto;          
        max-height: 300px;     
        object-fit: contain;   
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border: 1px solid #eee;
        background-color: #f8f9fa;
        transition: transform 0.3s ease;
        display: block; 
        margin: 0 auto;
    }
    .ad-banner-img:hover {
        transform: scale(1.01);
        cursor: pointer;
    }
    .ad-label {
        display: block;
        text-align: right;
        font-size: 0.75rem;
        color: #adb5bd;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ad-error {
        background: #f8d7da; 
        color: #721c24; 
        padding: 20px; 
        border-radius: 8px; 
        border: 1px solid #f5c6cb;
    }
</style>

<div class="dashboard-container">

    <!-- MENSAJE DE BIENVENIDA -->
    <div class="welcome-message">
        <h1>¡Bienvenido, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?>!</h1>
        <p>Explora nuestra colección de libros y encuentra tu próxima gran lectura</p>
    </div>

    <!-- INFO ÚLTIMO LOGIN -->
    <?php if ($fecha_ultimo_login): ?>
        <div class="ultimo-login-info">
            <i class="fas fa-clock"></i>
            <span>
                Tu último acceso fue el 
                <strong><?php echo $fecha_ultimo_login->format('d/m/Y'); ?></strong>
                a las <strong><?php echo $fecha_ultimo_login->format('H:i'); ?></strong>
                desde la IP: <strong><?php echo htmlspecialchars($ultimo_login_info['ip']); ?></strong>
            </span>
        </div>
    <?php elseif ($ultimo_login_info): ?>
        <div class="ultimo-login-info"><i class="fas fa-clock"></i> <span>Parece ser tu primera visita. ¡Bienvenido!</span></div>
    <?php endif; ?>

    <!-- BARRA DE BÚSQUEDA -->
    <div class="search-container">
        <form action="dashboard.php" method="GET" class="search-form">
            <input type="text" name="q" class="search-input" placeholder="Buscar por título o autor..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <?php if (!empty($busqueda)): ?>
                <a href="dashboard.php" class="btn-clear" title="Limpiar búsqueda"><i class="fas fa-times"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <!-- RESULTADOS / CONTENIDO -->
    <?php if (!empty($error_db)): ?>
        <div class="error-db"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_db); ?></div>
    
    <?php elseif (empty($libros)): ?>
        <div class="no-libros" style="text-align: center; padding: 40px;">
            <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
            <p style="font-size: 1.2em;">No encontramos libros que coincidan con "<strong><?php echo htmlspecialchars($busqueda); ?></strong>".</p>
            <a href="dashboard.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Ver todos los libros</a>
        </div>
        
    <?php else: ?>

        <?php if (!empty($busqueda)): ?>
            <h2 class="section-title" style="margin-left: 20px;">Resultados para "<?php echo htmlspecialchars($busqueda); ?>"</h2>
        <?php endif; ?>

        <!-- SECCIÓN: NOVEDADES -->
        <section class="book-section">
            <?php if (empty($busqueda)): ?>
            <h2 class="section-title"><i class="fas fa-star"></i> Novedades</h2>
            <?php endif; ?>
            
            <div class="horizontal-scroll-container">
                <?php foreach ($libros as $libro): ?>
                    <?php
                    $url_portada = 'https://via.placeholder.com/240x250.png?text=Libro';
                    if (!empty($libro['isbn'])) {
                        $url_portada = 'https://covers.openlibrary.org/b/isbn/' . htmlspecialchars($libro['isbn']) . '-M.jpg';
                    }
                    $id = $libro['id_libro'] ?? $libro['id'] ?? 0;
                    ?>
                    <div class="book-card-h">
                        <!-- IMAGEN CLICABLE -->
                        <img src="<?php echo $url_portada; ?>?default=false" alt="Portada" class="card-img-top clickable-book"
                             onclick="abrirModal(<?php echo $id; ?>)"
                             onerror="this.onerror=null; this.src='https://via.placeholder.com/240x250.png?text=Sin+Imagen';">
                        
                        <div class="card-body">
                            <!-- TÍTULO CLICABLE -->
                            <h3 class="clickable-book" onclick="abrirModal(<?php echo $id; ?>)" title="Ver detalles">
                                <?php echo htmlspecialchars($libro['titulo']); ?>
                            </h3>
                            <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                            <div class="price"><?php echo number_format($libro['precio'], 2, ',', '.'); ?> €</div>
                        </div>
                        
                        <div class="card-footer card-actions">
                            <button onclick="agregarAlCarrito(<?php echo $id; ?>, '<?php echo htmlspecialchars($libro['titulo'], ENT_QUOTES); ?>')" class="btn-agregar-ajax">
                                <i class="fas fa-cart-plus"></i> Añadir
                            </button>
                            <a href="carrito.php?accion=agregar&id=<?php echo $id; ?>" class="btn-comprar-ya">
                                <i class="fas fa-check"></i> Comprar ya
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- === BLOQUE PUBLICIDAD ROTATIVA CON ENLACES === -->
        <div class="ad-container">
            <span class="ad-label">Publicidad Patrocinada</span>
            <!-- ID "banner-link" para cambiar el href dinámicamente -->
            <a id="banner-link" href="#" target="_blank">
                <!-- La imagen se cargará aquí vía JavaScript -->
                <img id="banner-rotativo" src="" alt="Publicidad" class="ad-banner-img">
            </a>
        </div>
        
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // 1. Array de OBJETOS con { imagen, url }
                // Puedes cambiar las URLs por las webs reales que desees
                const anunciosLocales = [
                    { 
                        img: "img/banner1.jpg", 
                        url: "https://www.cocacola.com" 
                    },
                    { 
                        img: "img/banner2.jpg", 
                        url: "https://www.cocacola.es" 
                    },
                    { 
                        img: "img/banner3.jpg", 
                        url: "https://www.netflix.es" 
                    },
                    { 
                        img: "img/banner4.jpg", 
                        url: "https://www.uclm.es" 
                    },
                    { 
                        img: "img/banner5.jpg", 
                        url: "https://www.penguinlibros.com" 
                    },
                    { 
                        img: "img/banner6.jpg", 
                        url: "https://ferialibromadrid.com" 
                    }
                ];

                // 2. Elegir un índice aleatorio
                const indiceAleatorio = Math.floor(Math.random() * anunciosLocales.length);
                const anuncioElegido = anunciosLocales[indiceAleatorio];

                // 3. Asignar la imagen y el enlace
                const bannerImg = document.getElementById("banner-rotativo");
                const bannerLink = document.getElementById("banner-link");
                
                if (bannerImg && bannerLink) {
                    bannerImg.src = anuncioElegido.img;
                    bannerLink.href = anuncioElegido.url;
                    
                    console.log("Cargando banner:", anuncioElegido.img);
                    console.log("Enlace destino:", anuncioElegido.url);
                    
                    // Manejo extra de errores de carga (404)
                    bannerImg.onerror = function() {
                        console.error("Error visualizando la imagen: " + this.src);
                        this.style.display = 'none';
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'ad-error';
                        errorMsg.innerHTML = 'Error 404: No se puede ver la imagen.<br>Ruta intentada: <strong>' + this.src + '</strong>';
                        this.parentElement.parentElement.appendChild(errorMsg); // Añadir al contenedor padre
                    };
                }
            });
        </script>
        <!-- ======================================================== -->

        <!-- SECCIÓN: RECOMENDADOS -->
        <?php if (empty($busqueda)): ?>
        <section class="book-section">
            <h2 class="section-title"><i class="fas fa-heart"></i> Recomendados para ti</h2>
            <div class="horizontal-scroll-container">
                <?php foreach (array_reverse($libros) as $libro): ?>
                    <?php
                    $url_portada = 'https://via.placeholder.com/240x250.png?text=Libro';
                    if (!empty($libro['isbn'])) {
                        $url_portada = 'https://covers.openlibrary.org/b/isbn/' . htmlspecialchars($libro['isbn']) . '-M.jpg';
                    }
                    $id = $libro['id_libro'] ?? $libro['id'] ?? 0;
                    ?>
                    <div class="book-card-h">
                        <img src="<?php echo $url_portada; ?>?default=false" alt="Portada" class="card-img-top clickable-book"
                             onclick="abrirModal(<?php echo $id; ?>)"
                             onerror="this.onerror=null; this.src='https://via.placeholder.com/240x250.png?text=Sin+Imagen';">
                        
                        <div class="card-body">
                            <h3 class="clickable-book" onclick="abrirModal(<?php echo $id; ?>)"><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                            <p class="autor"><?php echo htmlspecialchars($libro['autor']); ?></p>
                            <div class="price"><?php echo number_format($libro['precio'], 2, ',', '.'); ?> €</div>
                        </div>
                        
                        <div class="card-footer card-actions">
                            <button onclick="agregarAlCarrito(<?php echo $id; ?>, '<?php echo htmlspecialchars($libro['titulo'], ENT_QUOTES); ?>')" class="btn-agregar-ajax">
                                <i class="fas fa-cart-plus"></i> Añadir
                            </button>
                            <a href="carrito.php?accion=agregar&id=<?php echo $id; ?>" class="btn-comprar-ya">
                                <i class="fas fa-check"></i> Comprar ya
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<!-- ========================================== -->
<!-- ESTRUCTURA DEL MODAL (Ventana Emergente)   -->
<!-- ========================================== -->
<div id="libroModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="cerrarModal()">&times;</span>
        
        <div class="modal-body-flex">
            <!-- Columna Imagen -->
            <div class="modal-img-container">
                <img id="modal-img" src="" alt="Portada" class="modal-img-display">
            </div>
            
            <!-- Columna Info -->
            <div class="modal-info-container">
                <h2 id="modal-titulo" class="modal-title">Título del Libro</h2>
                <div id="modal-autor" class="modal-author">Autor</div>
                
                <div class="modal-meta">
                    <span id="modal-editorial"><i class="fas fa-building"></i> Editorial</span>
                    <span id="modal-anio"><i class="fas fa-calendar"></i> 2024</span>
                    <span id="modal-isbn"><i class="fas fa-barcode"></i> ISBN</span>
                </div>
                
                <div id="modal-descripcion" class="modal-desc">
                    Descripción del libro...
                </div>
                
                <div id="modal-precio" class="modal-price">0,00 €</div>
                
                <div class="modal-buttons">
                    <!-- Botón Añadir (AJAX) -->
                    <button id="modal-btn-add" class="modal-btn" style="background: white; border: 2px solid #007bff; color: #007bff;">
                        <i class="fas fa-cart-plus"></i> Añadir al Carrito
                    </button>
                    
                    <!-- Botón Comprar (Link) -->
                    <a id="modal-btn-buy" href="#" class="modal-btn" style="background: #28a745; color: white; text-decoration: none; border: 2px solid #28a745; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check"></i> Comprar Ya
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NOTIFICACIÓN FLOTANTE (Toast) -->
<div id="toast-notification">Producto añadido al carrito</div>

<!-- ========================================== -->
<!-- SCRIPTS JAVASCRIPT                         -->
<!-- ========================================== -->

<!-- 1. Pasamos el catálogo completo de PHP a JS para que el modal tenga los datos -->
<script>
    // Convertimos el array de PHP a un objeto JSON de JS
    // JSON_UNESCAPED_UNICODE asegura que tildes y ñ se vean bien
    const catalogoLibros = <?php echo json_encode($libros, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script>
    // --- LÓGICA DEL MODAL ---
    
    function abrirModal(idLibro) {
        // 1. Buscar el libro en el array JS
        // Usamos 'id_libro' (o 'id' si tu base de datos es antigua)
        const libro = catalogoLibros.find(l => (l.id_libro == idLibro || l.id == idLibro));
        
        if (!libro) return; // Si no lo encuentra, no hace nada

        // 2. Rellenar los datos en el HTML del modal
        document.getElementById('modal-titulo').textContent = libro.titulo;
        document.getElementById('modal-autor').textContent = libro.autor;
        document.getElementById('modal-editorial').innerHTML = '<i class="fas fa-building"></i> ' + (libro.editorial || 'N/A');
        
        // Cuidado con 'año' que puede venir como 'a\u00f1o' en JSON. Accedemos por clave dinámica si hace falta
        const anio = libro.año || libro.anio || libro.year || 'N/A';
        document.getElementById('modal-anio').innerHTML = '<i class="fas fa-calendar"></i> ' + anio;
        
        document.getElementById('modal-isbn').innerHTML = '<i class="fas fa-barcode"></i> ' + (libro.isbn || 'N/A');
        document.getElementById('modal-descripcion').textContent = libro.descripcion || 'Sin descripción disponible.';
        
        // Formatear precio
        const precioNum = parseFloat(libro.precio);
        document.getElementById('modal-precio').textContent = precioNum.toLocaleString('es-ES', { minimumFractionDigits: 2 }) + ' €';

        // Imagen con fallback
        let urlImg = 'https://via.placeholder.com/240x250.png?text=Libro';
        if (libro.isbn) {
            urlImg = 'https://covers.openlibrary.org/b/isbn/' + libro.isbn + '-L.jpg?default=false'; 
        }
        
        const imgElement = document.getElementById('modal-img');
        imgElement.src = urlImg;
        // Reiniciamos el evento de error para que no haga bucle infinito si el placeholder falla
        imgElement.onerror = function() {
            this.onerror = null; 
            this.src = 'https://via.placeholder.com/240x250.png?text=Sin+Imagen';
        };

        // 3. Configurar los botones del modal
        const btnAdd = document.getElementById('modal-btn-add');
        const btnBuy = document.getElementById('modal-btn-buy');

        // Limpiamos eventos anteriores clonando el botón (truco rápido)
        // o simplemente reasignamos el onclick
        btnAdd.onclick = function() {
            agregarAlCarrito(idLibro, libro.titulo);
        };

        btnBuy.href = 'carrito.php?accion=agregar&id=' + idLibro;

        // 4. Mostrar el modal
        document.getElementById('libroModal').style.display = "block";
        document.body.style.overflow = "hidden"; // Evitar scroll de fondo
    }

    function cerrarModal() {
        document.getElementById('libroModal').style.display = "none";
        document.body.style.overflow = "auto"; // Restaurar scroll
    }

    // Cerrar si se hace clic fuera del contenido
    window.onclick = function(event) {
        const modal = document.getElementById('libroModal');
        if (event.target == modal) {
            cerrarModal();
        }
    }

    // --- LÓGICA DEL CARRITO (AJAX) ---

    function agregarAlCarrito(idLibro, tituloLibro) {
        fetch('carrito.php?accion=agregar&id=' + idLibro)
            .then(response => {
                if (response.ok) {
                    mostrarNotificacion(tituloLibro);
                    actualizarContadorNavbar();
                } else {
                    console.error('Error al añadir al carrito');
                }
            })
            .catch(error => console.error('Error de red:', error));
    }

    function mostrarNotificacion(titulo) {
        var x = document.getElementById("toast-notification");
        x.innerHTML = '<i class="fas fa-check"></i> ' + titulo + ' añadido al carrito';
        x.className = "show";
        setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
    }

    function actualizarContadorNavbar() {
        var contador = document.getElementById("nav-carrito-contador");
        if (contador) {
            var valorActual = parseInt(contador.innerText);
            if (isNaN(valorActual)) valorActual = 0;
            var nuevoValor = valorActual + 1;
            contador.innerText = nuevoValor;
            contador.style.display = "inline-block"; 
        }
    }
</script>

<?php
include_once __DIR__ . '/../src/templates/footer.php';
?>