<?php
// 1. INICIAR LA SESIÓN (DEBE ser lo primero)
// Usamos session_status() para evitar iniciarla si ya está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICACIÓN DE SEGURIDAD
// Comprobamos si el usuario ha iniciado sesión Y si su rol es 'administrador'.
// (Asegúrate de que tu script de login guarde 'user_id' y 'rol' en la $_SESSION)
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    
    // Si no cumple las condiciones, lo redirigimos a la página de login
    // Puedes cambiar 'login.php' por la página que prefieras (ej. 'index.php')
    header('Location: /../../login.php');
    
    // Detenemos la ejecución del script inmediatamente
    exit;
}

// --- Si el script llega hasta aquí, el usuario ES un administrador ---

// Incluimos la configuración de la base de datos
// La ruta es relativa desde 'public/registro.php' hacia 'src/config/config.php'
require_once __DIR__ . '/../../src/config/config.php';

$mensaje = ''; // Variable para mostrar mensajes al usuario

// Verificamos si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger y sanitizar los datos del formulario
    // La función htmlspecialchars() previene ataques XSS
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $password = $_POST['password']; // La contraseña no se sanitiza con htmlspecialchars, se hashea
    $rol = htmlspecialchars(trim($_POST['rol']));

    // 2. Validaciones básicas
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password) || empty($rol)) {
        $mensaje = '<p class="error">Por favor, rellena todos los campos.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = '<p class="error">El formato del email no es válido.</p>';
    } elseif (!in_array($rol, ['cliente', 'administrador'])) { // Validación de seguridad para el rol
        $mensaje = '<p class="error">El rol seleccionado no es válido.</p>';
    } else {
        // 3. Hashear la contraseña (¡SUPER IMPORTANTE!)
        // password_hash() es la función recomendada por PHP para esto
        $password_hasheada = password_hash($password, PASSWORD_DEFAULT);

        try {
            // 4. Preparar la consulta SQL para insertar datos
            // Usamos sentencias preparadas para prevenir inyecciones SQL
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password, rol) VALUES (:nombre, :email, :telefono, :password, :rol)");

            // 5. Asignar los valores a los parámetros
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telefono', $telefono);
            $stmt->bindParam(':password', $password_hasheada);
            $stmt->bindParam(':rol', $rol);

            // 6. Ejecutar la consulta
            if ($stmt->execute()) {
                $mensaje = '<p class="success">¡Registro de usuario exitoso!</p>';
                // Ya no es necesario redirigir, el admin se queda en la página
            } else {
                $mensaje = '<p class="error">Error al registrar el usuario.</p>';
            }
        } catch (PDOException $e) {
            // Capturar errores de la base de datos (ej. email duplicado si es UNIQUE)
            if ($e->getCode() == 23000) { // Código de error para entrada duplicada
                 $mensaje = '<p class="error">El email ya está registrado.</p>';
            } else {
                 $mensaje = '<p class="error">Error en la base de datos: ' . $e->getMessage() . '</p>';
            }
        }
    }
}

// Incluimos la cabecera (header) de nuestra plantilla
include_once __DIR__ . '/../../src/templates/header.php';
?>



<div class="container">
    <!-- Título cambiado para reflejar que es una herramienta de admin -->
    <h2>Registro de Usuarios (Panel de Admin)</h2>

    <?php echo $mensaje; // Mostramos cualquier mensaje (éxito o error) ?>

    <form action="registro.php" method="POST">
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="telefono">Teléfono:</label>
            <input type="text" id="telefono" name="telefono">
        </div>

        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="rol">Tipo de cuenta:</label>
                <select id="rol" name="rol" required>
                    <option value="cliente" selected>Cliente</option> 
                    <option value="administrador">Administrador</option>
                </select>
        </div>

        <div class="form-group">
            <input type="submit" value="Registrar Usuario">
        </div>
    </form>
</div>

<?php
// Incluimos el pie de página (footer)
include_once __DIR__ . '/../../src/templates/footer.php';
?>