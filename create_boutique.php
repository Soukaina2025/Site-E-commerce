<?php
session_start();

// Gestion des langues
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'] ?? 'fr';

// Vérifier si le dossier uploads existe, sinon le créer
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();
        
        // Récupération et validation des données
        $nom = htmlspecialchars($_POST['nom']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $telephone = htmlspecialchars($_POST['telephone']);
        $adresse = htmlspecialchars($_POST['adresse']);
        $description = htmlspecialchars($_POST['description']);
        $admin_email = filter_var($_POST['admin_email'], FILTER_VALIDATE_EMAIL);
        $admin_password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        // Récupération des réseaux sociaux
$facebook = !empty($_POST['facebook']) ? filter_var($_POST['facebook'], FILTER_VALIDATE_URL) : null;
$instagram = !empty($_POST['instagram']) ? filter_var($_POST['instagram'], FILTER_VALIDATE_URL) : null;
$twitter = !empty($_POST['twitter']) ? filter_var($_POST['twitter'], FILTER_VALIDATE_URL) : null;
$pinterest = !empty($_POST['pinterest']) ? filter_var($_POST['pinterest'], FILTER_VALIDATE_URL) : null;
        
        if (!$email || !$admin_email) {
            throw new Exception($lang === 'ar' ? "بريد إلكتروني غير صالح" : "Email invalide");
        }
        
        // Validation des fichiers
        function handleUpload($fileInput, $maxSize = 2 * 1024 * 1024) {
            global $lang;
            
            if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
                return '';
            }
            
            $file = $_FILES[$fileInput];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception($lang === 'ar' ? "نوع الملف غير مسموح به" : "Type de fichier non autorisé");
            }
            
            if ($file['size'] > $maxSize) {
                throw new Exception($lang === 'ar' ? "الملف كبير جداً (الحد الأقصى 2MB)" : "Fichier trop volumineux (max 2MB)");
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $destination = 'uploads/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception($lang === 'ar' ? "خطأ في رفع الملف" : "Erreur lors de l'upload du fichier");
            }
            
            return $destination;
        }
        
        $logo_path = handleUpload('logo');
        $image_path = handleUpload('image');
        
        // Insertion en base de données
       $stmt = $conn->prepare("INSERT INTO boutiques 
                       (nom, email, telephone, adresse, description, logo, boutique_image, 
                       admin_email, admin_password, facebook, instagram, twitter, pinterest) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $nom, $email, $telephone, $adresse, $description, 
    $logo_path, $image_path, $admin_email, $admin_password,
    $facebook, $instagram, $twitter, $pinterest
]);
        
        $_SESSION['boutique_id'] = $conn->lastInsertId();
        $_SESSION['boutique_nom'] = $nom;
        
        $conn->commit();
        header("Location: admin.php");
        exit;
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $lang === 'ar' ? 'إنشاء متجر جديد' : 'Créer une nouvelle boutique' ?></title>
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
            --border-radius: 12px;
            --box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .form-container {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 600px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            margin: 20px 0;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .form-container::before {
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
            margin-bottom: 1.8rem;
            font-size: 1.75rem;
            font-weight: 600;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -0.6rem;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            font-family: inherit;
            background-color: #f9f9f9;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
            padding: 1rem;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
            background-color: white;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 2.4rem;
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .file-input-container {
            position: relative;
            margin-top: 0.5rem;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            background: var(--light-gray);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            border: 1px dashed #ccc;
        }
        
        .file-input-label:hover {
            background: #e2e6ea;
            border-color: var(--primary);
        }
        
        .file-input-label i {
            margin-right: 0.7rem;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 0.1px;
            height: 0.1px;
            overflow: hidden;
        }
        
        .file-name {
            margin-left: 0.7rem;
            color: var(--gray);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
        }
        
        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
        }
        
        button:hover {
            background: linear-gradient(to right, var(--secondary), var(--primary));
            transform: translateY(-3px);
        }
        
        button:active {
            transform: translateY(-1px);
        }
        
        .error {
            color: var(--danger);
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 0.8rem;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            font-size: 0.95rem;
        }
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.95rem;
        }
        
        .login-link:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .logo i {
            font-size: 2.5rem;
            color: var(--primary);
            background: rgba(108, 99, 255, 0.1);
            padding: 1.5rem;
            border-radius: 50%;
            border: 2px dashed var(--primary);
        }
        
        /* Sélecteur de langue */
        .language-switcher {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 10;
        }
        
        .language-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(108, 99, 255, 0.1);
            color: var(--dark);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.85rem;
        }
        
        .language-btn:hover {
            background: rgba(108, 99, 255, 0.2);
        }
        
        .language-btn .arrow {
            font-size: 0.7rem;
            transition: var(--transition);
        }
        
        .language-btn.active .arrow {
            transform: rotate(180deg);
        }
        
        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            min-width: 150px;
            display: none;
            animation: fadeIn 0.3s;
            z-index: 100;
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
            gap: 0.7rem;
            padding: 0.6rem 1rem;
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.9rem;
        }
        
        .language-option:hover {
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
        }
        
        .language-option .check {
            color: var(--primary);
            width: 1rem;
            text-align: center;
            font-size: 0.8rem;
        }
        
        /* Styles RTL pour l'arabe */
        [dir="rtl"] .input-icon {
            right: 1rem;
            left: auto;
        }
        
        [dir="rtl"] input, 
        [dir="rtl"] textarea, 
        [dir="rtl"] select {
            padding: 0.9rem 3rem 0.9rem 1rem;
        }
        
        [dir="rtl"] .file-input-label i {
            margin-right: 0;
            margin-left: 0.7rem;
        }
        
        [dir="rtl"] .file-name {
            margin-left: 0;
            margin-right: 0.7rem;
        }
        
        [dir="rtl"] .language-switcher {
            right: auto;
            left: 1.5rem;
        }
        
        [dir="rtl"] .language-dropdown {
            right: auto;
            left: 0;
        }
        
        /* Media Queries pour le responsive */
        @media (max-width: 768px) {
            .form-container {
                padding: 2rem;
            }
            
            h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .logo i {
                font-size: 2rem;
                padding: 1.2rem;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 15px;
                min-height: auto;
                height: auto;
            }
            
            .form-container {
                padding: 1.5rem;
                margin: 15px 0;
            }
            
            h2 {
                font-size: 1.3rem;
                margin-bottom: 1.2rem;
            }
            
            .form-group {
                margin-bottom: 1.2rem;
            }
            
            input, textarea, select {
                padding: 0.8rem 1rem 0.8rem 2.8rem;
                font-size: 0.9rem;
            }
            
            [dir="rtl"] input, 
            [dir="rtl"] textarea, 
            [dir="rtl"] select {
                padding: 0.8rem 2.8rem 0.8rem 1rem;
            }
            
            .input-icon {
                top: 2.2rem;
                font-size: 1rem;
                left: 0.8rem;
            }
            
            [dir="rtl"] .input-icon {
                right: 0.8rem;
            }
            
            .file-input-label {
                padding: 0.7rem 0.9rem;
            }
            
            .file-input-label i {
                font-size: 1rem;
            }
            
            button {
                padding: 0.9rem;
                font-size: 0.95rem;
            }
            
            .language-switcher {
                top: 1rem;
                right: 1rem;
            }
            
            [dir="rtl"] .language-switcher {
                left: 1rem;
            }
            
            .logo i {
                font-size: 1.8rem;
                padding: 1rem;
            }
        }
        
        @media (max-width: 400px) {
            .form-container {
                padding: 1.2rem;
            }
            
            h2 {
                font-size: 1.2rem;
            }
            
            input, textarea, select {
                padding: 0.7rem 0.9rem 0.7rem 2.5rem;
            }
            
            [dir="rtl"] input, 
            [dir="rtl"] textarea, 
            [dir="rtl"] select {
                padding: 0.7rem 2.5rem 0.7rem 0.9rem;
            }
            
            .input-icon {
                top: 2rem;
                font-size: 0.9rem;
                left: 0.7rem;
            }
            
            [dir="rtl"] .input-icon {
                right: 0.7rem;
            }
            
            .language-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .language-dropdown {
                min-width: 130px;
            }
            
            .language-option {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
        }
        .social-input-group {
    position: relative;
    margin-bottom: 0.8rem;
}

.social-input-group .input-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    left: 1rem;
    color: var(--gray);
}

