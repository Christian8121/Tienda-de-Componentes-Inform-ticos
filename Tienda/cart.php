<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Inicializar el carrito si no existe y el usuario no está logueado
if (!estaLogueado() && !isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    
    switch ($action) {
        case 'add':
            // Añadir producto al carrito
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
            
            $result = añadirProductoACarrito($producto_id, $cantidad);
            
            // Responder con JSON si es una solicitud AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Producto añadido al carrito' : 'No se pudo añadir el producto',
                    'cartCount' => verificarSiHayProductosEnCarrito()
                ]);
                exit;
            }
            
            // Redirigir de vuelta a la página anterior
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
            header("Location: $referer");
            exit;
            
        case 'update':
            // Actualizar cantidad de un producto
            $cantidad = (int)$_POST['cantidad'];
            
            $result = actualizarCantidadProductoEnCarrito($producto_id, $cantidad);
            
            // Responder con JSON si es una solicitud AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $cartData = obtenerProductosDelCarrito();
                $item_subtotal = 0;
                
                foreach ($cartData['items'] as $item) {
                    if ($item['id'] == $producto_id) {
                        $item_subtotal = $item['subtotal'];
                        break;
                    }
                }
                
                echo json_encode([
                    'success' => $result,
                    'cartCount' => verificarSiHayProductosEnCarrito(),
                    'total' => $cartData['total'],
                    'item_subtotal' => $item_subtotal
                ]);
                exit;
            }
            
            header("Location: cart.php");
            exit;
            
        case 'remove':
            // Eliminar un producto del carrito
            $result = eliminarProductoDelCarrito($producto_id);
            
            // Responder con JSON si es una solicitud AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode([
                    'success' => $result,
                    'message' => $result ? 'Producto eliminado del carrito' : 'No se pudo eliminar el producto',
                    'cartCount' => verificarSiHayProductosEnCarrito()
                ]);
                exit;
            }
            
            header("Location: cart.php");
            exit;
            
        case 'clear':
            // Vaciar el carrito
            limpiarCarrito();
            
            header("Location: cart.php");
            exit;
    }
}

