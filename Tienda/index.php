<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Buscar productos
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$whereBusqueda = '';

if (!empty($busqueda)) {
    $whereBusqueda = "WHERE nombre LIKE :busqueda OR descripcion_corta LIKE :busqueda OR categoria LIKE :busqueda";
}

// Obtener productos
$query = "SELECT * FROM productos $whereBusqueda ORDER BY fecha_creacion DESC";
$stmt = $db->prepare($query);

if (!empty($busqueda)) {
    $parametroBusqueda = "%$busqueda%";
    $stmt->bindParam(':busqueda', $parametroBusqueda);
}

$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="bg-primary text-white p-4 rounded">
            <h1 class="display-5">Bienvenido a SeveStore</h1>
            <p class="lead">Descubre nuestra selección de componentes informáticos de alta calidad.</p>
        </div>
    </div>
</div>

<?php if (!empty($busqueda)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h4>Resultados para: "<?php echo htmlspecialchars($busqueda); ?>"</h4>
            <p>Se encontraron <?php echo count($productos); ?> productos</p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <?php if (empty($productos)): ?>
        <div class="col-12">
            <div class="alert alert-warning">
                No se encontraron productos<?php echo !empty($busqueda) ? ' para tu búsqueda.' : '.'; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($productos as $producto): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card card-product h-100">
                    <img src="/assets/img/productos/<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg'; ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($producto['descripcion_corta']); ?></p>
                        <div class="mt-auto">
                            <p class="h4 text-primary"><?php echo formatearPrecio($producto['precio']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="product.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary">Ver detalles</a>
                                
                                <!-- Formulario de añadir al carrito con AJAX -->
                                <form action="cart.php" method="post" class="add-to-cart-form">
                                    <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary add-to-cart-btn" data-bs-toggle="tooltip" title="Añadir al carrito">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Toast para notificaciones -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<?php include 'includes/footer.php'; ?>
