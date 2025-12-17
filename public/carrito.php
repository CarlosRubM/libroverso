<?php
// carrito.php
session_start();

// 1. Verificar Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Configuración y Helpers
require_once __DIR__ . '/../src/config/config.php'; 

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// =================================================================
// 3. LÓGICA DE ACCIONES GET (Agregar/Eliminar/Vaciar)
// =================================================================
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    switch ($accion) {
        case 'agregar':
            if ($id) {
                if (isset($_SESSION['carrito'][$id])) {
                    $_SESSION['carrito'][$id]++;
                } else {
                    $_SESSION['carrito'][$id] = 1;
                }
            }
            break;

        case 'eliminar':
            if ($id && isset($_SESSION['carrito'][$id])) {
                unset($_SESSION['carrito'][$id]);
            }
            break;

        case 'vaciar':
            $_SESSION['carrito'] = [];
            break;
    }

    header('Location: carrito.php');
    exit;
}

// 4. Obtener datos de la Base de Datos
$items_del_carrito = [];
$total_carrito = 0.00;
$error_db = '';

if (!empty($_SESSION['carrito'])) {
    $ids_libros = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($ids_libros), '?'));
    
    // Consulta para obtener detalles de los libros en el carrito
    $sql = "SELECT id_libro, titulo, autor, precio, isbn FROM libros WHERE id_libro IN ($placeholders)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids_libros);
        $libros_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($libros_db as $libro) {
            $id = $libro['id_libro'];
            
            if (!isset($_SESSION['carrito'][$id])) continue;

            $cantidad = $_SESSION['carrito'][$id];
            $subtotal = $libro['precio'] * $cantidad;

            $items_del_carrito[] = [
                'id' => $id,
                'titulo' => $libro['titulo'],
                'precio' => (float)$libro['precio'],
                'isbn' => $libro['isbn'],
                'autor' => $libro['autor'],
                'cantidad' => (int)$cantidad,
                'subtotal' => $subtotal
            ];
            $total_carrito += $subtotal;
        }
    } catch (PDOException $e) {
        $error_db = "Error BD: " . $e->getMessage();
    }
}

include_once __DIR__ . '/../src/templates/header.php'; 
?>

