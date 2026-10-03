<?php
session_start();
require 'config.php';

// Définir le fuseau horaire
date_default_timezone_set('Africa/Casablanca');

// Vérifier si l'ID produit est présent
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID produit non spécifié");
}

$product_id = (int)$_GET['id'];

// Gestion de la langue - doit être avant toute utilisation de $lang
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $lang = $_SESSION['lang'];
    header("Location: detail.php?id=".$product_id); // Redirige avec le même ID produit
    exit;
}

// Récupérer les infos du produit
$stmt = $conn->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$product_id]);
$produit = $stmt->fetch();

if (!$produit) {
    die($lang === 'ar' ? "المنتج غير موجود" : "Produit non trouvé");
}

// Récupérer les infos de la boutique associée
$boutique = null;
if (!empty($produit['boutique_id'])) {
    $stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
    $stmt->execute([$produit['boutique_id']]);
    $boutique = $stmt->fetch();
}

// Gestion de l'ajout au panier
if (isset($_GET['ajouter'])) {
    $_SESSION['panier'][$product_id] = ($_SESSION['panier'][$product_id] ?? 0) + 1;
    
    // Message de confirmation
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => $lang === 'ar' 
            ? 'تمت إضافة المنتج إلى السلة بنجاح' 
            : 'Produit ajouté au panier avec succès'
    ];
    
    header("Location: panier.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'تفاصيل المنتج' : 'Détail du produit' ?></title>
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

        .product-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .product-content {
            display: flex;
            gap: 30px;
        }
        
        .product-image {
            flex: 1;
            min-width: 0;
            text-align: center;
        }
        
        .product-details {
            flex: 1;
            min-width: 0;
        }
        
        img {
            width: 100%;
            max-width: 400px;
            height: auto;
            max-height: 400px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .price {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 20px 0;
        }
        
        .description {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            line-height: 1.7;
        }
        
        .btn-add {
            display: inline-block;
            margin-top: 20px;
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
        }
        
        .btn-add:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
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
        a{
            text-decoration:none;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .product-content {
                flex-direction: column;
            }
            
            .header-content {
                flex-direction: row;
                justify-content: space-between;
            }
            
            .panier {
                width: auto;
                justify-content: flex-end;
            }
            
        }
        
        /* RTL styles for Arabic */
        html[dir="rtl"] .header-content,
        html[dir="rtl"] .footer-content {
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
        
        /* RTL styles */
        html[dir="rtl"] .header-content,
        html[dir="rtl"] .footer-content,
        html[dir="rtl"] .card-actions {
            direction: rtl;
        }
        html[dir="rtl"] .footer-column h3::after {
            left: auto;
            right: 0;
        }
        
        html[dir="rtl"] .back-link:hover {
            transform: translateX(5px);
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
    <a href="?lang=fr&id=<?= $_GET['id'] ?? '' ?>" class="language-option" style="color:dark;">
        <?php if($lang === 'fr'): ?>
            <i class="fas fa-check"></i>
        <?php else: ?>
            <i class="fas fa-language" style="visibility: hidden;"></i>
        <?php endif; ?>
        Français
    </a>
    <a href="?lang=ar&id=<?= $_GET['id'] ?? '' ?>" class="language-option">
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
        <div class="product-container">
            <div class="product-content">
                <div class="product-image">
                    <img src="<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
                </div>
                
                <div class="product-details">
                    <h2><?= htmlspecialchars($produit['nom']) ?></h2>
                    <div class="price"><?= number_format($produit['prix'], 2) ?> <?= $lang === 'ar' ? 'درهم' : 'DHS' ?></div>
                    
                    <?php if (!empty($produit['description'])): ?>
                    <div class="description">
                        <p><?= nl2br(htmlspecialchars($produit['description'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <a href="detail.php?id=<?= $produit['id'] ?>&ajouter=1" class="btn-add">
                        <?= $lang === 'ar' ? 'أضف إلى السلة' : 'Ajouter au panier' ?>
                    </a>
                </div>
            </div>
        </div>
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

        // Animation du badge du panier
        const addButton = document.querySelector('.btn-add');
        const badge = document.querySelector('.badge');
        
        if (addButton && badge) {
            addButton.addEventListener('click', function(e) {
                badge.classList.add('pulse');
                setTimeout(() => {
                    badge.classList.remove('pulse');
                }, 500);
            });
        }
        document.querySelector('.cart-btn').addEventListener('click', function() {
    window.location.href = 'panier.php?id=<?= $boutique['id'] ?? '' ?>';
});
    </script>
</body>
</html>