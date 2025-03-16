<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirigir si el usuario no está logueado
if (!estaLogueado()) {
    header('Location: login.php');
    exit;
}

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener todos los pedidos del usuario
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM detalle_pedido WHERE pedido_id = p.id) as num_productos 
          FROM pedidos p 
          WHERE p.usuario_id = ? 
          ORDER BY p.fecha_pedido DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="border-bottom pb-3">
                <i class="bi bi-box-seam me-2"></i> Mis Pedidos
            </h1>
        </div>
    </div>
    
    <?php if (empty($pedidos)): ?>
        <div class="alert alert-info">
            <h4 class="alert-heading"><i class="bi bi-info-circle me-2"></i>No tienes pedidos</h4>
            <p>Aún no has realizado ningún pedido. ¡Explora nuestra tienda y encuentra productos increíbles!</p>
            <hr>
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-shop"></i> Ir a la tienda
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Nº Pedido</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Productos</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                    <td>
                                        <?php 
                                        switch ($pedido['estado']) {
                                            case 'pendiente':
                                                echo '<span class="badge bg-warning">Pendiente</span>';
                                                break;
                                            case 'procesando':
                                                echo '<span class="badge bg-info">Procesando</span>';
                                                break;
                                            case 'enviado':
                                                echo '<span class="badge bg-primary">Enviado</span>';
                                                break;
                                            case 'entregado':
                                                echo '<span class="badge bg-success">Entregado</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">Desconocido</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $pedido['num_productos']; ?> productos</td>
                                    <td class="text-end"><?php echo formatearPrecio($pedido['total']); ?></td>
                                    <td class="text-center">
                                        <a href="tracking.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="tooltip" title="Seguimiento">
                                            <i class="bi bi-truck"></i>
                                        </a>
                                        <?php if ($pedido['estado'] == 'entregado'): ?>
                                        <a href="generate_invoice.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Descargar factura">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
