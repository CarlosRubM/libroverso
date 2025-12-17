<?php
// mis_pedidos.php
session_start();
require_once __DIR__ . '/../src/config/config.php';

// 1. Verificar Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_cliente = $_SESSION['user_id'];

// =================================================================
// LÓGICA: CANCELAR PEDIDO (CAMBIAR ESTADO A 'CANCELADO')
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cancelar') {
    $id_pedido_a_cancelar = filter_input(INPUT_POST, 'id_pedido', FILTER_VALIDATE_INT);

    if ($id_pedido_a_cancelar) {
        // Verificar que el pedido pertenece al usuario Y está en estado 'pendiente'
        $sql_check = "SELECT id_pedido FROM pedido WHERE id_pedido = ? AND id_cliente = ? AND estado = 'pendiente'";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_pedido_a_cancelar, $id_cliente]);
        
        if ($stmt_check->fetch()) {
            // Actualizamos el estado a 'cancelado' (No borramos el registro)
            $sql_update = "UPDATE pedido SET estado = 'cancelado' WHERE id_pedido = ?";
            $stmt_update = $pdo->prepare($sql_update);
            
            if ($stmt_update->execute([$id_pedido_a_cancelar])) {
                $_SESSION['mensaje'] = "El pedido #$id_pedido_a_cancelar ha sido cancelado correctamente.";
            } else {
                $_SESSION['mensaje_error'] = "Error al intentar actualizar el estado del pedido.";
            }
        } else {
            $_SESSION['mensaje_error'] = "No se puede cancelar este pedido (ya no está pendiente o no existe).";
        }
    }
    
    header("Location: mis_pedidos.php");
    exit;
}

// 2. Obtener los pedidos ordenados por fecha
$sql = "SELECT * FROM pedido WHERE id_cliente = ? ORDER BY fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cliente]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <h1 style="margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <i class="fas fa-history"></i> Mis Pedidos
    </h1>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pedidos)): ?>
        <div style="text-align: center; padding: 50px; background: #f9f9f9; border-radius: 10px;">
            <i class="fas fa-box-open" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
            <p style="font-size: 1.2em; color: #666;">No tienes pedidos registrados todavía.</p>
            <a href="dashboard.php" style="display: inline-block; margin-top: 10px; color: #007bff; text-decoration: none; font-weight: bold;">Explorar Tienda</a>
        </div>
    <?php else: ?>
        
        <?php foreach ($pedidos as $pedido): ?>
            
            <?php 
                // Obtener detalles de los libros del pedido
                $sql_detalles = "
                    SELECT d.cantidad, d.precioUnitario, l.titulo, l.autor, l.isbn 
                    FROM detallepedido d
                    JOIN libros l ON d.id_libro = l.id_libro
                    WHERE d.id_pedido = ?
                ";
                $stmt_det = $pdo->prepare($sql_detalles);
                $stmt_det->execute([$pedido['id_pedido']]);
                $detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
                
                // === LÓGICA DE COLORES Y ESTILOS SEGÚN ESTADO ===
                $estado = $pedido['estado'];
                $color_fondo = '#6c757d'; // Gris (Default)
                $color_texto = '#fff';
                $texto_estado = ucfirst(str_replace('_', ' ', $estado)); // "en_transito" -> "En transito"

                switch ($estado) {
                    case 'pendiente':
                        $color_fondo = '#ffc107'; // Amarillo
                        $color_texto = '#333';    // Texto oscuro
                        break;
                    case 'en_transito':
                        $color_fondo = '#17a2b8'; // Azul Cian
                        $texto_estado = 'En Tránsito'; 
                        break;
                    case 'completado':
                        $color_fondo = '#28a745'; // Verde
                        break;
                    case 'cancelado':
                        $color_fondo = '#dc3545'; // Rojo
                        break;
                }
            ?>

            <!-- TARJETA DEL PEDIDO -->
            <div class="pedido-card" style="border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 30px; overflow: hidden; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                
                <!-- Cabecera -->
                <div class="pedido-header" style="background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span style="font-weight: bold; font-size: 1.1em; color: #333;">PEDIDO #<?php echo $pedido['id_pedido']; ?></span>
                        <div style="font-size: 0.9em; color: #666; margin-top: 4px;">
                            <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($pedido['fecha'])); ?> &nbsp;|&nbsp; 
                            <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($pedido['fecha'])); ?>
                        </div>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="text-align: right;">
                            <div style="font-size: 0.9em; color: #666;">Total</div>
                            <div style="font-weight: bold; font-size: 1.2em; color: #333;"><?php echo number_format($pedido['total'], 2, ',', '.'); ?> €</div>
                        </div>

                        <!-- BOTÓN CANCELAR (Solo visible si está pendiente) -->
                        <?php if ($pedido['estado'] === 'pendiente'): ?>
                            <form action="mis_pedidos.php" method="POST" onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?');">
                                <input type="hidden" name="accion" value="cancelar">
                                <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                                <button type="submit" style="background-color: transparent; color: #dc3545; border: 1px solid #dc3545; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9em; transition: all 0.3s;" onmouseover="this.style.backgroundColor='#dc3545'; this.style.color='white';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#dc3545';">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cuerpo -->
                <div class="pedido-body" style="padding: 20px;">
                    
                    <div style="margin-bottom: 15px;">
                        Estado: 
                        <span style="background-color: <?php echo $color_fondo; ?>; color: <?php echo $color_texto; ?>; padding: 5px 12px; border-radius: 15px; font-size: 0.85em; text-transform: uppercase; font-weight: bold; display: inline-block;">
                            <?php echo htmlspecialchars($texto_estado); ?>
                        </span>
                    </div>

                    <h4 style="margin-bottom: 10px; font-size: 1em; color: #555; border-bottom: 1px solid #eee; padding-bottom: 5px;">Productos</h4>
                    
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.95em;">
                        <tbody>
                            <?php foreach ($detalles as $item): ?>
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 10px 0;">
                                        <strong><?php echo htmlspecialchars($item['titulo']); ?></strong>
                                        <br>
                                        <span style="font-size: 0.85em; color: #888;"><?php echo htmlspecialchars($item['autor']); ?></span>
                                    </td>
                                    <td style="padding: 10px 0; text-align: center; color: #555;">
                                        <?php echo $item['cantidad']; ?> x <?php echo number_format($item['precioUnitario'], 2, ',', '.'); ?> €
                                    </td>
                                    <td style="padding: 10px 0; text-align: right; font-weight: 600;">
                                        <?php echo number_format($item['cantidad'] * $item['precioUnitario'], 2, ',', '.'); ?> €
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../src/templates/footer.php'; ?>