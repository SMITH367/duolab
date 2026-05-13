<?php
require '../../global/connection.php';
require '../../plugins/fpdf/fpdf.php';

if (!isset($_GET['id'])) {
    echo "ID de factura no especificado.";
    exit;
}
$id = $_GET['id'];

// Obtener datos de la factura
$sql = "SELECT ti.*, CONCAT(e.name, ' ', e.last_name_1) AS seller_name
        FROM tbl_invoice ti
        LEFT JOIN tbl_user u ON ti.seller_id = u.id
        LEFT JOIN tbl_employee e ON u.employee_id = e.id
        WHERE ti.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invoice) {
    echo "Factura no encontrada.";
    exit;
}

// Detalle de la factura
$sqlDetail = "SELECT * FROM tbl_invoice_detail WHERE invoice_id = ?";
$stmtDetail = $pdo->prepare($sqlDetail);
$stmtDetail->execute([$id]);
$details = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

class PDF extends FPDF
{
    function Header()
    {
        // Logo
        if (file_exists('../../img/creamos_logo.png')) {
            $this->Image('../../img/creamos_logo.png', 10, 10, 40);
        }

        // Datos de la empresa (encabezado estándar a la derecha)
        $this->SetXY(110, 10);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(11, 35, 65);
        $this->Cell(90, 6, utf8_decode('CREAMOS, SOLUCIONES QUE IDENTIFICAN Y PROTEGEN'), 0, 1, 'R');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(80, 80, 80);
        $this->SetX(110);
        $this->Cell(90, 5, utf8_decode('NIT: 902023482'), 0, 1, 'R');
        $this->SetX(110);
        $this->Cell(90, 5, utf8_decode('Tel: 3233561715 | creamos.ctg@gmail.com'), 0, 1, 'R');
        $this->Ln(15);
    }
    function Footer()
    {
        global $invoice;
        $this->SetY(-20);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 4, utf8_decode('creamos.ctg@gmail.com | Tel: 3233561715'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Cartagena, Colombia'), 0, 1, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);

// Título de la cuenta de cobro
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(235, 87, 35);
$pdf->Cell(0, 10, utf8_decode('CUENTA DE COBRO'), 0, 1, 'C');
$pdf->Ln(5);

// Bloque de información (Centrado)
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(0, 7, utf8_decode($invoice['name']), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 6, utf8_decode('NIT: ' . $invoice['ruc']), 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(0, 6, utf8_decode('DEBE A:'), 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 7, utf8_decode('SOLUCIONES INTEGRALES Y LOGISTICAS SAS'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, utf8_decode('NIT 902023482'), 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(0, 6, utf8_decode('POR CONCEPTO DE:'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 6, utf8_decode('Venta de productos según Factura N° ' . $invoice['series'] . '-' . $invoice['number']), 0, 1, 'C');

// Tabla de detalle de productos
$pdf->Ln(10);
$pdf->SetFillColor(11, 35, 65);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(10, 8, utf8_decode('#'), 0, 0, 'C', true);
$pdf->Cell(90, 8, utf8_decode('DESCRIPCIÓN DEL PRODUCTO'), 0, 0, 'L', true);
$pdf->Cell(25, 8, utf8_decode('CANT.'), 0, 0, 'C', true);
$pdf->Cell(30, 8, utf8_decode('VALOR UNIT.'), 0, 0, 'R', true);
$pdf->Cell(35, 8, utf8_decode('TOTAL'), 0, 1, 'R', true);
$pdf->SetTextColor(50, 50, 50);
$pdf->SetFont('Arial', '', 9);
$pdf->SetDrawColor(220, 220, 220);
$pdf->SetLineWidth(0.2);

$i = 1;
foreach ($details as $detail) {
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Salto de página si es necesario
    if ($y > 240) {
        $pdf->AddPage();
        $y = $pdf->GetY();
        // (Re-imprimir cabeceras de tabla si se desea, pero por brevedad lo dejamos así)
    }

    $pdf->SetXY($x + 10, $y + 1);
    $pdf->MultiCell(90, 6, utf8_decode($detail['item_name']), 0, 'L');
    $newY = $pdf->GetY() + 1;
    $height = max(8, $newY - $y);

    $pdf->SetXY($x, $y);
    $pdf->Cell(10, $height, $i++, 0, 0, 'C');

    $pdf->SetXY($x + 100, $y);
    $pdf->Cell(25, $height, $detail['item_quantity'], 0, 0, 'C');
    $pdf->Cell(30, $height, '$ ' . number_format($detail['item_unit_price'], 2), 0, 0, 'R');
    $pdf->Cell(35, $height, '$ ' . number_format($detail['item_quantity'] * $detail['item_unit_price'], 2), 0, 1, 'R');

    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
}

// Totales
$pdf->Ln(8);
$pdf->SetFillColor(248, 249, 250);
$pdf->Rect(120, $pdf->GetY(), 80, 26, 'F');
$pdf->SetY($pdf->GetY() + 3);
$pdf->SetX(120);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(35, 6, 'Subtotal:', 0, 0, 'L');
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(45, 6, '$ ' . number_format($invoice['total_sub'], 2), 0, 1, 'R');
$pdf->SetX(120);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(35, 6, 'IVA (19%):', 0, 0, 'L');
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(45, 6, '$ ' . number_format($invoice['total_tax'], 2), 0, 1, 'R');
$pdf->Ln(2);
$pdf->SetX(120);
$pdf->SetFillColor(235, 87, 35);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(35, 9, 'TOTAL:', 0, 0, 'L', true);
$pdf->Cell(45, 9, '$ ' . number_format($invoice['total_net'], 2), 0, 1, 'R', true);

// Espacio para la firma
$pdf->Ln(20);
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(50, 50, 50);
$pdf->Cell(0, 6, utf8_decode('Cordialmente,'), 0, 1, 'L');

$pdf->Ln(10);
// Nombre en cursiva para simular firma
$pdf->SetFont('Arial', 'I', 13);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(0, 6, utf8_decode('Brayan Puentes Ruiz'), 0, 1, 'L');

// Detalles finales
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(0, 5, utf8_decode('CC 1102796536'), 0, 1, 'L');
$pdf->Cell(0, 5, utf8_decode('Director Comercial'), 0, 1, 'L');

$pdf->Output('I', 'CuentaCobro_' . $invoice['number'] . '.pdf');
?>