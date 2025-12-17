<?php
// Iniciamos la sesión en la cabecera si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica para contar items en el carrito (Solo útil para clientes)
$items_en_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    $items_en_carrito = array_sum($_SESSION['carrito']); 
}

// === LÓGICA DE RUTAS ===
// Detectamos si el script actual se está ejecutando desde la carpeta '/admin/'
// Esto permite que el header funcione bien tanto en la raíz como dentro del panel de admin.
$en_carpeta_admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

// Prefijos para los enlaces dependiendo de dónde estemos
$ruta_admin = $en_carpeta_admin ? '' : 'admin/';       // Si estoy en raíz, voy a 'admin/'. Si estoy en admin, me quedo ahí.
$ruta_raiz  = $en_carpeta_admin ? '../' : '';          // Si estoy en admin, subo '../'. Si estoy en raíz, nada.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libroverso</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Usamos la ruta raíz dinámica para el CSS también por seguridad -->
    <link rel="stylesheet" href="<?php echo $ruta_raiz; ?>css/style.css"> 
    
    <style>
        .nav-cart-link {
            position: relative; 
            display: inline-flex; 
            align-items: center;
            gap: 6px; 
        }
        .cart-counter {
            position: absolute;
            top: -6px;    
            left: 10px;   
            background-color: #dc3545; 
            color: white;
            border-radius: 50%; 
            padding: 2px 6px;
            font-size: 0.75rem; 
            font-weight: bold;
            line-height: 1; 
        }
        /* Estilo extra para el menú de admin para diferenciarlo */
        .nav-link-admin {
            color: #ffc107 !important; /* Amarillo para destacar */
        }
    </style>
</head>
<body>

<nav class="navbar">
    
    <!-- El logo siempre lleva al inicio (dependiendo del rol podría ir a dashboard o admin) -->
    <a href="<?php echo $ruta_raiz; ?>index.php" class="logo">Libroverso</a>
    
    <div class="nav-links">
        
        <?php if (isset($_SESSION['user_id'])): ?>
            
            <!-- ======================================================= -->
            <!-- MENÚ ESPECÍFICO PARA ADMINISTRADORES                   -->
            <!-- ======================================================= -->
            <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'administrador'): ?>
                
                <a href="<?php echo $ruta_admin; ?>index.php" class="nav-link-admin"><i class="fas fa-tachometer-alt"></i> Panel</a>
                <a href="<?php echo $ruta_admin; ?>registro.php"><i class="fas fa-users"></i> Usuarios</a>
                
                <!-- Enlaces futuros que pediste -->
                <!-- Apuntan a archivos dentro de la carpeta admin -->
                <a href="<?php echo $ruta_admin; ?>gestionar_libros.php"><i class="fas fa-book"></i> Libros</a>
                <a href="<?php echo $ruta_admin; ?>ver_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>

            <!-- ======================================================= -->
            <!-- MENÚ ESPECÍFICO PARA CLIENTES (O Usuarios normales)    -->
            <!-- ======================================================= -->
            <?php else: ?>

                <a href="<?php echo $ruta_raiz; ?>dashboard.php">Tienda</a>
                <a href="<?php echo $ruta_raiz; ?>mis_pedidos.php">Mis Pedidos</a>
                
                <!-- Carrito (Solo visible para clientes) -->
                <a href="<?php echo $ruta_raiz; ?>carrito.php" title="Ver carrito" class="nav-cart-link">
                    <i class="fas fa-shopping-cart"></i> Carrito
                    <span class="cart-counter" 
                          id="nav-carrito-contador" 
                          <?php if ($items_en_carrito <= 0): ?>style="display: none;"<?php endif; ?>>
                        <?php echo $items_en_carrito; ?>
                    </span>
                </a>

            <?php endif; ?>
            
            <!-- ======================================================= -->
            <!-- ELEMENTOS COMUNES (Saludo y Logout)                     -->
            <!-- ======================================================= -->
            <span class="nav-user-greet">Hola, <?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
            
            <!-- Usamos $ruta_raiz para que el logout funcione tanto si estás en /admin/ como en / -->
            <a href="<?php echo $ruta_raiz; ?>logout.php" class="nav-button">Cerrar Sesión</a>

        <?php else: ?>
            
            <!-- Menú para Visitantes (No logueados) -->
            <a href="index.php">Inicio</a>
            <a href="registro.php" class="nav-button-outline">Registrarse</a>
            <a href="login.php" class="nav-button">Login</a>
        
        <?php endif; ?>
        
    </div>
</nav>
<main>