.social-input-group input {
    padding-left: 3rem !important;
}

[dir="rtl"] .social-input-group .input-icon {
    left: auto;
    right: 1rem;
}

[dir="rtl"] .social-input-group input {
    padding-left: 1rem !important;
    padding-right: 3rem !important;
}
    </style>
</head>
<body>
    <div class="form-container">
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
            <i class="fas fa-store"></i>
        </div>
        
        <h2><?= $lang === 'ar' ? 'إنشاء متجر جديد' : 'Créer une nouvelle boutique' ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'اسم المتجر' : 'Nom de la boutique' ?></label>
                <i class="fas fa-store-alt input-icon"></i>
                <input type="text" name="nom" placeholder="<?= $lang === 'ar' ? 'مثال: متجري الجميل' : 'Ex: Ma Belle Boutique' ?>" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'البريد الإلكتروني للتواصل' : 'Email de contact' ?></label>
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" name="email" placeholder="contact@maboutique.com" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'الهاتف' : 'Téléphone' ?></label>
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="telephone" placeholder="06 12 34 56 78" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'العنوان' : 'Adresse' ?></label>
                <i class="fas fa-map-marker-alt input-icon"></i>
                <input type="text" name="adresse" placeholder="<?= $lang === 'ar' ? '123 شارع التجارة، المدينة' : '123 Rue du Commerce, Ville' ?>" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'الوصف' : 'Description' ?></label>
                <i class="fas fa-align-left input-icon"></i>
                <textarea name="description" placeholder="<?= $lang === 'ar' ? 'صف متجرك...' : 'Décrivez votre boutique...' ?>" required></textarea>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'شعار المتجر' : 'Logo de la boutique' ?></label>
                <div class="file-input-container">
                    <label class="file-input-label">
                        <i class="fas fa-image"></i>
                        <span class="file-name"><?= $lang === 'ar' ? 'اختر ملف...' : 'Choisir un fichier...' ?></span>
                        <input type="file" name="logo" accept="image/*" class="file-input" id="logo-input">
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'صورة المتجر' : 'Image de la boutique' ?></label>
                <div class="file-input-container">
                    <label class="file-input-label">
                        <i class="fas fa-images"></i>
                        <span class="file-name"><?= $lang === 'ar' ? 'اختر ملف...' : 'Choisir un fichier...' ?></span>
                        <input type="file" name="image" accept="image/*" class="file-input" id="image-input">
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'البريد الإلكتروني للمدير' : 'Email admin' ?></label>
                <i class="fas fa-user-shield input-icon"></i>
                <input type="email" name="admin_email" placeholder="admin@maboutique.com" required>
            </div>
            
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'كلمة مرور المدير' : 'Mot de passe admin' ?></label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="admin_password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
    <label><?= $lang === 'ar' ? 'روابط التواصل الاجتماعي' : 'Réseaux sociaux' ?></label>
    
    <div class="social-input-group">
        <i class="fab fa-facebook-f input-icon"></i>
        <input type="url" name="facebook" placeholder="<?= $lang === 'ar' ? 'رابط الفيسبوك (اختياري)' : 'Lien Facebook (optionnel)' ?>">
    </div>
    
    <div class="social-input-group">
        <i class="fab fa-instagram input-icon"></i>
        <input type="url" name="instagram" placeholder="<?= $lang === 'ar' ? 'رابط الإنستغرام (اختياري)' : 'Lien Instagram (optionnel)' ?>">
    </div>
    
    <div class="social-input-group">
        <i class="fab fa-twitter input-icon"></i>
        <input type="url" name="twitter" placeholder="<?= $lang === 'ar' ? 'رابط تويتر (اختياري)' : 'Lien Twitter (optionnel)' ?>">
    </div>
    
    <div class="social-input-group">
        <i class="fab fa-pinterest input-icon"></i>
        <input type="url" name="pinterest" placeholder="<?= $lang === 'ar' ? 'رابط بنترست (اختياري)' : 'Lien Pinterest (optionnel)' ?>">
    </div>
</div>
            
            <button type="submit">
                <i class="fas fa-plus-circle"></i> <?= $lang === 'ar' ? 'إنشاء المتجر' : 'Créer la boutique' ?>
            </button>
            
            <a href="admin_login.php" class="login-link">
                <i class="fas fa-sign-in-alt"></i> <?= $lang === 'ar' ? 'لديك حساب بالفعل؟ تسجيل الدخول' : 'Déjà un compte? Se connecter' ?>
            </a>
        </form>
    </div>

    <script>
        // Afficher le nom des fichiers sélectionnés
        document.getElementById('logo-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "<?= $lang === 'ar' ? 'اختر ملف...' : 'Choisir un fichier...' ?>";
            this.parentNode.querySelector('.file-name').textContent = fileName;
        });
        
        document.getElementById('image-input').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "<?= $lang === 'ar' ? 'اختر ملف...' : 'Choisir un fichier...' ?>";
            this.parentNode.querySelector('.file-name').textContent = fileName;
        });
        
        // Gestion du sélecteur de langue
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
    </script>
</body>
</html>