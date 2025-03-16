<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario ha iniciado sesión y es administrador
if (!estaLogueado() || !esAdmin()) {
    header("Location: login.php");
    exit;
}

// Inicializar la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Determinar qué sección mostrar
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar si hay un mensaje de toast en la sesión
$show_toast = false;
$toast_message = '';
$toast_type = 'success';

if (isset($_SESSION['toast_message'])) {
    $show_toast = true;
    $toast_message = $_SESSION['toast_message'];
    $toast_type = $_SESSION['toast_type'] ?? 'success';
    
    // Limpiar las variables de sesión después de usarlas
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
}

// También podemos verificar por parámetros en la URL
if (isset($_GET['update_success'])) {
    $show_toast = true;
    $toast_message = "¡Producto actualizado correctamente!";
    $toast_type = "success";
}

// Título de la página según la sección
$page_title = "Panel de Administración";
switch ($section) {
    case 'productos':
        $page_title = "Gestión de Productos";
        break;
    case 'usuarios':
        $page_title = "Gestión de Usuarios";
        break;
    case 'pedidos':
        $page_title = "Gestión de Pedidos";
        break;
    case 'notificaciones':
        $page_title = "Gestión de Notificaciones";
        break;
}

// Mensajes de operaciones
$success_message = '';
$error_message = '';

// Procesar acciones POST generales
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_product':
                // Código para actualizar producto
                // ...existing code...
                break;
            
            case 'create_product':
                // Código para crear producto
                // ...existing code...
                break;
            
            case 'update_user':
                // Código para actualizar usuario
                // ...existing code...
                break;
                
            case 'create_user':
                // Código para crear usuario
                // ...existing code...
                break;
                
            case 'update_order_status':
                // Código para actualizar estado de pedido
                // ...existing code...
                break;
                
            case 'create_notification':
                // Código para crear notificación
                // ...existing code...
                break;
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar / Menú lateral -->
        <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky">
                <div class="p-3 mb-3 bg-primary text-white rounded">
                    <h5><i class="bi bi-speedometer2 me-2"></i>Panel Admin</h5>
                    <p class="mb-0">Bienvenido, <?php echo $_SESSION['user_name']; ?></p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'dashboard' ? 'active' : ''; ?>" href="admin_panel.php">
                            <i class="bi bi-house-door me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'productos' ? 'active' : ''; ?>" href="admin_panel.php?section=productos">
                            <i class="bi bi-box-seam me-2"></i> Productos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'usuarios' ? 'active' : ''; ?>" href="admin_panel.php?section=usuarios">
                            <i class="bi bi-people me-2"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'pedidos' ? 'active' : ''; ?>" href="admin_panel.php?section=pedidos">
                            <i class="bi bi-cart3 me-2"></i> Pedidos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $section === 'notificaciones' ? 'active' : ''; ?>" href="admin_panel.php?section=notificaciones">
                            <i class="bi bi-bell me-2"></i> Notificaciones
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-shop me-2"></i> Ver Tienda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Contenido principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?php echo $page_title; ?></h1>
                <?php if ($section !== 'dashboard'): ?>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($section === 'productos' && $action === 'list'): ?>
                            <a href="admin_panel.php?section=productos&action=add" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Nuevo Producto
                            </a>
                        <?php elseif ($section === 'usuarios' && $action === 'list'): ?>
                            <a href="admin_panel.php?section=usuarios&action=add" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
                            </a>
                        <?php elseif ($section === 'notificaciones' && $action === 'list'): ?>
                            <a href="admin_panel.php?section=notificaciones&action=add" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg me-1"></i> Nueva Notificación
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php
            // Cargar el contenido según la sección seleccionada
            switch ($section) {
                case 'productos':
                    include 'admin/productos.php';
                    break;
                case 'usuarios':
                    include 'admin/usuarios.php';
                    break;
                case 'pedidos':
                    include 'admin/pedidos.php';
                    break;
                case 'notificaciones':
                    include 'admin/notificaciones.php';
                    break;
                default:
                    include 'admin/dashboard.php';
            }
            ?>
        </main>
    </div>
</div>

<!-- Sistema de Toast para notificaciones -->
<?php if ($show_toast): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="adminToast" class="toast align-items-center text-white bg-<?php echo $toast_type; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php if ($toast_type === 'success'): ?>
                    <i class="bi bi-check-circle-fill me-2"></i>
                <?php elseif ($toast_type === 'warning'): ?>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php elseif ($toast_type === 'danger'): ?>
                    <i class="bi bi-x-circle-fill me-2"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle-fill me-2"></i>
                <?php endif; ?>
                <?php echo $toast_message; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// Inicializar y mostrar el toast
document.addEventListener('DOMContentLoaded', function() {
    const toastElement = document.getElementById('adminToast');
    if (toastElement) {
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000 // El toast desaparecerá después de 5 segundos
        });
        toast.show();
    }
});
</script>
<?php endif; ?>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
// Script para manejar la confirmación de eliminación
document.addEventListener('DOMContentLoaded', function() {
    // Configurar el modal de confirmación de eliminación
    const deleteModal = document.getElementById('deleteConfirmModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const deleteUrl = button.getAttribute('data-bs-delete-url');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            confirmDeleteBtn.setAttribute('href', deleteUrl);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>