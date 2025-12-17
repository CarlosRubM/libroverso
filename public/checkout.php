<?php
// checkout.php
session_start();

// 1. Verificar Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Configuración
require_once __DIR__ . '/../src/config/config.php';

// 3. Verificar que hay algo en el carrito
if (empty($_SESSION['carrito'])) {
    header('Location: carrito.php');
    exit;
}

// 4. Recalcular el total (Por seguridad, siempre desde el servidor)
$total_a_pagar = 0.00;
$ids_libros = array_keys($_SESSION['carrito']);
$placeholders = implode(',', array_fill(0, count($ids_libros), '?'));

$sql = "SELECT id_libro, precio FROM libros WHERE id_libro IN ($placeholders)";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids_libros);
    $libros_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($libros_db as $libro) {
        $id = $libro['id_libro'];
        if (isset($_SESSION['carrito'][$id])) {
            $cantidad = $_SESSION['carrito'][$id];
            $total_a_pagar += $libro['precio'] * $cantidad;
        }
    }
} catch (PDOException $e) {
    die("Error al calcular total: " . $e->getMessage());
}

include_once __DIR__ . '/../src/templates/header.php'; 
?>

<div class="checkout-container" style="max-width: 900px; margin: 40px auto; display: flex; gap: 30px; flex-wrap: wrap;">

    <div class="checkout-resumen" style="flex: 1; min-width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h3 style="margin-top: 0;">Resumen del Pedido</h3>
        <hr>
        <p>Estás a punto de comprar <strong><?php echo array_sum($_SESSION['carrito']); ?></strong> artículos.</p>
        
        <div style="display: flex; justify-content: space-between; font-size: 1.4em; margin-top: 20px;">
            <span>Total a Pagar:</span>
            <span style="font-weight: bold; color: #333;"><?php echo number_format($total_a_pagar, 2, ',', '.'); ?> €</span>
        </div>
        
        <div style="margin-top: 30px; font-size: 0.9em; color: #666;">
            <p><i class="fas fa-lock"></i> Pago 100% Seguro</p>
            <p><i class="fas fa-shield-alt"></i> Encriptación SSL de 256-bits</p>
        </div>
        
        <a href="carrito.php" style="display: block; margin-top: 20px; text-align: center; color: #007bff; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Volver al carrito
        </a>
    </div>

    <div class="checkout-form" style="flex: 2; min-width: 300px; padding: 20px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h2 style="margin-top: 0; margin-bottom: 20px;"><i class="far fa-credit-card"></i> Detalles de Pago</h2>
        
        <form action="procesar_compra.php" method="POST" id="form-pago">
            
            <input type="hidden" name="total_procesado" value="<?php echo $total_a_pagar; ?>">

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre del Titular</label>
                <input type="text" name="titular" required placeholder="Como aparece en la tarjeta" 
                       style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Número de Tarjeta</label>
                <div style="position: relative;">
                    <input type="text" name="numero_tarjeta" required placeholder="0000 0000 0000 0000" maxlength="19" pattern="[0-9\s]{13,19}"
                           style="width: 100%; padding: 10px; padding-left: 40px; border: 1px solid #ccc; border-radius: 4px;">
                    <i class="fas fa-credit-card" style="position: absolute; left: 12px; top: 12px; color: #999;"></i>
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Caducidad</label>
                    <input type="text" name="caducidad" required placeholder="MM/YY" maxlength="5"
                           style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">CVV <i class="fas fa-question-circle" title="3 dígitos al reverso" style="color:#999; cursor:help;"></i></label>
                    <input type="password" name="cvv" required placeholder="123" maxlength="4" pattern="[0-9]{3,4}"
                           style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>

            <hr style="margin: 25px 0; border: 0; border-top: 1px solid #eee;">

            <button type="submit" class="btn-pagar" 
                    style="width: 100%; background-color: #28a745; color: white; padding: 15px; font-size: 1.1em; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                Pagar <?php echo number_format($total_a_pagar, 2, ',', '.'); ?> €
            </button>

        </form>
    </div>
</div>

<script>
// Script simple para formatear la tarjeta (espacios cada 4 números)
document.querySelector('input[name="numero_tarjeta"]').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, ''); // Eliminar todo lo que no sea dígito
    value = value.match(/.{1,4}/g)?.join(' ') || value; // Agrupar de 4 en 4
    e.target.value = value;
});

// Script simple para caducidad (añadir / automáticamente)
document.querySelector('input[name="caducidad"]').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});
</script>

<?php include_once __DIR__ . '/../src/templates/footer.php'; ?>