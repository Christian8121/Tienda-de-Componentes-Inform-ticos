<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    define('INCLUDED_FROM_ADMIN_PANEL', true);
}

// Procesar acciones específicas para productos
switch ($action) {
    case 'add':
        // Agregar nuevo producto
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar datos del formulario
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $descripcion_corta = isset($_POST['descripcion_corta']) ? trim($_POST['descripcion_corta']) : '';
            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
            $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
            
            // Validar campos obligatorios
            if (empty($nombre) || empty($precio) || $precio <= 0) {
                $error_message = "El nombre y precio son obligatorios. El precio debe ser mayor que 0.";
            } else {
                try {
                    // Procesar la imagen si se ha subido
                    $imagen = 'default.jpg'; // Valor predeterminado
                    
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK && $_FILES['imagen']['size'] > 0) {
                        $upload_dir = 'assets/img/productos/';
                        
                        // Verificar que el directorio existe, crearlo si no
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $temp_name = $_FILES['imagen']['tmp_name'];
                        $image_name = $_FILES['imagen']['name'];
                        $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
                        
                        // Generar un nombre único para la imagen
                        $unique_name = strtolower(str_replace(' ', '_', $nombre)) . '_' . uniqid() . '.' . $extension;
                        $upload_path = $upload_dir . $unique_name;
                        
                        // Tipos de archivo permitidos
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($extension, $allowed_extensions)) {
                            if (move_uploaded_file($temp_name, $upload_path)) {
                                $imagen = $unique_name;
                            } else {
                                $error_message = "No se pudo subir la imagen.";
                            }
                        } else {
                            $error_message = "Formato de imagen no válido. Se permiten: jpg, jpeg, png, gif";
                        }
                    }
                    
                    if (!isset($error_message)) {
                        // Insertar el producto en la base de datos
                        $query = "INSERT INTO productos (nombre, descripcion_corta, descripcion, precio, stock, imagen, categoria) VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$nombre, $descripcion_corta, $descripcion, $precio, $stock, $imagen, $categoria]);
                        
                        $success_message = "Producto creado correctamente.";
                        header("Location: admin_panel.php?section=productos&success=" . urlencode($success_message));
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_message = "Error al crear el producto: " . $e->getMessage();
                }
            }
        }
        
        // Mostrar formulario para agregar producto
        include 'admin/productos_form.php';
        break;
        
    case 'edit':
        // Editar producto existente
        if (!$id) {
            $error_message = "ID de producto no especificado.";
            header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
            exit;
        }
        
        // Obtener información del producto
        try {
            $query = "SELECT * FROM productos WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $error_message = "Producto no encontrado.";
                header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
                exit;
            }
            
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Ver qué datos tenemos del producto
            error_log("Producto cargado - ID: {$id}, Nombre: {$producto['nombre']}, Imagen: {$producto['imagen']}");
            
        } catch (PDOException $e) {
            $error_message = "Error al cargar el producto: " . $e->getMessage();
            error_log($error_message);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Debug: Ver qué datos recibimos del formulario
            error_log("POST data recibida: " . print_r($_POST, true));
            error_log("FILES data recibida: " . (isset($_FILES['imagen']) ? print_r($_FILES['imagen'], true) : "No hay imagen"));
            
            // Validar datos del formulario
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $descripcion_corta = isset($_POST['descripcion_corta']) ? trim($_POST['descripcion_corta']) : '';
            $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
            $precio = isset($_POST['precio']) ? floatval($_POST['precio']) : 0;
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
            
            // Usar la función updateProduct() para actualizar el producto
            $result = actualizarProducto($id, [
                'nombre' => $nombre,
                'descripcion_corta' => $descripcion_corta,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'stock' => $stock,
                'categoria' => $categoria
            ], isset($_FILES['imagen']) ? $_FILES['imagen'] : null);
            
            if ($result['success']) {
                // Registrar notificación para el administrador
                if (isset($_SESSION['user_id'])) {
                    $admin_id = $_SESSION['user_id'];
                    try {
                        $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) 
                                VALUES (?, ?, ?, 'admin_action', ?)";
                        $stmt = $db->prepare($query);
                        $titulo = "Producto Actualizado";
                        $mensaje = "El producto '{$nombre}' ha sido actualizado correctamente.";
                        $enlace = "admin_panel.php?section=productos&action=edit&id={$id}";
                        $stmt->execute([$admin_id, $titulo, $mensaje, $enlace]);
                    } catch (Exception $e) {
                        // Solo registrar el error, no detener el flujo
                        error_log("Error al crear notificación: " . $e->getMessage());
                    }
                }
                
                // Almacenar mensaje para toast
                $_SESSION['toast_message'] = $result['message'];
                $_SESSION['toast_type'] = "success";
                
                // Redirección a la lista de productos
                header("Location: admin_panel.php?section=productos");
                exit; // Importante: detener ejecución después de redireccionar
            } else {
                $error_message = $result['message'];
                error_log("Error al actualizar producto: " . $error_message);
            }
        }
        
        // Mostrar formulario para editar producto
        include 'admin/productos_form.php';
        break;
        
    case 'delete':
        // Eliminar producto
        if (!$id) {
            $error_message = "ID de producto no especificado.";
            header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
            exit;
        }
        
        // Utilizar la nueva función deleteProduct para eliminar el producto
        $resultado = eliminarProducto($id);
        
        if ($resultado['success']) {
            // Almacenar información para mostrar el toast
            $_SESSION['toast_message'] = $resultado['message'];
            $_SESSION['toast_type'] = 'success';
            $_SESSION['toast_title'] = 'Producto Eliminado';
            
            // Redirigir a la lista de productos
            header("Location: admin_panel.php?section=productos");
        } else {
            $error_message = $resultado['message'];
            header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
        }
        exit;
        break;
        
    case 'search':
        // Nueva acción para búsqueda de productos
        $search_term = isset($_GET['term']) ? trim($_GET['term']) : '';
        
        if (!empty($search_term)) {
            $query = "SELECT * FROM productos 
                      WHERE nombre LIKE :term 
                      OR descripcion_corta LIKE :term 
                      OR descripcion LIKE :term 
                      OR categoria LIKE :term
                      ORDER BY id DESC";
            $stmt = $db->prepare($query);
            $stmt->execute(['term' => "%$search_term%"]);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include 'admin/productos_search_results.php';
        } else {
            header("Location: admin_panel.php?section=productos");
            exit;
        }
        break;
        
    case 'duplicate':
        // Nueva acción para duplicar un producto
        if (!$id) {
            $error_message = "ID de producto no especificado.";
            header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
            exit;
        }
        
        try {
            // Obtener el producto original
            $query = "SELECT * FROM productos WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $error_message = "Producto no encontrado.";
                header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
                exit;
            }
            
            $producto_original = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Duplicar producto con "(copia)" en el nombre
            $nuevo_nombre = $producto_original['nombre'] . " (copia)";
            
            // Insertar duplicado
            $query = "INSERT INTO productos (
                        nombre, descripcion_corta, descripcion, 
                        precio, stock, imagen, categoria
                     ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                $nuevo_nombre,
                $producto_original['descripcion_corta'],
                $producto_original['descripcion'],
                $producto_original['precio'],
                $producto_original['stock'],
                $producto_original['imagen'], // Usamos la misma imagen
                $producto_original['categoria']
            ]);
            
            $nuevo_id = $db->lastInsertId();
            $success_message = "Producto duplicado correctamente.";
            
            // Opcional: redirigir directamente a la edición del nuevo producto
            header("Location: admin_panel.php?section=productos&action=edit&id={$nuevo_id}&success=" . urlencode($success_message));
            exit;
            
        } catch (PDOException $e) {
            $error_message = "Error al duplicar el producto: " . $e->getMessage();
            header("Location: admin_panel.php?section=productos&error=" . urlencode($error_message));
            exit;
        }
        break;
        
    case 'list':
    default:
        // Filtrar por stock bajo o categoría si se solicita
        $stock_filter = isset($_GET['filter']) && $_GET['filter'] === 'low_stock';
        $categoria_filter = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
        
        // Construir cláusula WHERE
        $where_clauses = [];
        $params = [];
        
        if ($stock_filter) {
            $where_clauses[] = "stock < 5";
        }
        
        if (!empty($categoria_filter)) {
            $where_clauses[] = "categoria = ?";
            $params[] = $categoria_filter;
        }
        
        $where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";
        
        // Obtener categorías únicas para el filtro
        $query_categorias = "SELECT DISTINCT categoria FROM productos ORDER BY categoria";
        $stmt_categorias = $db->prepare($query_categorias);
        $stmt_categorias->execute();
        $categorias = $stmt_categorias->fetchAll(PDO::FETCH_COLUMN);
        
        // Listar productos con paginación
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = 10; // productos por página
        $offset = ($page - 1) * $per_page;
        
        // Contar total de productos (para paginación)
        $count_query = "SELECT COUNT(*) FROM productos $where_clause";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->execute($params);
        $total_productos = $stmt_count->fetchColumn();
        $total_pages = ceil($total_productos / $per_page);
        
        // Obtener productos para la página actual
        $query = "SELECT * FROM productos $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mostrar la vista de lista
        ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Productos</h6>
                    <div class="d-flex flex-wrap mt-2 mt-md-0">
                        <!-- Búsqueda -->
                        <form action="admin_panel.php" method="get" class="d-none d-sm-inline-block me-2">
                            <input type="hidden" name="section" value="productos">
                            <input type="hidden" name="action" value="search">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" name="term" placeholder="Buscar producto...">
                                <button class="btn btn-sm btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Filtros -->
                        <div class="dropdown me-2">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="categoriaDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-filter"></i> Categoría
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="categoriaDropdown">
                                <li><a class="dropdown-item" href="admin_panel.php?section=productos">Todas</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php foreach ($categorias as $cat): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $categoria_filter === $cat ? 'active' : ''; ?>" 
                                       href="admin_panel.php?section=productos&categoria=<?php echo urlencode($cat); ?>">
                                        <?php echo htmlspecialchars($cat); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <?php if ($stock_filter): ?>
                            <a href="admin_panel.php?section=productos<?php echo !empty($categoria_filter) ? '&categoria=' . urlencode($categoria_filter) : ''; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="bi bi-x"></i> Quitar filtro stock
                            </a>
                        <?php else: ?>
                            <a href="admin_panel.php?section=productos&filter=low_stock<?php echo !empty($categoria_filter) ? '&categoria=' . urlencode($categoria_filter) : ''; ?>" class="btn btn-sm btn-outline-warning me-2">
                                <i class="bi bi-exclamation-triangle"></i> Stock bajo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($productos)): ?>
                    <p class="text-center text-muted">
                        No hay productos 
                        <?php echo $stock_filter ? 'con stock bajo' : ''; ?>
                        <?php echo !empty($categoria_filter) ? 'en la categoría ' . htmlspecialchars($categoria_filter) : ''; ?>.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable">
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
                    
                    <!-- Paginación -->
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Navegación de páginas" class="mt-4">
                        <ul class="pagination justify-content-center"></ul></ul>
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : "admin_panel.php?section=productos&page=" . ($page - 1) . ($stock_filter ? '&filter=low_stock' : '') . (!empty($categoria_filter) ? '&categoria=' . urlencode($categoria_filter) : ''); ?>">
                                    Anterior
                                </a>
                            </li>
                            
                            <?php for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>"></li></li>
                                <a class="page-link" href="admin_panel.php?section=productos&page=<?php echo $i; ?><?php echo $stock_filter ? '&filter=low_stock' : ''; ?><?php echo !empty($categoria_filter) ? '&categoria=' . urlencode($categoria_filter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>"></li></li>
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : "admin_panel.php?section=productos&page=" . ($page + 1) . ($stock_filter ? '&filter=low_stock' : '') . (!empty($categoria_filter) ? '&categoria=' . urlencode($categoria_filter) : ''); ?>">
                                    Siguiente
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        break;
}
?>