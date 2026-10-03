<?php
date_default_timezone_set('Africa/Casablanca');
session_start();
require 'config.php';

// Récupération de l'ID de la boutique depuis l'URL ou la session
$boutique_id = $_GET['id'] ?? $_SESSION['boutique_id'] ?? 1;
$_SESSION['boutique_id'] = $boutique_id;

// Récupération des informations de la boutique
$stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
$stmt->execute([$boutique_id]);
$boutique = $stmt->fetch(PDO::FETCH_ASSOC);

// Gestion de la langue
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $lang = $_SESSION['lang'];
    header("Location: panier.php?id=" . $boutique_id);
    exit;
}

// Gestion des notifications
$notifications = [];
if (!empty($_SESSION['notifications'])) {
    $notifications = $_SESSION['notifications'];
    unset($_SESSION['notifications']);
}

// Initialisation des variables
$panier = $_SESSION['panier'] ?? [];
$produits = [];
$total = 0;

// Supprimer un produit
if (isset($_GET['supprimer'])) {
    unset($panier[$_GET['supprimer']]);
    $_SESSION['panier'] = $panier;
    header("Location: panier.php?id=" . $boutique_id);
    exit;
}

// Mettre à jour la quantité
if (isset($_POST['update_quantity'])) {
    $product_id = $_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    if ($new_quantity > 0) {
        $panier[$product_id] = $new_quantity;
    } else {
        unset($panier[$product_id]);
    }
    
    $_SESSION['panier'] = $panier;
    header("Location: panier.php?id=" . $boutique_id);
    exit;
}

// Charger les produits du panier
if (!empty($panier)) {
    $ids = implode(",", array_keys($panier));
    $stmt = $conn->query("SELECT * FROM produits WHERE id IN ($ids) AND boutique_id = $boutique_id");
    $produits = $stmt->fetchAll() ?: [];
    
    // Calculer le total
    foreach ($produits as $prod) {
        $qte = $panier[$prod['id']];
        $total += $qte * $prod['prix'];
    }
}

