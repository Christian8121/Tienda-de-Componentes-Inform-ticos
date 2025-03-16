<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    define('INCLUDED_FROM_ADMIN_PANEL', true);
}

// Procesar acciones específicas para notificaciones
switch ($action) {
    case 'add':
        // Formulario para crear nueva notificación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar datos del formulario
            $usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
            $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
            $mensaje = isset($_POST['mensaje']) ? trim($_POST['mensaje']) : '';
            $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : 'general';
            $enlace = isset($_POST['enlace']) ? trim($_POST['enlace']) : '';
            
            // Validar campos obligatorios
            if (empty($titulo) || empty($mensaje)) {
                $error_message = "El título y mensaje son campos obligatorios.";
            } else {
                try {
                    // Crear notificación
                    if ($usuario_id > 0) {
                        // Notificación para un usuario específico
                        $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) 
                                  VALUES (?, ?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$usuario_id, $titulo, $mensaje, $tipo, $enlace]);
                    } else {
                        // Notificación para todos los usuarios
                        $query = "SELECT id FROM usuarios";
                        $stmt = $db->query($query);
                        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($usuarios as $usuario) {
                            $query = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo, enlace) 
                                      VALUES (?, ?, ?, ?, ?)";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$usuario['id'], $titulo, $mensaje, $tipo, $enlace]);
                        }
                    }
                    
                    $success_message = "Notificación enviada correctamente.";
                    header("Location: admin_panel.php?section=notificaciones&success=" . urlencode($success_message));
                    exit;
                } catch (PDOException $e) {
                    $error_message = "Error al crear la notificación: " . $e->getMessage();
                }
            }
        }
        
        // Obtener lista de usuarios para el formulario
        $query = "SELECT id, nombre, email FROM usuarios ORDER BY nombre";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mostrar formulario para añadir notificación
        ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Nueva Notificación</h6>
                    <a href="admin_panel.php?section=notificaciones" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="admin_panel.php?section=notificaciones&action=add" method="post">
                    <div class="mb-3">
                        <label for="usuario_id" class="form-label">Destinatario</label>
                        <select class="form-select" id="usuario_id" name="usuario_id">
                            <option value="0">Todos los usuarios</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nombre'] . ' (' . $usuario['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Seleccione un usuario específico o "Todos los usuarios" para enviar a todos.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título*</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mensaje" class="form-label">Mensaje*</label>
                        <textarea class="form-control" id="mensaje" name="mensaje" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de notificación</label>
                        <select class="form-select" id="tipo" name="tipo">
                            <option value="general">General</option>
                            <option value="alerta">Alerta</option>
                            <option value="info">Información</option>
                            <option value="aviso">Aviso</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="enlace" class="form-label">Enlace (opcional)</label>
                        <input type="text" class="form-control" id="enlace" name="enlace" placeholder="Ejemplo: product.php?id=1">
                        <div class="form-text">URL a la que se dirigirá el usuario al hacer clic en la notificación.</div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a href="admin_panel.php?section=notificaciones" class="btn btn-secondary me-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-bell me-1"></i> Enviar Notificación
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        break;
        
    case 'delete':
        // Eliminar notificación
        if (!$id) {
            $error_message = "ID de notificación no especificado.";
            header("Location: admin_panel.php?section=notificaciones&error=" . urlencode($error_message));
            exit;
        }
        
        try {
            $query = "DELETE FROM notificaciones WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            $success_message = "Notificación eliminada correctamente.";
            header("Location: admin_panel.php?section=notificaciones&success=" . urlencode($success_message));
            exit;
        } catch (PDOException $e) {
            $error_message = "Error al eliminar la notificación: " . $e->getMessage();
            header("Location: admin_panel.php?section=notificaciones&error=" . urlencode($error_message));
            exit;
        }
        break;
        
    case 'list':
    default:
        // Listar notificaciones
        $query = "SELECT n.*, u.nombre as nombre_usuario 
                  FROM notificaciones n
                  JOIN usuarios u ON n.usuario_id = u.id
                  ORDER BY n.fecha_creacion DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mostrar la vista de lista
        ?>
        <div class="card shadow mb-4">
            <div class="card-body">
                <?php if (empty($notificaciones)): ?>
                    <p class="text-center text-muted">No hay notificaciones en el sistema.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notificaciones as $notificacion): ?>
                                    <tr>
                                        <td><?php echo $notificacion['id']; ?></td>
                                        <td><?php echo htmlspecialchars($notificacion['nombre_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($notificacion['titulo']); ?></td>
                                        <td>
                                            <?php
                                            switch ($notificacion['tipo']) {
                                                case 'alerta':
                                                    echo '<span class="badge bg-warning">Alerta</span>';
                                                    break;
                                                case 'info':
                                                    echo '<span class="badge bg-info">Info</span>';
                                                    break;
                                                case 'pedido_entregado':
                                                    echo '<span class="badge bg-success">Pedido entregado</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">General</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($notificacion['leida']): ?>
                                                <span class="badge bg-success">Leída</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">No leída</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_creacion'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info view-notification" 
                                                    data-bs-toggle="modal" data-bs-target="#viewNotificationModal" 
                                                    data-id="<?php echo $notificacion['id']; ?>"
                                                    data-titulo="<?php echo htmlspecialchars($notificacion['titulo']); ?>"
                                                    data-mensaje="<?php echo htmlspecialchars($notificacion['mensaje']); ?>"
                                                    data-enlace="<?php echo htmlspecialchars($notificacion['enlace']); ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal" data-bs-delete-url="admin_panel.php?section=notificaciones&action=delete&id=<?php echo $notificacion['id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Modal para ver detalles de la notificación -->
        <div class="modal fade" id="viewNotificationModal" tabindex="-1" aria-labelledby="viewNotificationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewNotificationModalLabel">Detalles de la Notificación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="notification-title"></h6>
                        <p class="notification-message"></p>
                        <p class="notification-link small"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Script para mostrar detalles de notificación en el modal
        document.addEventListener('DOMContentLoaded', function() {
            const viewNotificationModal = document.getElementById('viewNotificationModal');
            if (viewNotificationModal) {
                viewNotificationModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const titulo = button.getAttribute('data-titulo');
                    const mensaje = button.getAttribute('data-mensaje');
                    const enlace = button.getAttribute('data-enlace');
                    
                    const notificationTitle = this.querySelector('.notification-title');
                    const notificationMessage = this.querySelector('.notification-message');
                    const notificationLink = this.querySelector('.notification-link');
                    
                    notificationTitle.textContent = titulo;
                    notificationMessage.textContent = mensaje;
                    
                    if (enlace && enlace !== 'null') {
                        notificationLink.innerHTML = '<strong>Enlace:</strong> ' + enlace;
                        notificationLink.style.display = 'block';
                    } else {
                        notificationLink.style.display = 'none';
                    }
                });
            }
        });
        </script>
        <?php
        break;
}
?>
