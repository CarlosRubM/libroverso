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

$mensaje = '';
$tipo_mensaje = '';

// =================================================================
// 3. LÓGICA POST: ACTUALIZACIÓN MASIVA
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    if ($_POST['accion'] === 'actualizar_masivo') {
        $estados_recibidos = $_POST['estados'] ?? [];
        $actualizados = 0;
        $errores = 0;
        
        // Lista blanca de estados permitidos
        $estados_permitidos = ['pendiente', 'en_transito', 'completado', 'cancelado'];

        if (!empty($estados_recibidos)) {
            try {
                // Preparamos la consulta una sola vez para ser eficientes
                $stmt_update = $pdo->prepare("UPDATE pedido SET estado = ? WHERE id_pedido = ?");

                foreach ($estados_recibidos as $id_pedido => $nuevo_estado) {
                    $id_pedido = (int)$id_pedido; // Asegurar entero
                    
                    if ($id_pedido && in_array($nuevo_estado, $estados_permitidos)) {
                        // Ejecutamos la actualización para cada pedido
                        // (Podríamos optimizar comprobando si el estado cambió antes de actualizar, 
                        // pero hacer el update directo es seguro y rápido para este volumen)
                        if ($stmt_update->execute([$nuevo_estado, $id_pedido])) {
                            // rowCount() nos dice si realmente se modificó algo, 
                            // pero execute() devuelve true si la consulta fue válida.
                            // Contamos como éxito si la consulta no falló.
                            $actualizados++;
                        } else {
                            $errores++;
                        }
                    }
                }
                
                $mensaje = "Se han procesado los cambios correctamente.";
                $tipo_mensaje = 'success';

            } catch (PDOException $e) {
                $mensaje = "Error al actualizar masivamente: " . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
}

// =================================================================
// 4. OBTENER LISTADO DE PEDIDOS
// =================================================================
$sql = "SELECT p.*, u.nombre as nombre_cliente, u.email 
        FROM pedido p 
        JOIN usuarios u ON p.id_cliente = u.id_usuario 
        ORDER BY p.fecha DESC";

try {
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar pedidos: " . $e->getMessage();
    $tipo_mensaje = 'error';
    $pedidos = [];
}
?>

<style>
    .admin-container { max-width: 1200px; margin: 30px auto 80px auto; padding: 0 20px; } /* Margin bottom extra para el botón flotante */
    
    /* Panel */
    .table-panel { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
    
    /* Tabla */
    .table-responsive { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    .admin-table th, .admin-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
    .admin-table th { background-color: #f8f9fa; font-weight: bold; color: #333; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px; }
    .admin-table tr:hover { background-color: #f9f9f9; }

    /* Badges de Estado Visual (Con Texto) */
    .badge-text { 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-size: 0.75em; 
        font-weight: bold; 
        text-transform: uppercase; 
        color: white; 
        white-space: nowrap;
    }
    .status-pendiente { background-color: #ffc107; color: #333; } /* Texto oscuro para amarillo */
    .status-en_transito { background-color: #17a2b8; }
    .status-completado { background-color: #28a745; }
    .status-cancelado { background-color: #dc3545; }

    /* Selectores */
    .status-select { 
        padding: 6px; 
        border-radius: 4px; 
        border: 1px solid #ddd; 
        font-size: 0.9em; 
        cursor: pointer; 
        background-color: white;
        max-width: 140px;
    }
    /* Colorear el borde del select según la opción elegida (Visual enhancement) */
    .status-select:focus { border-color: #007bff; outline: none; }

    /* Barra Flotante de Guardado */
    .save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background-color: white;
        padding: 15px 20px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        display: flex;
        justify-content: flex-end;
        align-items: center;
        z-index: 1000;
        border-top: 1px solid #eee;
    }
    
    .btn-save-all {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        font-weight: bold;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.3s, transform 0.1s;
        box-shadow: 0 4px 6px rgba(0,123,255,0.2);
    }
    .btn-save-all:hover { background-color: #0056b3; transform: translateY(-1px); }
    .btn-save-all:active { transform: translateY(1px); }

    /* Mensajes */
    .msg-box { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div class="admin-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1><i class="fas fa-dolly"></i> Gestión de Pedidos</h1>
        <a href="index.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="msg-box <?php echo ($tipo_mensaje == 'success') ? 'msg-success' : 'msg-error'; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <div class="table-panel">
        <?php if (empty($pedidos)): ?>
            <p style="text-align: center; color: #666; padding: 20px;">No hay pedidos registrados.</p>
        <?php else: ?>
            
            <!-- FORMULARIO MASIVO QUE ENVUELVE LA TABLA -->
            <form method="POST" action="ver_pedidos.php" id="form-pedidos">
                <input type="hidden" name="accion" value="actualizar_masivo">
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Total</th>
                                <th style="width: 300px;">Estado Actual &rarr; Nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php 
                                    // Determinar clase para el badge
                                    $estado_class = 'status-' . $p['estado'];
                                    $estado_texto = str_replace('_', ' ', strtoupper($p['estado']));
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $p['id_pedido']; ?></strong></td>
                                    
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($p['fecha'])); ?><br>
                                        <small style="color:#888;"><?php echo date('H:i', strtotime($p['fecha'])); ?></small>
                                    </td>
                                    
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['nombre_cliente']); ?></strong><br>
                                        <small style="color:#666;"><?php echo htmlspecialchars($p['email']); ?></small>
                                    </td>
                                    
                                    <td style="font-weight: bold;"><?php echo number_format($p['total'], 2, ',', '.'); ?> €</td>
                                    
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <!-- Etiqueta Visual del Estado Actual -->
                                            <span class="badge-text <?php echo $estado_class; ?>">
                                                <?php echo $estado_texto; ?>
                                            </span>
                                            
                                            <span style="color: #999; font-size: 1.2em;">&rsaquo;</span>

                                            <!-- Selector para cambiar -->
                                            <select name="estados[<?php echo $p['id_pedido']; ?>]" class="status-select">
                                                <option value="pendiente" <?php echo ($p['estado']=='pendiente')?'selected':''; ?>>Pendiente</option>
                                                <option value="en_transito" <?php echo ($p['estado']=='en_transito')?'selected':''; ?>>En Tránsito</option>
                                                <option value="completado" <?php echo ($p['estado']=='completado')?'selected':''; ?>>Completado</option>
                                                <option value="cancelado" <?php echo ($p['estado']=='cancelado')?'selected':''; ?>>Cancelado</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- BARRA INFERIOR FLOTANTE -->
                <div class="save-bar">
                    <div style="margin-right: auto; color: #666; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> Modifica los selectores y guarda para aplicar cambios.
                    </div>
                    <button type="submit" class="btn-save-all">
                        <i class="fas fa-save"></i> Guardar Todos los Cambios
                    </button>
                </div>

            </form>

        <?php endif; ?>
    </div>
</div>

<!-- Script pequeño para resaltar cambios visualmente (Opcional) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.status-select');
    
    selects.forEach(select => {
        // Guardar valor original
        select.dataset.original = select.value;
        
        select.addEventListener('change', function() {
            // Cambiar color de fondo si se ha modificado
            if (this.value !== this.dataset.original) {
                this.style.backgroundColor = '#fff3cd'; // Amarillo claro
                this.style.borderColor = '#ffc107';
            } else {
                this.style.backgroundColor = 'white';
                this.style.borderColor = '#ddd';
            }
        });
    });
});
</script>

<?php
include_once __DIR__ . '/../../src/templates/footer.php';
?>