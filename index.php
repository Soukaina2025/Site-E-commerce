<?php
session_start();
require 'config.php';

// Gestion de la langue
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $lang = $_SESSION['lang'];
    header("Location: acceuil.php");
    exit;
}

// Traitement de la recherche de boutique
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_boutique'])) {
    $nom_boutique = trim($_POST['nom_boutique']);
    
    if (!empty($nom_boutique)) {
        $stmt = $conn->prepare("SELECT id FROM boutiques WHERE nom LIKE ?");
        $stmt->execute(["%$nom_boutique%"]);
        $boutique = $stmt->fetch();
        
        if ($boutique) {
            header("Location: acceuil.php?id=" . $boutique['id']);
            exit;
        } else {
            $error = $lang === 'ar' ? 'لم يتم العثور على المتجر' : 'Boutique non trouvée';
        }
    } else {
        $error = $lang === 'ar' ? 'الرجاء إدخال اسم المتجر' : 'Veuillez entrer un nom de boutique';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'ar' ? 'ابحث عن متجرك' : 'Trouvez votre boutique' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: rgb(112, 46, 183);
            --secondary: #2575fc;
            --primary-light: rgba(112, 46, 183, 0.8);
            --light: #f8f9fa;
            --white: #ffffff;
            --text: #2d3436;
            --text-light: #636e72;
            --border: #dfe6e9;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --main-bg: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background-color: var(--white);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        /* Header */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 12px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .logo {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .logo:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-link {
            font-size: 1rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
        }
        
        .admin-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 80px 0;
            text-align: center;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                       url('uploads/gettyimages-1494586734-640x640.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            overflow: hidden;
            min-height: auto;
            box-sizing: border-box;
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
        }

        .main-content > * {
            position: relative;
            z-index: 1;
        }
        
        .main-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .main-content p {
            font-size: 1.2rem;
            color: white;
            max-width: 700px;
            margin: 0 auto 2.5rem;
        }
        
        .search-box {
            background: var(--white);
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        .search-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .search-box h2 {
            margin-bottom: 25px;
            font-size: 1.6rem;
            color: var(--text);
            font-weight: 600;
        }
        
        .search-form {
            display: flex;
            flex-direction: column;
        }
        
        .input-group {
            display: flex;
            margin-bottom: 15px;
        }
        
        .input-group input {
            flex: 1;
            padding: 14px 22px;
            border: 1px solid var(--border);
            border-radius: 8px 0 0 8px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s;
            background: rgba(255,255,255,0.8);
        }
        
        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }
        
        .input-group button {
            padding: 0 30px;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .input-group button:hover {
            background: var(--primary-light);
        }
        
        .error {
            color: #ff4757;
            margin-bottom: 15px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        /* Boutiques List */
        .boutiques-list {
            padding: 60px 0;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .boutiques-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    justify-content: center; /* Nouveau - Centre toute la grille */
}

.boutique-card {
    background: var(--white);
    border-radius: 12px;
    padding: 30px;
    width: 280px; /* Largeur fixe */
    text-align: center;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid var(--border);
    margin: 0 auto; /* Centre la carte dans sa cellule */
}
@media (min-width: 768px) and (max-width: 1024px) {
    .boutiques-grid {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 25px;
    }
    
    .boutique-card {
        width: calc(50% - 13px); /* 2 cartes par ligne avec espacement */
        max-width: 280px;
    }
}
        
        .boutique-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-color: var(--primary-light);
        }
        
        .boutique-card i {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .boutique-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--text);
            font-weight: 600;
        }
        
        .boutique-card p {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Footer */
        footer {
            background: var(--light);
            color: var(--text);
            padding: 40px 0 25px;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid var(--border);
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .social-links a {
            color: var(--text-light);
            font-size: 1.3rem;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        .copyright {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        /* Language Switcher */
        .language-switcher {
            position: relative;
        }
        
        .language-btn {
            background: rgba(255,255,255,0.15);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .language-btn:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 8px 0;
            min-width: 150px;
            z-index: 100;
            display: none;
            animation: fadeIn 0.2s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .language-switcher:hover .language-dropdown {
            display: block;
        }
        
        .language-option {
            padding: 10px 16px;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        
        .language-option:hover {
            background: var(--light);
        }
        
        .boutiques-list h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: var(--text);
        }
        
        /* Correction spécifique pour iPad */
        @media (min-width: 768px) and (max-width: 1024px) {
            .boutiques-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .boutique-card {
                max-width: 100%;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                padding: 70px 0;
            }
            
            .main-content h1 {
                font-size: 2.2rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 60px 0;
            }
            
            .main-content h1 {
                font-size: 2rem;
            }
            
            .main-content p {
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            
            .search-box {
                padding: 30px;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-group input {
                border-radius: 8px;
                margin-bottom: 12px;
            }
            
            .input-group button {
                border-radius: 8px;
                padding: 14px;
                width: 100%;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: center;
                margin-top: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 50px 0;
            }
            
            .main-content h1 {
                font-size: 1.8rem;
            }
            
            .search-box {
                padding: 25px;
            }
            
            .search-box h2 {
                font-size: 1.4rem;
            }
            
            .boutiques-grid {
                grid-template-columns: 1fr;
            }
            
            .boutique-card {
                padding: 25px;
            }
            
            .header-right {
                flex-direction: row;
                align-items: center;
                gap: 10px;
            }
            
            .admin-link {
                padding: 6px 10px;
                font-size: 0.9rem;
            }
            
            .language-btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
        }
        
        /* RTL Support */
        html[dir="rtl"] .input-group input {
            border-radius: 0 8px 8px 0;
        }
        
        html[dir="rtl"] .input-group button {
            border-radius: 8px 0 0 8px;
        }
        
        html[dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }
        
        html[dir="rtl"] .header-right {
            flex-direction: row;
        }
        
        @media (max-width: 576px) and (dir="rtl") {
            .header-right {
                flex-direction: row;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-store"></i>
                <span><?= $lang === 'ar' ? 'دليل المتاجر' : 'Guide des boutiques' ?></span>
            </a>
            
            <div class="header-right">
                <a href="admin_login.php" class="admin-link">
                    <i class="fas fa-user-shield"></i>
                    <span><?= $lang === 'ar' ? 'مسؤول المتجر' : 'Espace gérant' ?></span>
                </a>
                
                <div class="language-switcher">
                    <button class="language-btn">
                        <i class="fas fa-globe"></i>
                        <?= $lang === 'ar' ? 'العربية' : 'Français' ?>
                    </button>
                    <div class="language-dropdown">
                        <a href="?lang=fr" class="language-option">
                            <?php if($lang === 'fr'): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-language" style="visibility: hidden;"></i>
                            <?php endif; ?>
                            Français
                        </a>
                        <a href="?lang=ar" class="language-option">
                            <?php if($lang === 'ar'): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="fas fa-language" style="visibility: hidden;"></i>
                            <?php endif; ?>
                            العربية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <h1><?= $lang === 'ar' ? 'ابحث عن متجرك المفضل' : 'Trouvez votre boutique préférée' ?></h1>
            <p><?= $lang === 'ar' ? 'اكتشف أفضل المتاجر والعروض الحصرية' : 'Découvrez les meilleures boutiques et offres exclusives' ?></p>
            
            <div class="search-box">
                <h2><?= $lang === 'ar' ? 'بحث عن المتجر' : 'Rechercher une boutique' ?></h2>
                
                <?php if ($error): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form class="search-form" method="POST">
                    <div class="input-group">
                        <input type="text" name="nom_boutique" placeholder="<?= $lang === 'ar' ? 'اسم المتجر...' : 'Nom de la boutique...' ?>" required>
                        <button type="submit">
                            <i class="fas fa-search"></i> <?= $lang === 'ar' ? 'بحث' : 'Rechercher' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <section class="boutiques-list">
        <div class="container">
            <h3><?= $lang === 'ar' ? 'متاجرنا الشائعة' : 'Nos boutiques populaires' ?></h3>
            
            <div class="boutiques-grid">
                <?php
                $boutiques = $conn->query("SELECT * FROM boutiques ORDER BY RAND() LIMIT 4")->fetchAll();
                
                foreach ($boutiques as $boutique): ?>
                    <div class="boutique-card" onclick="window.location='acceuil.php?id=<?= $boutique['id'] ?>'">
                        <i class="fas fa-store"></i>
                        <h4><?= htmlspecialchars($boutique['nom']) ?></h4>
                        <p><?= $lang === 'ar' ? 'اكتشف أحدث المنتجات' : 'Découvrez nos nouveaux produits' ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <footer>
        <div class="container">
            <p class="copyright">&copy; <?= date('Y') ?> <?= $lang === 'ar' ? 'جميع الحقوق محفوظة' : 'Tous droits réservés' ?></p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="nom_boutique"]').focus();
            
            const cards = document.querySelectorAll('.boutique-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.4s ease ' + (index * 0.15) + 's';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    document.querySelector('header').style.padding = '8px 0';
                    document.querySelector('header').style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                } else {
                    document.querySelector('header').style.padding = '12px 0';
                    document.querySelector('header').style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                }
            });
        });
    </script>
</body>
</html>