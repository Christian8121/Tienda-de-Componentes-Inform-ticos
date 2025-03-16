// Función para validar el formulario de productos
function validateProductForm() {
    const name = document.getElementById('nombre');
    const price = document.getElementById('precio');
    const stock = document.getElementById('stock');
    
    if (name.value.trim() === '') {
        alert('El nombre del producto es obligatorio');
        name.focus();
        return false;
    }
    
    if (isNaN(parseFloat(price.value)) || parseFloat(price.value) <= 0) {
        alert('El precio debe ser un número mayor que cero');
        price.focus();
        return false;
    }
    
    if (isNaN(parseInt(stock.value)) || parseInt(stock.value) < 0) {
        alert('El stock debe ser un número positivo');
        stock.focus();
        return false;
    }
    
    return true;
}

// Vista previa de imagen al subir un archivo
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('imagen');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // Habilitar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Actualizar cantidad en el carrito
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    if (quantityInputs) {
        quantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
                const form = this.closest('form');
                form.submit();
            });
        });
    }
    
    // Añadir producto al carrito con AJAX y animación
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    if (addToCartForms) {
        addToCartForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const button = this.querySelector('button[type="submit"]');
                const originalContent = button.innerHTML;
                
                // Animación del botón
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Añadiendo...';
                button.disabled = true;
                
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
                        // Actualizar contador del carrito
                        updateCartBadge(data.cartCount);
                        
                        // Animar el icono del carrito
                        animateCartIcon();
                        
                        // Mostrar toast de confirmación
                        showCartToast('Producto añadido al carrito');
                        
                        // Restaurar el botón
                        setTimeout(() => {
                            button.innerHTML = originalContent;
                            button.disabled = false;
                            button.classList.remove('btn-success');
                            button.classList.add('btn-primary');
                        }, 1000);
                        
                        // Cambiar temporalmente el botón a verde
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            });
        });
    }
    
    // Inicializar el sistema de notificaciones si el usuario está logueado
    initNotificationSystem();
});

// Función para confirmar eliminación
function confirmarEliminacion(nombre, formId) {
    if (confirm(`¿Estás seguro de que deseas eliminar "${nombre}"?`)) {
        document.getElementById(formId).submit();
    }
    return false;
}

// Función para actualizar el contador del carrito
function updateCartBadge(count) {
    const badge = document.querySelector('.navbar .badge');
    if (badge) {
        badge.textContent = count;
        
        if (count > 0) {
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Función para animar el icono del carrito
function animateCartIcon() {
    const cartIcon = document.querySelector('.navbar .bi-cart3');
    if (cartIcon) {
        cartIcon.style.transition = 'transform 0.5s ease';
        cartIcon.style.transform = 'scale(1.5)';
        
        setTimeout(() => {
            cartIcon.style.transform = 'scale(1)';
        }, 500);
    }
}

// Función para mostrar toast de confirmación
function showCartToast(message) {
    // Buscar o crear un toast
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'cartToast' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Eliminar el toast del DOM después de que se oculte
    toastElement.addEventListener('hidden.bs.toast', function () {
        this.remove();
    });
}

// Función para inicializar el sistema de notificaciones
function initNotificationSystem() {
    const notificationBell = document.querySelector('.notification-bell');
    if (!notificationBell) return; // No hay campana de notificaciones (usuario no logueado)
    
    const notificationDropdown = document.querySelector('.notification-dropdown-menu');
    const notificationList = document.querySelector('.notification-list');
    const markAllReadBtn = document.querySelector('.mark-all-read');
    
    // Cargar notificaciones cuando se abre el dropdown
    notificationBell.addEventListener('click', function() {
        loadNotifications();
    });
    
    // Marcar todas como leídas
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAllNotificationsAsRead();
        });
    }
    
    // Cargar notificaciones automáticamente cada minuto
    setInterval(loadNotifications, 60000);
    
    // Cargar notificaciones iniciales
    loadNotifications();
}

// Función para cargar notificaciones mediante AJAX
function loadNotifications() {
    const notificationList = document.querySelector('.notification-list');
    if (!notificationList) return;
    
    const formData = new FormData();
    formData.append('action', 'get_notifications');
    
    fetch('ajax/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationBadge(data.unread_count);
            renderNotifications(data.notifications, notificationList);
        }
    })
    .catch(error => {
        console.error('Error al cargar notificaciones:', error);
    });
}

// Función para renderizar las notificaciones en el dropdown
function renderNotifications(notifications, container) {
    if (!container) return;
    
    if (notifications.length === 0) {
        container.innerHTML = '<div class="text-center p-3 text-muted">No tienes notificaciones</div>';
        return;
    }
    
    let html = '';
    
    notifications.forEach(notification => {
        const unreadClass = notification.leida ? '' : 'unread';
        const iconClass = getNotificationIcon(notification.tipo);
        
        html += `
            <a href="${notification.enlace || '#'}" class="notification-item ${unreadClass}" data-id="${notification.id}">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <div class="notification-icon">
                            <i class="bi ${iconClass}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="notification-title">${notification.titulo}</div>
                        <div class="notification-message">${notification.mensaje}</div>
                        <div class="notification-time">${notification.tiempo_relativo}</div>
                    </div>
                </div>
            </a>
        `;
    });
    
    container.innerHTML = html;
    
    // Agregar eventos para marcar como leída al hacer clic
    container.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            if (!this.classList.contains('unread')) return;
            
            markNotificationAsRead(notificationId);
            this.classList.remove('unread');
        });
    });
}

// Función para obtener el ícono según el tipo de notificación
function getNotificationIcon(tipo) {
    switch (tipo) {
        case 'pedido_entregado':
            return 'bi-box-seam text-success';
        case 'pedido_enviado':
            return 'bi-truck text-primary';
        case 'error':
            return 'bi-exclamation-triangle text-danger';
        case 'alerta':
            return 'bi-exclamation-circle text-warning';
        default:
            return 'bi-bell text-info';
    }
}

// Función para marcar una notificación como leída
function markNotificationAsRead(notificationId) {
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
            updateNotificationBadge(data.unread_count);
        }
    })
    .catch(error => {
        console.error('Error al marcar la notificación como leída:', error);
    });
}

// Función para marcar todas las notificaciones como leídas
function markAllNotificationsAsRead() {
    const formData = new FormData();
    formData.append('action', 'mark_all_as_read');
    
    fetch('ajax/notifications.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar UI
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
            });
            
            updateNotificationBadge(0);
            
            // Ocultar botón de marcar todas como leídas
            const markAllReadBtn = document.querySelector('.mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.style.display = 'none';
            }
            
            // Mostrar mensaje
            showNotificationToast(`${data.count} notificación(es) marcada(s) como leída(s)`);
        }
    })
    .catch(error => {
        console.error('Error al marcar todas las notificaciones como leídas:', error);
    });
}

// Función para actualizar el badge de notificaciones
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    const markAllReadBtn = document.querySelector('.mark-all-read');
    
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
            
            // Mostrar botón de marcar todas como leídas
            if (markAllReadBtn) markAllReadBtn.style.display = 'block';
        } else {
            badge.style.display = 'none';
            
            // Ocultar botón de marcar todas como leídas
            if (markAllReadBtn) markAllReadBtn.style.display = 'none';
        }
    }
}

// Función para mostrar un toast de notificación
function showNotificationToast(message, title = 'Notificación', type = 'primary') {
    // Buscar o crear un toast container
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'notification-toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Limpiar después de ocultarse
    toastElement.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}
