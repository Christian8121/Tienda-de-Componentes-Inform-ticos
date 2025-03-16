<?php
session_start();

// Verificar si el usuario ha iniciado sesión
function estaLogueado() {
    return isset($_SESSION['user_id']);
}

// Verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redireccionar si no es administrador
function redireccionarSiNoAdmin() {
    if (!esAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

// Verificar si hay productos en el carrito
function verificarSiHayProductosEnCarrito() {
    // Si el usuario está logueado, consultar la base de datos
    if (estaLogueado()) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT SUM(cantidad) as total FROM carrito WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? (int)$row['total'] : 0;
    }
    
    // Si no está logueado, usar sesión (mantener compatibilidad)
    if (isset($_SESSION['cart'])) {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['cantidad'];
        }
        return $count;
    }
    return 0;
}

// Formatear precio
function formatearPrecio($price) {
    return number_format($price, 2, ',', '.') . ' €';
}

// Obtener productos del carrito
function obtenerProductosDelCarrito() {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $items = [];
    $total = 0;
    
    // Si el usuario está logueado, obtener productos de la base de datos
    if (estaLogueado()) {
        $query = "SELECT c.*, p.nombre, p.precio, p.imagen 
                  FROM carrito c 
                  JOIN productos p ON c.producto_id = p.id 
                  WHERE c.usuario_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $subtotal = $row['precio'] * $row['cantidad'];
            $total += $subtotal;
            
            $items[] = [
                'id' => $row['producto_id'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'cantidad' => $row['cantidad'],
                'subtotal' => $subtotal,
                'imagen' => $row['imagen']
            ];
        }
    } 
    // Para usuarios no logueados, usar sesión
    else if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $producto_id => $item) {
            $query = "SELECT * FROM productos WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($producto) {
                $subtotal = $producto['precio'] * $item['cantidad'];
                $total += $subtotal;
                
                $items[] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $subtotal,
                    'imagen' => $producto['imagen']
                ];
            }
        }
    }
    
    return [
        'items' => $items,
        'total' => $total
    ];
}

// Añadir producto al carrito
function añadirProductoACarrito($producto_id, $cantidad = 1) {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el producto existe
    $query = "SELECT id, stock FROM productos WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$producto_id]);
    
    if ($stmt->rowCount() == 0) {
        return false; // Producto no existe
    }
    
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar stock disponible
    if ($producto['stock'] < $cantidad) {
        return false; // No hay suficiente stock
    }
    
    // Si el usuario está logueado, guardar en base de datos
    if (estaLogueado()) {
        $usuario_id = $_SESSION['user_id'];
        
        // Verificar si el producto ya está en el carrito del usuario
        $query = "SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_id, $producto_id]);
        
        if ($stmt->rowCount() > 0) {
            // Actualizar cantidad
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nueva_cantidad = $row['cantidad'] + $cantidad;
            
            $query = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
            $stmt = $db->prepare($query);
            return $stmt->execute([$nueva_cantidad, $usuario_id, $producto_id]);
        } else {
            // Insertar nuevo registro
            $query = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
            $stmt = $db->prepare($query);
            return $stmt->execute([$usuario_id, $producto_id, $cantidad]);
        }
    } 
    // Si no está logueado, usar sesión
    else {
        if (isset($_SESSION['cart'][$producto_id])) {
            $_SESSION['cart'][$producto_id]['cantidad'] += $cantidad;
        } else {
            $_SESSION['cart'][$producto_id] = [
                'cantidad' => $cantidad
            ];
        }
        return true;
    }
}

// Actualizar cantidad de un producto en el carrito
function actualizarCantidadProductoEnCarrito($producto_id, $cantidad) {
    require_once 'config/database.php';
    
    if ($cantidad <= 0) {
        return eliminarProductoDelCarrito($producto_id);
    }
    
    // Si el usuario está logueado, actualizar en base de datos
    if (estaLogueado()) {
        $database = new Database();
        $db = $database->getConnection();
        $usuario_id = $_SESSION['user_id'];
        
        $query = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$cantidad, $usuario_id, $producto_id]);
    } 
    // Si no está logueado, actualizar en sesión
    else if (isset($_SESSION['cart'][$producto_id])) {
        $_SESSION['cart'][$producto_id]['cantidad'] = $cantidad;
        return true;
    }
    
    return false;
}

