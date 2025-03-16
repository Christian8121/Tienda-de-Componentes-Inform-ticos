    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>SeveStore</h5>
                    <p>Tu tienda de confianza para componentes informáticos.</p>
                </div>
                <div class="col-md-3">
                    <h5>Enlaces</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../' : ''; ?>index.php" class="text-white">Inicio</a></li>
                        <li><a href="#" class="text-white">Sobre nosotros</a></li>
                        <li><a href="#" class="text-white">Contacto</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contacto</h5>
                    <address>
                        <i class="bi bi-geo-alt"></i> Calle Ejemplo, 123<br>
                        <i class="bi bi-telephone"></i> +34 640 955 513<br>
                        <i class="bi bi-envelope"></i> info@sevestore.com
                    </address>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="m-0">&copy; <?php echo date('Y'); ?> SeveStore. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../' : ''; ?>assets/js/scripts.js"></script>
</body>
</html>