// Validation de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom']) && isset($_POST['email']) && isset($_POST['telephone'])) {
    try {
        $conn->beginTransaction();
        
        // 1. Vérifier si le client existe déjà
        $stmt = $conn->prepare("SELECT id, code_client FROM clients WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $client_id = $client['id'];
            $code_client = $client['code_client'];
            $stmt = $conn->prepare("UPDATE clients SET nom = ?, telephone = ? WHERE id = ?");
            $stmt->execute([$_POST['nom'], $_POST['telephone'], $client_id]);
            $is_new_client = false;
        } else {
            $code_client = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
            $stmt = $conn->prepare("INSERT INTO clients (nom, email, telephone, code_client, boutique_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['nom'], $_POST['email'], $_POST['telephone'], $code_client, $boutique_id]);
            $client_id = $conn->lastInsertId();
            $is_new_client = true;
        }
        
        // 2. Enregistrer les commandes
        if (!empty($panier)) {
            $ids = implode(",", array_keys($panier));
            $stmt = $conn->query("SELECT * FROM produits WHERE id IN ($ids) AND boutique_id = $boutique_id");
            $produits_commande = $stmt->fetchAll() ?: [];
            
            foreach ($produits_commande as $prod) {
                $qte = $panier[$prod['id']];
                
                if ($prod['stock'] !== null && $qte > $prod['stock']) {
                    throw new Exception($lang === 'ar' ? "عذرًا، المنتج '{$prod['nom']}' لم يعد متوفرًا بالكمية المطلوبة" : "Désolé, le produit '{$prod['nom']}' n'est plus disponible en quantité suffisante.");
                }
                
                $stmt = $conn->prepare("INSERT INTO commandes (produit_id, client_id, quantity, boutique_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$prod['id'], $client_id, $qte, $boutique_id]);
                
                if ($prod['stock'] !== null) {
                    $new_stock = $prod['stock'] - $qte;
                    $stmt = $conn->prepare("UPDATE produits SET stock = ? WHERE id = ?");
                    $stmt->execute([$new_stock, $prod['id']]);
                }
            }
        }
        
        $conn->commit();
        
        // Calculer le total
        $total = 0;
        foreach ($produits as $prod) {
            $qte = $panier[$prod['id']];
            $total += $qte * $prod['prix'];
        }

        // Générer le PDF de reçu
        require_once('tcpdf/tcpdf.php');

        // Configuration TCPDF pour l'arabe
        class MYPDF extends TCPDF {
            public function Header() {}
            public function Footer() {}
        }

        try {
            $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($boutique['nom'] ?? 'LUXEBOUTIQUE');
            $pdf->SetTitle($lang === 'ar' ? 'إيصال الطلب' : 'Reçu de commande');
            $pdf->SetSubject($lang === 'ar' ? 'إيصال الطلب' : 'Reçu de commande');

            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);

            $pdf->AddPage();

            // Configurer la police selon la langue
            if ($lang === 'ar') {
                $pdf->setRTL(true);
                $pdf->SetFont('aealarabiya', '', 12);
            } else {
                $pdf->SetFont('dejavusans', '', 12); // Utiliser DejaVuSans qui supporte Unicode
            }

            // Contenu HTML du PDF
            $html = '
            <style>
                h1 { color: #702eb7; font-size: 20px; text-align: center; }
                .logo { text-align: center; margin-bottom: 20px; }
                .info { margin-bottom: 20px; }
                .info strong { width: 120px; display: inline-block; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                table th { background-color: #f2f2f2; padding: 8px; text-align: ' . ($lang === 'ar' ? 'right' : 'left') . '; }
                table td { padding: 8px; border-bottom: 1px solid #ddd; text-align: ' . ($lang === 'ar' ? 'right' : 'left') . '; }
                .total { font-weight: bold; text-align: right; font-size: 16px; margin-top: 20px; }
                .footer { margin-top: 30px; font-size: 12px; text-align: center; color: #666; }
            </style>

            <div class="logo">
                <h1>' . htmlspecialchars($boutique['nom'] ?? 'LUXEBOUTIQUE') . '</h1>
                <p>' . ($lang === 'ar' ? 'إيصال الطلب' : 'Reçu de commande') . '</p>
            </div>

            <div class="info">
                <p><strong>' . ($lang === 'ar' ? 'رقم الطلب:' : 'Numéro de commande:') . '</strong> #' . str_pad($client_id, 6, '0', STR_PAD_LEFT) . '</p>
                <p><strong>' . ($lang === 'ar' ? 'كود العميل:' : 'Code client:') . '</strong> ' . $code_client . '</p>
                <p><strong>' . ($lang === 'ar' ? 'التاريخ:' : 'Date:') . '</strong> ' . date('d/m/Y H:i') . '</p>
                <p><strong>' . ($lang === 'ar' ? 'العميل:' : 'Client:') . '</strong> ' . htmlspecialchars($_POST['nom']) . '</p>
                <p><strong>' . ($lang === 'ar' ? 'البريد الإلكتروني:' : 'Email:') . '</strong> ' . htmlspecialchars($_POST['email']) . '</p>
                <p><strong>' . ($lang === 'ar' ? 'الهاتف:' : 'Téléphone:') . '</strong> ' . htmlspecialchars($_POST['telephone']) . '</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>' . ($lang === 'ar' ? 'المنتج' : 'Produit') . '</th>
                        <th>' . ($lang === 'ar' ? 'السعر' : 'Prix unitaire') . '</th>
                        <th>' . ($lang === 'ar' ? 'الكمية' : 'Quantité') . '</th>
                        <th>' . ($lang === 'ar' ? 'المجموع' : 'Total') . '</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($produits as $prod) {
                $qte = $panier[$prod['id']];
                $sous_total = $qte * $prod['prix'];
                
                $html .= '
                    <tr>
                        <td>' . htmlspecialchars($prod['nom']) . '</td>
                        <td>' . number_format($prod['prix'], 2) . ' DHS</td>
                        <td>' . $qte . '</td>
                        <td>' . number_format($sous_total, 2) . ' DHS</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>

            <div class="total">
                ' . ($lang === 'ar' ? 'المجموع:' : 'Total:') . ' ' . number_format($total, 2) . ' DHS
            </div>

            <div class="footer">
                <p>' . ($lang === 'ar' ? 'شكرًا لطلبك!' : 'Merci pour votre commande !') . '</p>
                <p>' . htmlspecialchars($boutique['nom'] ?? 'LUXEBOUTIQUE') . ' - ' . ($lang === 'ar' ? 'خدمة العملاء:' : 'Service client:') . ' ' . htmlspecialchars($boutique['email'] ?? 'contact@luxeboutique.com') . ' - ' . ($lang === 'ar' ? 'الهاتف:' : 'Tél:') . ' ' . htmlspecialchars($boutique['telephone'] ?? '+212 6 00 00 00 00') . '</p>
            </div>';

            $pdf->writeHTML($html, true, false, true, false, '');

            // Sauvegarder le PDF
       $pdf_content = $pdf->Output('recu_commande.pdf', 'S'); // 'S' pour obtenir le contenu sous forme de string

// Stocker le PDF en session pour téléchargement ultérieur
$_SESSION['pdf_receipt'] = [
    'content' => $pdf_content,
    'filename' => 'recu_commande_' . $client_id . '.pdf'
];

// Notifications avec lien vers une nouvelle page de téléchargement
$_SESSION['notifications'] = [
    [
        'type' => 'success',
        'message' => $lang === 'ar' 
            ? 'تم تأكيد طلبك بنجاح! <a href="download_receipt.php" target="_blank">تحميل الإيصال</a>' 
            : 'Votre commande a été validée avec succès! <a href="download_receipt.php" target="_blank">Télécharger le reçu</a>'
    ]
];

            if ($is_new_client) {
                $_SESSION['notifications'][] = [
                    'type' => 'info',
                    'message' => $lang === 'ar' 
                         ? 'رمز العميل الخاص بك هو: <strong>' . $code_client . '</strong>. يُرجى الاحتفاظ به بعناية، فستحتاج إليه للوصول إلى سجل طلباتك.'
    : 'Votre code client est : <strong>' . $code_client . '</strong>. Veuillez le conserver soigneusement, il vous sera nécessaire pour accéder à l’historique de vos commandes.'               ];
            }

        } catch (Exception $e) {
            error_log("Erreur génération PDF: " . $e->getMessage());
            $_SESSION['notifications'] = [
                [
                    'type' => 'error',
                    'message' => $lang === 'ar' 
                        ? 'تم تأكيد طلبك ولكن لم يتم إنشاء الإيصال'
                        : 'Votre commande a été validée mais le reçu n\'a pas pu être généré'
                ]
            ];
            
            if ($is_new_client) {
                $_SESSION['notifications'][] = [
                    'type' => 'info',
                    'message' => $lang === 'ar' 
                        ? 'كود العميل الخاص بك هو: <strong>' . $code_client . '</strong> (احتفظ به للوصول إلى طلباتك المستقبلية).'
                        : 'Votre code client est: <strong>' . $code_client . '</strong> (Conservez-le pour accéder à vos commandes futures).'
                ];
            }
        }

        $_SESSION['panier'] = [];
        $panier = [];
        $produits = [];
        
        header("Location: panier.php?id=" . $boutique_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['notifications'] = [
            [
                'type' => 'error',
                'message' => $lang === 'ar' 
                    ? 'خطأ في تأكيد الطلب: ' . $e->getMessage()
                    : 'Erreur lors de la commande: ' . $e->getMessage()
            ]
        ];
        
        if (!empty($panier)) {
            $ids = implode(",", array_keys($panier));
            $stmt = $conn->query("SELECT * FROM produits WHERE id IN ($ids) AND boutique_id = $boutique_id");
            $produits = $stmt->fetchAll() ?: [];
        }
        
        header("Location: panier.php?id=" . $boutique_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $boutique ? htmlspecialchars($boutique['nom']) . ' | ' : '' ?><?= $lang === 'ar' ? 'سلة التسوق' : 'Panier' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: rgb(112, 46, 183);
            --secondary: #2575fc;
            --accent: rgb(92, 170, 230);
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--dark);
            min-height: 100vh;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header moderne */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo img {
            height: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .close-menu{
            display:none;
        }

        .menu-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 1.5rem;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .nav-links a i {
            font-size: 1.1rem;
        }

        .panier {
            position: relative;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            position: relative;
        }

        .cart-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Menu mobile */
@media (max-width: 992px) {
    #supp {
        display: none;
    }
    .menu-toggle {
        display: flex;
    }
    .close-menu {
        display: block;
    }
    
    .nav-links {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        flex-direction: column;
        padding: 60px 20px 20px;
        gap: 10px;
        z-index: 998;
        transition: left 0.3s ease;
        box-shadow: 5px 0 15px rgba(0,0,0,0.2);
        border-radius: 0;
        overflow-y: auto;
    }
    
    html[dir="rtl"] .nav-links {
        left: auto;
        right: -100%;
        border-radius: 0;
    }
    
    .nav-links.active {
        left: 0;
    }
    
    html[dir="rtl"] .nav-links.active {
        right: 0;
        left: auto;
    }
    
    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 997;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .menu-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .nav-links a {
        width: 100%;
        justify-content: flex-start;
        padding: 12px 15px;
        margin: 0;
    }
    
    .close-menu {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 999;
    }
    
    html[dir="rtl"] .close-menu {
        right: auto;
        left: 15px;
    }
}
@media (max-width: 400px) {
    .cart-item {
        flex-direction: column;
        padding: 15px 0;
    }
    
    .item-image {
        width: 100%;
        height: auto;
        max-height: 150px;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .item-details {
        width: 100%;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .item-name {
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    .item-price {
        font-size: 15px;
        margin: 5px 0;
    }
    
    .item-actions {
        width: 100%;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
    
    
    .quantity-input {
        width: 40px;
        text-align: center;
    }
    
    .btn-delete {
        margin-left: auto;
    }
    
    #supp {
        display: none;
    }
     /* Centrer le titre et le prix */
    .item-details {
        text-align: center;
        width: 100%;
        margin-right: 0;
        margin-bottom: 10px;
    }

    /* Centrer le contrôle de quantité */
    .item-actions {
        width: 100%;
        justify-content: center;
    }

    .quantity-control {
        justify-content:center;
    }

    /* Optionnel: ajuster l'espacement */
    .cart-item {
        padding: 15px 0;
        text-align: center;
    }
     .quantity-control {
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto; /* Centrage horizontal */
        width: auto; /* Annule toute largeur fixe */
    }

    /* Optionnel : ajustement des boutons +/- */
    .quantity-btn {
        padding: 5px 12px; /* Meilleure zone cliquable */
    }

    /* Pour le conteneur des actions */
    .item-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
}

        /* Animation du badge */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .badge.pulse {
            animation: pulse 0.5s ease;
        }

        /* Sélecteur de langue */
        .language-switcher {
            position: relative;
        }

        .language-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .language-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 150px;
            z-index: 100;
            display: none;
        }

        .language-switcher.active .language-dropdown {
            display: block;
        }

        .language-option {
            padding: 8px 15px;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .language-option:hover {
            background: #f8f9fa;
            color: var(--primary);
        }

        .language-option i {
            width: 16px;
            text-align: center;
            color: var(--primary);
        }

        html[dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }

        /* Main content */
        main {
            flex: 1;
            padding: 40px 0;
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 28px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        /* Cart items */
        .cart-items {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--light);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .item-details {
            flex: 1;
            margin-right:10px;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .item-comment {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 5px;
            font-style: italic;
        }
        
        .item-price {
            font-weight: 600;
            color: var(--primary);
        }
        
        .item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid var(--light);
            border-radius: 20px;
            padding: 5px 10px;
        }
        
        .quantity-btn {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            color: var(--primary);
            padding: 0 10px;
        }
        
        .quantity {
            margin: 0 10px;
        }
        
        .btn-delete {
            color: var(--danger);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        a{
            text-decoration:none;
        }
        
        .btn-delete:hover {
            transform: scale(1.08);
            color: var(--primary);
        }
        
        /* Cart summary */
        .cart-summary {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 18px;
            color: var(--primary);
            border-top: 1px solid var(--light);
            padding-top: 15px;
            margin-top: 10px;
        }
        
        /* Form */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        input, textarea {
            padding: 12px 15px;
            width: 100%;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus, textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(92, 170, 230, 0.2);
        }
        
        .btn-add {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        
        .btn-add:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .empty-cart p {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 30px;
        }
        
        /* Back link */
        .back-link {
            display: inline-block;
            margin: 20px 0 40px;
            padding: 10px 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .back-link:hover {
            transform: translateX(-5px);
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 30px;
            margin-top: 80px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-column h3 {
            font-size: 1.25rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--accent);
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column ul li {
            margin-bottom: 12px;
        }

        .footer-column ul li a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-column ul li a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            color: white;
            font-size: 1.25rem;
            transition: all 0.3s;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.1);
        }

        .social-links a:hover {
            transform: translateY(-3px);
            background: var(--accent);
        }

        .copyright {
            text-align: center;
            padding-top: 40px;
            margin-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #adb5bd;
            font-size: 0.9rem;
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 10px;
            right: 30px;
            padding: 15px 25px 15px 15px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            z-index: 1000;
            display: flex;
            align-items: center;
            max-width: 400px;
            animation: slideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            border-left: 5px solid;
            transform: translateX(calc(100% + 20px));
        }

        .notification.success {
            background-color: #28a745;
            border-left-color: #1e7e34;
        }

        .notification.error {
            background-color: #dc3545;
            border-left-color: #bd2130;
        }

        .notification.info {
            background-color: #17a2b8;
            border-left-color: #117a8b;
        }

        .notification i {
            margin-right: 12px;
            font-size: 22px;
            flex-shrink: 0;
        }

        .notification-content {
            flex-grow: 1;
        }

        .notification-close {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
        }

        .notification-close:hover {
            opacity: 1;
            background-color: rgba(255,255,255,0.3);
        }

        .notification-close::before {
            content: '×';
            font-size: 18px;
            font-weight: bold;
            color: white;
        }

        .notification a {
            color: white;
            text-decoration: underline;
            font-weight: 500;
        }

        .notification a:hover {
            text-decoration: none;
        }

        @keyframes slideIn {
            to { transform: translateX(0); }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translateY(-20px); }
        }

        /* Effet de rebond à l'entrée */
        @keyframes bounceIn {
            0% { transform: translateX(calc(100% + 20px)); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(5px); }
            100% { transform: translateX(0); }
        }

        /* RTL styles */
        html[dir="rtl"] .header-content,
        html[dir="rtl"] .footer-content,
        html[dir="rtl"] .card-actions {
            direction: rtl;
        }

        html[dir="rtl"] .badge {
            right: auto;
            left: -5px;
        }

        html[dir="rtl"] .tag {
            right: auto;
            left: 10px;
        }

        html[dir="rtl"] .footer-column h3::after {
            left: auto;
            right: 0;
        }
         .language-switcher a{
            color: black;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }
            
            .cart-summary, .form-container {
                padding: 20px 15px;
            }
            
            .notification {
                right: 15px;
                left: 15px;
                max-width: calc(100% - 30px);
            }
            
            .empty-cart {
                padding: 30px 15px;
            }
        }
       .nav-links a.active {
    background: rgba(255, 255, 255, 0.2) !important;
    transform: translateY(-2px);
    position: relative;
}

.nav-links a.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: white;
}

/* Pour la version mobile */
@media (max-width: 768px) {
    .nav-links a.active {
        background: rgba(255, 255, 255, 0.2) !important;
    }
    
    .nav-links a.active::after {
        display: none;
    }
}
    </style>
</head>
<body>
  <header>
    <div class="header-content">
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <a href="acceuil.php<?= $boutique ? '?id='.$boutique['id'] : '' ?>" class="logo">
            <?php if($boutique && !empty($boutique['logo'])): ?>
                <img src="<?= htmlspecialchars($boutique['logo']) ?>" alt="<?= htmlspecialchars($boutique['nom']) ?>">
            <?php else: ?>
                <i class="fas fa-gem"></i>
            <?php endif; ?>
            <span><?= $boutique ? htmlspecialchars($boutique['nom']) : 'Nom de boutique' ?></span>
        </a>
        
        <div class="nav-links">
            <button class="close-menu">
                <i class="fas fa-times"></i>
            </button>
             <div class="language-switcher">
                    <button class="language-btn">
                        <i class="fas fa-globe"></i>
                        <?= $lang === 'ar' ? 'العربية' : 'Français' ?>
                    </button>
                    <div class="language-dropdown">
                        <a href="?lang=fr&id=<?= $boutique['id'] ?? '' ?>" class="language-option" style="color:dark;">
                            <?php if($lang === 'fr'): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-language" style="visibility: hidden;"></i>
                            <?php endif; ?>
                            Français
                        </a>
                        <a href="?lang=ar&id=<?= $boutique['id'] ?? '' ?>" class="language-option">
                            <?php if($lang === 'ar'): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-language" style="visibility: hidden;"></i>
                            <?php endif; ?>
                            العربية
                        </a>
                    </div>
                </div>

            <a href="acceuil.php<?= $boutique ? '?id='.$boutique['id'] : '' ?>">
                <i class="fas fa-home"></i>
                <span><?= $lang === 'ar' ? 'الرئيسية' : 'Accueil' ?></span>
            </a>
            <a href="panier.php?id=<?= $boutique['id'] ?? '' ?>" class="active">
                <i class="fas fa-shopping-cart"></i>
                <span><?= $lang === 'ar' ? 'سلة المشتريات' : 'Mon panier' ?></span>
            </a>
            <a href="mes_commandes.php?id=<?= $boutique['id'] ?? '' ?>">
                <i class="fas fa-box-open"></i>
                <span><?= $lang === 'ar' ? 'طلباتي' : 'Mes commandes' ?></span>
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span><?= $lang === 'ar' ? 'تسجيل الخروج' : 'Déconnexion' ?></span>
            </a>
        </div>
        
        <div class="panier">
            <button class="cart-btn">
                <i class="fas fa-shopping-cart"></i>
                <span class="badge"><?= array_sum($_SESSION['panier'] ?? []) ?></span>
            </button>
        </div>
    </div>
    <div class="menu-overlay"></div>
</header>


    <main class="container">
        <?php foreach ($notifications as $notif): ?>
            <div class="notification <?= $notif['type'] ?>" style="animation: bounceIn 0.6s forwards; margin-bottom: 10px;">
                <i class="fas <?= 
                    $notif['type'] === 'success' ? 'fa-check-circle' : 
                    ($notif['type'] === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle')
                ?>"></i>
                <div class="notification-content">
                    <?= $notif['message'] ?>
                </div>
                <div class="notification-close"></div>
            </div>
        <?php endforeach; ?>

        <script>
            document.querySelectorAll('.notification-close').forEach(btn => {
                btn.addEventListener('click', function() {
                    const notification = this.closest('.notification');
                    notification.style.animation = 'fadeOut 0.4s forwards';
                    setTimeout(() => notification.remove(), 400);
                });
            });
        </script>
        
       <br>
        <h2><?= $lang === 'ar' ? 'سلة التسوق' : 'Votre Panier' ?></h2>
        
        <?php if (empty($produits)): ?>
            <div class="empty-cart">
                <p><?= $lang === 'ar' ? 'سلة التسوق فارغة' : 'Votre panier est vide.' ?></p>
                <a href="acceuil.php?id=<?= $boutique_id ?>" class="btn-add"><?= $lang === 'ar' ? 'تصفح منتجاتنا' : 'Découvrir nos produits' ?></a>
            </div>
        <?php else: 
            // Réinitialiser le total avant la boucle
            $total = 0;
        ?>
            <div class="cart-items">
                <?php foreach ($produits as $prod): 
                    $qte = $panier[$prod['id']];
                    $sous_total = $qte * $prod['prix'];
                    $total += $sous_total;
                ?>
                <div class="cart-item">
                    <img src="<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['nom']) ?>" class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($prod['nom']) ?></div>
                        <div class="item-price"><?= number_format($prod['prix'], 2) ?> DHS</div>
                    </div>
                    <div class="item-actions">
                        <form class="quantity-form" method="post">
                            <input type="hidden" name="update_quantity" value="1">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn decrease">-</button>
                                <input type="number" name="quantity" class="quantity-input" 
                                       value="<?= $qte ?>" min="1" max="99" readonly>
                                <button type="button" class="quantity-btn increase">+</button>
                            </div>
                        </form>
                        <a href="?supprimer=<?= $prod['id'] ?>&id=<?= $boutique_id ?>" class="btn-delete">✖ <span id="supp"><?= $lang === 'ar' ? 'حذف' : 'Supprimer'  ?></span></a >
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span><?= $lang === 'ar' ? 'المجموع الجزئي' : 'Sous-total' ?></span>
                    <span><?= number_format($total, 2) ?> DHS</span>
                </div>
                <div class="summary-row summary-total">
                    <span><?= $lang === 'ar' ? 'المجموع الكلي' : 'Total' ?></span>
                    <span><?= number_format($total, 2) ?> DHS</span>
                </div>
            </div>

            <form action="" method="POST" class="form-container">
                <h3><?= $lang === 'ar' ? 'معلومات العميل' : 'Informations Client' ?></h3>
                <div class="form-group">
                    <input type="text" name="nom" placeholder="<?= $lang === 'ar' ? 'الاسم الكامل' : 'Votre nom complet' ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="<?= $lang === 'ar' ? 'البريد الإلكتروني' : 'Votre email' ?>" >
                </div>
                <div class="form-group">
                    <input type="tel" name="telephone" placeholder="<?= $lang === 'ar' ? 'رقم الهاتف' : 'Votre numéro de téléphone' ?>" required>
                </div>
                <button type="submit" class="btn-add"><?= $lang === 'ar' ? 'تأكيد الطلب' : 'Valider la commande' ?></button>
            </form>
        <?php endif; ?>
    </main>

     <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3><?= $boutique ? htmlspecialchars($boutique['nom']) : 'Nom de boutique' ?></h3>
                <p><?= $boutique ? htmlspecialchars($boutique['description']) : ($lang === 'ar' ? 'وجهتكم المميزة لمنتجات استثنائية' : 'Votre destination premium pour des produits d\'exception') ?></p>
               <div class="social-links">
    <?php if (!empty($boutique['facebook'])): ?>
        <a href="<?= htmlspecialchars($boutique['facebook']) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
    <?php endif; ?>
    
    <?php if (!empty($boutique['instagram'])): ?>
        <a href="<?= htmlspecialchars($boutique['instagram']) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
    <?php endif; ?>
    
    <?php if (!empty($boutique['twitter'])): ?>
        <a href="<?= htmlspecialchars($boutique['twitter']) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
    <?php endif; ?>
    
    <?php if (!empty($boutique['pinterest'])): ?>
        <a href="<?= htmlspecialchars($boutique['pinterest']) ?>" target="_blank"><i class="fab fa-pinterest"></i></a>
    <?php endif; ?>
</div>
            </div>
            <div class="footer-column">
                <h3><?= $lang === 'ar' ? 'اتصل بنا' : 'Contactez-nous' ?></h3>
                <ul>
                    <li><a href="#"><i class="fas fa-phone"></i> <?= $boutique ? htmlspecialchars($boutique['telephone'] ?? ($lang === 'ar' ? 'غير متوفر' : 'Non disponible')) : ($lang === 'ar' ? 'اتصل بنا' : 'Contactez-nous') ?></a></li>
                   <li>
    <a href="mailto:<?= htmlspecialchars($boutique['email']) ?>">
        <i class="fas fa-envelope"></i>
        <?= $boutique ? htmlspecialchars($boutique['email'] ?? ($lang === 'ar' ? 'غير متوفر' : 'Non disponible')) : ($lang === 'ar' ? 'البريد الإلكتروني' : 'Email') ?>
    </a>
</li>

                    <li>
    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($boutique ? htmlspecialchars($boutique['adresse'] ?? '') : '') ?>" target="_blank">
        <i class="fas fa-map-marker-alt"></i> 
        <?= $boutique ? htmlspecialchars($boutique['adresse'] ?? ($lang === 'ar' ? 'غير متوفر' : 'Non disponible')) : ($lang === 'ar' ? 'العنوان' : 'Adresse') ?>
    </a>
</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date('Y') ?> <?= $boutique ? htmlspecialchars($boutique['nom']) : 'LUXEBOUTIQUE' ?>. <?= $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'Tous droits réservés.' ?>
        </div>
    </footer>

    <script>
        // Gestion du menu mobile
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        const menuOverlay = document.querySelector('.menu-overlay');
        const closeMenu = document.querySelector('.close-menu');
        
        menuToggle.addEventListener('click', function() {
            navLinks.classList.add('active');
            menuOverlay.classList.add('active');
        });
        
        closeMenu.addEventListener('click', function() {
            navLinks.classList.remove('active');
            menuOverlay.classList.remove('active');
        });
        
        menuOverlay.addEventListener('click', function() {
            navLinks.classList.remove('active');
            menuOverlay.classList.remove('active');
        });

        // Gestion du sélecteur de langue
        const languageSwitcher = document.querySelector('.language-switcher');
        const languageBtn = document.querySelector('.language-btn');

        languageBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            languageSwitcher.classList.toggle('active');
        });

        // Fermer le menu déroulant quand on clique ailleurs
        document.addEventListener('click', function() {
            if (languageSwitcher.classList.contains('active')) {
                languageSwitcher.classList.remove('active');
            }
        });

        // Empêcher la fermeture quand on clique dans le menu déroulant
        const languageDropdown = document.querySelector('.language-dropdown');
        if (languageDropdown) {
            languageDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

        // Gestion des boutons +/-
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const input = form.querySelector('.quantity-input');
                let quantity = parseInt(input.value);
                
                if (this.classList.contains('increase')) {
                    quantity++;
                } else if (this.classList.contains('decrease') && quantity > 1) {
                    quantity--;
                }
                
                input.value = quantity;
                
                // Soumettre le formulaire via AJAX
                fetch('panier.php?id=<?= $boutique_id ?>', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => {
                    if (response.ok) {
                        location.reload();
                    }
                });
            });
        });



        document.querySelector('.cart-btn').addEventListener('click', function() {
    window.location.href = 'panier.php?id=<?= $boutique['id'] ?? '' ?>';
});
    </script>
</body>
</html>