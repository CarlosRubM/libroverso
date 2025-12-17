<?php
session_start();
include_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container" style="max-width: 600px; margin: 50px auto; text-align: center;">
    <div style="background: #f8d7da; padding: 40px; border-radius: 10px; color: #721c24; border: 1px solid #f5c6cb;">
        <i class="fas fa-times-circle" style="font-size: 4em; margin-bottom: 20px;"></i>
        <h1>Pago Cancelado</h1>
        <p>El proceso de pago ha sido cancelado. Tu pedido sigue guardado como "Pendiente".</p>
        
        <br>
        <a href="mis_pedidos.php" style="background: #721c24; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Volver a mis pedidos
        </a>
    </div>
</div>

<?php include_once __DIR__ . '/../src/templates/footer.php'; ?>