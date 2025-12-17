<?php
// 1. Iniciar sesión y seguridad
session_start();

// Verificar si es administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

// 2. Configuración y Header
require_once __DIR__ . '/../../src/config/config.php';
include_once __DIR__ . '/../../src/templates/header.php';

// Variables para el formulario (por defecto vacías)
$id_libro = '';
$isbn = '';
$titulo = '';
$autor = '';
$precio = '';
$editorial = '';
$anio = '';
$descripcion = '';
$modo_edicion = false;

$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'error'

// =================================================================
// 3. LÓGICA DE POST (CREAR, EDITAR, BORRAR)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recogemos la acción
    $accion = $_POST['accion'] ?? '';

    // Datos comunes del formulario
    $p_isbn = trim($_POST['isbn'] ?? '');
    $p_titulo = trim($_POST['titulo'] ?? '');
    $p_autor = trim($_POST['autor'] ?? '');
    $p_precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $p_editorial = trim($_POST['editorial'] ?? '');
    $p_anio = filter_input(INPUT_POST, 'anio', FILTER_VALIDATE_INT);
    $p_descripcion = trim($_POST['descripcion'] ?? '');
    $p_id = filter_input(INPUT_POST, 'id_libro', FILTER_VALIDATE_INT);

    try {
        if ($accion === 'borrar') {
            // --- BORRAR ---
            if ($p_id) {
                $stmt = $pdo->prepare("DELETE FROM libros WHERE id_libro = ?");
                $stmt->execute([$p_id]);
                $mensaje = "Libro eliminado correctamente.";
                $tipo_mensaje = "success";
            }
        } 
        elseif ($accion === 'guardar') {
            
            // Validaciones básicas
            if (empty($p_isbn) || empty($p_titulo) || !$p_precio) {
                throw new Exception("El ISBN, Título y Precio son obligatorios.");
            }

            if ($p_id) {
                // --- EDITAR (UPDATE) ---
                $sql = "UPDATE libros SET isbn=?, titulo=?, autor=?, precio=?, editorial=?, año=?, descripcion=? WHERE id_libro=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$p_isbn, $p_titulo, $p_autor, $p_precio, $p_editorial, $p_anio, $p_descripcion, $p_id]);
                $mensaje = "Libro actualizado correctamente.";
            } else {
                // --- CREAR (INSERT) ---
                $sql = "INSERT INTO libros (isbn, titulo, autor, precio, editorial, año, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$p_isbn, $p_titulo, $p_autor, $p_precio, $p_editorial, $p_anio, $p_descripcion]);
                $mensaje = "Libro creado correctamente.";
            }
            $tipo_mensaje = "success";
            
            // Limpiar formulario tras guardar
            if (!$p_id) {
                $p_isbn = $p_titulo = $p_autor = $p_editorial = $p_descripcion = '';
                $p_precio = $p_anio = '';
            }
        }

    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
        // Si hay error SQL (ej: ISBN duplicado)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $mensaje = "Error: Ese ISBN ya está registrado en otro libro.";
        }
    }
}

// =================================================================
// 4. LÓGICA GET (CARGAR DATOS PARA EDITAR)
// =================================================================
if (isset($_GET['editar'])) {
    $id_editar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($id_editar) {
        $stmt = $pdo->prepare("SELECT * FROM libros WHERE id_libro = ?");
        $stmt->execute([$id_editar]);
        $libro_edit = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($libro_edit) {
            $modo_edicion = true;
            $id_libro = $libro_edit['id_libro'];
            $isbn = $libro_edit['isbn'];
            $titulo = $libro_edit['titulo'];
            $autor = $libro_edit['autor'];
            $precio = $libro_edit['precio'];
            $editorial = $libro_edit['editorial'];
            $anio = $libro_edit['año'];
            $descripcion = $libro_edit['descripcion'];
        }
    }
}

