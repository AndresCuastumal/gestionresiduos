        </div> <!-- Cierre del div.container que abrimos en header.php -->
        <footer class="footer mt-5 py-3 bg-strong text-center">
            <div class="footer-content">
                <p>Secretaría Municipal de Salud - Alcaldía de Pasto &copy; <?php echo date('Y'); ?> Todos los derechos reservados</p>
                
                
                
                <p class="version">Versión 1.0.0</p>
            </div>
        </footer>

        <!-- Scripts globales -->
        <script src="/gestionresiduos/assets/js/main.js"></script>
        
        <!-- Script específico para la página -->
        <?php if (isset($page_script)): ?>
            <script src="/gestionresiduos/assets/js/<?php echo $page_script; ?>"></script>
        <?php endif; ?>
    </body>
</html>