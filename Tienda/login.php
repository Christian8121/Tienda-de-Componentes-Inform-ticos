<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si el usuario ya está logueado, redirigir a la página principal
if(estaLogueado()) {
    header("Location: index.php");
    exit;
}

$error = '';

// Procesar el formulario cuando se envía
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validaciones básicas
    if(empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        // Conectar a la base de datos
        $database = new Database();
        $db = $database->getConnection();
        
        // Preparar la consulta
        $query = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña
            // NOTA: En un entorno de producción, debes usar password_verify() en lugar de comparación directa
            if($row['password'] === $password || password_verify($password, $row['password'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['nombre'];
                $_SESSION['user_email'] = $row['email'];
                $_SESSION['user_role'] = $row['rol'];
                
                // Migrar el carrito de la sesión a la base de datos
                migrarCarritoDeSesionBD($row['id']);
                
                // Redirigir según el rol
                if($row['rol'] === 'admin') {
                    header("Location: index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="m-0">Iniciar Sesión</h4>
                </div>
                <div class="card-body">
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Introduce tu email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Introduce tu contraseña" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
