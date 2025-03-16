<?php

use App\Core\Database;
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar que el usuario ha iniciado sesión
if (!estaLogueado()) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$usuario_id = $_SESSION['user_id'];

switch ($action) {
    case 'get_notifications':
        // Obtener notificaciones recientes para el dropdown
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$usuario_id, $limit]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar las notificaciones para mostrar
        $processed_notifications = [];
        foreach ($notifications as $notification) {
            $processed_notifications[] = [
                'id' => $notification['id'],
                'titulo' => $notification['titulo'],
                'mensaje' => $notification['mensaje'],
                'leida' => (bool)$notification['leida'],
                'tipo' => $notification['tipo'],
                'enlace' => $notification['enlace'],
                'tiempo_relativo' => formatearFechaParaNotificaciones($notification['fecha_creacion']),
                'fecha_creacion' => date('d/m/Y H:i', strtotime($notification['fecha_creacion']))
            ];
        }
        
        // Obtener el conteo de notificaciones no leídas
        $unread_count = obtenerConteodeNotificacionesNoLeidas();
        
        echo json_encode([
            'success' => true, 
            'notifications' => $processed_notifications,
            'unread_count' => $unread_count,
            'has_notifications' => count($processed_notifications) > 0
        ]);
        break;
        
    case 'mark_as_read':
        // Marcar una notificación como leída
        $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
        
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de notificación inválido']);
            exit;
        }
        
        $result = marcarNotificacionComoLeida($notification_id, $usuario_id);
        $unread_count = obtenerConteodeNotificacionesNoLeidas();
        
        echo json_encode([
            'success' => $result, 
            'unread_count' => $unread_count,
            'message' => $result ? 'Notificación marcada como leída' : 'No se pudo marcar la notificación'
        ]);
        break;
        
    case 'mark_all_as_read':
        // Marcar todas las notificaciones como leídas
        $count = marcarTodasNotificacionesComoLeidas($usuario_id);
        
        echo json_encode([
            'success' => true, 
            'count' => $count,
            'message' => $count . ' notificación(es) marcada(s) como leída(s)'
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false, 
            'message' => 'Acción no válida'
        ]);
}
?>
