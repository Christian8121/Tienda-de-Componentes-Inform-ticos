<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    define('INCLUDED_FROM_ADMIN_PANEL', true);
}

// Procesar acciones específicas para usuarios
switch ($action) {
    case 'add':
        // Agregar nuevo usuario
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar datos del formulario
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'usuario';
            
            // Validar campos obligatorios
            if (empty($nombre) || empty($email) || empty($password)) {
                $error_message = "Todos los campos son obligatorios.";
            } else {
                try {
                    // Verificar si el email ya está registrado
                    $query = "SELECT id FROM usuarios WHERE email = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$email]);
                    
                    if ($stmt->rowCount() > 0) {
                        $error_message = "Este email ya está registrado.";
                    } else {
                        // Cifrar la contraseña
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insertar el nuevo usuario
                        $query = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$nombre, $email, $hashed_password, $rol]);
                        
                        $success_message = "Usuario creado correctamente.";
                        header("Location: admin_panel.php?section=usuarios&success=" . urlencode($success_message));
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_message = "Error al crear el usuario: " . $e->getMessage();
                }
            }
        }
        
        // Mostrar formulario para agregar usuario
        include 'admin/usuarios_form.php';
        break;
        
    case 'edit':
        // Editar usuario existente
        if (!$id) {
            $error_message = "ID de usuario no especificado.";
            header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
            exit;
        }
        
        // Obtener información del usuario
        $query = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            $error_message = "Usuario no encontrado.";
            header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
            exit;
        }
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar datos del formulario
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $password = isset($_POST['password']) ? trim($_POST['password']) : '';
            $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'usuario';
            
            // Validar campos obligatorios
            if (empty($nombre) || empty($email)) {
                $error_message = "El nombre y email son campos obligatorios.";
            } else {
                try {
                    // Verificar si el email ya está en uso por otro usuario
                    $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$email, $id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $error_message = "Este email ya está siendo utilizado por otro usuario.";
                    } else {
                        // Actualizar usuario con o sin nueva contraseña
                        if (!empty($password)) {
                            // Cifrar la nueva contraseña
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $query = "UPDATE usuarios SET nombre = ?, email = ?, password = ?, rol = ? WHERE id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$nombre, $email, $hashed_password, $rol, $id]);
                        } else {
                            // Actualizar sin cambiar la contraseña
                            $query = "UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$nombre, $email, $rol, $id]);
                        }
                        
                        $success_message = "Usuario actualizado correctamente.";
                        header("Location: admin_panel.php?section=usuarios&success=" . urlencode($success_message));
                        exit;
                    }
                } catch (PDOException $e) {
                    $error_message = "Error al actualizar el usuario: " . $e->getMessage();
                }
            }
        }
        
        // Mostrar formulario para editar usuario
        include 'admin/usuarios_form.php';
        break;
        
    case 'delete':
        // Eliminar usuario
        if (!$id) {
            $error_message = "ID de usuario no especificado.";
            header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
            exit;
        }
        
        // Verificar que no estemos eliminando el usuario actual
        if ($id == $_SESSION['user_id']) {
            $error_message = "No puedes eliminar tu propio usuario.";
            header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
            exit;
        }
        
        try {
            // Primero verificar si el usuario tiene pedidos
            $queryCheckPedidos = "SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?";
            $stmtCheckPedidos = $db->prepare($queryCheckPedidos);
            $stmtCheckPedidos->execute([$id]);
            $hasPedidos = $stmtCheckPedidos->fetchColumn() > 0;
            
            if ($hasPedidos) {
                $error_message = "No se puede eliminar el usuario porque tiene pedidos asociados. Recomendamos anonimizar sus datos en su lugar.";
                header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
                exit;
            }
            
            // Las tablas carrito y notificaciones tienen ON DELETE CASCADE, 
            // por lo que se eliminarán automáticamente al eliminar el usuario
            $query = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            $success_message = "Usuario eliminado correctamente.";
            header("Location: admin_panel.php?section=usuarios&success=" . urlencode($success_message));
            exit;
        } catch (PDOException $e) {
            $error_message = "Error al eliminar el usuario: " . $e->getMessage();
            header("Location: admin_panel.php?section=usuarios&error=" . urlencode($error_message));
            exit;
        }
        break;
        
    case 'list':
    default:
        // Listar usuarios
        $query = "SELECT * FROM usuarios ORDER BY id ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mostrar la vista de lista
        ?>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Fecha de registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay usuarios registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <?php if ($usuario['rol'] == 'admin'): ?>
                                                <span class="badge bg-danger">Administrador</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Usuario</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></td>
                                        <td>
                                            <a href="admin_panel.php?section=usuarios&action=edit&id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-bs-delete-url="admin_panel.php?section=usuarios&action=delete&id=<?php echo $usuario['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        break;
}
?>