// Eliminar un producto del carrito
function eliminarProductoDelCarrito($producto_id) {
    // Si el usuario está logueado, eliminar de la base de datos
    if (estaLogueado()) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        $usuario_id = $_SESSION['user_id'];
        
        $query = "DELETE FROM carrito WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$usuario_id, $producto_id]);
    } 
    // Si no está logueado, eliminar de sesión
    else if (isset($_SESSION['cart'][$producto_id])) {
        unset($_SESSION['cart'][$producto_id]);
        return true;
    }
    
    return false;
}

// Vaciar el carrito
function limpiarCarrito() {
    // Si el usuario está logueado, eliminar todos sus productos del carrito en la base de datos
    if (estaLogueado()) {
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        $usuario_id = $_SESSION['user_id'];
        
        $query = "DELETE FROM carrito WHERE usuario_id = ?";
        $stmt = $db->prepare($query);
        return $stmt->execute([$usuario_id]);
    } 
    // Si no está logueado, vaciar la sesión
    else {
        $_SESSION['cart'] = [];
        return true;
    }
}

// Migrar carrito de sesión a base de datos cuando un usuario inicia sesión
function migrarCarritoDeSesionBD($usuario_id) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return true; // No hay nada que migrar
    }
    
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        foreach ($_SESSION['cart'] as $producto_id => $item) {
            // Verificar si el producto ya está en el carrito del usuario
            $query = "SELECT cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$usuario_id, $producto_id]);
            
            if ($stmt->rowCount() > 0) {
                // Actualizar cantidad
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $nueva_cantidad = $row['cantidad'] + $item['cantidad'];
                
                $query = "UPDATE carrito SET cantidad = ? WHERE usuario_id = ? AND producto_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$nueva_cantidad, $usuario_id, $producto_id]);
            } else {
                // Insertar nuevo registro
                $query = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$usuario_id, $producto_id, $item['cantidad']]);
            }
        }
        
        $db->commit();
        
        // Limpiar carrito de sesión
        $_SESSION['cart'] = [];
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al migrar carrito a la base de datos: " . $e->getMessage());
        return false;
    }
}

/**
 * Funciones para el sistema de notificaciones
 */

// Obtener el conteo de notificaciones no leídas para el usuario actual
function obtenerConteodeNotificacionesNoLeidas() {
    if (!estaLogueado()) {
        return 0;
    }
    
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['total'] ?? 0;
}

// Crear una nueva notificación
function crearNotificacion($usuario_id, $titulo, $mensaje, $tipo = 'general', $enlace = null) {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$usuario_id, $titulo, $mensaje, $tipo, $enlace]);
    
    return $result ? $db->lastInsertId() : false;
}

// Crear notificación de pedido entregado
function crearNotificacionPedidoEntregado($pedido_id, $usuario_id) {
    $titulo = "¡Tu pedido ha sido entregado!";
    $mensaje = "Tu pedido #" . str_pad($pedido_id, 8, '0', STR_PAD_LEFT) . " ha sido entregado correctamente. ¡Gracias por tu compra!";
    $enlace = "tracking.php?id=" . $pedido_id;
    
    return crearNotificacion($usuario_id, $titulo, $mensaje, 'pedido_entregado', $enlace);
}

// Marcar notificación como leída
function marcarNotificacionComoLeida($notificacion_id, $usuario_id) {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$notificacion_id, $usuario_id]);
    
    return $stmt->rowCount() > 0;
}

// Marcar todas las notificaciones como leídas
function marcarTodasNotificacionesComoLeidas($usuario_id) {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    
    return $stmt->rowCount();
}

// Formatear fecha relativa para notificaciones
function formatearFechaParaNotificaciones($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculamos las semanas manualmente pero sin asignarlas al objeto $diff
    $weeks = floor($diff->d / 7);
    $diff->d -= $weeks * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana', // Usaremos esto con nuestra variable $weeks
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );
    
    foreach ($string as $k => &$v) {
        if ($k === 'w' && $weeks) {
            // Para las semanas usamos nuestra variable $weeks
            $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
        } elseif ($k !== 'w' && isset($diff->$k) && $diff->$k) {
            // Para el resto de unidades de tiempo usamos las propiedades de $diff
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) {
        return 'ahora';
    }

    $string = array_slice($string, 0, 1);
    return 'hace ' . implode(', ', $string);
}

/**
 * Actualiza un producto en la base de datos
 * 
 * @param int $id ID del producto a actualizar
 * @param array $data Datos del producto (nombre, descripcion_corta, descripcion, precio, stock, categoria)
 * @param array|null $imagen Datos de la imagen cargada ($_FILES['imagen'])
 * @return array Resultado de la operación ['success' => bool, 'message' => string]
 */
