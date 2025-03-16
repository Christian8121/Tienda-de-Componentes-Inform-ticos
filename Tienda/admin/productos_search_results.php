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
                Resultados de búsqueda: "<?php echo htmlspecialchars($search_term); ?>"
            </h6>
            <a href="admin_panel.php?section=productos" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a todos los productos
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($productos)): ?>
            <div class="alert alert-info">
                <p class="mb-0">No se encontraron productos que coincidan con "<strong><?php echo htmlspecialchars($search_term); ?></strong>".</p>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <p>Se encontraron <?php echo count($productos); ?> productos para "<strong><?php echo htmlspecialchars($search_term); ?></strong>"</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td class="text-center">
                                    <img src="assets/img/productos/<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg'; ?>" 
                                        alt="<?php echo htmlspecialchars($producto['nombre']); ?>" 
                                        style="width: 50px; height: 50px; object-fit: contain;">
                                </td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td><?php echo formatearPrecio($producto['precio']); ?></td>
                                <td>
                                    <?php if ($producto['stock'] < 5): ?>
                                        <span class="badge bg-danger"><?php echo $producto['stock']; ?></span>
                                    <?php else: ?>
                                        <?php echo $producto['stock']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="admin_panel.php?section=productos&action=edit&id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-bs-delete-url="admin_panel.php?section=productos&action=delete&id=<?php echo $producto['id']; ?>" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <a href="../product.php?id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Ver en tienda">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="admin_panel.php?section=productos&action=duplicate&id=<?php echo $producto['id']; ?>" class="btn btn-sm btn-secondary" title="Duplicar producto" onclick="return confirm('¿Desea duplicar este producto?');">
                                            <i class="bi bi-copy"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
