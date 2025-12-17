<?php
// Incluimos la cabecera (esto también iniciará la sesión si no se ha hecho)
// La cabecera ya se encarga de mostrar "Login/Registro" o "Mi Cuenta/Logout"
// dependiendo del estado de la sesión.
require_once __DIR__ . '/../src/templates/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <h1>Tu Próxima Aventura Empieza Aquí</h1>
        <p>Explora nuestra vasta colección de libros y encuentra historias que te transportarán a otros mundos. ¡Regístrate o inicia sesión para comenzar!</p>
        
        <div class="hero-buttons">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="registro.php" class="btn">Regístrate</a>
                <a href="login.php" class="btn">Inicia Sesión</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn">Mi Cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="featured-section">
    <h2>Libros Destacados</h2>
    <p>Descubre las obras más populares y las últimas novedades en nuestro catálogo.</p>
    
    <div class="book-grid">
        <div class="book-item">
            <img src="https://covers.openlibrary.org/b/isbn/9780544003415-M.jpg" alt="Portada de El Señor de los Anillos">
            <h3>El Señor de los Anillos</h3>
            <p>La comunidad del anillo...</p>
            <div class="price">24.99 €</div>
        </div>
        <div class="book-item">
            <img src="https://covers.openlibrary.org/b/isbn/9780441172719-M.jpg" alt="Portada de Dune">
            <h3>Dune</h3>
            <p>Una épica de ciencia ficción...</p>
            <div class="price">21.99 €</div>
        </div>
        <div class="book-item">
            <img src="https://covers.openlibrary.org/b/isbn/9780735211292-M.jpg" alt="Portada de Hábitos Atómicos">
            <h3>Hábitos Atómicos</h3>
            <p>Un método sencillo y comprobado para crear buenos hábitos y romper los malos.</p>
            <div class="price">22.90 €</div>
        </div>
        <div class="book-item">
            <img src="https://covers.openlibrary.org/b/isbn/9780590353427-M.jpg" alt="Portada de Harry Potter">
            <h3>Harry Potter y la piedra filosofal</h3>
            <p>Un joven mago descubre su destino al asistir a una escuela de hechicería.</p>
            <div class="price">19.99 €</div>
        </div>
    </div>
</div>

<?php
// Incluimos el pie de página
require_once __DIR__ . '/../src/templates/footer.php';
?>
