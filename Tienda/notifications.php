<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirigir si el usuario no está logueado
if (!estaLogueado()) {
    header('Location: login.php');
    exit;
}

// Marcar una notificación como leída si se proporciona un ID
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    marcarNotificacionComoLeida($_GET['mark_read'], $_SESSION['user_id']);
}

// Marcar todas como leídas si se solicita
if (isset($_GET['mark_all_read'])) {
    marcarTodasNotificacionesComoLeidas($_SESSION['user_id']);
}

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener todas las notificaciones del usuario
$query = "SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="border-bottom pb-3">
                <i class="bi bi-bell me-2"></i> Mis Notificaciones
            </h1>
        </div>
    </div>
    
    <?php if (empty($notificaciones)): ?>
        <div class="alert alert-info">
            <p class="mb-0"><i class="bi bi-info-circle me-2"></i> No tienes notificaciones.</p>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Todas las notificaciones</h5>
                <a href="?mark_all_read=1" class="btn btn-sm btn-primary">
                    <i class="bi bi-check-all me-1"></i> Marcar todas como leídas
                </a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($notificaciones as $notificacion): ?>
                    <div class="list-group-item notification-page-item <?php echo $notificacion['leida'] ? '' : 'unread'; ?>">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">
                                    <?php
                                    $iconClass = '';
                                    switch ($notificacion['tipo']) {
                                        case 'pedido_entregado':
                                            $iconClass = 'bi-box-seam text-success';
                                            break;
                                        case 'pedido_enviado':
                                            $iconClass = 'bi-truck text-primary';
                                            break;
                                        case 'error':
                                            $iconClass = 'bi-exclamation-triangle text-danger';
                                            break;
                                        case 'alerta':
                                            $iconClass = 'bi-exclamation-circle text-warning';
                                            break;
                                        default:
                                            $iconClass = 'bi-bell text-info';
                                    }
                                    ?>
                                    <i class="bi <?php echo $iconClass; ?> me-2"></i> <?php echo htmlspecialchars($notificacion['titulo']); ?>
                                </h5>
                                <p class="mb-1"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($notificacion['fecha_creacion'])); ?> 
                                    (<?php echo formatearFechaParaNotificaciones($notificacion['fecha_creacion']); ?>)
                                </small>
                            </div>
                            <div class="d-flex">
                                <?php if (!$notificacion['leida']): ?>
                                    <a href="?mark_read=<?php echo $notificacion['id']; ?>" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="tooltip" title="Marcar como leída">
                                        <i class="bi bi-check-lg"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($notificacion['enlace'])): ?>
                                    <a href="<?php echo $notificacion['enlace']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Ver detalles">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Marcar notificaciones como leídas al cargar la página
    document.querySelectorAll('.notification-page-item.unread').forEach(item => {
        const links = item.querySelectorAll('a');
        links.forEach(link => {
            // Añadir parámetro para redirigir de vuelta a esta página después de marcar como leída
            if (link.href.includes('mark_read=')) {
                link.addEventListener('click', function(e) {
                    // AJAX para marcar como leída sin recargar la página
                    e.preventDefault();
                    
                    const notificationId = link.href.split('mark_read=')[1];
                    const formData = new FormData();
                    formData.append('action', 'mark_as_read');
                    formData.append('notification_id', notificationId);
                    
                    fetch('ajax/notifications.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar UI
                            const notificationItem = link.closest('.notification-page-item');
                            notificationItem.classList.remove('unread');
                            link.remove();
                        }
                    });
                });
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
