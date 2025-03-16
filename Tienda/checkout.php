<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redireccionar si el carrito está vacío
$cart_data = obtenerProductosDelCarrito();
if (empty($cart_data['items'])) {
    header('Location: cart.php');
    exit;
}

// Redireccionar si el usuario no ha iniciado sesión
if (!estaLogueado()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Variables de control
$error = '';
$success = false;
$order_id = 0;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
    $ciudad = isset($_POST['ciudad']) ? trim($_POST['ciudad']) : '';
    $codigo_postal = isset($_POST['codigo_postal']) ? trim($_POST['codigo_postal']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'Efectivo';
    
    // Validación básica
    if (empty($nombre) || empty($direccion) || empty($ciudad) || empty($codigo_postal) || empty($telefono) || empty($metodo_pago)) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } elseif (!preg_match('/^[0-9]{5}$/', $codigo_postal)) {
        $error = 'El código postal debe contener 5 dígitos.';
    } elseif (!preg_match('/^[0-9]{9}$/', $telefono)) {
        $error = 'El número de teléfono debe contener 9 dígitos sin espacios ni guiones.';
    } else {
        // Conexión a la base de datos
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Crear el pedido
            $query = "INSERT INTO pedidos (usuario_id, total, metodo_pago, direccion_envio, estado) VALUES (?, ?, ?, ?, 'pendiente')";
            $stmt = $db->prepare($query);
            $direccion_completa = $direccion . ', ' . $ciudad . ', ' . $codigo_postal;
            $stmt->execute([$_SESSION['user_id'], $cart_data['total'], $metodo_pago, $direccion_completa]);
            
            // Obtener el ID del pedido recién creado
            $order_id = $db->lastInsertId();
            
            // Registrar el estado inicial en el historial
            $query = "INSERT INTO historial_estados_pedido (pedido_id, estado, comentario) VALUES (?, 'pendiente', 'Pedido recibido')";
            $stmt = $db->prepare($query);
            $stmt->execute([$order_id]);
            
            // Insertar los detalles del pedido
            $query = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            foreach ($cart_data['items'] as $item) {
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['cantidad'],
                    $item['precio']
                ]);
                
                // Actualizar el stock del producto
                $query_update = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt_update = $db->prepare($query_update);
                $stmt_update->execute([$item['cantidad'], $item['id']]);
            }
            
            // Guardar información de pago
            $metodo_pago_text = '';
            switch ($metodo_pago) {
                case 'tarjeta':
                    $metodo_pago_text = 'Tarjeta de crédito/débito';
                    break;
                case 'paypal':
                    $metodo_pago_text = 'PayPal';
                    break;
                case 'transferencia':
                    $metodo_pago_text = 'Transferencia bancaria';
                    break;
                default:
                    $metodo_pago_text = 'Otro';
            }
            
            $query = "UPDATE pedidos SET metodo_pago = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$metodo_pago_text, $order_id]);
            
            // Confirmar la transacción
            $db->commit();
            
            // Una vez verificado que la inserción del pedido en la base de datos es exitosa:
            if ($order_id) {
                // 1. Vaciar el carrito del usuario
                limpiarCarrito(); // Función definida en #file:functions.php
                
                // 2. Registrar notificación en la base de datos
                $sql = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, leida, fecha_creacion)
                        VALUES (?, 'Compra Realizada', 'Tu pedido ha sido procesado con éxito.', 0, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                
                // 3. Mostrar notificación visual (toast)
                $_SESSION['toast_message'] = '¡Pedido realizado correctamente!';
                $_SESSION['toast_type'] = 'success';
                $_SESSION['toast_title'] = 'Compra Exitosa';
            }
            
            // Vaciar el carrito
            $_SESSION['cart'] = [];
            
            // Marcar como exitoso
            $success = true;
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            $db->rollBack();
            $error = "Error al procesar el pedido: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <?php if ($success): ?>
        <!-- Confirmación de pedido exitoso -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-success mb-4 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h4 class="my-0"><i class="bi bi-check-circle-fill me-2"></i> ¡Pedido Realizado con Éxito!</h4>
                    </div>
                    <div class="card-body text-center py-5">
                        <h2 class="card-title mb-4">Gracias por tu compra</h2>
                        <p class="lead mb-4">Tu pedido se ha procesado correctamente.</p>
                        <p class="mb-4">Número de pedido: <strong>#<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></strong></p>
                        <p class="mb-5">Te hemos enviado un correo electrónico con los detalles de tu compra.</p>
                        
                        <!-- Botones de acción -->
                        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                            <a href="tracking.php?id=<?php echo $order_id; ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-truck me-2"></i> Seguir mi pedido
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-shop me-2"></i> Seguir comprando
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Detalles del proceso -->
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle me-2"></i> ¿Qué sucede ahora?</h5>
                    <p class="mb-0">Tu pedido será procesado y enviado en breve. Puedes seguir el estado del envío utilizando la opción "Seguir mi pedido".</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Proceso de pago -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="border-bottom pb-3"><i class="bi bi-credit-card me-2"></i> Finalizar Compra</h1>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Datos de envío y facturación</h5>
                    </div>
                    <div class="card-body">
                        <form id="checkout-form" method="post" action="checkout.php">
                            <!-- Datos personales -->
                            <div class="mb-4">
                                <h6 class="mb-3">Información de contacto</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nombre" class="form-label">Nombre completo *</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo $_SESSION['user_email']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telefono" class="form-label">Teléfono *</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Ej. 612345678" required>
                                        <small class="text-muted">9 dígitos sin espacios ni guiones</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="mb-4">
                                <h6 class="mb-3">Dirección de envío</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="direccion" class="form-label">Dirección completa *</label>
                                        <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Calle, número, piso, puerta, etc." required>
                                    </div>
                                    <div class="col-md-5">
                                        <label for="ciudad" class="form-label">Ciudad *</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="codigo_postal" class="form-label">Código postal *</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" placeholder="Ej. 28001" required>
                                        <small class="text-muted">5 dígitos</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="provincia" class="form-label">Provincia</label>
                                        <select class="form-select" id="provincia" name="provincia">
                                            <option value="">Selecciona...</option>
                                            <option value="Madrid">Madrid</option>
                                            <option value="Barcelona">Barcelona</option>
                                            <option value="Valencia">Valencia</option>
                                            <option value="Sevilla">Sevilla</option>
                                            <option value="Alicante">Alicante</option>
                                            <!-- Más opciones... -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Método de pago -->
                            <div class="mb-4">
                                <h6 class="mb-3">Método de pago</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta" value="tarjeta" checked>
                                            <label class="form-check-label d-flex align-items-center" for="tarjeta">
                                                <i class="bi bi-credit-card fs-5 me-2 text-primary"></i> Tarjeta de crédito/débito
                                            </label>
                                        </div>
                                        <div id="tarjeta-details" class="payment-details mb-3 ps-4">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="card_number" class="form-label">Número de tarjeta</label>
                                                    <input type="text" class="form-control" id="card_number" placeholder="XXXX XXXX XXXX XXXX">
                                                    <small class="text-success">* Esta información no se almacenará (simulación)</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="card_expiry" class="form-label">Fecha de expiración</label>
                                                    <input type="text" class="form-control" id="card_expiry" placeholder="MM/AA">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="card_cvv" class="form-label">CVV</label>
                                                    <input type="text" class="form-control" id="card_cvv" placeholder="123">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="metodo_pago" id="paypal" value="paypal">
                                            <label class="form-check-label d-flex align-items-center" for="paypal">
                                                <i class="bi bi-paypal fs-5 me-2 text-primary"></i> PayPal
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="metodo_pago" id="transferencia" value="transferencia">
                                            <label class="form-check-label d-flex align-items-center" for="transferencia">
                                                <i class="bi bi-bank fs-5 me-2 text-primary"></i> Transferencia bancaria
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notas del pedido -->
                            <div class="mb-4">
                                <h6 class="mb-3">Notas del pedido (opcional)</h6>
                                <textarea class="form-control" id="notas" name="notas" rows="3" placeholder="Instrucciones especiales para la entrega, etc."></textarea>
                            </div>
                            
                            <!-- Aceptar términos -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                                <label class="form-check-label" for="terminos">
                                    He leído y acepto los <a href="#" target="_blank">términos y condiciones</a> y la <a href="#" target="_blank">política de privacidad</a>
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Resumen del pedido -->
                <div class="card shadow-sm mb-4 sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Resumen del pedido</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                                <?php foreach ($cart_data['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>
                                        <span class="text-muted d-block">x<?php echo $item['cantidad']; ?></span>
                                    </td>
                                    <td class="text-end"><?php echo formatearPrecio($item['subtotal']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Subtotal</th>
                                    <th class="text-end"><?php echo formatearPrecio($cart_data['total']); ?></th>
                                </tr>
                                <tr>
                                    <td>Envío</td>
                                    <td class="text-end text-success">Gratis</td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end h5"><?php echo formatearPrecio($cart_data['total']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="submit" form="checkout-form" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-lock"></i> Realizar pedido
                        </button>
                    </div>
                </div>
                
                <!-- Información sobre seguridad -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h6><i class="bi bi-shield-check me-2 text-success"></i> Pago seguro</h6>
                        <p class="small mb-0">Tus datos de pago están protegidos. Utilizamos la tecnología más avanzada para proteger tus transacciones.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Controlar la visibilidad de los detalles de pago
    const metodoPago = document.querySelectorAll('input[name="metodo_pago"]');
    const tarjetaDetails = document.getElementById('tarjeta-details');
    
    metodoPago.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.id === 'tarjeta') {
                tarjetaDetails.style.display = 'block';
            } else {
                tarjetaDetails.style.display = 'none';
            }
        });
    });
    
    // Validación del formulario
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const telefono = document.getElementById('telefono').value;
            const codigoPostal = document.getElementById('codigo_postal').value;
            
            // Validar teléfono (9 dígitos)
            if (!/^\d{9}$/.test(telefono)) {
                e.preventDefault();
                alert('El número de teléfono debe contener 9 dígitos sin espacios ni guiones.');
                return false;
            }
            
            // Validar código postal (5 dígitos)
            if (!/^\d{5}$/.test(codigoPostal)) {
                e.preventDefault();
                alert('El código postal debe contener 5 dígitos.');
                return false;
            }
            
            // Si el método de pago es tarjeta, validar los campos de tarjeta
            const metodoPagoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
            if (metodoPagoSeleccionado && metodoPagoSeleccionado.value === 'tarjeta') {
                const cardNumber = document.getElementById('card_number').value;
                const cardExpiry = document.getElementById('card_expiry').value;
                const cardCvv = document.getElementById('card_cvv').value;
                
                if (!cardNumber || !cardExpiry || !cardCvv) {
                    e.preventDefault();
                    alert('Por favor, complete todos los datos de la tarjeta.');
                    return false;
                }
            }
            
            // Confirmar pedido
            if (!confirm('¿Está seguro de que desea realizar el pedido?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
