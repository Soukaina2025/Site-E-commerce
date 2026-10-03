<?php
session_start();

if (!isset($_SESSION['pdf_receipt'])) {
    header("Location: panier.php");
    exit;
}

$pdf = $_SESSION['pdf_receipt'];

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $pdf['filename'] . '"');
echo $pdf['content'];

// Supprimer le PDF de la session après téléchargement
unset($_SESSION['pdf_receipt']);
exit;
?>