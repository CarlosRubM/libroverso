<?php
session_start();
require_once __DIR__ . '/../src/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_pedido = filter_input(INPUT_GET, 'id_pedido', FILTER_VALIDATE_INT);

if ($id_pedido) {
    // Actualizamos el estado del pedido a 'completado'
    // Asumimos que si llega aquí es porque PayPal confirmó el pago
    $sql = "UPDATE pedido SET estado = 'pendiente' WHERE id_pedido = ? AND id_cliente = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_pedido, $_SESSION['user_id']]);
}

include_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container" style="max-width: 600px; margin: 50px auto; text-align: center;">
    <div style="background: #d4edda; padding: 40px; border-radius: 10px; color: #155724; border: 1px solid #c3e6cb;">
        <i class="fas fa-check-circle" style="font-size: 4em; margin-bottom: 20px;"></i>
        <h1>¡Pago Realizado con Éxito!</h1>
        <p>Gracias por tu compra. Tu pedido <strong>#<?php echo $id_pedido; ?></strong> ha sido procesado correctamente.</p>
        
        <br>
        <a href="mis_pedidos.php" class="btn-pagar" style="background: #155724; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Ver mis pedidos
        </a>
    </div>
</div>

<?php include_once __DIR__ . '/../src/templates/footer.php'; ?>