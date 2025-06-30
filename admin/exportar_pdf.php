<?php
require_once 'includes/auth.php';
require_once '../fpdf/fpdf.php'; // Ruta a FPDF (ajustala si está en otra carpeta)

// Recibir filtros
$filtro = $_GET['estado'] ?? 'todos';
$busqueda = $_GET['buscar'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$condiciones = "user_id = ?";
$params = [$_SESSION['user']['id']];

if ($filtro !== 'todos') {
    $condiciones .= " AND status = ?";
    $params[] = $filtro;
}

if (!empty($fecha)) {
    $condiciones .= " AND DATE(created_at) = ?";
    $params[] = $fecha;
}

if (!empty($busqueda)) {
    $palabras = explode(" ", $busqueda);
    foreach ($palabras as $palabra) {
        $condiciones .= " AND (
            name LIKE ? OR lastname LIKE ? OR
            text_line1 LIKE ? OR text_line2 LIKE ? OR text_line3 LIKE ? OR text_line4 LIKE ?
        )";
        for ($i = 0; $i < 6; $i++) {
            $params[] = "%" . $palabra . "%";
        }
    }
}

$sql = "SELECT * FROM orders WHERE $condiciones ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Iniciar PDF horizontal
class PDF extends FPDF {
    function header() {
        $this->SetFont("Arial", "B", 14);
        $this->Cell(0, 10, "Listado de Pedidos - SoloSellos", 0, 1, "C");
        $this->SetFont("Arial", "", 11);
        $this->Cell(0, 8, "Exportado el: " . date("d/m/Y H:i"), 0, 1);
        $this->Ln(2);
    }
}

$pdf = new PDF("L");
$pdf->AddPage();

// Encabezados
$pdf->SetFillColor(230, 230, 230);
$pdf->SetFont("Arial", "B", 11);
$pdf->Cell(10, 8, "#", 1, 0, "C", true);
$pdf->Cell(40, 8, "Cliente", 1, 0, "C", true);
$pdf->Cell(50, 8, "Email", 1, 0, "C", true);
$pdf->Cell(20, 8, "Modelo", 1, 0, "C", true);
$pdf->Cell(85, 8, "Texto del sello", 1, 0, "C", true);
$pdf->Cell(25, 8, "Estado", 1, 0, "C", true);
$pdf->Cell(35, 8, "Fecha", 1, 1, "C", true);

// Filas
$pdf->SetFont("Arial", "", 10);
foreach ($orders as $o) {
    $texto = trim(implode(" ", [
        $o['text_line1'], $o['text_line2'], $o['text_line3'], $o['text_line4']
    ]));
    $pdf->Cell(10, 8, $o['id'], 1);
    $pdf->Cell(40, 8, $o['name'] . ' ' . $o['lastname'], 1);
    $pdf->Cell(50, 8, $o['email'], 1);
    $pdf->Cell(20, 8, $o['model_id'], 1);
    $pdf->Cell(85, 8, $texto, 1);
    $pdf->Cell(25, 8, $o['status'], 1);
    $pdf->Cell(35, 8, date("d/m/Y H:i", strtotime($o['created_at'])), 1, 1);
}

// Salida
$pdf->Output("D", "pedidos_exportados.pdf");
exit;
