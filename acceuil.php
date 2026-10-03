<?php
session_start();
require 'config.php';

// Recherche de la boutique
$boutique = null;
if (isset($_GET['id'])) {
    $boutique_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
    $stmt->execute([$boutique_id]);
    $boutique = $stmt->fetch();
}

// Récupérer les produits
if ($boutique) {
    $produits = $conn->query("SELECT * FROM produits WHERE boutique_id = " . $boutique['id'])->fetchAll();
} else {
    $produits = [];
}

// Ajouter au panier
if (isset($_GET['ajouter'])) {
    $id = $_GET['ajouter'];
    $_SESSION['panier'][$id] = ($_SESSION['panier'][$id] ?? 0) + 1;
    header("Location: acceuil.php" . ($boutique ? "?id=" . $boutique['id'] : ""));
    exit;
}

// Gestion de la langue
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $lang = $_SESSION['lang'];
    header("Location: acceuil.php" . ($boutique ? "?id=" . $boutique['id'] : ""));
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $boutique ? htmlspecialchars($boutique['nom']) . ' | ' : '' ?><?= $lang === 'ar' ? 'متجرنا الإلكتروني | شغفنا، أسلوبك' : 'Boutique en Ligne | Votre Style, Notre Passion' ?></title>
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
        a{
            text-decoration:none;
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
            padding: 12px 15px;
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
       @media (max-width: 768px) {
    .menu-toggle {
        display: block;
    }
    .close-menu {
        display: block;
    }
    
    .nav-links {
        position: fixed;
        top: 0;
        left: -100%;
        width: 280px;
        height: 100vh; /* Prend toute la hauteur de l'écran */
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

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= htmlspecialchars($boutique['boutique_image'] ?? '') ?>');
            background-position: center;
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 30px;
        }

        /* Produits */
        .produits-container {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .section-title h2 {
            font-size: 2.25rem;
            display: inline-block;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            margin: 15px auto 0;
            border-radius: 2px;
        }

        .produits {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .card-img {
            height: 220px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card:hover .card-img img {
            transform: scale(1.05);
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .card-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
            flex: 1;
            text-align: center;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-add {
            background: var(--success);
            color: white;
            border: none;
        }

        .btn-add:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--info);
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            z-index: 1;
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

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        .card:nth-child(5) { animation-delay: 0.5s; }
        .card:nth-child(6) { animation-delay: 0.6s; }

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
    </style>
    <style>
/* Nouveau style pour le header et le menu */
header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 15px 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
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
}

.logo img {
    height: 40px;
    border-radius: 8px;
}

/* Nouveau style pour la navigation */
.nav-links {
    display: flex;
    align-items: center;
    gap: 15px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 12px 15px;
    font-weight: 500;
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.nav-links a::after {
    content: '';
    position: absolute;
    left: 0;
    height: 2px;
    transition: width 0.3s ease;
}

.nav-links a:hover::after {
    width: 100%;
}

.nav-links a i {
    font-size: 1.1rem;
}

/* Style pour le panier */
.panier {
    position: relative;
    margin-left: 15px;
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
}

/* Menu mobile */
.menu-toggle {
    display: none;
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .menu-toggle {
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
    
    .close-menu {
        position: absolute;
        top: 20px;
        right: 20px;
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 997;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .menu-overlay.active {
        opacity: 1;
        visibility: visible;
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
       .language-switcher a{
            color: black;
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

            <a href="acceuil.php<?= $boutique ? '?id='.$boutique['id'] : '' ?>" class="active">
                <i class="fas fa-home"></i>
                <span><?= $lang === 'ar' ? 'الرئيسية' : 'Accueil' ?></span>
            </a>
             <a href="panier.php?id=<?= $boutique['id'] ?? '' ?>">
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
    
    <section class="hero">
        <div class="container">
            <h1><?= $lang === 'ar' ? 'اكتشف مجموعتنا الحصرية' : 'Découvrez Notre Collection Exclusive' ?></h1>
            <p><?= $lang === 'ar' ? 'منتجات عالية الجودة مختارة بعناية من أجل راحتكم' : 'Des produits de qualité supérieure sélectionnés avec soin pour votre plus grand plaisir' ?></p>
        </div>
    </section>
    
    <section class="produits-container">
        <div class="container">
            <div class="section-title">
                <h2><?= $lang === 'ar' ? 'منتجاتنا المميزة' : 'Nos Produits Phares' ?></h2>
            </div>
            
            <div class="produits">
                <?php if($boutique && count($produits) > 0): ?>
                    <?php foreach ($produits as $p): ?>
                    <div class="card">
                        <?php if($p['stock'] < 10): ?>
                            <span class="tag"><?= $lang === 'ar' ? 'كمية محدودة!' : 'Stock limité!' ?></span>
                        <?php endif; ?>
                        <div class="card-img">
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($p['nom']) ?></h3>
                            <div class="card-price"><?= number_format($p['prix'], 2) ?> <?= $lang === 'ar' ? 'درهم' : 'DHS' ?></div>
                            <div class="card-actions">
                                <a href="?ajouter=<?= $p['id'] ?><?= $boutique ? '&id=' . $boutique['id'] : '' ?>" class="btn-sm btn-add">
                                    <i class="fas fa-cart-plus"></i> <?= $lang === 'ar' ? 'إضافة' : 'Ajouter' ?>
                                </a>
                                <a href="detail.php?id=<?= $p['id'] ?>" class="btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i> <?= $lang === 'ar' ? 'تفاصيل' : 'Détails' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php elseif($boutique): ?>
                    <div style="text-align: center; width: 100%; padding: 40px 0;">
                        <i class="fas fa-box-open" style="font-size: 50px; color: #ccc;"></i>
                        <h3><?= $lang === 'ar' ? 'لا توجد منتجات متاحة في هذه المتجر' : 'Aucun produit disponible dans cette boutique' ?></h3>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; width: 100%; padding: 40px 0;">
                        <i class="fas fa-store" style="font-size: 50px; color: #ccc;"></i>
                        <h3><?= $lang === 'ar' ? 'الرجاء اختيار متجر لعرض منتجاته' : 'Veuillez sélectionner une boutique pour voir ses produits' ?></h3>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
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
        
        // Animation des cartes
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                    }
                });
            }, { threshold: 0.1 });
            
            cards.forEach(card => {
                observer.observe(card);
            });
            
            // Animation du badge du panier
            const addButtons = document.querySelectorAll('[href^="?ajouter"]');
            const badge = document.querySelector('.badge');
            
            addButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Animation du badge
                    badge.classList.add('pulse');
                    setTimeout(() => {
                        badge.classList.remove('pulse');
                    }, 500);
                    
                    // Redirection
                    setTimeout(() => {
                        window.location.href = this.getAttribute('href');
                    }, 200);
                });
            });
            
            // Redirection du bouton panier
            document.querySelector('.cart-btn').addEventListener('click', function() {
                window.location.href = 'panier.php?id=<?= $boutique['id'] ?? '' ?>';
            });
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
    </script>
</body>
</html>