<?php
/**
 * Fichero de Configuración de la Base de Datos
 *
 * Define las constantes para la conexión y crea
 * el objeto $pdo que se usará en el resto de la aplicación.
 */

// 1. Credenciales de la Base de Datos
// (Ajusta 'nombre_de_tu_bd' al nombre real de tu base de datos)

define('DB_HOST', 'localhost');          // El servidor donde está tu BD (casi siempre localhost en XAMPP)
define('DB_NAME', 'libroverso');  // El nombre de tu base de datos en phpMyAdmin
define('DB_USER', 'root');               // El usuario de la BD (por defecto 'root' en XAMPP)
define('DB_PASS', '');                   // La contraseña de la BD (por defecto está vacía en XAMPP)
define('DB_CHARSET', 'utf8mb4');         // El set de caracteres (recomendado)


// 2. Creación de la Conexión (DSN)
// No cambies esta parte a menos que sepas lo que haces

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Reporta errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones nativas
];

// 3. Intento de Conexión
// Aquí creamos el objeto $pdo que usarás en tus otras páginas

try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
     // Si algo falla, muestra un error y detiene la ejecución
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

?>