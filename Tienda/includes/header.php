<?php require_once 'includes/functions.php'; ?>
<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determinar la página actual para marcar elementos de navegación
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SeveStore - Tienda de Informática</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- Cabecera / Navbar -->
    <header class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                <img src="assets/img/1-Logo.PNG" alt="logo" width="30" height="24" class="d-inline-block align-text-top rounded-circle me-2">
                SeveStore
            </a>
            
            <!-- Botón de menú móvil -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menú principal -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <!-- Categorías -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-grid me-1"></i> Categorías
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php?buscar=Procesadores">Procesadores</a></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Tarjetas+Gráficas">Tarjetas Gráficas</a></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Memoria+RAM">Memoria RAM</a></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Almacenamiento">Almacenamiento</a></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Placas+Base">Placas Base</a></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Portatiles">Portátiles</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?buscar=Periféricos">Periféricos</a></li>
                        </ul>
                    </li>
                    
                    <?php if (esAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'admin_panel.php') ? 'active' : ''; ?>" href="admin_panel.php">
                            <i class="bi bi-speedometer2 me-1"></i> Panel Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Búsqueda -->
                <form class="d-flex mx-auto" action="index.php" method="get">
                    <input class="form-control me-2" type="search" name="buscar" placeholder="Buscar productos..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
                
                <!-- Menú de usuario -->
                <ul class="navbar-nav ms-auto">
                    <!-- Carrito -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>" href="cart.php">
                            <i class="bi bi-cart3"></i>
                            <?php if (estaLogueado() || isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="badge rounded-pill bg-danger"><?php echo verificarSiHayProductosEnCarrito(); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if (estaLogueado()): ?>
                        <!-- Notificaciones -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bell"></i>
                                <span class="badge rounded-pill bg-danger notification-badge" style="display: none;"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 300px;" aria-labelledby="notificationsDropdown">
                                <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    Notificaciones
                                    <a href="javascript:void(0)" class="text-primary mark-all-read" style="font-size: 0.8rem; text-decoration: none;">Marcar todas como leídas</a>
                                </h6>
                                <div class="notification-list">
                                    <!-- Las notificaciones se cargarán aquí con AJAX -->
                                    <div class="text-center p-2">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center text-primary" href="notifications.php">Ver todas las notificaciones</a>
                            </div>
                        </li>
                        
                        <!-- Usuario logueado -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="orders.php"><i class="bi bi-box-seam me-2"></i> Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="tracking.php"><i class="bi bi-truck me-2"></i> Seguimiento</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Usuario no logueado -->
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" href="register.php">
                                <i class="bi bi-person-plus me-1"></i> Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <!-- El contenido de la página irá aquí -->
