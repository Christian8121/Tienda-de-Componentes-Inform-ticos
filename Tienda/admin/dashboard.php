<?php
// Verificar que este archivo no se acceda directamente
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)));
}

// Estadísticas para el dashboard
$stats = [
    'total_users' => 0,
    'total_products' => 0,
    'total_orders' => 0,
    'total_revenue' => 0,
    'pending_orders' => 0,
    'low_stock_products' => 0,
];

// Obtener estadísticas reales de la base de datos
try {
    // Total usuarios
    $query = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $db->query($query);
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total productos
    $query = "SELECT COUNT(*) as total FROM productos";
    $stmt = $db->query($query);
    $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Productos con poco stock (menos de 5)
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock < 5";
    $stmt = $db->query($query);
    $stats['low_stock_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total pedidos y ingresos
    $query = "SELECT COUNT(*) as total, SUM(total) as revenue FROM pedidos";
    $stmt = $db->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_orders'] = $result['total'];
    $stats['total_revenue'] = $result['revenue'];
    
    // Pedidos pendientes
    $query = "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'";
    $stmt = $db->query($query);
    $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pedidos recientes
    $query = "SELECT p.*, u.nombre as nombre_usuario FROM pedidos p 
              JOIN usuarios u ON p.usuario_id = u.id 
              ORDER BY fecha_pedido DESC LIMIT 5";
    $stmt = $db->query($query);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Productos con bajo stock
    $query = "SELECT * FROM productos WHERE stock < 5 ORDER BY stock ASC LIMIT 5";
    $stmt = $db->query($query);
    $low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Error al obtener estadísticas: " . $e->getMessage();
}
?>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Usuarios</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Productos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_products']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box-seam fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pedidos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_orders']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-cart3 fs-2 text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Ingresos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatearPrecio($stats['total_revenue'] ?? 0); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-euro fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contenido del Dashboard -->
<div class="row">
    <!-- Pedidos recientes -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Pedidos recientes</h6>
                <a href="admin_panel.php?section=pedidos" class="btn btn-sm btn-primary">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <p class="text-center text-muted">No hay pedidos recientes.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nombre_usuario']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['fecha_pedido'])); ?></td>
                                        <td>
                                            <?php 
                                            switch ($order['estado']) {
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
                                        <td><?php echo formatearPrecio($order['total']); ?></td>
                                        <td>
                                            <a href="admin_panel.php?section=pedidos&action=view&id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alertas y Productos con bajo stock -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-danger">Alertas</h6>
            </div>
            <div class="card-body">
                <?php if ($stats['pending_orders'] > 0): ?>
                    <div class="alert alert-warning mb-3">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i> Pedidos pendientes</h6>
                        <p class="mb-0">Hay <?php echo $stats['pending_orders']; ?> pedidos pendientes de procesamiento.</p>
                        <hr>
                        <a href="admin_panel.php?section=pedidos&filter=pendiente" class="btn btn-sm btn-warning">Ver pedidos</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($stats['low_stock_products'] > 0): ?>
                    <div class="alert alert-danger mb-3">
                        <h6><i class="bi bi-exclamation-circle me-2"></i> Productos con bajo stock</h6>
                        <p class="mb-0">Hay <?php echo $stats['low_stock_products']; ?> productos con stock por debajo de 5 unidades.</p>
                        <hr>
                        <a href="admin_panel.php?section=productos&filter=low_stock" class="btn btn-sm btn-danger">Ver productos</a>
                    </div>
                    
                    <?php if (!empty($low_stock)): ?>
                        <h6 class="mt-4 mb-3">Productos críticos:</h6>
                        <ul class="list-group">
                            <?php foreach ($low_stock as $product): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <?php echo htmlspecialchars($product['nombre']); ?>
                                        <span class="badge bg-danger ms-2"><?php echo $product['stock']; ?> en stock</span>
                                    </span>
                                    <a href="admin_panel.php?section=productos&action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php 
                if ($stats['pending_orders'] == 0 && $stats['low_stock_products'] == 0): 
                ?>
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle me-2"></i> Todo en orden</h6>
                        <p class="mb-0">No hay alertas que requieran tu atención en este momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>