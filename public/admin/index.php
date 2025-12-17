<?php
// 1. Iniciar la sesión
session_start();

// VERIFICAR SI ESTÁ LOGUEADO Y SI ES ADMINISTRADOR
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'administrador') {
    // Si no es admin, lo expulsamos
    // Ajusta la ruta si tu login está en otro lugar
    header('Location: ../login.php'); 
    exit;
}

// Incluimos la configuración (ajusta la ruta según tu estructura de carpetas)
// Asumiendo que estamos en /admin/ y src está en ../src/ o ../../src/
require_once __DIR__ . '/../../src/config/config.php';

// Incluimos el header global (o crea uno específico para admin si prefieres)
include_once __DIR__ . '/../../src/templates/header.php';
?>

<!-- === ESTILOS ESPECÍFICOS DEL PANEL ADMIN === -->
<style>
    .admin-dashboard {
        max-width: 800px;
        margin: 40px auto;
        padding: 20px;
    }

    .welcome-message {
        text-align: center;
        margin-bottom: 40px;
    }

    .welcome-message h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 10px;
    }

    .welcome-message p {
        color: #666;
        font-size: 1.2rem;
    }

    /* Grid para los botones de acción */
    .admin-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    /* Estilo de los botones grandes */
    .admin-button {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        background-color: white;
        border: 1px solid #eee;
        border-radius: 10px;
        text-decoration: none;
        color: #333;
        font-weight: bold;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .admin-button i {
        font-size: 3rem;
        margin-bottom: 15px;
        color: #007bff; /* Color principal iconos */
    }

    .admin-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        border-color: #007bff;
    }

    /* Botón de Cerrar Sesión (Estilo Danger) */
    .admin-button.danger {
        border-color: #ffcccc;
        background-color: #fff5f5;
    }
    .admin-button.danger i {
        color: #dc3545;
    }
    .admin-button.danger:hover {
        background-color: #dc3545;
        color: white;
        border-color: #dc3545;
    }
    .admin-button.danger:hover i {
        color: white;
    }
</style>

<div class="admin-dashboard">

    <div class="welcome-message">
        <h1><i class="fas fa-user-shield"></i> Panel de Admin</h1>
        <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></strong>. ¿Qué deseas gestionar hoy?</p>
    </div>

    <!-- SECCIÓN DE ACCIONES -->
    <div class="admin-actions">
        
        <!-- Botón 1: Gestionar Usuarios (Va a registro.php en la carpeta admin) -->
        <a href="registro.php" class="admin-button">
            <i class="fas fa-users-cog"></i>
            <span>Gestionar Usuarios</span>
        </a>

        <!-- Botón 2: Gestionar Libros (Activo) -->
        
        <a href="gestionar_libros.php" class="admin-button">
            <i class="fas fa-book"></i>
            <span>Gestionar Libros</span>
        </a>

        <!-- Botón 3: Ver Pedidos (Futuro) -->
        <a href="ver_pedidos.php" class="admin-button">
            <i class="fas fa-clipboard-list"></i>
            <span>Ver Pedidos</span>
        </a>

        <!-- Botón 4: Cerrar Sesión -->
        <a href="../../logout.php" class="admin-button danger">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>

</div>

<?php
include_once __DIR__ . '/../../src/templates/footer.php';
?>