function actualizarProducto($id, $data, $imagen = null) {
    global $db;
    
    // Validación de datos básicos
    if (empty($data['nombre']) || floatval($data['precio']) <= 0) {
        return [
            'success' => false, 
            'message' => "El nombre y precio son obligatorios. El precio debe ser mayor que 0."
        ];
    }
    
    try {
        // Verificar que el producto existe
        $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            return [
                'success' => false, 
                'message' => "El producto no existe."
            ];
        }
        
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagen_actual = $producto['imagen'];
        
        // Procesar nueva imagen si se ha proporcionado
        if ($imagen && $imagen['error'] === UPLOAD_ERR_OK && $imagen['size'] > 0) {
            $upload_dir = 'assets/img/productos/';
            
            // Crear directorio si no existe
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    return [
                        'success' => false,
                        'message' => "No se pudo crear el directorio para imágenes."
                    ];
                }
            }
            
            $temp_name = $imagen['tmp_name'];
            $image_name = $imagen['name'];
            $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            
            // Verificar extensión permitida
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($extension, $allowed_extensions)) {
                return [
                    'success' => false,
                    'message' => "Formato de imagen no válido. Se permiten: jpg, jpeg, png, gif"
                ];
            }
            
            // Generar nombre único para la imagen
            $unique_name = strtolower(str_replace(' ', '_', $data['nombre'])) . '_' . uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $unique_name;
            
            // Subir archivo
            if (!move_uploaded_file($temp_name, $upload_path)) {
                return [
                    'success' => false,
                    'message' => "Error al subir la imagen. Verifica los permisos del directorio."
                ];
            }
            
            // Eliminar imagen anterior si no es la predeterminada
            if ($imagen_actual !== 'default.jpg' && $imagen_actual !== 'default.PNG' && file_exists($upload_dir . $imagen_actual)) {
                @unlink($upload_dir . $imagen_actual);
            }
            
            // Actualizar nombre de imagen en los datos
            $data['imagen'] = $unique_name;
        } else {
            // Mantener la imagen actual
            $data['imagen'] = $imagen_actual;
        }
        
        // Actualizar el producto en la base de datos
        $query = "UPDATE productos SET 
                  nombre = ?, 
                  descripcion_corta = ?, 
                  descripcion = ?, 
                  precio = ?, 
                  stock = ?, 
                  imagen = ?, 
                  categoria = ? 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([
            $data['nombre'],
            $data['descripcion_corta'],
            $data['descripcion'],
            $data['precio'],
            $data['stock'],
            $data['imagen'],
            $data['categoria'],
            $id
        ])) {
            return [
                'success' => true,
                'message' => "Producto actualizado correctamente."
            ];
        } else {
            $error_info = $stmt->errorInfo();
            return [
                'success' => false,
                'message' => "Error al actualizar: " . ($error_info[2] ?? "Error desconocido")
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error en updateProduct: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Error del servidor: " . $e->getMessage()
        ];
    }
}

/**
 * Sube una imagen al servidor y devuelve el nombre del archivo
 * 
 * @param array $file Archivo del formulario ($_FILES['campo'])
 * @param string $nombre Nombre base para el archivo
 * @param string $upload_dir Directorio donde se guardará la imagen
 * @return array Resultado de la operación y nombre del archivo
 */
function subirUnaImagenAlProducto($file, $nombre, $upload_dir = '/assets/img/productos/') {
    $result = [
        'success' => false,
        'filename' => 'default.png',
        'error' => ''
    ];
    
    // Verificar si se ha subido un archivo
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
        $result['error'] = 'No se ha subido ningún archivo válido';
        return $result;
    }
    
    // Asegurarse que el directorio existe
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $result['error'] = 'No se pudo crear el directorio de destino';
            return $result;
        }
    }
    
    // Obtener información del archivo
    $temp_name = $file['tmp_name'];
    $image_name = $file['name'];
    $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    
    // Generar un nombre único para la imagen
    $unique_name = strtolower(str_replace(' ', '_', $nombre)) . '_' . uniqid() . '.' . $extension;
    $upload_path = $upload_dir . $unique_name;
    
    // Validar extensión
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowed_extensions)) {
        $result['error'] = 'Formato de imagen no válido. Se permiten: jpg, jpeg, png, gif';
        return $result;
    }
    
    // Subir el archivo
    if (move_uploaded_file($temp_name, $upload_path)) {
        $result['success'] = true;
        $result['filename'] = $unique_name;
    } else {
        $result['error'] = 'No se pudo subir la imagen. Error: ' . error_get_last()['message'];
    }
    
    return $result;
}

