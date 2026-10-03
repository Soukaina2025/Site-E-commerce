<?php
session_start();

// Gestion des langues
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'] ?? 'fr';

require 'config.php';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Vérifier les identifiants dans la table boutiques
    $stmt = $conn->prepare("SELECT * FROM boutiques WHERE admin_email = ?");
    $stmt->execute([$email]);
    $boutique = $stmt->fetch();
    
    if ($boutique && password_verify($password, $boutique['admin_password'])) {
        $_SESSION['boutique_id'] = $boutique['id'];
        $_SESSION['boutique_nom'] = $boutique['nom'];
        header("Location: admin.php");
        exit;
    } else {
        $error = $lang === 'ar' ? "البريد الإلكتروني أو كلمة المرور غير صحيحة" : "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $lang === 'ar' ? 'تسجيل الدخول للمدير' : 'Connexion Admin' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c63ff;
            --secondary: #4d44db;
            --accent: #a29bfe;
            --light: #f8f9fa;
            --dark: #343a40;
            --success: #28a745;
            --danger: #dc3545;
            --gray: #6c757d;
            --light-gray: #e9ecef;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', 'Segoe UI', 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        h2 {
            color: var(--dark);
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 38px;
            color: var(--gray);
            font-size: 18px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }
        
        button:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-2px);
        }
        
        .error {
            color: var(--danger);
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--danger);
        }
        
        .create-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--gray);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .create-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo i {
            font-size: 40px;
            color: var(--primary);
            background: rgba(108, 99, 255, 0.1);
            padding: 20px;
            border-radius: 50%;
        }
        
        /* Style pour le bouton de retour */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
            background: rgba(108, 99, 255, 0.1);
            color: var(--dark);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .back-button:hover {
            background: rgba(108, 99, 255, 0.2);
            transform: translateX(-3px);
        }
        
        /* Style pour le sélecteur de langue */
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        
        .language-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(108, 99, 255, 0.1);
            color: var(--dark);
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            width: auto;
            margin: 0;
            box-shadow: none;
        }
        
        .language-btn:hover {
            background: rgba(108, 99, 255, 0.2);
            transform: none;
        }
        
        .language-btn .arrow {
            font-size: 12px;
            transition: transform 0.3s;
        }
        
        .language-btn.active .arrow {
            transform: rotate(180deg);
        }
        
        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px 0;
            margin-top: 5px;
            min-width: 150px;
            display: none;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .language-dropdown.show {
            display: block;
        }
        
        .language-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .language-option:hover {
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
        }
        
        .language-option .check {
            color: var(--primary);
            width: 16px;
            text-align: center;
        }
        
        /* Styles RTL pour l'arabe */
        [dir="rtl"] .input-icon {
            right: 15px;
            left: auto;
        }
        
        [dir="rtl"] input {
            padding: 14px 45px 14px 15px;
        }
        
        [dir="rtl"] .language-switcher {
            right: auto;
            left: 20px;
        }
        
        [dir="rtl"] .back-button {
            left: auto;
            right: 20px;
        }
        
        [dir="rtl"] h2::after {
            left: auto;
            right: 50%;
            transform: translateX(50%);
        }
        
        [dir="rtl"] .error {
            border-left: none;
            border-right: 4px solid var(--danger);
        }
        
        [dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .login-container {
                padding: 30px 20px;
                box-shadow: none;
                border: 1px solid #eee;
            }
            
            .login-container:hover {
                transform: none;
            }
            
            h2 {
                font-size: 24px;
                margin-bottom: 25px;
            }
            
            input {
                padding: 12px 15px 12px 45px;
            }
            
            [dir="rtl"] input {
                padding: 12px 45px 12px 15px;
            }
            
            button {
                padding: 12px;
            }
            
            .back-button, .language-switcher {
                top: 10px;
            }
            
            .back-button {
                left: 10px;
                width: 35px;
                height: 35px;
            }
            
            [dir="rtl"] .back-button {
                left: auto;
                right: 10px;
            }
            
            .language-btn {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .logo i {
                font-size: 35px;
                padding: 15px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .input-icon {
                top: 34px;
                font-size: 16px;
            }
            
            .error {
                padding: 10px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 25px 15px;
                width: 95%;
            }
            
            h2 {
                font-size: 22px;
            }
            
            .language-btn span.arrow {
                display: none;
            }
            
            .create-link {
                font-size: 14px;
            }
            
            @media (max-height: 600px) {
                body {
                    align-items: flex-start;
                    padding-top: 20px;
                }
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Bouton de retour -->
        <a href="acceuil.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <!-- Sélecteur de langue -->
        <div class="language-switcher">
            <button class="language-btn" id="languageBtn" onclick="toggleLanguageDropdown()">
                <i class="fas fa-globe"></i>
                <?= $lang === 'ar' ? 'العربية' : 'Français' ?>
                <span class="arrow">▼</span>
            </button>
            <div class="language-dropdown" id="languageDropdown">
                <a href="?lang=fr" class="language-option">
                    <span class="check">
                        <?php if($lang === 'fr'): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </span>
                    Français
                </a>
                <a href="?lang=ar" class="language-option">
                    <span class="check">
                        <?php if($lang === 'ar'): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </span>
                    العربية
                </a>
            </div>
        </div>
        
        <div class="logo">
            <i class="fas fa-store-alt"></i>
        </div>
        
        <h2><?= $lang === 'ar' ? 'تسجيل الدخول للمدير' : 'Connexion Admin' ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'البريد الإلكتروني للمدير' : 'Email admin' ?></label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="<?= $lang === 'ar' ? 'بريدك@الإلكتروني' : 'votre@email.com' ?>" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'كلمة المرور' : 'Mot de passe' ?></label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="<?= $lang === 'ar' ? '••••••••' : '••••••••' ?>" required>
            </div>
            
            <button type="submit" name="login">
                <i class="fas fa-sign-in-alt"></i> <?= $lang === 'ar' ? 'تسجيل الدخول' : 'Se connecter' ?>
            </button>
            
            <a href="create_boutique.php" class="create-link">
                <i class="fas fa-plus-circle"></i> <?= $lang === 'ar' ? 'إنشاء متجر جديد' : 'Créer une nouvelle boutique' ?>
            </a>
        </form>
    </div>

    <script>
        function toggleLanguageDropdown() {
            const dropdown = document.getElementById('languageDropdown');
            const btn = document.getElementById('languageBtn');
            dropdown.classList.toggle('show');
            btn.classList.toggle('active');
        }

        // Fermer le dropdown si on clique ailleurs
        window.onclick = function(event) {
            if (!event.target.matches('.language-btn') && !event.target.closest('.language-switcher')) {
                const dropdown = document.getElementById('languageDropdown');
                const btn = document.getElementById('languageBtn');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                    btn.classList.remove('active');
                }
            }
        }

        // Gestion du redimensionnement de la fenêtre
        function handleResize() {
            const dropdown = document.getElementById('languageDropdown');
            const btn = document.getElementById('languageBtn');
            if (window.innerWidth > 768) {
                // Sur desktop, ferme le dropdown si ouvert
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                    btn.classList.remove('active');
                }
            }
        }

        // Écouteur d'événement pour le redimensionnement
        window.addEventListener('resize', handleResize);

        // Fermer le dropdown au clic sur une option (pour mobile)
        document.querySelectorAll('.language-option').forEach(option => {
            option.addEventListener('click', () => {
                const dropdown = document.getElementById('languageDropdown');
                const btn = document.getElementById('languageBtn');
                dropdown.classList.remove('show');
                btn.classList.remove('active');
            });
        });
    </script>
</body>
</html>