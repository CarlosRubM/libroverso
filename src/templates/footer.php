</main> <!-- Cierre de la etiqueta <main> abierta en header.php -->

<footer class="footer-simple">
    <p>&copy; <?php echo date('Y'); ?> Libroverso</p>
</footer>

<!-- 
================================================================
SCRIPT JAVASCRIPT PARA EL CARRITO AJAX
================================================================
Este script se cargará en todas las páginas que incluyan el footer.
Se encargará de actualizar el carrito en la página 'carrito.php'.
-->
<script>
// Esperar a que todo el HTML esté cargado
document.addEventListener('DOMContentLoaded', function() {

    /**
     * Función para formatear números como moneda (Ej: 1250.7 -> "1.250,70 €")
     * @param {number} numero - El número a formatear.
     * @returns {string} - El número formateado como moneda EUR.
     */
    function formatearMoneda(numero) {
        return new Intl.NumberFormat('es-ES', { 
            style: 'currency', 
            currency: 'EUR' 
        }).format(numero);
    }

    /**
     * Recalcula el TOTAL de todo el carrito (el resumen)
     * leyendo los subtotales de cada fila.
     */
    function actualizarTotalGeneral() {
        let totalGeneral = 0;
        
        // Busca todas las filas (items) que queden en la tabla
        document.querySelectorAll('.fila-item-carrito').forEach(function(fila) {
            const input = fila.querySelector('.input-cantidad');
            const precioUnitario = parseFloat(input.dataset.precioUnitario);
            const cantidad = parseInt(input.value);

            // Si la cantidad es válida, se suma al total
            if (!isNaN(precioUnitario) && !isNaN(cantidad) && cantidad > 0) {
                totalGeneral += precioUnitario * cantidad;
            }
        });

        // Actualiza el <span> del total en el resumen
        const elementoTotal = document.getElementById('carrito-total-general');
        if (elementoTotal) {
            elementoTotal.textContent = formatearMoneda(totalGeneral);
        }
    }
    
    /**
     * Actualiza el contador de items en la barra de navegación (header).
     */
    function actualizarContadorHeader() {
        let totalItems = 0;
        
        // Suma la cantidad de todos los inputs en la página
        document.querySelectorAll('.input-cantidad').forEach(function(input) {
            const cantidad = parseInt(input.value);
            if (!isNaN(cantidad) && cantidad > 0) {
                totalItems += cantidad;
            }
        });
        
        // Busca el <span> del contador en el header
        const contador = document.getElementById('nav-carrito-contador');
        
        if (contador) {
            if (totalItems > 0) {
                contador.textContent = totalItems;
                contador.style.display = ''; // Mostrar el contador
            } else {
                contador.style.display = 'none'; // Ocultar si es 0
            }
        }
    }

    /**
     * Envía la actualización al servidor (AJAX) usando fetch.
     * @param {string} idLibro - El ID del libro a actualizar.
     * @param {number} cantidad - La nueva cantidad.
     * @param {HTMLElement} filaParaEliminar - La fila <tr> por si hay que borrarla (si cantidad es 0).
     */
    function enviarActualizacionAJAX(idLibro, cantidad, filaParaEliminar) {
        
        // Preparamos los datos para enviar (como un formulario)
        const formData = new FormData();
        formData.append('id_libro', idLibro);
        formData.append('cantidad', cantidad);

        // Usamos fetch para la llamada AJAX a nuestro endpoint PHP
        fetch('actualizar_carrito_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                // Si la respuesta no es 2xx, lanzamos un error
                throw new Error('Respuesta del servidor no fue OK: ' + response.statusText);
            }
            return response.json(); // Convertimos la respuesta a JSON
        })
        .then(data => {
            // El servidor responde con { success: true, ... }
            if (data.success) {
                console.log('Sesión actualizada con éxito.');
                
                // Si la cantidad era 0 y el servidor lo confirmó,
                // eliminamos la fila <tr> de la tabla.
                if (cantidad <= 0 && filaParaEliminar) {
                    filaParaEliminar.remove();
                    
                    // Comprobar si la tabla quedó vacía
                    if (document.querySelectorAll('.fila-item-carrito').length === 0) {
                        // Si está vacía, recargamos la página para mostrar
                        // el mensaje "Tu carrito está vacío" de PHP
                        window.location.reload();
                    }
                }
            } else {
                // El servidor respondió { success: false, message: '...' }
                console.error('Error al actualizar el carrito:', data.message);
                alert('Error: ' + data.message + '. La página se recargará para re-sincronizar.');
                window.location.reload(); // Recargar para re-sincronizar
            }
        })
        .catch(error => {
            // Error de red o al procesar el fetch
            console.error('Error de Fetch:', error);
            alert('Hubo un problema de conexión. La página se recargará para re-sincronizar.');
            window.location.reload(); // Recargar para re-sincronizar
        });
    }


    // === PUNTO DE ENTRADA ===
    
    // Solo ejecutamos la lógica si estamos en la página del carrito
    // (buscando la tabla del carrito)
    const tablaCarrito = document.querySelector('.tabla-carrito');
    
    if (tablaCarrito) {
        
        // 1. Seleccionar TODOS los inputs de cantidad
        const inputsCantidad = document.querySelectorAll('.input-cantidad');

        // 2. Añadir un "detector de eventos" (listener) a CADA uno
        inputsCantidad.forEach(function(input) {
            
            // Se dispara cuando el usuario quita el foco (clic fuera) o da Enter.
            input.addEventListener('change', function() {
                
                // --- 1. ACTUALIZACIÓN VISUAL (INMEDIATA) ---
                
                // a. Encontrar la fila (el <tr>) más cercana a este input
                const fila = input.closest('.fila-item-carrito');
                
                // b. Encontrar el <strong> del subtotal DENTRO de esa fila
                const elementoSubtotal = fila.querySelector('.item-subtotal');
                
                // c. Obtener los valores
                const precioUnitario = parseFloat(input.dataset.precioUnitario);
                let cantidad = parseInt(input.value);

                // d. Validar cantidad (mínimo 0)
                if (isNaN(cantidad) || cantidad < 0) {
                    cantidad = 0; // Si pone algo raro, lo reseteamos a 0
                    input.value = 0;
                }

                // e. Calcular y mostrar nuevo subtotal
                const nuevoSubtotal = precioUnitario * cantidad;
                elementoSubtotal.textContent = formatearMoneda(nuevoSubtotal);
                
                // f. Llamar a las funciones que recalculan los TOTALES
                actualizarTotalGeneral();
                actualizarContadorHeader();

                // --- 2. ACTUALIZACIÓN DEL SERVIDOR (AJAX) ---
                
                const idLibro = input.dataset.idLibro;
                
                // Pasamos la 'fila' para poder eliminarla si la cantidad es 0
                enviarActualizacionAJAX(idLibro, cantidad, fila);
            });
        });
    } // fin de if (estamos en pág. carrito)

}); // fin de DOMContentLoaded
</script>

</body>
</html>