/**
 * Crea una notificación administrativa para el panel de control
 * 
 * @param int $usuario_id ID del usuario destinatario
 * @param string $titulo Título de la notificación
 * @param string $mensaje Contenido de la notificación
 * @param string $tipo Tipo de notificación (por defecto 'admin_action')
 * @param string $enlace Enlace opcional relacionado con la notificación
 * @return bool Éxito de la operación
 */
function crearNotificacionAdmin($usuario_id, $titulo, $mensaje, $tipo = 'admin_action', $enlace = '') {
    global $db;
    try {
        $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        return $stmt->execute([$usuario_id, $titulo, $mensaje, $tipo, $enlace]);
    } catch (Exception $e) {
        error_log("Error al crear notificación administrativa: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina un archivo del servidor si existe
 * 
 * @param string $ruta Ruta del archivo a eliminar
 * @return bool Éxito de la operación
 */
function eliminarArchivo($ruta) {
    if (file_exists($ruta)) {
        return unlink($ruta);
    }
    return false;
}

/**
 * Función para eliminar un producto de manera segura manejando todas sus relaciones
 * 
 * @param int $id ID del producto a eliminar
 * @return array Resultado de la operación
 */
function eliminarProducto($id) {
    global $db;
    try {
        // Iniciar transacción para asegurar integridad
        $db->beginTransaction();
        
        // 1. Verificar que el producto existe y obtener su información
        $sql = "SELECT * FROM productos WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            return [
                'success' => false,
                'message' => 'El producto no existe o ya fue eliminado.'
            ];
        }

        // 2. PRIMERO: Eliminar referencias en la tabla carrito
        $sql = "DELETE FROM carrito WHERE producto_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $carritoEliminados = $stmt->rowCount();
        
        // 3. SEGUNDO: Manejar referencias en detalle_pedido
        // Esta es una decisión de diseño - en lugar de eliminarlos,
        // podríamos marcarlos como "producto eliminado" para mantener el historial
        // pero en este caso los marcaremos como NULL para mantener el historial
        $sql = "UPDATE detalle_pedido SET producto_id = NULL WHERE producto_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $pedidosActualizados = $stmt->rowCount();
        
        // 4. TERCERO: Eliminar el producto de la base de datos
        $sql = "DELETE FROM productos WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            // Si no se eliminó ninguna fila, hacer rollback
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'No se pudo eliminar el producto de la base de datos.'
            ];
        }
        
        // 5. Eliminar la imagen principal asociada si no es la predeterminada
        if (strtolower($producto['imagen']) !== 'default.jpg') {
            $imagen_path = 'assets/img/productos/' . $producto['imagen']; // Ruta relativa
            if (file_exists($imagen_path)) {
                eliminarArchivo($imagen_path);
            }
        }
        
        // 6. Crear notificación para el administrador que realizó la eliminación
        if (isset($_SESSION['user_id'])) {
            try {
                $sql = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, fecha_creacion) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                
                $usuario_id = $_SESSION['user_id'];
                $titulo = "Producto Eliminado";
                $mensaje = "El producto '{$producto['nombre']}' ha sido eliminado correctamente.";
                $tipo = "admin_action";
                
                $stmt->execute([$usuario_id, $titulo, $mensaje, $tipo]);
                
            } catch (Exception $e) {
                // Solo registrar el error, no detener el flujo de la eliminación
                error_log("Error al crear notificación de eliminación: " . $e->getMessage());
            }
        }
        
        // Confirmar todas las operaciones
        $db->commit();
        
        return [
            'success' => true,
            'message' => "Producto '{$producto['nombre']}' eliminado correctamente."
        ];
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Registrar el error
        error_log("Error al eliminar producto ID {$id}: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => "Error al eliminar el producto: " . $e->getMessage()
        ];
    }
}

/**
 * Verifica si una tabla existe en la base de datos
 * 
 * @param PDO $db Conexión a la base de datos
 * @param string $tableName Nombre de la tabla
 * @return bool True si existe, false en caso contrario
 */
function verificarTablaSiExiste($db, $tableName) {
    try {
        $result = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica si una columna existe en una tabla
 * 
 * @param PDO $db Conexión a la base de datos
 * @param string $tableName Nombre de la tabla
 * @param string $columnName Nombre de la columna
 * @return bool True si existe, false en caso contrario
 */
function verificarColumnaSiExiste($db, $tableName, $columnName) {
    try {
        $result = $db->query("SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}