// Obtener los productos del carrito para mostrarlos
$cart_data = obtenerProductosDelCarrito();
$cart_items = $cart_data['items'];
$cart_total = $cart_data['total'];

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="border-bottom pb-3">
                <i class="bi bi-cart3 me-2"></i> Mi Carrito
            </h1>
        </div>
    </div>
    
    <?php if (empty($cart_items)): ?>
        <div class="row">
            <div class="col">
                <div class="alert alert-info" role="alert">
                    <h4 class="alert-heading"><i class="bi bi-info-circle"></i> Tu carrito está vacío</h4>
                    <p>Aún no has añadido productos al carrito. Explora nuestra tienda y añade productos que te interesen.</p>
                    <hr>
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-shop"></i> Continuar comprando
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Productos en el carrito -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Productos (<span id="cart-count"><?php echo count($cart_items); ?></span>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="py-3">Producto</th>
                                        <th scope="col" class="text-center py-3">Precio</th>
                                        <th scope="col" class="text-center py-3">Cantidad</th>
                                        <th scope="col" class="text-center py-3">Subtotal</th>
                                        <th scope="col" class="text-center py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                    <tr class="cart-item" data-id="<?php echo $item['id']; ?>">
                                        <td class="align-middle">
                                            <div class="d-flex align-items-center">
                                                <img src="assets/img/productos/<?php echo !empty($item['imagen']) ? htmlspecialchars($item['imagen']) : 'default.jpg'; ?>" 
                                                     class="cart-item-img me-3" 
                                                     alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                                <div>
                                                    <a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none">
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['nombre']); ?></h6>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <?php echo formatearPrecio($item['precio']); ?>
                                        </td>
                                        <td class="align-middle text-center" style="width: 150px;">
                                            <form action="cart.php" method="post" class="quantity-form">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                                <div class="input-group input-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary btn-quantity" data-action="decrease">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <input type="number" name="cantidad" class="form-control text-center cart-quantity" 
                                                           value="<?php echo $item['cantidad']; ?>" min="1" max="99">
                                                    <button type="button" class="btn btn-outline-secondary btn-quantity" data-action="increase">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="align-middle text-center item-subtotal">
                                            <?php echo formatearPrecio($item['subtotal']); ?>
                                        </td>
                                        <td class="align-middle text-center">
                                            <form action="cart.php" method="post" class="remove-form">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="producto_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger btn-remove" data-bs-toggle="tooltip" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Seguir comprando
                        </a>
                        <form action="cart.php" method="post">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de que deseas vaciar el carrito?')">
                                <i class="bi bi-trash"></i> Vaciar carrito
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Resumen del carrito -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Resumen del pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span id="cart-subtotal"><?php echo formatearPrecio($cart_total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Gastos de envío:</span>
                            <span class="text-success">Gratis</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong id="cart-total"><?php echo formatearPrecio($cart_total); ?></strong>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="checkout.php" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-credit-card"></i> Proceder al pago
                        </a>
                    </div>
                </div>
                
                <!-- Métodos de pago -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Aceptamos</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <i class="bi bi-credit-card fs-2 text-primary"></i>
                            <i class="bi bi-paypal fs-2 text-primary"></i>
                            <i class="bi bi-wallet2 fs-2 text-primary"></i>
                            <i class="bi bi-bank fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Nuevo bloque para acceder a pedidos -->
                <?php if (estaLogueado()): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white"></div>
                        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Mis Pedidos</h5>
                    </div>
                    <div class="card-body">
                        <p>¿Quieres revisar el estado de tus pedidos anteriores?</p>
                        <a href="orders.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-box-seam me-2"></i>Ver mis pedidos
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Toast de notificación -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="cartToast" class="toast align-items-center bg-success text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle me-2"></i> 
                <span id="toastMessage">Producto añadido al carrito</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Script para el carrito -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar cambios en la cantidad de productos
    document.querySelectorAll('.btn-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.quantity-form');
            const input = form.querySelector('.cart-quantity');
            const action = this.getAttribute('data-action');
            
            let currentValue = parseInt(input.value);
            if (action === 'increase') {
                input.value = currentValue + 1;
            } else if (action === 'decrease' && currentValue > 1) {
                input.value = currentValue - 1;
            }
            
            // Enviar formulario con AJAX
            updateCartItem(form);
        });
    });
    
    // Actualizar carrito al cambiar manualmente la cantidad
    document.querySelectorAll('.cart-quantity').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('.quantity-form');
            updateCartItem(form);
        });
    });
    
    // Eliminar producto con AJAX
    document.querySelectorAll('.btn-remove').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.remove-form');
            const itemRow = this.closest('.cart-item');
            
            if (confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
                const formData = new FormData(form);
                
                fetch('cart.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animar la eliminación del producto
                        itemRow.style.transition = 'all 0.5s ease';
                        itemRow.style.opacity = '0';
                        itemRow.style.height = '0';
                        
                        setTimeout(() => {
                            itemRow.remove();
                            updateCartUI(data.cartCount);
                            
                            // Si no hay más productos, recargar la página
                            if (data.cartCount === 0) {
                                window.location.reload();
                            }
                        }, 500);
                        
                        // Mostrar notificación
                        showToast('Producto eliminado del carrito', 'danger');
                    }
                });
            }
        });
    });
    
    // Función para actualizar un elemento del carrito con AJAX
    function updateCartItem(form) {
        const formData = new FormData(form);
        const productId = formData.get('producto_id');
        
        fetch('cart.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la UI
                updateCartUI(data.cartCount);
                
                // Actualizar subtotal del producto
                const itemRow = form.closest('.cart-item');
                const subtotalElement = itemRow.querySelector('.item-subtotal');
                
                // Animar cambio de subtotal
                subtotalElement.style.transition = 'all 0.3s ease';
                subtotalElement.style.backgroundColor = '#FFFF99';
                setTimeout(() => {
                    subtotalElement.style.backgroundColor = 'transparent';
                }, 1000);
                
                // Formatear y actualizar subtotal
                const formattedSubtotal = new Intl.NumberFormat('es-ES', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(data.item_subtotal);
                
                subtotalElement.textContent = formattedSubtotal;
                
                // Actualizar el total del carrito
                const cartTotal = document.getElementById('cart-total');
                const cartSubtotal = document.getElementById('cart-subtotal');
                
                const formattedTotal = new Intl.NumberFormat('es-ES', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(data.total);
                
                cartTotal.textContent = formattedTotal;
                cartSubtotal.textContent = formattedTotal;
            }
        });
    }
    
    // Función para actualizar elementos de la UI relacionados con el carrito
    function updateCartUI(count) {
        // Actualizar el contador del carrito en la navbar
        const navbarCounter = document.querySelector('.navbar .badge');
        if (navbarCounter) {
            if (count > 0) {
                navbarCounter.textContent = count;
                navbarCounter.style.display = 'inline-block';
            } else {
                navbarCounter.style.display = 'none';
            }
        }
        
        // Actualizar contador en la página de carrito
        const cartCount = document.getElementById('cart-count');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }
    
    // Función para mostrar notificaciones toast
    function showToast(message, type = 'success') {
        const toastElement = document.getElementById('cartToast');
        const messageElement = document.getElementById('toastMessage');
        
        // Establecer mensaje y estilo
        messageElement.textContent = message;
        
        // Cambiar el estilo según el tipo
        toastElement.className = `toast align-items-center text-white border-0`;
        toastElement.classList.add(`bg-${type}`);
        
        // Mostrar el toast
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
