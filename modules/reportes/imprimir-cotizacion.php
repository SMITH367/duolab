<?php
require '../../plugins/fpdf/fpdf.php';
require '../../global/connection.php';

if (!isset($_GET['id'])) {
    echo "ID de cotización no especificado.";
    exit;
}

$id = $_GET['id'];

// Obtener datos de la cotización
$sql = "SELECT q.*, CONCAT(e.name, ' ', e.last_name_1) as seller_name 
        FROM tbl_quotation q 
        LEFT JOIN tbl_user u ON q.user_id = u.id 
        LEFT JOIN tbl_employee e ON u.employee_id = e.id
        WHERE q.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote) {
    echo "Cotización no encontrada.";
    exit;
}

// Obtener detalle de la cotización
$sqlDetail = "SELECT * FROM tbl_quotation_detail WHERE quotation_id = ?";
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
        } else {
            $this->SetFont('Arial', 'B', 24);
            $this->SetTextColor(11, 35, 65);
            $this->SetXY(10, 15);
            $this->Cell(40, 8, utf8_decode('CREAMOS'), 0, 1, 'L');
        }

        // Empresa Details
        $this->SetXY(55, 12);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(11, 35, 65);
        $this->Cell(60, 5, utf8_decode('CREAMOS S.I.P.'), 0, 1, 'L');

        $this->SetX(55);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(60, 4, utf8_decode('NIT: 902023482'), 0, 1, 'L');
        $this->SetX(55);
        $this->Cell(60, 4, utf8_decode('Cartagena, Colombia'), 0, 1, 'L');
        $this->SetX(55);
        $this->Cell(60, 4, utf8_decode('Tel: 3233561715 | creamos.ctg@gmail.com'), 0, 1, 'L');

        // Recuadro Cotización
        $this->SetXY(130, 12);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(235, 87, 35);
        $this->Cell(70, 6, utf8_decode('COTIZACIÓN'), 0, 1, 'R');

        $this->SetXY(130, 20);
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(70, 5, utf8_decode('N°: ' . $GLOBALS['quote']['number']), 0, 1, 'R');

        $this->SetXY(130, 25);
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(70, 5, utf8_decode('Fecha de emisión: ' . date("d/m/Y", strtotime($GLOBALS['quote']['date']))), 0, 1, 'R');

        // Linea naranja separadora
        $this->SetDrawColor(235, 87, 35);
        $this->SetLineWidth(0.8);
        $this->Line(10, 36, 200, 36);
        $this->SetLineWidth(0.2); // Reset

        $this->Ln(15);
    }

    function Footer()
    {
        $this->SetY(-20);
        $this->SetDrawColor(220, 220, 220);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(4);
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

$startY = $pdf->GetY();

// Cuadro Cliente (Izquierda)
$pdf->SetFillColor(248, 249, 250);
$pdf->Rect(10, $startY, 90, 35, 'F');
$pdf->SetFillColor(11, 35, 65);
$pdf->Rect(10, $startY, 2, 35, 'F');

$pdf->SetXY(15, $startY + 3);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(80, 5, utf8_decode('PREPARADO PARA:'), 0, 1, 'L');

$pdf->SetXY(15, $startY + 10);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(80, 5, utf8_decode($quote['name']), 0, 1, 'L');

$pdf->SetXY(15, $startY + 16);
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(80, 5, utf8_decode('NIT: ' . $quote['ruc']), 0, 1, 'L');

$pdf->SetXY(15, $startY + 21);
$pdf->MultiCell(80, 5, utf8_decode('Dirección: ' . $quote['address']), 0, 'L');

// Cuadro Validez (Derecha)
$pdf->SetFillColor(248, 249, 250);
$pdf->Rect(110, $startY, 90, 35, 'F');
$pdf->SetFillColor(11, 35, 65);
$pdf->Rect(110, $startY, 2, 35, 'F');

$pdf->SetXY(115, $startY + 3);
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(11, 35, 65);
$pdf->Cell(80, 5, utf8_decode('INFORMACIÓN DE VALIDEZ'), 0, 1, 'L');

$pdf->SetXY(115, $startY + 11);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->MultiCell(80, 5, utf8_decode('La presente oferta tiene una validez de ' . $quote['payment_days'] . ' días calendario a partir de la fecha de emisión. Precios sujetos a cambios sin previo aviso.'), 0, 'L');

$pdf->SetY($startY + 45);

// Tabla Detalle
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

    // Control de salto de página
    if ($y > 250) {
        $pdf->AddPage();
        $y = $pdf->GetY();
        // Reimprimir cabecera de tabla
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
        $x = $pdf->GetX();
        $y = $pdf->GetY();
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

$pdf->Ln(8);

// Totals area background
$pdf->SetFillColor(248, 249, 250);
$pdf->Rect(120, $pdf->GetY(), 80, 26, 'F');

$pdf->SetY($pdf->GetY() + 3);

// Totals
$pdf->SetX(120);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(35, 6, 'Subtotal:', 0, 0, 'L');
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(45, 6, '$ ' . number_format($quote['total_sub'], 2), 0, 1, 'R');

$pdf->SetX(120);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(35, 6, 'IVA (19%):', 0, 0, 'L');
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(45, 6, '$ ' . number_format($quote['total_tax'], 2), 0, 1, 'R');

$pdf->Ln(2);
$pdf->SetX(120);
$pdf->SetFillColor(235, 87, 35);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(35, 9, 'TOTAL:', 0, 0, 'L', true);
$pdf->Cell(45, 9, '$ ' . number_format($quote['total_net'], 2), 0, 1, 'R', true);

$pdf->Output("I", "Cotizacion_" . $quote['number'] . ".pdf");