<div class="carrito-container">

    <div class="carrito-header">
        <h1><i class="fas fa-shopping-cart"></i> Tu Carrito de Compra</h1>
        <!-- Pequeño indicador de estado para el usuario (AJAX) -->
        <span id="estado-guardado" style="font-size: 0.9em; color: green; display: none;">
            <i class="fas fa-check"></i> Cambios guardados
        </span>
    </div>

    <?php if (!empty($error_db)): ?>
        <div class="error-db">
            <p style="color:red"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_db); ?></p>
        </div>

    <?php elseif (empty($items_del_carrito)): ?>
        <div class="carrito-vacio">
            <i class="fas fa-box-open" style="font-size: 3em; color: #ccc;"></i>
            <h2>Tu carrito está vacío</h2>
            <p>Parece que aún no has añadido ningún libro.</p>
            <br>
            <a href="dashboard.php" class="btn-seguir-comprando" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Explorar Libros</a>
        </div>

    <?php else: ?>

    <div class="carrito-layout" style="display: flex; gap: 20px;">
        
        <!-- COLUMNA IZQUIERDA: ITEMS -->
        <div class="carrito-items" style="flex: 2;">
            <?php foreach ($items_del_carrito as $item): ?>
                <?php 
                    $url_portada = 'https://via.placeholder.com/80x120?text=Sin+Imagen'; 
                    if (!empty($item['isbn'])) {
                        $url_portada = 'https://covers.openlibrary.org/b/isbn/' . htmlspecialchars($item['isbn']) . '-M.jpg';
                    }
                ?>

                <div class="carrito-item" style="display: flex; gap: 15px; border-bottom: 1px solid #eee; padding: 15px 0;">
                    
                    <div class="item-imagen">
                        <img src="<?php echo $url_portada; ?>" alt="Libro" style="width: 80px; height: auto;">
                    </div>

                    <div class="item-detalles" style="flex: 1;">
                        <h3><?php echo htmlspecialchars($item['titulo']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($item['autor']); ?></p>
                        <p><?php echo number_format($item['precio'], 2, ',', '.'); ?> € / ud.</p>
                    </div>

                    <div class="item-cantidad">
                        <label>Cant:</label>
                        <input 
                            type="number" 
                            value="<?php echo $item['cantidad']; ?>" 
                            min="0" max="99"
                            class="input-cantidad" 
                            style="width: 60px; text-align: center;"
                            data-precio-unitario="<?php echo $item['precio']; ?>"
                            data-id-libro="<?php echo $item['id']; ?>"
                        >
                    </div>

                    <div class="item-subtotal">
                        <span class="subtotal-precio" id="subtotal-display-<?php echo $item['id']; ?>" style="font-weight: bold;">
                            <?php echo number_format($item['subtotal'], 2, ',', '.'); ?> €
                        </span>
                    </div>

                    <div class="item-eliminar">
                        <a href="carrito.php?accion=eliminar&id=<?php echo $item['id']; ?>" 
                           onclick="return confirm('¿Eliminar producto?');" 
                           style="color: red;">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- BOTÓN VACIAR CARRITO (MOVIDO AQUÍ) -->
            <div style="margin-top: 20px; text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
                <a href="carrito.php?accion=vaciar" onclick="return confirm('¿Estás seguro de que quieres vaciar todo el carrito?');" 
                   style="color: #dc3545; text-decoration: none; font-size: 0.9em;">
                    <i class="fas fa-trash-alt"></i> Vaciar todo el carrito
                </a>
            </div>

        </div>

        <!-- COLUMNA DERECHA: RESUMEN -->
        <div class="carrito-resumen" style="flex: 1; background: #f8f9fa; padding: 25px; border-radius: 8px; height: fit-content; border: 1px solid #e9ecef;">
            <h3 style="margin-top: 0;">Resumen del Pedido</h3>
            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #ddd;">
            
            <div style="display: flex; justify-content: space-between; font-size: 1.3em; margin-bottom: 25px;">
                <span>Total:</span>
                <span class="precio-total" id="total-general-display" style="font-weight: bold; color: #333;">
                    <?php echo number_format($total_carrito, 2, ',', '.'); ?> €
                </span>
            </div>
            
            <!-- CONTENEDOR DE BOTONES (FLEX COLUMN) -->
            <div style="display: flex; flex-direction: column; gap: 12px;">
                
                <!-- 1. Botón Principal: Pagar con PayPal -->
                <!-- Quitamos checkout.php porque PayPal gestionará los datos -->
                <a href="procesar_compra.php" class="btn-pagar" 
                   style="display: block; width: 100%; padding: 12px; background: #ffc439; color: #333; text-align: center; text-decoration: none; border-radius: 50px; font-weight: bold; transition: background 0.3s; border: 1px solid #ffc439;">
                    <i class="fab fa-paypal"></i> Pagar con PayPal
                </a>

                <!-- 2. Botón Secundario: Seguir Comprando -->
                <a href="dashboard.php" 
                   style="display: block; width: 100%; padding: 10px; background: white; color: #007bff; border: 1px solid #007bff; text-align: center; text-decoration: none; border-radius: 5px; font-weight: 600; transition: all 0.3s;">
                    <i class="fas fa-arrow-left"></i> Seguir Comprando
                </a>
                
                <!-- Botón de vaciar carrito eliminado de aquí -->

            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- ======================================================= -->
<!-- SCRIPT DE ACTUALIZACIÓN EN TIEMPO REAL (AJAX MEJORADO)   -->
<!-- ======================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const URL_AJAX = 'actualizar_carrito_ajax.php'; 
    const TIEMPO_ESPERA = 500; 
    let timeoutGuardado = null;
    const estadoGuardado = document.getElementById('estado-guardado');

    const inputs = document.querySelectorAll('.input-cantidad');

    inputs.forEach(input => {
        
        // Función para actualizar visualmente
        const actualizarVisual = () => {
            const idLibro = input.dataset.idLibro;
            const precioUnitario = parseFloat(input.dataset.precioUnitario);
            let cantidad = parseInt(input.value);
            if (isNaN(cantidad)) cantidad = 0;

            const nuevoSubtotal = cantidad * precioUnitario;
            const spanSubtotal = document.getElementById(`subtotal-display-${idLibro}`);
            if(spanSubtotal) spanSubtotal.textContent = formatearEuros(nuevoSubtotal);

            recalcularTotalGeneral();
        };

        // Función para enviar al servidor
        const guardarDatos = () => {
            mostrarEstado('Guardando...', '#666');
            const idLibro = input.dataset.idLibro;
            let cantidad = parseInt(input.value);
            if (isNaN(cantidad)) cantidad = 0;
            enviarAlServidor(idLibro, cantidad);
        };

        // EVENTO 1: INPUT
        input.addEventListener('input', function() {
            actualizarVisual();
            clearTimeout(timeoutGuardado);
            timeoutGuardado = setTimeout(guardarDatos, TIEMPO_ESPERA);
        });

        // EVENTO 2: CHANGE
        input.addEventListener('change', function() {
            clearTimeout(timeoutGuardado); 
            guardarDatos(); 
        });
    });

    // --- Funciones Auxiliares ---

    function mostrarEstado(texto, color) {
        if(estadoGuardado) {
            estadoGuardado.style.display = 'inline';
            estadoGuardado.style.color = color;
            estadoGuardado.innerHTML = texto;
        }
    }

    function recalcularTotalGeneral() {
        let total = 0;
        document.querySelectorAll('.input-cantidad').forEach(inp => {
            const p = parseFloat(inp.dataset.precioUnitario);
            const c = parseInt(inp.value) || 0;
            total += p * c;
        });
        
        const spanTotal = document.getElementById('total-general-display');
        if(spanTotal) spanTotal.textContent = formatearEuros(total);
    }

    function formatearEuros(cantidad) {
        return cantidad.toLocaleString('es-ES', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        }) + ' €';
    }

    function enviarAlServidor(id, cantidad) {
        fetch(URL_AJAX, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_libro=${id}&cantidad=${cantidad}`,
            keepalive: true 
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                console.log(`Guardado OK: Libro ${id}`);
                mostrarEstado('<i class="fas fa-check"></i> Guardado', 'green');
                setTimeout(() => { 
                    if(estadoGuardado) estadoGuardado.style.display = 'none'; 
                }, 2000);
            }
        })
        .catch(err => {
            console.error('Error Red:', err);
            mostrarEstado('<i class="fas fa-times"></i> Error al guardar', 'red');
        });
    }
});
</script>

<?php 
include_once __DIR__ . '/../src/templates/footer.php'; 
?>