<?php

session_start();

// Configurar cabecera JSON inmediatamente
header('Content-Type: application/json');

// 1. Verificaciones de seguridad
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['carrito'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o inválida']);
    exit;
}

// 2. Obtener datos
$id_libro = filter_input(INPUT_POST, 'id_libro', FILTER_VALIDATE_INT);
$cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);

if ($id_libro === false || $cantidad === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos numéricos inválidos']);
    exit;
}

// 3. Lógica de actualización
try {
    if (isset($_SESSION['carrito'][$id_libro])) {
        
        if ($cantidad > 0 && $cantidad <= 99) {
            $_SESSION['carrito'][$id_libro] = $cantidad;
            $accion = 'actualizado';
        } else {
            // Si es 0 o negativo, lo quitamos (aunque visualmente el JS lo mostrará como 0 hasta recargar)
            unset($_SESSION['carrito'][$id_libro]);
            $accion = 'eliminado';
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Carrito actualizado correctamente',
            'accion' => $accion
        ]);

    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'El producto no existe en el carrito']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error servidor: ' . $e->getMessage()]);
}
exit;
?>