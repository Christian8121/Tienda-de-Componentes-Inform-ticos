<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si se ha proporcionado un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$pedido_id = intval($_GET['id']);

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    $_SESSION['redirect_after_login'] = 'tracking.php?id=' . $pedido_id;
    header('Location: login.php');
    exit;
}

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener información del pedido
$query = "SELECT p.*, u.nombre AS nombre_usuario 
          FROM pedidos p 
          JOIN usuarios u ON p.usuario_id = u.id 
          WHERE p.id = ? AND p.usuario_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id, $_SESSION['user_id']]);

// Verificar si el pedido existe y pertenece al usuario
if ($stmt->rowCount() == 0) {
    header('Location: index.php');
    exit;
}

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener los productos del pedido
$query = "SELECT dp.*, p.nombre, p.imagen 
          FROM detalle_pedido dp 
          JOIN productos p ON dp.producto_id = p.id 
          WHERE dp.pedido_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener el historial de estados del pedido
$query = "SELECT * FROM historial_estados_pedido 
          WHERE pedido_id = ? 
          ORDER BY fecha_cambio DESC";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id]);
$historial_estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular el progreso del pedido basado en el estado actual
$progreso = 0;
switch ($pedido['estado']) {
    case 'pendiente':
        $progreso = 25;
        $clase_estado = 'bg-warning';
        break;
    case 'procesando':
        $progreso = 50;
        $clase_estado = 'bg-info';
        break;
    case 'enviado':
        $progreso = 75;
        $clase_estado = 'bg-primary';
        break;
    case 'entregado':
        $progreso = 100;
        $clase_estado = 'bg-success';
        break;
    default:
        $progreso = 0;
        $clase_estado = 'bg-secondary';
}

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Seguimiento de Pedido #<?php echo str_pad($pedido_id, 8, '0', STR_PAD_LEFT); ?></li>
                </ol>
            </nav>
            <h1 class="border-bottom pb-3">
                <i class="bi bi-truck me-2"></i> Seguimiento de Pedido
            </h1>
        </div>
    </div>

    <!-- Resumen y estado del pedido -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Estado del Pedido</h5>
                    <span class="badge <?php echo $clase_estado; ?>"><?php echo ucfirst($pedido['estado']); ?></span>
                </div>
                <div class="card-body">
                    <!-- Barra de progreso -->
                    <div class="mb-4">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated <?php echo $clase_estado; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $progreso; ?>%;" 
                                 aria-valuenow="<?php echo $progreso; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $progreso; ?>%
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estados del pedido -->
                    <div class="tracking-steps">
                        <div class="d-flex justify-content-between position-relative mb-5">
                            <div class="tracking-line position-absolute" style="top: 20px; height: 3px; left: 7%; width: 86%; background-color: #dee2e6; z-index: 1;"></div>
                            
                            <!-- Pedido recibido -->
                            <div class="text-center position-relative" style="z-index: 2;">
                                <div class="tracking-icon mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center 
                                          <?php echo $progreso >= 25 ? $clase_estado : 'bg-secondary'; ?>" 
                                     style="width: 50px; height: 50px; color: white;">
                                    <i class="bi bi-basket"></i>
                                </div>
                                <p class="mb-0 small">Recibido</p>
                                <small class="text-muted">
                                    <?php 
                                    echo date('d/m/Y', strtotime($pedido['fecha_pedido']));
                                    ?>
                                </small>
                            </div>
                            
                            <!-- Procesando -->
                            <div class="text-center position-relative" style="z-index: 2;">
                                <div class="tracking-icon mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center 
                                          <?php echo $progreso >= 50 ? $clase_estado : 'bg-secondary'; ?>" 
                                     style="width: 50px; height: 50px; color: white;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <p class="mb-0 small">Procesando</p>
                                <small class="text-muted">
                                    <?php 
                                    // Mostrar fecha de procesamiento si existe
                                    $fecha_procesando = '';
                                    foreach ($historial_estados as $historial) {
                                        if ($historial['estado'] == 'procesando') {
                                            $fecha_procesando = date('d/m/Y', strtotime($historial['fecha_cambio']));
                                            break;
                                        }
                                    }
                                    echo $fecha_procesando ?: '-';
                                    ?>
                                </small>
                            </div>
                            
                            <!-- Enviado -->
                            <div class="text-center position-relative" style="z-index: 2;">
                                <div class="tracking-icon mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center 
                                          <?php echo $progreso >= 75 ? $clase_estado : 'bg-secondary'; ?>" 
                                     style="width: 50px; height: 50px; color: white;">
                                    <i class="bi bi-truck"></i>
                                </div>
                                <p class="mb-0 small">Enviado</p>
                                <small class="text-muted">
                                    <?php 
                                    // Mostrar fecha de envío si existe
                                    $fecha_enviado = '';
                                    foreach ($historial_estados as $historial) {
                                        if ($historial['estado'] == 'enviado') {
                                            $fecha_enviado = date('d/m/Y', strtotime($historial['fecha_cambio']));
                                            break;
                                        }
                                    }
                                    echo $fecha_enviado ?: '-';
                                    ?>
                                </small>
                            </div>
                            
                            <!-- Entregado -->
                            <div class="text-center position-relative" style="z-index: 2;">
                                <div class="tracking-icon mb-2 mx-auto rounded-circle d-flex align-items-center justify-content-center 
                                          <?php echo $progreso >= 100 ? $clase_estado : 'bg-secondary'; ?>" 
                                     style="width: 50px; height: 50px; color: white;">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <p class="mb-0 small">Entregado</p>
                                <small class="text-muted">
                                    <?php 
                                    // Mostrar fecha de entrega si existe
                                    $fecha_entregado = '';
                                    foreach ($historial_estados as $historial) {
                                        if ($historial['estado'] == 'entregado') {
                                            $fecha_entregado = date('d/m/Y', strtotime($historial['fecha_cambio']));
                                            break;
                                        }
                                    }
                                    echo $fecha_entregado ?: '-';
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mensaje del estado actual -->
                    <div class="alert <?php echo str_replace('bg-', 'alert-', $clase_estado); ?> mb-4">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php if($pedido['estado'] == 'pendiente'): ?>
                                    <i class="bi bi-hourglass fs-1"></i>
                                <?php elseif($pedido['estado'] == 'procesando'): ?>
                                    <i class="bi bi-box-seam fs-1"></i>
                                <?php elseif($pedido['estado'] == 'enviado'): ?>
                                    <i class="bi bi-truck fs-1"></i>
                                <?php elseif($pedido['estado'] == 'entregado'): ?>
                                    <i class="bi bi-check2-circle fs-1"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if($pedido['estado'] == 'pendiente'): ?>
                                    <h5>Tu pedido ha sido recibido</h5>
                                    <p class="mb-0">Estamos verificando el pago. Pronto comenzaremos a preparar tu pedido.</p>
                                <?php elseif($pedido['estado'] == 'procesando'): ?>
                                    <h5>Tu pedido está siendo preparado</h5>
                                    <p class="mb-0">Estamos empaquetando tus productos. Pronto será enviado.</p>
                                <?php elseif($pedido['estado'] == 'enviado'): ?>
                                    <h5>¡Tu pedido está en camino!</h5>
                                    <p class="mb-0">Hemos enviado tu pedido. Puedes hacer seguimiento con el número de envío.</p>
                                <?php elseif($pedido['estado'] == 'entregado'): ?>
                                    <h5>¡Tu pedido ha sido entregado!</h5>
                                    <p class="mb-0">Tu pedido ha sido entregado con éxito. ¡Gracias por tu compra!</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Historial de estados -->
                    <div class="mb-4">
                        <h6>Historial de estados</h6>
                        <div class="list-group">
                            <?php foreach($historial_estados as $historial): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <?php 
                                        $estado_text = '';
                                        $estado_icon = '';
                                        switch($historial['estado']) {
                                            case 'pendiente':
                                                $estado_text = 'Pedido recibido';
                                                $estado_icon = 'hourglass';
                                                break;
                                            case 'procesando':
                                                $estado_text = 'En preparación';
                                                $estado_icon = 'box-seam';
                                                break;
                                            case 'enviado':
                                                $estado_text = 'Pedido enviado';
                                                $estado_icon = 'truck';
                                                break;
                                            case 'entregado':
                                                $estado_text = 'Entregado';
                                                $estado_icon = 'check2-circle';
                                                break;
                                        }
                                        echo "<i class='bi bi-{$estado_icon} me-2'></i> {$estado_text}";
                                        ?>
                                    </h6>
                                    <small><?php echo date('d/m/Y H:i', strtotime($historial['fecha_cambio'])); ?></small>
                                </div>
                                <?php if(!empty($historial['comentario'])): ?>
                                    <p class="mb-1 small text-muted"><?php echo htmlspecialchars($historial['comentario']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalles de envío -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Detalles de Envío</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Dirección de envío:</strong></p>
                            <p class="mb-3"><?php echo htmlspecialchars($pedido['direccion_envio'] ?? 'No especificada'); ?></p>
                            
                            <p class="mb-1"><strong>Método de pago:</strong></p>
                            <p class="mb-0"><?php echo htmlspecialchars($pedido['metodo_pago'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha del pedido:</strong></p>
                            <p class="mb-3"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                            
                            <?php if($pedido['estado'] == 'enviado' || $pedido['estado'] == 'entregado'): ?>
                                <p class="mb-1"><strong>Número de seguimiento:</strong></p>
                                <p class="mb-0">TRK<?php echo str_pad($pedido_id * 7 + 1000000, 10, '0', STR_PAD_LEFT); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if($pedido['estado'] == 'entregado'): ?>
                        <!-- Botón de descarga de factura -->
                        <div class="mt-4">
                            <a href="generate_invoice.php?id=<?php echo $pedido_id; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-2"></i> Descargar factura
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Productos en el pedido -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Productos en tu pedido</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="py-3">Producto</th>
                                    <th scope="col" class="text-center py-3">Precio</th>
                                    <th scope="col" class="text-center py-3">Cantidad</th>
                                    <th scope="col" class="text-end py-3">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="assets/img/productos/<?php echo !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg'; ?>" 
                                                 class="cart-item-img me-3" 
                                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($producto['nombre']); ?></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php echo formatearPrecio($producto['precio_unitario']); ?>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php echo $producto['cantidad']; ?>
                                    </td>
                                    <td class="align-middle text-end">
                                        <?php echo formatearPrecio($producto['precio_unitario'] * $producto['cantidad']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                                    <td class="text-end"><strong><?php echo formatearPrecio($pedido['total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Asistencia -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">¿Necesitas ayuda?</h5>
                </div>
                <div class="card-body">
                    <p>Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos.</p>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope me-2 text-primary"></i>
                            <strong>Email:</strong>
                        </div>
                        <p class="ms-4 mb-0">soporte@sevestore.com</p>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone me-2 text-primary"></i>
                            <strong>Teléfono:</strong>
                        </div>
                        <p class="ms-4 mb-0">+34 123 456 789</p>
                    </div>
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-clock me-2 text-primary"></i>
                            <strong>Horario de atención:</strong>
                        </div>
                        <p class="ms-4 mb-0">Lunes a Viernes: 9:00 - 18:00<br>Sábados: 10:00 - 14:00</p>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <a href="#" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-text me-2"></i> Contactar con soporte
                    </a>
                </div>
            </div>
            
            <!-- FAQs -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Preguntas frecuentes</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="false" aria-controls="faqCollapse1">
                                    ¿Cuánto tiempo tarda en llegar mi pedido?
                                </button>
                            </h2>
                            <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    El tiempo de entrega habitual es de 3-5 días laborables, dependiendo de la ubicación y disponibilidad de los productos.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                    ¿Puedo cambiar la dirección de envío?
                                </button>
                            </h2>
                            <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Solo es posible cambiar la dirección si tu pedido aún se encuentra en estado "Pendiente". Contacta con nuestro servicio de atención al cliente para solicitar el cambio.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                    ¿Cómo puedo cancelar mi pedido?
                                </button>
                            </h2>
                            <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Los pedidos solo pueden ser cancelados si están en estado "Pendiente". Para cancelar, contacta con nuestro servicio de atención al cliente lo antes posible.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para actualizar el estado del pedido (para demostración) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Esta función solo es para demostración y simulación
    // En un entorno real, el estado se actualizaría desde el panel de administración
    // o mediante un proceso automatizado
    
    // Simular actualización de estado cada 30 segundos (solo para demostración)
    const simulateStatusUpdate = () => {
        // Solo simular para pedidos pendientes o en proceso
        const currentStatus = '<?php echo $pedido['estado']; ?>';
        if (currentStatus === 'pendiente' || currentStatus === 'procesando' || currentStatus === 'enviado') {
            fetch('simulate_status_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pedido_id=<?php echo $pedido_id; ?>&current_status=${currentStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status_changed) {
                    // Si el estado cambió, recargar la página para ver la actualización
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    };
    
    // Ejecutar la simulación cada 30 segundos
    const updateInterval = setInterval(simulateStatusUpdate, 30000);
    
    // Limpiar intervalo cuando el usuario abandona la página
    window.addEventListener('beforeunload', function() {
        clearInterval(updateInterval);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
