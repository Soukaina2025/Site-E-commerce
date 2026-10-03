<?php
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
    header("Location: mes_commandes.php?id=" . $boutique_id);
    exit;
}

// Gestion de la déconnexion
if (!isset($_POST['code_client'])) {
    unset($_SESSION['client_id']);
    unset($_SESSION['client_nom']);
    unset($_SESSION['code_client']);
}



$notification = [
    'type' => '',
    'message' => ''
];

// Vérification du code client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code_client'])) {
    $code_client = strtoupper(trim($_POST['code_client']));
    
    $stmt = $conn->prepare("SELECT id, nom FROM clients WHERE code_client = ? AND boutique_id = ?");
    $stmt->execute([$code_client, $boutique_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        $_SESSION['client_id'] = $client['id'];
        $_SESSION['client_nom'] = $client['nom'];
        $_SESSION['code_client'] = $code_client;
    } else {
        $notification = [
            'type' => 'error',
            'message' => $lang === 'ar' ? 'كود العميل غير صحيح. يرجى التحقق والمحاولة مرة أخرى.' : 'Code client invalide. Veuillez vérifier et réessayer.'
        ];
    }
}

// Récupérer les commandes si le client est identifié
$commandes = [];
if (isset($_SESSION['client_id'])) {
    $stmt = $conn->prepare("
        SELECT c.id, c.date_commande, p.nom as produit_nom, p.image, c.statut, p.prix, c.quantity 
        FROM commandes c
        JOIN produits p ON c.produit_id = p.id
        WHERE c.client_id = ? AND c.boutique_id = ?
        ORDER BY c.date_commande DESC
    ");
    $stmt->execute([$_SESSION['client_id'], $boutique_id]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'طلباتي' : 'Mes Commandes' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
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
        
        .close-menu {
            display: none;
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
            display: flex;
            align-items: center;
            gap: 15px;
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
        @media (max-width: 768px) {
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
                padding: 80px 20px 20px;
                gap: 10px;
                z-index: 998;
                transition: left 0.3s ease;
                box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            }
            
            html[dir="rtl"] .nav-links {
                left: auto;
                right: -100%;
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
            
            /* Style pour le bouton de fermeture */
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
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .language-switcher a {
            color: black;
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

        /* Contenu principal */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Formulaire code client */
        .code-client-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            max-width: 500px;
            margin: 40px auto;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .code-client-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .code-client-form h3 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }

        .code-client-form input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            margin-bottom: 10px;
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

        .code-client-form input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(112, 46, 183, 0.1);
            outline: none;
        }

        .code-client-form small {
            display: block;
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .btn-add {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(112, 46, 183, 0.3);
        }

        /* Client connecté */
        .client-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Liste des commandes */
        .commandes-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .commande-group {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 20px;
        }
        
        .commande-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px dashed var(--light-gray);
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .commande-date {
            font-weight: bold;
            color: var(--primary);
        }
        
        .commande-id {
            color: var(--gray);
        }

        /* Items de commande */
        .cart-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            background: #f9f9f9;
            transition: all 0.3s;
        }

        .cart-item:hover {
            background: #f0f0f0;
            transform: translateX(5px);
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }
        
        .item-name1 {
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
            background:green;
            border-radius:10px;
            width: 120px;
            padding: 2px;
            text-align:center;
        }
        
        .item-name2 {
            font-weight: 600;
            margin-bottom: 5px;
            color: white;
            background:orange;
            border-radius:10px;
            width: 120px;
            padding: 2px;
            text-align:center;
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .quantity-control {
            background: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Panier vide */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin: 40px 0;
        }

        .empty-cart p {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: slideIn 0.5s forwards;
        }

        .notification.error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .notification.success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Titre */
        main .container h2 {
            text-align: center;
            margin: 40px 0;
            font-size: 32px;
            color: var(--primary);
            position: relative;
            padding-bottom: 15px;
        }

        main .container h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .footer-column h3 {
            font-size: 18px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 40px;
            height: 2px;
            background: var(--accent);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
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
            font-size: 20px;
            transition: transform 0.3s;
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
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #adb5bd;
            font-size: 14px;
        }

        /* RTL adjustments */
        html[dir="rtl"] .language-btn i {
            margin-right: 0;
            margin-left: 5px;
        }
        
        html[dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }
        
        html[dir="rtl"] .language-option i {
            margin-right: 0;
            margin-left: 8px;
        }
        
        html[dir="rtl"] .cart-item {
            flex-direction: row-reverse;
        }
        
        html[dir="rtl"] .item-actions {
            align-items: flex-start;
        }
        
        html[dir="rtl"] .commande-header {
            flex-direction: row-reverse;
        }
        
        html[dir="rtl"] .notification {
            right: auto;
            left: 20px;
            animation: slideInRTL 0.5s forwards;
        }
        
        @keyframes slideInRTL {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: row;
                justify-content: space-between;
            }
            
            .panier {
                width: auto;
                justify-content: flex-end;
            }
            
            .client-info {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-actions {
                align-items: center;
                margin-top: 10px;
            }
            
            .item-image {
                width: 150px;
                height: 150px;
            }
        }
        
        /* RTL styles for Arabic */
        html[dir="rtl"] .header-content,
        html[dir="rtl"] .footer-content,
        html[dir="rtl"] .card-actions {
            direction: rtl;
        }
        
        html[dir="rtl"] .logo i {
            margin-right: 0;
            margin-left: 10px;
        }
        
        html[dir="rtl"] .badge {
            right: auto;
            left: -5px;
        }
        
        html[dir="rtl"] .footer-column h3::after {
            left: auto;
            right: 0;
        }
                a{
            text-decoration:none;
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
            <a href="panier.php?id=<?= $boutique['id'] ?? '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <span><?= $lang === 'ar' ? 'سلة المشتريات' : 'Mon panier' ?></span>
            </a>
            <a href="mes_commandes.php?id=<?= $boutique['id'] ?? '' ?>" class="active">
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
        <?php if (!empty($notification['message'])): ?>
            <div class="notification <?= $notification['type'] ?>">
                <i class="fas <?= $notification['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <div><?= htmlspecialchars($notification['message']) ?></div>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification').style.animation = 'fadeOut 0.5s forwards';
                    setTimeout(() => document.querySelector('.notification').remove(), 500);
                }, 5000);
            </script>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['client_id'])): ?>
            <div class="code-client-form">
                <h3><?= $lang === 'ar' ? 'الوصول إلى طلباتي' : 'Accéder à mes commandes' ?></h3>
                <form method="POST">
                    <input type="hidden" name="boutique_id" value="<?= $boutique_id ?>">
                    <div class="form-group">
                        <input type="text" name="code_client" placeholder="<?= $lang === 'ar' ? 'أدخل كود العميل الخاص بك' : 'Entrez votre code client' ?>" required>
                        <small><?= $lang === 'ar' ? 'تم تزويدك بهذا الرمز عند تقديم الطلب.' : 'Ce code vous a été fourni lors de votre commande.' ?></small>
                    </div>
                    <button type="submit" class="btn-add"><?= $lang === 'ar' ? 'عرض طلباتي' : 'Voir mes commandes' ?></button>
                </form>
            </div>
        <?php else: ?>
            <div class="client-info">
                <p><?= $lang === 'ar' ? 'مرحباً' : 'Bonjour' ?> <strong><?= htmlspecialchars($_SESSION['client_nom']) ?></strong> (<?= $lang === 'ar' ? 'كود العميل:' : 'Code client:' ?> <?= htmlspecialchars($_SESSION['code_client']) ?>)</p>
                <a href="?logout=1&id=<?= $boutique_id ?>" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i> <?= $lang === 'ar' ? 'تغيير العميل' : 'Changer de client' ?>
                </a>
            </div>
            
            <?php if (empty($commandes)): ?>
                <div class="empty-cart">
                    <p><?= $lang === 'ar' ? 'ليس لديك أي طلبات.' : 'Vous n\'avez aucune commande.' ?></p>
                    <a href="acceuil.php?id=<?= $boutique_id ?>" class="btn btn-primary"><?= $lang === 'ar' ? 'تصفح منتجاتنا' : 'Découvrir nos produits' ?></a>
                </div>
            <?php else: ?>
                <div class="commandes-list">
                    <?php
                    // Grouper les commandes par date
                    $grouped_commandes = [];
                    foreach ($commandes as $commande) {
                        $date = date('d/m/Y', strtotime($commande['date_commande']));
                        $grouped_commandes[$date][] = $commande;
                    }
                    
                    foreach ($grouped_commandes as $date => $commandes_group): 
                        $total_group = 0;
                        foreach ($commandes_group as $commande) {
                            $total_group += $commande['prix'] * $commande['quantity'];
                        }
                    ?>
                    <div class="commande-group">
                        <div class="commande-header">
                            <span class="commande-date"><?= $lang === 'ar' ? 'طلب بتاريخ' : 'Commande du' ?> <?= $date ?></span>
                            <span class="commande-id"><?= $lang === 'ar' ? 'المجموع:' : 'Total:' ?> <?= number_format($total_group, 2) ?> DHS</span>
                        </div>
                        
                        <?php foreach ($commandes_group as $commande): ?>
                        <div class="cart-item">
                            <img src="<?= htmlspecialchars($commande['image']) ?>" alt="<?= htmlspecialchars($commande['produit_nom']) ?>" class="item-image">
                            <div class="item-details">
                               <div class="<?= $commande['statut'] === 'Prête à retirer' ? 'item-name1' : 'item-name2' ?>">
                                <?= htmlspecialchars($commande['statut']) ?>
                            </div>
                                <div class="item-name"><?= htmlspecialchars($commande['produit_nom']) ?></div>
                                <div class="item-price"><?= number_format($commande['prix'], 2) ?> DHS</div>
                            </div>
                            <div class="item-actions">
                                <div class="quantity-control">
                                    <?= $lang === 'ar' ? 'الكمية:' : 'Quantité:' ?> <?= $commande['quantity'] ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
        document.querySelector('.cart-btn').addEventListener('click', function() {
    window.location.href = 'panier.php?id=<?= $boutique['id'] ?? '' ?>';
});
    </script>
</body>
</html>
</body>
</html>