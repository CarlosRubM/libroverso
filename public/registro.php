<?php
// Incluimos la configuración de la base de datos
// La ruta es relativa desde 'public/registro.php' hacia 'src/config.php'
require_once __DIR__ . '/../src/config/config.php';

$mensaje = ''; // Variable para mostrar mensajes al usuario

// Verificamos si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recoger y sanitizar los datos del formulario
    // La función htmlspecialchars() previene ataques XSS
    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $email = htmlspecialchars(trim($_POST['email']));
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $password = $_POST['password']; // La contraseña no se sanitiza con htmlspecialchars, se hashea
    
    // --- CAMBIO ---
    // Asignamos el rol "cliente" automáticamente en lugar de cogerlo del POST
    $rol = 'cliente';

    // 2. Validaciones básicas
    // (Hemos quitado $rol de la comprobación)
    if (empty($nombre) || empty($email) || empty($telefono) || empty($password)) {
        $mensaje = '<p class="error">Por favor, rellena todos los campos.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = '<p class="error">El formato del email no es válido.</p>';
    } 
    // --- CAMBIO ---
    // Se ha eliminado el 'elseif' que comprobaba el rol, ya no es necesario
    else {
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
            $stmt->bindParam(':rol', $rol); // $rol aquí siempre será 'cliente'

            // 6. Ejecutar la consulta
            if ($stmt->execute()) {
                $mensaje = '<p class="success">¡Registro exitoso! Ya puedes iniciar sesión.</p>';
                // Opcional: Redirigir al usuario a la página de login
                // header('Location: login.php');
                // exit();
            } else {
                $mensaje = '<p class="error">Error al registrar el usuario.</p>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Código de error para entrada duplicada
                
                // Obtenemos el mensaje de error completo del driver
                $error_info_message = $e->errorInfo[2]; 

                // Buscamos pistas en el mensaje
                // Los nombres ('email', 'telefono') deben coincidir con el nombre de tu 
                // índice UNIQUE o el nombre de la columna en la BD.
                if (strpos($error_info_message, 'email') !== false) {
                    $mensaje = '<p class="error">El email ya está registrado.</p>';
                } elseif (strpos($error_info_message, 'telefono') !== false) {
                    $mensaje = '<p class="error">El teléfono ya está registrado.</p>';
                } else {
                    $mensaje = '<p class="error">Error de duplicado. Uno de los campos ya existe.</p>';
                }

            } else {
                $mensaje = '<p class="error">Error en la base de datos: ' . $e->getMessage() . '</p>';
            }
        }
    }
}

// Incluimos la cabecera (header) de nuestra plantilla
include_once __DIR__ . '/../src/templates/header.php';
?>

<div class="container">
    <h2>Registro de Clientes</h2>

    <?php echo $mensaje; // Mostramos cualquier mensaje (éxito o error) ?>

    <form action="registro.php" method="POST"> <!-- Asegúrate de que 'action' apunta a este mismo archivo -->
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
        
        <!-- --- CAMBIO ---
             Se ha eliminado el div.form-group que contenía el <select> para el ROL 
        -->

        <div class="form-group">
            <input type="submit" value="Registrarse">
        </div>
    </form>
</div>

<?php
// Incluimos el pie de página (footer)
include_once __DIR__ . '/../src/templates/footer.php';
?>