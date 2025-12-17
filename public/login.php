<?php
// 1. INICIAMOS LA SESIÓN
// session_start() debe ser lo primero en tu script, incluso antes de cualquier HTML.
session_start();

// 2. VERIFICAR SI EL USUARIO YA ESTÁ LOGUEADO
// --> CAMBIO 1: Verificamos también el rol para redirigir a la página correcta
if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol'])) {
    if ($_SESSION['user_rol'] === 'administrador') {
        header('Location: admin/index.php'); // Redirige a los administradores
    } else {
        header('Location: dashboard.php'); // Redirige a los clientes
    }
    exit;
} elseif (isset($_SESSION['user_id'])) {
    // Fallback por si la sesión es antigua y no tiene 'user_rol'
    header('Location: dashboard.php');
    exit;
}

// 3. INCLUIR LA CONFIGURACIÓN
require_once __DIR__ . '/../src/config/config.php';

$mensaje = '';

// 4. VERIFICAR SI EL FORMULARIO FUE ENVIADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validar que no estén vacíos
    if (empty($email) || empty($password)) {
        $mensaje = '<p class="error">Por favor, rellena todos los campos.</p>';
    } else {
        try {
            // 5. BUSCAR AL USUARIO POR EMAIL
            // Tu consulta "SELECT *" ya trae la columna 'rol' que necesitamos
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();

            // 6. VERIFICAR SI EL USUARIO EXISTE Y LA CONTRASEÑA ES CORRECTA
            if ($user && password_verify($password, $user['password'])) {
                
                $user_id = $user['id_usuario'];
                
                // ========== NUEVO: GESTIÓN DEL HISTORIAL DE LOGIN ==========
                
                // 6.1. Obtener el ÚLTIMO LOGIN antes de guardar el nuevo
                try {
                    $stmt_ultimo = $pdo->prepare("
                        SELECT fecha, ip_usuario 
                        FROM historial_login 
                        WHERE id_usuario = ? 
                        ORDER BY fecha DESC 
                        LIMIT 1
                    ");
                    $stmt_ultimo->execute([$user_id]);
                    $ultimo_login = $stmt_ultimo->fetch(PDO::FETCH_ASSOC);
                    
                    // Guardar en cookie el último login (antes del actual)
                    if ($ultimo_login) {
                        $cookie_data = json_encode([
                            'fecha' => $ultimo_login['fecha'],
                            'ip' => $ultimo_login['ip_usuario']
                        ]);
                        // Cookie válida por 30 días
                        setcookie('ultimo_login', $cookie_data, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                } catch (PDOException $e) {
                    // Error silencioso, no afecta el login
                    error_log("Error al obtener último login: " . $e->getMessage());
                }
                
                // 6.2. Registrar el LOGIN ACTUAL en la base de datos
                try {
                    $ip_actual = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
                    
                    $stmt_historial = $pdo->prepare("
                        INSERT INTO historial_login (id_usuario, fecha, ip_usuario) 
                        VALUES (?, NOW(), ?)
                    ");
                    $stmt_historial->execute([$user_id, $ip_actual]);
                    
                } catch (PDOException $e) {
                    // Error silencioso, no afecta el login
                    error_log("Error al guardar historial de login: " . $e->getMessage());
                }
                
                // 6.3. OPCIONAL: Mantener solo los últimos 10 registros por usuario
                try {
                    $stmt_limpiar = $pdo->prepare("
                        DELETE FROM historial_login 
                        WHERE id_usuario = ? 
                        AND id_historial NOT IN (
                            SELECT id_historial FROM (
                                SELECT id_historial 
                                FROM historial_login 
                                WHERE id_usuario = ? 
                                ORDER BY fecha DESC 
                                LIMIT 10
                            ) AS ultimos
                        )
                    ");
                    $stmt_limpiar->execute([$user_id, $user_id]);
                } catch (PDOException $e) {
                    // Error silencioso
                    error_log("Error al limpiar historial antiguo: " . $e->getMessage());
                }
                
                // ========== FIN GESTIÓN DEL HISTORIAL ==========
                
                // 7. INICIAR LA SESIÓN
                session_regenerate_id(true); // Regenera el ID de sesión por seguridad
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                
                // --> CAMBIO 2: Guardamos el ROL del usuario en la sesión
                $_SESSION['user_rol'] = $user['rol'];

                // 8. REDIRIGIR SEGÚN EL ROL
                // --> CAMBIO 3: Lógica de redirección basada en el rol
                if ($user['rol'] === 'administrador') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit; // Importante salir después de la redirección

            } else {
                // Mensaje de error genérico por seguridad
                $mensaje = '<p class="error">El email o la contraseña son incorrectos.</p>';
            }

        } catch (PDOException $e) {
            $mensaje = '<p class="error">Error en la base de datos: ' . $e->getMessage() . '</p>';
        }
    }
}

// 9. INCLUIR LA CABECERA
include_once __DIR__ . '/../src/templates/header.php';
?>

<link rel="stylesheet" href="css/style.css">


<div class="container">
    <h2>Iniciar Sesión</h2>

    <?php echo $mensaje; // Muestra el mensaje de error/éxito ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <input type="submit" value="Entrar">
        </div>
    </form>
</div>

<?php
// 10. INCLUIR EL PIE DE PÁGINA
include_once __DIR__ . '/../src/templates/footer.php';
?>