<?php
// Verificar que este archivo no se acceda directamente
defined('INCLUDED_FROM_ADMIN_PANEL') or define('INCLUDED_FROM_ADMIN_PANEL', true);

// Determinar si estamos en modo edición o creación
$is_edit_mode = isset($producto) && !empty($producto);
$form_title = $is_edit_mode ? 'Editar Producto' : 'Nuevo Producto';
$form_action = $is_edit_mode ? "admin_panel.php?section=productos&action=edit&id={$id}" : "admin_panel.php?section=productos&action=add";

// Asegurar que tenemos datos completos del producto en modo edición
if ($is_edit_mode) {
    error_log("Formulario para producto ID: {$producto['id']} - Acción: $form_action");
}
?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo $form_title; ?></h6>
            <a href="admin_panel.php?section=productos" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="<?php echo $form_action; ?>" method="post" enctype="multipart/form-data" id="producto-form">
            <!-- Campo para validar que el formulario fue enviado -->
            <input type="hidden" name="form_submit" value="1">
            
            <div class="row">
                <!-- Información básica -->
                <div class="col-lg-8">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del producto*</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               value="<?php echo $is_edit_mode ? htmlspecialchars($producto['nombre']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion_corta" class="form-label">Descripción corta</label>
                        <input type="text" class="form-control" id="descripcion_corta" name="descripcion_corta"
                               value="<?php echo $is_edit_mode ? htmlspecialchars($producto['descripcion_corta']) : ''; ?>">
                        <div class="form-text">Breve descripción que aparecerá en las tarjetas de producto</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción completa</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="6"><?php echo $is_edit_mode ? htmlspecialchars($producto['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="precio" class="form-label">Precio*</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required 
                                           value="<?php echo $is_edit_mode ? number_format($producto['precio'], 2, '.', '') : ''; ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock*</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0" required 
                                       value="<?php echo $is_edit_mode ? intval($producto['stock']) : '0'; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría*</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $categorias = ['Procesadores', 'Tarjetas Gráficas', 'Memoria RAM', 'Almacenamiento', 
                                                'Placas Base', 'Fuentes de Alimentación', 'Refrigeración', 'Periféricos'];
                                    foreach ($categorias as $cat) {
                                        $selected = $is_edit_mode && $producto['categoria'] == $cat ? 'selected' : '';
                                        echo "<option value=\"$cat\" $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Imagen -->
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header py-2">
                            <h6 class="m-0 font-weight-bold text-primary">Imagen del producto</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php 
                                $img_src = "assets/img/productos/";
                                $img_src .= $is_edit_mode && !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : 'default.jpg';
                                ?>
                                <img src="<?php echo $img_src; ?>" 
                                     alt="<?php echo $is_edit_mode ? htmlspecialchars($producto['nombre']) : 'Sin imagen'; ?>" 
                                     class="img-thumbnail form-image-preview mb-2" id="imagePreview">
                            </div>
                            
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Seleccionar imagen</label>
                                <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*">
                                <div class="form-text">Formatos admitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información sobre la imagen actual -->
                    <?php if ($is_edit_mode && !empty($producto['imagen'])): ?>
                    <div class="alert alert-info">
                        <small>
                            <strong>Imagen actual:</strong> <?php echo htmlspecialchars($producto['imagen']); ?><br>
                            <em>Sube una nueva imagen para reemplazarla o deja este campo vacío para mantener la actual.</em>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    <?php echo $is_edit_mode ? 'Al guardar cambios, será redirigido a la lista de productos.' : 'Al crear el producto, será redirigido a la lista de productos.'; ?>
                </div>
                <div>
                    <a href="admin_panel.php?section=productos" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" id="submit-btn" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> <?php echo $is_edit_mode ? 'Guardar cambios' : 'Crear producto'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Vista previa de imagen y validación del formulario
document.addEventListener('DOMContentLoaded', function() {
    // Código para la vista previa de imagen
    const imagen = document.getElementById('imagen');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imagen && imagePreview) {
        imagen.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Validación mejorada del formulario
    const form = document.getElementById('producto-form');
    const submitBtn = document.getElementById('submit-btn');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            // Validar campos obligatorios
            const nombre = document.getElementById('nombre').value.trim();
            const precio = parseFloat(document.getElementById('precio').value);
            const categoria = document.getElementById('categoria').value;
            
            let isValid = true;
            let errorMsg = '';
            
            if (nombre === '') {
                isValid = false;
                errorMsg += "- El nombre del producto es obligatorio.\n";
            }
            
            if (isNaN(precio) || precio <= 0) {
                isValid = false;
                errorMsg += "- El precio debe ser mayor que 0.\n";
            }
            
            if (categoria === '') {
                isValid = false;
                errorMsg += "- Debe seleccionar una categoría.\n";
            }
            
            if (!isValid) {
                event.preventDefault();
                alert("Por favor corrija los siguientes errores:\n" + errorMsg);
            } else {
                // Todo correcto, mostrar indicación de procesamiento
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
                
                // Depuración - Verificar URL de acción
                console.log("Enviando formulario a: " + form.getAttribute('action'));
                
                // Log para depuración
                console.log('Enviando formulario:', {
                    action: form.getAttribute('action'),
                    method: form.getAttribute('method'),
                    enctype: form.getAttribute('enctype'),
                    fields: {
                        nombre: document.getElementById('nombre').value,
                        precio: document.getElementById('precio').value,
                        stock: document.getElementById('stock').value,
                        categoria: document.getElementById('categoria').value
                    }
                });
            }
        });
    }
});
</script>
