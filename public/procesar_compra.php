<?php
// procesar_compra.php
session_start();
require_once __DIR__ . '/../src/config/config.php'; 

// 1. Validaciones de seguridad
// CAMBIO: Quitamos "&& $_SERVER['REQUEST_METHOD'] === 'POST'" para permitir acceso desde enlace
if (!isset($_SESSION['user_id']) || empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit;
}

$id_cliente = $_SESSION['user_id'];
$ids_libros = array_keys($_SESSION['carrito']);

try {
    $pdo->beginTransaction();

    // A. Recalcular Total (Seguridad)
    $placeholders = implode(',', array_fill(0, count($ids_libros), '?'));
    $sql_precios = "SELECT id_libro, precio FROM libros WHERE id_libro IN ($placeholders)";
    
    $stmt = $pdo->prepare($sql_precios);
    $stmt->execute($ids_libros);
    $libros_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pedido = 0;
    $items_insertar = [];

    foreach ($libros_db as $libro) {
        $id = $libro['id_libro'];
        if (isset($_SESSION['carrito'][$id])) {
            $cantidad = $_SESSION['carrito'][$id];
            $precio = $libro['precio'];
            
            $total_pedido += $precio * $cantidad;

            $items_insertar[] = [
                'id_libro' => $id,
                'cantidad' => $cantidad,
                'precio' => $precio
            ];
        }
    }

    // B. Insertar PEDIDO (ESTADO: PENDIENTE)
    // Se guarda como 'pendiente' hasta que PayPal confirme el pago en el siguiente paso
    $sql_pedido = "INSERT INTO pedido (id_cliente, total, fecha, estado) VALUES (?, ?, NOW(), 'pendiente')";
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([$id_cliente, $total_pedido]);
    
    $id_pedido_generado = $pdo->lastInsertId();

    // C. Insertar DETALLEPEDIDO
    $sql_detalle = "INSERT INTO detallepedido (id_pedido, id_libro, cantidad, precioUnitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);

    foreach ($items_insertar as $item) {
        $stmt_detalle->execute([
            $id_pedido_generado,
            $item['id_libro'],
            $item['cantidad'],
            $item['precio']
        ]);
    }

    // D. FINALIZAR
    $pdo->commit();

    // Vaciamos el carrito (El pedido ya está registrado en BD)
    $_SESSION['carrito'] = [];

    // CAMBIO: REDIRECCIÓN A PASARELA DE PAGO INTERMEDIA
    // Enviamos al usuario a pagar.php con el ID del pedido recién creado
    header("Location: pagar.php?id_pedido=" . $id_pedido_generado);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al procesar el pedido: " . $e->getMessage());
}
?>