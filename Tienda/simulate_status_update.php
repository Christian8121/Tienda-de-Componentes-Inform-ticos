<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Este archivo simula la actualización automática de estados del pedido
// En un sistema real, esto se haría a través del panel de administración
// o mediante un proceso automatizado en segundo plano

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que el usuario ha iniciado sesión
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Obtener datos del pedido
$pedido_id = isset($_POST['pedido_id']) ? intval($_POST['pedido_id']) : 0;
$current_status = isset($_POST['current_status']) ? $_POST['current_status'] : '';

if (empty($pedido_id) || empty($current_status)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Verificar que el pedido pertenece al usuario actual
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id, $_SESSION['user_id']]);

if ($stmt->rowCount() == 0) {
    echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
    exit;
}

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

// Determinar el siguiente estado (para simulación)
$next_status = '';
$comentario = '';

switch ($current_status) {
    case 'pendiente':
        $next_status = 'procesando';
        $comentario = 'Tu pedido está siendo procesado y preparado para envío.';
        break;
    case 'procesando':
        $next_status = 'enviado';
        $comentario = 'Tu pedido ha sido enviado y está en camino.';
        break;
    case 'enviado':
        $next_status = 'entregado';
        $comentario = '¡Tu pedido ha sido entregado con éxito!';
        break;
    default:
        echo json_encode(['success' => true, 'status_changed' => false]);
        exit;
}

// Decisión aleatoria para simular si actualizar o no el estado (20% de probabilidad)
// Esto es solo para simular comportamiento aleatorio, en un sistema real
// seguiría la lógica de negocio y eventos reales
$should_update = (mt_rand(1, 100) <= 20);

if (!$should_update) {
    echo json_encode(['success' => true, 'status_changed' => false]);
    exit;
}

try {
    // Iniciar transacción
    $db->beginTransaction();
    
    // Actualizar el estado del pedido
    $query = "UPDATE pedidos SET estado = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$next_status, $pedido_id]);
    
    // Registrar el cambio en el historial
    $query = "INSERT INTO historial_estados_pedido (pedido_id, estado, comentario) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$pedido_id, $next_status, $comentario]);
    
    // Si el estado cambió a "entregado", crear una notificación
    if ($next_status === 'entregado') {
        crearNotificacionPedidoEntregado($pedido_id, $_SESSION['user_id']);
    }
    
    // Confirmar la transacción
    $db->commit();
    
    // Responder con éxito
    echo json_encode([
        'success' => true, 
        'status_changed' => true,
        'old_status' => $current_status,
        'new_status' => $next_status,
        'message' => 'Estado actualizado correctamente',
        'show_notification' => ($next_status === 'entregado')
    ]);
    
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $db->rollBack();
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar el estado: ' . $e->getMessage()
    ]);
}
?>
