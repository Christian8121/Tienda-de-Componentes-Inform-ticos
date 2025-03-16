<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    define('INCLUDED_FROM_ADMIN_PANEL', true);
}

// Procesar acciones específicas para pedidos
switch ($action) {
    case 'view':
        // Ver detalles de un pedido específico
        if (!$id) {
            $error_message = "ID de pedido no especificado.";
            header("Location: admin_panel.php?section=pedidos&error=" . urlencode($error_message));
            exit;
        }
        
        try {
            // Obtener información del pedido
            $query = "SELECT p.*, u.nombre as nombre_cliente, u.email as email_cliente 
                      FROM pedidos p 
                      JOIN usuarios u ON p.usuario_id = u.id 
                      WHERE p.id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $error_message = "Pedido no encontrado.";
                header("Location: admin_panel.php?section=pedidos&error=" . urlencode($error_message));
                exit;
            }
            
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener productos del pedido
            $query = "SELECT dp.*, p.nombre, p.imagen 
                      FROM detalle_pedido dp 
                      JOIN productos p ON dp.producto_id = p.id 
                      WHERE dp.pedido_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener historial de estados
            $query = "SELECT * FROM historial_estados_pedido 
                      WHERE pedido_id = ? 
                      ORDER BY fecha_cambio DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $historial_estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar actualización de estado si se envió el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
                $nuevo_estado = $_POST['nuevo_estado'];
                $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
                
                // Comprobar que el nuevo estado sea válido
                $estados_validos = ['pendiente', 'procesando', 'enviado', 'entregado'];
                if (!in_array($nuevo_estado, $estados_validos)) {
                    $error_message = "Estado no válido.";
                } else {
                    try {
                        $db->beginTransaction();
                        
                        // Actualizar estado del pedido
                        $query = "UPDATE pedidos SET estado = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$nuevo_estado, $id]);
                        
                        // Insertar entrada en historial
                        $query = "INSERT INTO historial_estados_pedido (pedido_id, estado, comentario) VALUES (?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$id, $nuevo_estado, $comentario]);
                        
                        // Si el estado es "entregado", crear notificación para el usuario
                        if ($nuevo_estado === 'entregado') {
                            $titulo = "¡Tu pedido ha sido entregado!";
                            $mensaje = "Tu pedido #" . str_pad($id, 8, '0', STR_PAD_LEFT) . " ha sido entregado. ¡Gracias por tu compra!";
                            $enlace = "tracking.php?id=" . $id;
                            
                            $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) 
                                      VALUES (?, ?, ?, 'pedido_entregado', ?)";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$pedido['usuario_id'], $titulo, $mensaje, $enlace]);
                        }
                        
                        $db->commit();
                        
                        // Recargar la página para ver los cambios
                        header("Location: admin_panel.php?section=pedidos&action=view&id={$id}&success=Estado+actualizado");
                        exit;
                    } catch (Exception $e) {
                        $db->rollBack();
                        $error_message = "Error al actualizar el estado: " . $e->getMessage();
                    }
                }
            }
            
            // Mostrar la vista de detalles del pedido
            include 'admin/pedidos_view.php';
            
        } catch (PDOException $e) {
            $error_message = "Error al obtener los detalles del pedido: " . $e->getMessage();
            include 'admin/error.php';
        }
        break;
        
    case 'list':
    default:
        // Filtrar por estado si se proporciona
        $estado_filter = isset($_GET['filter']) ? $_GET['filter'] : '';
        $where_clause = '';
        
        if (!empty($estado_filter) && in_array($estado_filter, ['pendiente', 'procesando', 'enviado', 'entregado'])) {
            $where_clause = "WHERE p.estado = '{$estado_filter}'";
        }
        
        // Consulta para obtener todos los pedidos (con filtro opcional)
        $query = "SELECT p.*, u.nombre as nombre_cliente,
                 (SELECT COUNT(*) FROM detalle_pedido WHERE pedido_id = p.id) as num_productos 
                 FROM pedidos p 
                 JOIN usuarios u ON p.usuario_id = u.id
                 $where_clause
                 ORDER BY p.fecha_pedido DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Pedidos</h6>
                    <div>
                        <!-- Filtros de estado -->
                        <div class="btn-group">
                            <a href="admin_panel.php?section=pedidos" class="btn btn-sm <?php echo empty($estado_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Todos
                            </a>
                            <a href="admin_panel.php?section=pedidos&filter=pendiente" class="btn btn-sm <?php echo $estado_filter === 'pendiente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                Pendientes
                            </a>
                            <a href="admin_panel.php?section=pedidos&filter=procesando" class="btn btn-sm <?php echo $estado_filter === 'procesando' ? 'btn-info' : 'btn-outline-info'; ?>">
                                Procesando
                            </a>
                            <a href="admin_panel.php?section=pedidos&filter=enviado" class="btn btn-sm <?php echo $estado_filter === 'enviado' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Enviados
                            </a>
                            <a href="admin_panel.php?section=pedidos&filter=entregado" class="btn btn-sm <?php echo $estado_filter === 'entregado' ? 'btn-success' : 'btn-outline-success'; ?>">
                                Entregados
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($pedidos)): ?>
                    <p class="text-center text-muted">No hay pedidos <?php echo !empty($estado_filter) ? 'con el estado seleccionado' : ''; ?>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Productos</th>
                                    <th>Total</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($pedido['id'], 8, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($pedido['nombre_cliente']); ?>
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
                                        <td><?php echo formatearPrecio($pedido['total']); ?></td>
                                        <td>
                                            <a href="admin_panel.php?section=pedidos&action=view&id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <?php if ($pedido['estado'] === 'entregado'): ?>
                                                <a href="generate_invoice.php?id=<?php echo $pedido['id']; ?>&admin=true" class="btn btn-sm btn-secondary" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        break;
}
?>
