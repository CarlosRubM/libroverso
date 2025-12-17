<?php
session_start();
require_once __DIR__ . '/../src/config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_pedido = filter_input(INPUT_GET, 'id_pedido', FILTER_VALIDATE_INT);

if (!$id_pedido) {
    header('Location: mis_pedidos.php');
    exit;
}

// 1. Obtener datos del pedido para saber cuánto cobrar
$stmt = $pdo->prepare("SELECT total FROM pedido WHERE id_pedido = ? AND id_cliente = ?");
$stmt->execute([$id_pedido, $_SESSION['user_id']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("Pedido no encontrado.");
}

$total_pagar = $pedido['total'];

// 2. Configuración de URLs (AJÚSTALAS A TU RUTA LOCALHOST EXACTA)
// Si tu proyecto está en http://localhost/libroverso/public/ ...
$base_url = "http://libroverso.local"; 
$url_exito = $base_url . "/pago_exito.php?id_pedido=" . $id_pedido;
$url_cancelado = $base_url . "/pago_cancelado.php";

include_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container" style="max-width: 600px; margin: 50px auto; text-align: center;">
    
    <h1><i class="fab fa-paypal" style="color: #003087;"></i> Finalizar Pago</h1>
    <p>Estás a un paso de completar tu pedido <strong>#<?php echo $id_pedido; ?></strong>.</p>
    
    <div style="background: #f8f9fa; padding: 30px; border-radius: 10px; margin: 20px 0; border: 1px solid #ddd;">
        <h2 style="margin: 0; color: #333;">Total a Pagar: <?php echo number_format($total_pagar, 2); ?> €</h2>
    </div>

    <!-- 
        FORMULARIO DE PAYPAL SANDBOX (SEGÚN TU PDF)
        action apunta a sandbox.paypal.com
    -->
    <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
        
        <!-- Tipo de comando: _xclick es para botón de comprar ahora simple -->
        <input type="hidden" name="cmd" value="_xclick">
        
        <!-- CORREO DEL VENDEDOR (SANDBOX BUSINESS) -->
        <!-- ¡¡CAMBIA ESTO POR TU CORREO BUSINESS DE SANDBOX!! -->
        <input type="hidden" name="business" value="sb-y8glt48043319@business.example.com">
        
        <!-- Detalles del Producto -->
        <input type="hidden" name="item_name" value="Pedido #<?php echo $id_pedido; ?> - Libroverso">
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="amount" value="<?php echo $total_pagar; ?>">
        
        <!-- URLs de retorno (Donde vuelve el usuario tras pagar) -->
        <input type="hidden" name="return" value="<?php echo $url_exito; ?>">
        <input type="hidden" name="cancel_return" value="<?php echo $url_cancelado; ?>">
        
        <!-- Estética del botón -->
        <button type="submit" style="background: #ffc439; border: none; padding: 15px 30px; border-radius: 25px; cursor: pointer; transition: transform 0.2s;">
            <img src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/PP_logo_h_100x26.png" alt="PayPal">
        </button>
        
        <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
            Serás redirigido a PayPal Sandbox para completar el pago de forma segura.
        </p>
    </form>

</div>

<?php include_once __DIR__ . '/../src/templates/footer.php'; ?>