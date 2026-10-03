<?php
date_default_timezone_set('Africa/Casablanca');
require_once('tcpdf/tcpdf.php');

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);

// Créer un nouveau document PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Paramètres du document
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Boutique Admin');
$pdf->SetTitle($data['title']);
$pdf->SetSubject('Liste des commandes');
$pdf->SetKeywords('PDF, commandes, boutique');

// Marges
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Police par défaut
$pdf->SetFont('dejavusans', '', 10);

// Ajouter une page
$pdf->AddPage();

// Titre
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, $data['title'], 0, 1, 'C');
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(0, 10, date('d/m/Y H:i'), 0, 1, 'R');
$pdf->Ln(5);

// En-têtes de tableau
$header = $data['headers'];

// Données
$rows = $data['rows'];

// Couleurs et épaisseur du trait
$pdf->SetFillColor(34, 128, 207);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(156, 200, 249);
$pdf->SetLineWidth(0.3);
$pdf->SetFont('dejavusans', 'B');

// En-têtes
$w = array(60, 20, 50, 40, 40, 30);
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Restaurer les couleurs et police
$pdf->SetFillColor(195, 218, 248);
$pdf->SetTextColor(0);
$pdf->SetFont('dejavusans');

// Données
$fill = false;
foreach($rows as $row) {
    $pdf->Cell($w[0], 6, $row['produit'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[1], 6, $row['quantite'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[2], 6, $row['client'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[3], 6, $row['contact'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[4], 6, $row['date'], 'LR', 0, 'C', $fill);
    $pdf->Cell($w[5], 6, $row['statut'], 'LR', 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Ligne de fermeture
$pdf->Cell(array_sum($w), 0, '', 'T');

// Générer le PDF
$pdf->Output('commandes.pdf', 'D');
?>