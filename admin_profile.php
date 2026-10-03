<?php
session_start();

// Vérifier la connexion
if (!isset($_SESSION['boutique_id'])) {
    header("Location: admin_login.php");
    exit;
}

require 'config.php';
$boutique_id = $_SESSION['boutique_id'];

// Récupérer les infos de la boutique
$stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
$stmt->execute([$boutique_id]);
$boutique = $stmt->fetch();

if (!$boutique) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// Gestion de la langue
$lang = $_SESSION['lang'] ?? 'fr';
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: admin_profile.php");
    exit;
}

// Traitement du formulaire
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_email = filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (!$admin_email) {
        $errors['email'] = $lang === 'ar' ? 'البريد الإلكتروني غير صالح' : 'Email invalide';
    }

    if (!password_verify($current_password, $boutique['admin_password'])) {
        $errors['current_password'] = $lang === 'ar' ? 'كلمة المرور الحالية غير صحيحة' : 'Mot de passe actuel incorrect';
    }

    if (!empty($new_password) && $new_password !== $confirm_password) {
        $errors['confirm_password'] = $lang === 'ar' ? 'كلمات المرور غير متطابقة' : 'Les mots de passe ne correspondent pas';
    }

    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE boutiques SET admin_email = ?, admin_password = ? WHERE id = ?");
                $stmt->execute([$admin_email, $hashed_password, $boutique_id]);
            } else {
                $stmt = $conn->prepare("UPDATE boutiques SET admin_email = ? WHERE id = ?");
                $stmt->execute([$admin_email, $boutique_id]);
            }

            $boutique['admin_email'] = $admin_email;
            if (!empty($new_password)) {
                $boutique['admin_password'] = $hashed_password;
            }

            $success = true;
        } catch (PDOException $e) {
            $errors['general'] = $lang === 'ar' ? 'حدث خطأ أثناء تحديث البيانات' : 'Une erreur est survenue lors de la mise à jour';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang === 'ar' ? 'الملف الشخصي للإداري' : 'Profil Admin' ?> - <?= htmlspecialchars($boutique['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3a86ff;
            --secondary: #8338ec;
            --accent: #ff006e;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --warning: #ffbe0b;
            --danger: #dc3545;
            --gray: #6c757d;
            --bg: #f5f7fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: var(--bg);
            color: var(--dark);
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        html[dir="rtl"] .sidebar {
            right: 0;
            left: auto;
        }
        
        .logo {
            text-align: center;
            padding: 20px 0 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        /* Sélecteur de langue */
        .language-switcher-sidebar {
            position: relative;
            padding: 0 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .language-btn-sidebar {
            width: 100%;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 4px;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s;
        }

        .language-btn-sidebar:hover {
            background: rgba(255,255,255,0.2);
        }

        .language-btn-sidebar i {
            margin-right: 8px;
        }

        html[dir="rtl"] .language-btn-sidebar i {
            margin-right: 0;
            margin-left: 8px;
        }

        .language-btn-sidebar .fa-chevron-down {
            margin-right: 0;
            margin-left: 8px;
            font-size: 0.8em;
        }

        html[dir="rtl"] .language-btn-sidebar .fa-chevron-down {
            margin-left: 0;
            margin-right: 8px;
        }

        .language-dropdown-sidebar {
            display: none;
            position: absolute;
            top: 100%;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            z-index: 100;
            overflow: hidden;
        }

        html[dir="rtl"] .language-dropdown-sidebar {
            left: auto;
            right: 20px;
        }

        .language-switcher-sidebar:hover .language-dropdown-sidebar {
            display: block;
        }

        .language-option-sidebar {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }

        .language-option-sidebar:hover {
            background: #f5f5f5;
        }

        .language-option-sidebar i {
            margin-right: 8px;
            color: var(--primary);
        }

        html[dir="rtl"] .language-option-sidebar i {
            margin-right: 0;
            margin-left: 8px;
        }
        
        .nav-menu {
            margin-top: 30px;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--accent);
        }
        
        html[dir="rtl"] .nav-link:hover, 
        html[dir="rtl"] .nav-link.active {
            border-left: none;
            border-right: 3px solid var(--accent);
        }
        
        .nav-link i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        html[dir="rtl"] .nav-link i {
            margin-right: 0;
            margin-left: 10px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }
        
        html[dir="rtl"] .main-content {
            margin-right: 250px;
            margin-left: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.8rem;
            color: var(--dark);
            font-weight: 600;
        }
        
        /* Profile Container */
        .profile-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 15px;
        }
        
        .info-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .info-box p {
            margin-bottom: 10px;
        }
        
        .info-box strong {
            color: var(--dark);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
            outline: none;
        }
        
        .text-danger {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        .text-success {
            color: var(--success);
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(56, 176, 0, 0.1);
            border-radius: 6px;
            text-align: center;
        }
        
        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            background: #2a75e6;
        }
        
        /* Menu Burger */
        .burger-menu {
            display: none;
            cursor: pointer;
            padding: 15px;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1100;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            align-items: center;
            justify-content: center;
        }
        
        html[dir="rtl"] .burger-menu {
            left: auto;
            right: 10px;
        }
        
        .burger-menu span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--dark);
            position: relative;
            transition: all 0.3s;
        }
        
        .burger-menu span:before,
        .burger-menu span:after {
            content: '';
            position: absolute;
            width: 22px;
            height: 2px;
            background: var(--dark);
            transition: all 0.3s;
        }
        
        .burger-menu span:before {
            top: -6px;
        }
        
        .burger-menu span:after {
            top: 6px;
        }
        
        .burger-menu.active span {
            background: transparent;
        }
        
        .burger-menu.active span:before {
            transform: rotate(45deg);
            top: 0;
        }
        
        .burger-menu.active span:after {
            transform: rotate(-45deg);
            top: 0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            html[dir="rtl"] .sidebar {
                transform: translateX(100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            html[dir="rtl"] .main-content {
                margin-right: 0;
            }
            
            .burger-menu {
                display: flex;
            }
        }
        
        @media (max-width: 768px) {
                .logo h2{
        font-size:20px;
        margin-left:30px;
    }
            .header h1 {
                margin-top: 30px;
            }
            
            .profile-container {
                padding: 20px;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
        .nav-menu a{
            margin-bottom:10px;
        }
    </style>
</head>
<body>
    <!-- Bouton Burger -->
    <div class="burger-menu">
        <span></span>
    </div>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h2><?= $lang === 'ar' ? 'المتجر الإداري' : 'Boutique Admin' ?></h2>
            </div>
            
            <div class="language-switcher-sidebar">
                <button class="language-btn-sidebar">
                    <i class="fas fa-globe"></i>
                    <?= $lang === 'ar' ? 'العربية' : 'Français' ?>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="language-dropdown-sidebar">
                    <a href="?lang=fr" class="language-option-sidebar">
                        <?php if($lang === 'fr'): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <span style="width: 16px; display: inline-block;"></span>
                        <?php endif; ?>
                        Français
                    </a>
                    <a href="?lang=ar" class="language-option-sidebar">
                        <?php if($lang === 'ar'): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            <span style="width: 16px; display: inline-block;"></span>
                        <?php endif; ?>
                        العربية
                    </a>
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-item">
                    <a href="admin.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span><?= $lang === 'ar' ? 'لوحة التحكم' : 'Tableau de bord' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="admin_produits.php" class="nav-link">
                        <i class="fas fa-box-open"></i>
                        <span><?= $lang === 'ar' ? 'المنتجات' : 'Produits' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="commandes.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?= $lang === 'ar' ? 'الطلبات' : 'Commandes' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="clients.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span><?= $lang === 'ar' ? 'العملاء' : 'Clients' ?></span>
                    </a>
                </div>
                  <div class="nav-item">
    <a href="admin_parameters.php" class="nav-link">
        <i class="fas fa-cog"></i>
        <span><?= $lang === 'ar' ? 'الإعدادات' : 'Paramètres' ?></span>
    </a>
</div>
                <div class="nav-item">
                    <a href="admin_profile.php" class="nav-link active">
                        <i class="fas fa-user-cog"></i>
                        <span><?= $lang === 'ar' ? 'الملف الشخصي' : 'Profil' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span><?= $lang === 'ar' ? 'تسجيل الخروج' : 'Déconnexion' ?></span>
                    </a>
                </div>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1 class="page-title"><?= $lang === 'ar' ? 'الملف الشخصي للإداري' : 'Profil Admin' ?></h1>
            </div>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h2><?= htmlspecialchars($boutique['nom']) ?></h2>
                    <p><?= $lang === 'ar' ? 'مدير المتجر' : 'Administrateur de la boutique' ?></p>
                </div>
                
                <?php if($success): ?>
                    <div class="text-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $lang === 'ar' ? 'تم تحديث البيانات بنجاح' : 'Données mises à jour avec succès' ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($errors['general'])): ?>
                    <div class="text-danger" style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $errors['general'] ?>
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <p><strong><?= $lang === 'ar' ? 'اسم المتجر:' : 'Boutique:' ?></strong> <?= htmlspecialchars($boutique['nom']) ?></p>
                    <p><strong><?= $lang === 'ar' ? 'البريد الإلكتروني للتواصل:' : 'Email de contact:' ?></strong> <?= htmlspecialchars($boutique['email']) ?></p>
                    <p><strong><?= $lang === 'ar' ? 'تاريخ الإنشاء:' : 'Date de création:' ?></strong> <?= date('d/m/Y', strtotime($boutique['created_at'])) ?></p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="admin_email"><?= $lang === 'ar' ? 'البريد الإلكتروني للإداري' : 'Email administrateur' ?></label>
                        <input type="email" id="admin_email" name="admin_email" class="form-control" 
                               value="<?= htmlspecialchars($boutique['admin_email']) ?>" required>
                        <?php if(isset($errors['email'])): ?>
                            <span class="text-danger"><?= $errors['email'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password"><?= $lang === 'ar' ? 'كلمة المرور الحالية' : 'Mot de passe actuel' ?></label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                        <?php if(isset($errors['current_password'])): ?>
                            <span class="text-danger"><?= $errors['current_password'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password"><?= $lang === 'ar' ? 'كلمة المرور الجديدة (اختياري)' : 'Nouveau mot de passe (optionnel)' ?></label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                        <?php if(isset($errors['new_password'])): ?>
                            <span class="text-danger"><?= $errors['new_password'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><?= $lang === 'ar' ? 'تأكيد كلمة المرور الجديدة' : 'Confirmer le nouveau mot de passe' ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        <?php if(isset($errors['confirm_password'])): ?>
                            <span class="text-danger"><?= $errors['confirm_password'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i>
                        <?= $lang === 'ar' ? 'حفظ التغييرات' : 'Enregistrer les modifications' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Menu Burger
        const burgerMenu = document.querySelector('.burger-menu');
        const sidebar = document.querySelector('.sidebar');
        const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
        
        burgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('active');
            
            // Empêche le défilement de la page lorsque le menu est ouvert
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            
            // Appliquer la transformation correcte selon la direction
            if (sidebar.classList.contains('active')) {
                sidebar.style.transform = 'translateX(0)';
            } else {
                sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
            }
        });
        
        // Fermer le menu lorsqu'on clique sur un lien
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 992) {
                    burgerMenu.classList.remove('active');
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                    sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
                }
            });
        });
        
        // Fermer le menu lorsqu'on clique à l'extérieur
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && 
                !sidebar.contains(e.target) && 
                !burgerMenu.contains(e.target) && 
                sidebar.classList.contains('active')) {
                burgerMenu.classList.remove('active');
                sidebar.classList.remove('active');
                document.body.style.overflow = '';
                sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
            }
        });
    </script>
</body>
</html>