<?php
// Verificar que este archivo no se acceda directamente
if (!defined('INCLUDED_FROM_ADMIN_PANEL')) {
    header('Location: ../index.php');
    exit;
}
?>

<div class="alert alert-danger">
    <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i> Ha ocurrido un error</h4>
    <p><?php echo isset($error_message) ? $error_message : 'Error desconocido'; ?></p>
    <hr>
    <p class="mb-0">
        <a href="javascript:history.back()" class="alert-link">
            <i class="bi bi-arrow-left me-1"></i> Volver atrás
        </a>
        o
        <a href="admin_panel.php" class="alert-link">
            <i class="bi bi-house-door me-1"></i> Ir al Dashboard
        </a>
    </p>
</div>