// =================================================================
// 5. OBTENER LISTA COMPLETA DE LIBROS
// =================================================================
$stmt_lista = $pdo->query("SELECT * FROM libros ORDER BY id_libro DESC");
$lista_libros = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .admin-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
    
    /* Panel Formulario */
    .form-panel { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 40px; border: 1px solid #eee; }
    .form-title { margin-top: 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; color: #333; }
    
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 0.9em; }
    .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
    .form-group textarea { resize: vertical; min-height: 80px; }
    
    .btn-submit { background: #28a745; color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: background 0.3s; }
    .btn-submit:hover { background: #218838; }
    
    .btn-cancel { background: #6c757d; color: white; text-decoration: none; padding: 12px 25px; border-radius: 4px; display: inline-block; font-weight: bold; }
    .btn-cancel:hover { background: #5a6268; }

    /* Tabla */
    .table-responsive { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
    .admin-table th, .admin-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
    .admin-table th { background-color: #f8f9fa; font-weight: bold; color: #333; }
    .admin-table tr:hover { background-color: #f1f1f1; }
    
    .action-btn { padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85em; margin-right: 5px; color: white; display: inline-block; }
    .btn-edit { background-color: #ffc107; color: #333; }
    .btn-delete { background-color: #dc3545; }
    
    /* Mensajes */
    .msg-box { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div class="admin-container">
    
    <!-- Encabezado con botón volver -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1><i class="fas fa-book"></i> Gestión de Libros</h1>
        <a href="index.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </div>

    <!-- Mensajes de Feedback -->
    <?php if ($mensaje): ?>
        <div class="msg-box <?php echo ($tipo_mensaje == 'success') ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO DE ALTA / EDICIÓN -->
    <div class="form-panel">
        <h2 class="form-title">
            <?php echo $modo_edicion ? '<i class="fas fa-edit"></i> Editar Libro' : '<i class="fas fa-plus-circle"></i> Añadir Nuevo Libro'; ?>
        </h2>

        <form method="POST" action="gestionar_libros.php">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id_libro" value="<?php echo htmlspecialchars($id_libro); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" required value="<?php echo htmlspecialchars($titulo); ?>" placeholder="Ej: Don Quijote">
                </div>
                <div class="form-group">
                    <label>Autor *</label>
                    <input type="text" name="autor" required value="<?php echo htmlspecialchars($autor); ?>" placeholder="Ej: Cervantes">
                </div>
                <div class="form-group">
                    <label>ISBN * (10 o 13 dígitos)</label>
                    <input type="text" name="isbn" required value="<?php echo htmlspecialchars($isbn); ?>" placeholder="Sin guiones">
                </div>
                <div class="form-group">
                    <label>Precio (€) *</label>
                    <input type="number" step="0.01" name="precio" required value="<?php echo htmlspecialchars($precio); ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Editorial</label>
                    <input type="text" name="editorial" value="<?php echo htmlspecialchars($editorial); ?>">
                </div>
                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" value="<?php echo htmlspecialchars($anio); ?>" placeholder="Ej: 2024">
                </div>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion"><?php echo htmlspecialchars($descripcion); ?></textarea>
            </div>

            <div style="margin-top: 15px;">
                <button type="submit" class="btn-submit">
                    <?php echo $modo_edicion ? 'Actualizar Libro' : 'Guardar Libro'; ?>
                </button>
                
                <?php if ($modo_edicion): ?>
                    <a href="gestionar_libros.php" class="btn-cancel">Cancelar Edición</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- LISTADO DE LIBROS -->
    <div class="form-panel">
        <h2 class="form-title"><i class="fas fa-list"></i> Catálogo Actual (<?php echo count($lista_libros); ?>)</h2>
        
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Portada</th>
                        <th>Título / Autor</th>
                        <th>ISBN</th>
                        <th>Precio</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_libros as $l): ?>
                        <tr>
                            <td><?php echo $l['id_libro']; ?></td>
                            <td>
                                <!-- Miniatura de portada usando la lógica de ISBN -->
                                <?php 
                                    $img_url = 'https://via.placeholder.com/40x60.png?text=No+Img';
                                    if(!empty($l['isbn'])) {
                                        $img_url = 'https://covers.openlibrary.org/b/isbn/' . htmlspecialchars($l['isbn']) . '-S.jpg';
                                    }
                                ?>
                                <img src="<?php echo $img_url; ?>" alt="Portada" style="width: 40px; height: auto;">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($l['titulo']); ?></strong><br>
                                <span style="font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($l['autor']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($l['isbn']); ?></td>
                            <td style="font-weight: bold; color: #28a745;"><?php echo number_format($l['precio'], 2, ',', '.'); ?> €</td>
                            <td>
                                <!-- Botón Editar (Recarga la página rellenando el formulario) -->
                                <a href="gestionar_libros.php?editar=<?php echo $l['id_libro']; ?>" class="action-btn btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Botón Borrar (Formulario oculto para seguridad) -->
                                <form action="gestionar_libros.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este libro permanentemente?');">
                                    <input type="hidden" name="accion" value="borrar">
                                    <input type="hidden" name="id_libro" value="<?php echo $l['id_libro']; ?>">
                                    <button type="submit" class="action-btn btn-delete" title="Borrar" style="border:none; cursor:pointer;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
include_once __DIR__ . '/../../src/templates/footer.php';
?>