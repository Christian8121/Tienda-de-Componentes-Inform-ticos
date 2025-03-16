<?php
// Verificar que este archivo no se acceda directamente
defined('INCLUDED_FROM_ADMIN_PANEL') or define('INCLUDED_FROM_ADMIN_PANEL', true);

// Determinar si estamos en modo edición o creación
$is_edit_mode = isset($usuario) && !empty($usuario);
$form_title = $is_edit_mode ? 'Editar Usuario' : 'Nuevo Usuario';
$form_action = $is_edit_mode ? "admin_panel.php?section=usuarios&action=edit&id={$id}" : "admin_panel.php?section=usuarios&action=add";
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo $form_title; ?></h6>
            <a href="admin_panel.php?section=usuarios" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="<?php echo $form_action; ?>" method="post" id="usuario-form">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre completo*</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required 
                       value="<?php echo $is_edit_mode ? htmlspecialchars($usuario['nombre']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email*</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       value="<?php echo $is_edit_mode ? htmlspecialchars($usuario['email']) : ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">
                    <?php echo $is_edit_mode ? 'Nueva contraseña (dejar en blanco para no cambiar)' : 'Contraseña*'; ?>
                </label>
                <input type="password" class="form-control" id="password" name="password" 
                       <?php echo $is_edit_mode ? '' : 'required'; ?>>
            </div>
            
            <div class="mb-3">
                <label for="rol" class="form-label">Rol*</label>
                <select class="form-select" id="rol" name="rol" required>
                    <option value="usuario" <?php echo ($is_edit_mode && $usuario['rol'] == 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                    <option value="admin" <?php echo ($is_edit_mode && $usuario['rol'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>
            </div>
            
            <?php if ($is_edit_mode): ?>
            <div class="mb-4">
                <label class="form-label">Fecha de registro</label>
                <p class="form-control-plaintext"><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-end mt-4">
                <a href="admin_panel.php?section=usuarios" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> <?php echo $is_edit_mode ? 'Guardar cambios' : 'Crear usuario'; ?>
                </button>
            </div>
        </form>
    </div>
</div>
