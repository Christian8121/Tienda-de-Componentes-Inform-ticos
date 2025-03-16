<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar que se ha proporcionado un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirigir a la página principal si no hay ID o no es válido
    header('Location: index.php');
    exit;
}

$producto_id = intval($_GET['id']);

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener detalles del producto
$query = "SELECT * FROM productos WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$producto_id]);

// Verificar si el producto existe
if ($stmt->rowCount() == 0) {
    // Redirigir a la página principal si el producto no existe
    header('Location: index.php');
    exit;
}

$producto = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener productos relacionados de la misma categoría
$query = "SELECT * FROM productos WHERE categoria = ? AND id != ? LIMIT 4";
$stmt = $db->prepare($query);
$stmt->execute([$producto['categoria'], $producto_id]);
$productos_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado
include 'includes/header.php';
?>

<!-- Migas de pan / Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
        <li class="breadcrumb-item"><a href="index.php?buscar=<?php echo urlencode($producto['categoria']); ?>"><?php echo htmlspecialchars($producto['categoria']); ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($producto['nombre']); ?></li>
    </ol>
</nav>

<!-- Detalles del producto -->
<div class="row mb-5">
    <!-- Imagen del producto -->
    <div class="col-md-5 mb-4">
        <img src="assets/img/productos/<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg'; ?>" 
             class="img-fluid rounded product-detail-img" 
             alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
    </div>
    
    <!-- Información del producto -->
    <div class="col-md-7">
        <h1 class="mb-3"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        
        <!-- Categoría -->
        <div class="mb-3">
            <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['categoria']); ?></span>
        </div>
        
        <!-- Precio -->
        <h2 class="text-primary mb-4"><?php echo formatearPrecio($producto['precio']); ?></h2>
        
        <!-- Disponibilidad -->
        <div class="mb-4">
            <?php if ($producto['stock'] > 0): ?>
                <span class="text-success">
                    <i class="bi bi-check-circle-fill"></i> En stock (<?php echo $producto['stock']; ?> disponibles)
                </span>
            <?php else: ?>
                <span class="text-danger">
                    <i class="bi bi-x-circle-fill"></i> Agotado
                </span>
            <?php endif; ?>
        </div>
        
        <!-- Descripción corta -->
        <p class="lead mb-4"><?php echo htmlspecialchars($producto['descripcion_corta']); ?></p>
        
        <!-- Botones de acción -->
        <div class="d-grid gap-2 d-md-flex mb-4">
            <?php if ($producto['stock'] > 0): ?>
                <form action="cart.php" method="post">
                    <div class="d-flex">
                        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="number" name="cantidad" min="1" max="<?php echo $producto['stock']; ?>" value="1" class="form-control me-2" style="width: 70px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cart-plus me-1"></i> Añadir al carrito
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    <i class="bi bi-x-circle me-1"></i> No disponible
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Descripción completa -->
        <div class="mt-5">
            <h4>Descripción detallada</h4>
            <hr>
            <div class="description">
                <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
            </div>
        </div>
    </div>
</div>

<!-- Productos relacionados -->
<?php if (!empty($productos_relacionados)): ?>
<div class="mt-5">
    <h3 class="mb-4">Productos relacionados</h3>
    <div class="row">
        <?php foreach($productos_relacionados as $relacionado): ?>
        <div class="col-md-3 mb-4">
            <div class="card card-product h-100">
                <img src="assets/img/productos/<?php echo !empty($relacionado['imagen']) ? htmlspecialchars($relacionado['imagen']) : 'default.jpg'; ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($relacionado['nombre']); ?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($relacionado['nombre']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($relacionado['descripcion_corta']); ?></p>
                    <div class="mt-auto">
                        <p class="h5 text-primary"><?php echo formatearPrecio($relacionado['precio']); ?></p>
                        <div class="d-flex justify-content-between">
                            <a href="product.php?id=<?php echo $relacionado['id']; ?>" class="btn btn-outline-primary btn-sm">Ver detalles</a>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="producto_id" value="<?php echo $relacionado['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
