<?php $rutaHtml = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'php' ? '../html/' : ''; ?>
 <footer class="xero-footer">
        <div class="footer-container">
            <!-- Columna 1: Logo y Fundadores -->
            <div class="footer-col brand-col">
                <div class="footer-logo">
                    <h2>XERO</h2>
                    <span class="footer-tagline">
                        Sistema de Gestión de Inventarios. Creamos soluciones para transformar los almacenes y tiendas en sistemas organizados.
                    </span>
                    <!-- Eslogan integrado -->
                    <p class="footer-slogan">"El motor digital de tu inventario, para que solo te ocupes de vender."</p>
                </div>
                <div class="footer-founders">
                    <p class="founders-title">Fundadores:</p>
                    <p>Afnin Ozuna García</p>
                    <p>Sara Nallely Mendoza García</p>
                    <p>Nicolás Matta Puentes</p>
                    <p class="founders-title">Colaborador del proyecto:</p>
                    <p>German Ocampo Henao</p>
                </div>
            </div>

            <!-- Columna 2: Enlaces del Software e Inventario -->
            <div class="footer-col">
                <h3>Navegación</h3>
                <ul>
                    <li><a href="inicio.php">Inicio</a></li>
                    <li><a href="agregar.php">Añadir Registro</a></li>
                    <li><a href="informes.php">Control de Stock</a></li>
                    <li><a href="productos.php">Módulo de Productos</a></li>
                </ul>
            </div>

            <!-- Columna 3: Legal y Privacidad orientada a Inventarios -->
            <div class="footer-col">
                <h3>Legal y Seguridad</h3>
                <ul>
                    <li><a href="#">Política de Privacidad de Datos</a></li>
                    <li><a href="#">Seguridad en el Inventario</a></li>
                    <li><a href="#">Términos del Servicio</a></li>
                    <li><a href="#">Soporte Técnico</a></li>
                </ul>
            </div>
        </div>

        <!-- Barra inferior con derechos -->
        <div class="footer-bottom">
            <p>&copy; 2026 XERO. Todos los derechos reservados. Xero Software protege la confidencialidad de tus registros de inventario y stock.</p>
        </div>
    </footer>
    <script src="../js/script.js" defer></script>
