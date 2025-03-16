<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    header('Location: ../index.php');
    exit;
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Detalles del Pedido #<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?>
            </h6>
            <a href="admin_panel.php?section=pedidos" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a la lista
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <!-- Información del pedido -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Información del Pedido</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>ID Pedido:</strong> #<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                        <p>
                            <strong>Estado actual:</strong> 
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
                        </p>
                        <p><strong>Total:</strong> <?php echo formatearPrecio($pedido['total']); ?></p>
                        <p><strong>Método de pago:</strong> <?php echo htmlspecialchars($pedido['metodo_pago'] ?? 'No especificado'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Información del cliente -->
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Información del Cliente</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email_cliente']); ?></p>
                        <p><strong>Dirección de envío:</strong> <?php echo htmlspecialchars($pedido['direccion_envio'] ?? 'No especificada'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Productos del pedido -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Productos en el Pedido</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio unitario</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="../assets/img/productos/<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg'; ?>" 
                                                 class="me-2" style="width: 50px; height: 50px; object-fit: contain;">
                                            <div>
                                                <?php echo htmlspecialchars($producto['nombre']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle"><?php echo formatearPrecio($producto['precio_unitario']); ?></td>
                                    <td class="align-middle text-center"><?php echo $producto['cantidad']; ?></td>
                                    <td class="align-middle text-end"><?php echo formatearPrecio($producto['precio_unitario'] * $producto['cantidad']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="text-end"><?php echo formatearPrecio($pedido['total']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Actualizar estado del pedido -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Actualizar Estado del Pedido</h6>
                    </div>
                    <div class="card-body">
                        <form action="admin_panel.php?section=pedidos&action=view&id=<?php echo $pedido['id']; ?>" method="post">
                            <div class="mb-3">
                                <label for="nuevo_estado" class="form-label">Nuevo estado</label>
                                <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                    <option value="pendiente" <?php echo $pedido['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="procesando" <?php echo $pedido['estado'] === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                    <option value="enviado" <?php echo $pedido['estado'] === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                    <option value="entregado" <?php echo $pedido['estado'] === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="comentario" class="form-label">Comentario (opcional)</label>
                                <textarea class="form-control" id="comentario" name="comentario" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar estado</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Historial de estados -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Historial de Estados</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($historial_estados as $historial): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php 
                                            switch ($historial['estado']) {
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
                                        </h6>
                                        <small><?php echo date('d/m/Y H:i', strtotime($historial['fecha_cambio'])); ?></small>
                                    </div>
                                    <?php if (!empty($historial['comentario'])): ?>
                                        <p class="mb-1 small"><?php echo htmlspecialchars($historial['comentario']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Acciones adicionales -->
        <div class="d-flex justify-content-end mt-3">
            <?php if ($pedido['estado'] === 'entregado'): ?>
                <a href="generate_invoice.php?id=<?php echo $pedido['id']; ?>&admin=true" class="btn btn-secondary me-2" target="_blank">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Ver Factura
                </a>
            <?php endif; ?>
            <a href="admin_panel.php?section=pedidos" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i> Volver a la lista
            </a>
        </div>
    </div>
</div>
