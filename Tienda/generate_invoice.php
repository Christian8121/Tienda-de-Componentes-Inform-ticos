<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    header('Location: login.php');
    exit;
}

// Verificar que se ha proporcionado un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$pedido_id = intval($_GET['id']);

// Conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener información del pedido
$query = "SELECT p.*, u.nombre AS nombre_usuario, u.email 
          FROM pedidos p 
          JOIN usuarios u ON p.usuario_id = u.id 
          WHERE p.id = ? AND p.usuario_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id, $_SESSION['user_id']]);

// Verificar si el pedido existe y pertenece al usuario
if ($stmt->rowCount() == 0) {
    header('Location: orders.php');
    exit;
}

$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

// Comprobar que el pedido está entregado
if ($pedido['estado'] != 'entregado') {
    header('Location: tracking.php?id=' . $pedido_id);
    exit;
}

// Obtener los productos del pedido
$query = "SELECT dp.*, p.nombre 
          FROM detalle_pedido dp 
          JOIN productos p ON dp.producto_id = p.id 
          WHERE dp.pedido_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$pedido_id]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir la librería FPDF
require('libs/fpdf/fpdf.php');

// Crear una clase extendida de FPDF para la factura
class PDF extends FPDF {
    function Header() {
        // Logo
        $this->Image('assets/img/1-Logo.PNG', 10, 6, 30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Mover a la derecha
        $this->Cell(80);
        // Título
        $this->Cell(30, 10, 'FACTURA', 0, 0, 'C');
        // Fecha
        $this->SetFont('Arial', '', 10);
        $this->Cell(80, 10, 'Fecha: ' . date('d/m/Y'), 0, 1, 'R');
        // Línea
        $this->Line(10, 30, 200, 30);
        $this->Ln(15);
    }
    
    function Footer() {
        // Posicionarse a 1.5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Crear documento PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Información de la empresa
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'SeveStore', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'CIF: B12345678', 0, 1);
$pdf->Cell(0, 6, 'Dirección: Calle Ejemplo, 123', 0, 1);
$pdf->Cell(0, 6, 'Email: info@sevestore.com', 0, 1);
$pdf->Cell(0, 6, 'Teléfono: +34 640 955 513', 0, 1);
$pdf->Ln(5);

// Información del cliente
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Datos del cliente:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Nombre: ' . $pedido['nombre_usuario'], 0, 1);
$pdf->Cell(0, 6, 'Email: ' . $pedido['email'], 0, 1);
$pdf->Cell(0, 6, 'Dirección de envío: ' . $pedido['direccion_envio'], 0, 1);
$pdf->Ln(5);

// Información del pedido
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Detalles del pedido:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Número de pedido: #' . str_pad($pedido['id'], 8, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(0, 6, 'Fecha: ' . date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])), 0, 1);
$pdf->Cell(0, 6, 'Estado: ' . ucfirst($pedido['estado']), 0, 1);
$pdf->Cell(0, 6, 'Método de pago: ' . $pedido['metodo_pago'], 0, 1);
$pdf->Ln(5);

// Tabla de productos
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Productos:', 0, 1);
$pdf->SetFillColor(235, 235, 235);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(90, 10, 'Producto', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Precio', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Subtotal', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
foreach ($productos as $producto) {
    $subtotal = $producto['precio_unitario'] * $producto['cantidad'];
    
    $pdf->Cell(90, 8, utf8_decode($producto['nombre']), 1, 0, 'L');
    $pdf->Cell(30, 8, number_format($producto['precio_unitario'], 2, ',', '.') . ' €', 1, 0, 'R');
    $pdf->Cell(30, 8, $producto['cantidad'], 1, 0, 'C');
    $pdf->Cell(40, 8, number_format($subtotal, 2, ',', '.') . ' €', 1, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(150, 10, 'Total', 1, 0, 'R', true);
$pdf->Cell(40, 10, number_format($pedido['total'], 2, ',', '.') . ' €', 1, 1, 'R', true);
$pdf->Ln(5);

// Información adicional
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Esta factura sirve como comprobante de compra.', 0, 1);
$pdf->Cell(0, 6, 'Para cualquier consulta relacionada con este pedido, por favor contacte con nuestro servicio de atención al cliente.', 0, 1);
$pdf->Cell(0, 6, 'Gracias por confiar en SeveStore.', 0, 1);

// Generar el PDF y enviarlo al navegador
$pdf->Output('Factura_' . str_pad($pedido['id'], 8, '0', STR_PAD_LEFT) . '.pdf', 'D');
?>
