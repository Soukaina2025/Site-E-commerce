<?php
require 'config.php';
$id = $_GET['id'] ?? null;

if ($id) {
    // Supprimer le produit
    $stmt = $conn->prepare("DELETE FROM produits WHERE id = ?");
    $stmt->execute([$id]);
}

// Retour à la liste admin
header("Location: admin_produits.php");
exit;
?>
