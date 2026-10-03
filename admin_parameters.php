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
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr'; // Français par défaut
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'];

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et validation des données
        $nom = htmlspecialchars($_POST['nom']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $telephone = htmlspecialchars($_POST['telephone']);
        $adresse = htmlspecialchars($_POST['adresse']);
        $description = htmlspecialchars($_POST['description']);
        $facebook = !empty($_POST['facebook']) ? filter_var($_POST['facebook'], FILTER_VALIDATE_URL) : null;
        $instagram = !empty($_POST['instagram']) ? filter_var($_POST['instagram'], FILTER_VALIDATE_URL) : null;
        $twitter = !empty($_POST['twitter']) ? filter_var($_POST['twitter'], FILTER_VALIDATE_URL) : null;
        $pinterest = !empty($_POST['pinterest']) ? filter_var($_POST['pinterest'], FILTER_VALIDATE_URL) : null;
        
        if (!$email) {
            throw new Exception($lang === 'ar' ? "بريد إلكتروني غير صالح" : "Email invalide");
        }
        
        // Gestion des fichiers uploadés
        function handleUpload($fileInput, $currentValue, $maxSize = 2 * 1024 * 1024) {
            global $lang;
            
            if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
                return $currentValue;
            }
            
            if ($_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($lang === 'ar' ? "خطأ في رفع الملف" : "Erreur lors de l'upload du fichier");
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
                throw new Exception($lang === 'ar' ? "خطأ في حفظ الملف" : "Erreur lors de l'enregistrement du fichier");
            }
            
            // Supprimer l'ancien fichier s'il existe
            if ($currentValue && file_exists($currentValue)) {
                unlink($currentValue);
            }
            
            return $destination;
        }
        
        $logo_path = handleUpload('logo', $boutique['logo']);
        $image_path = handleUpload('image', $boutique['boutique_image']);
        
        // Mise à jour en base de données
        $stmt = $conn->prepare("UPDATE boutiques SET 
                              nom = ?, email = ?, telephone = ?, adresse = ?, description = ?, 
                              logo = ?, boutique_image = ?, facebook = ?, instagram = ?, 
                              twitter = ?, pinterest = ?
                              WHERE id = ?");
        
        $stmt->execute([
            $nom, $email, $telephone, $adresse, $description,
            $logo_path, $image_path, $facebook, $instagram, 
            $twitter, $pinterest, $boutique_id
        ]);
        
        // Mettre à jour la session avec le nouveau nom
        $_SESSION['boutique_nom'] = $nom;
        
        // Recharger les infos de la boutique
        $stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
        $stmt->execute([$boutique_id]);
        $boutique = $stmt->fetch();
        
        $success = $lang === 'ar' ? "تم تحديث المعلومات بنجاح" : "Informations mises à jour avec succès";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'الإعدادات' : 'Paramètres' ?> - <?= htmlspecialchars($boutique['nom']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Utilisez le même style que dans votre admin.php */
:root {
    --primary: #3a86ff;
    --secondary: #8338ec;
    --accent: #ff006e;
    --light: #f8f9fa;
    --dark: #212529;
    --success: #38b000;
    --warning: #ffbe0b;
    --danger: #ef233c;
    --gray: #6c757d;
    --bg: #f5f7fa;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--dark);
    line-height: 1.6;
    padding: 0;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
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
    left: 0;
    transform: translateX(-100%);
}

.sidebar.active {
    transform: translateX(0);
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
    border-left: 3px solid transparent;
}

.nav-link:hover, .nav-link.active {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left: 3px solid var(--accent);
}

.nav-link i {
    margin-right: 10px;
    font-size: 1.1rem;
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 30px;
    margin-left: 250px;
    width: calc(100% - 250px);
    transition: margin-left 0.3s;
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
    text-align: left;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Form Styles */
.settings-form {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark);
    text-align: left;
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
    outline: none;
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.2);
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.file-upload {
    position: relative;
    margin-bottom: 20px;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    border: 2px dashed #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.file-upload-label:hover {
    border-color: var(--primary);
    background: rgba(58, 134, 255, 0.05);
}

.file-upload-label i {
    font-size: 2rem;
    color: var(--primary);
    margin-bottom: 10px;
}

.file-upload-label span {
    color: var(--gray);
    text-align: center;
}

.file-upload-input {
    position: absolute;
    width: 0.1px;
    height: 0.1px;
    opacity: 0;
    overflow: hidden;
}

.preview-image {
    max-width: 100%;
    max-height: 200px;
    margin-top: 15px;
    border-radius: 6px;
    display: block;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 1rem;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #2a75e6;
    transform: translateY(-2px);
}

.social-input-group {
    position: relative;
    margin-bottom: 15px;
}

.social-input-group i {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    left: 15px;
    color: var(--gray);
}

.social-input-group input {
    padding-left: 45px !important;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(56, 176, 0, 0.1);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-danger {
    background: rgba(239, 35, 60, 0.1);
    color: var(--danger);
    border-left: 4px solid var(--danger);
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

.language-btn-sidebar .fa-chevron-down {
    margin-right: 0;
    margin-left: 8px;
    font-size: 0.8em;
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

/* Styles RTL (Arabe) */
html[dir="rtl"] {
    direction: rtl;
}

html[dir="rtl"] .sidebar {
    right: 0;
    left: auto;
    transform: translateX(100%);
}

html[dir="rtl"] .sidebar.active {
    transform: translateX(0);
}

html[dir="rtl"] .main-content {
    margin-right: 250px;
    margin-left: 0;
}

html[dir="rtl"] .nav-link {
    border-left: none;
    border-right: 3px solid transparent;
}

html[dir="rtl"] .nav-link:hover, 
html[dir="rtl"] .nav-link.active {
    border-left: none;
    border-right: 3px solid var(--accent);
}

html[dir="rtl"] .nav-link i {
    margin-right: 0;
    margin-left: 10px;
}

html[dir="rtl"] .burger-menu {
    right: 10px;
    left: auto;
}

html[dir="rtl"] .social-input-group i {
    left: auto;
    right: 15px;
}

html[dir="rtl"] .social-input-group input {
    padding-left: 15px !important;
    padding-right: 45px !important;
}

html[dir="rtl"] .language-btn-sidebar i:first-child {
    margin-right: 0;
    margin-left: 8px;
}

html[dir="rtl"] .language-btn-sidebar .fa-chevron-down {
    margin-left: 0;
    margin-right: 8px;
}

html[dir="rtl"] .language-option-sidebar i {
    margin-right: 0;
    margin-left: 8px;
}

html[dir="rtl"] .form-group label,
html[dir="rtl"] .file-upload-label span,
html[dir="rtl"] .header p,
html[dir="rtl"] .alert,
html[dir="rtl"] .page-title {
    text-align: right;
}



html[dir="rtl"] .user-info {
    flex-direction: row-reverse;
}

/* Responsive Design */
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
    .main-content {
        padding: 20px;
    }
    
    .settings-form {
        padding: 20px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 15px;
    }
    
    .settings-form {
        padding: 15px;
    }
    
    .form-control {
        padding: 10px 12px;
    }
    
    .btn {
        padding: 10px 15px;
    }
}

.header h1 {
    margin-top: 40px;
}

/* Correction pour l'aperçu des images */
.file-upload {
    position: relative;
}

.file-upload-label {
    text-align: center;
}

html[dir="rtl"] .file-upload-label {
    text-align: center;
}

.preview-image {
    margin-left: auto;
    margin-right: auto;
}
    </style>
</head>
<body>
    <!-- Bouton Burger -->
    <div class="burger-menu">
        <span></span>
    </div>
    
    <div class="admin-container">
        <!-- Sidebar - Même que dans admin.php -->
        <div class="sidebar">
            <div class="logo">
                <h2><?= $lang === 'ar' ? 'المتجر الإداري' : 'Boutique Admin' ?></h2>
            </div>
            
            <!-- Sélecteur de langue dans la sidebar -->
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
                    <a href="admin_parametres.php" class="nav-link active">
                        <i class="fas fa-cog"></i>
                        <span><?= $lang === 'ar' ? 'الإعدادات' : 'Paramètres' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="admin_profile.php" class="nav-link ">
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
                <div>
                    <h1 class="page-title"><?= $lang === 'ar' ? 'إعدادات المتجر' : 'Paramètres de la boutique' ?></h1>
                    <p><?= $lang === 'ar' ? 'قم بتعديل معلومات متجرك' : 'Modifiez les informations de votre boutique' ?></p>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="settings-form">
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'اسم المتجر' : 'Nom de la boutique' ?></label>
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($boutique['nom']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'البريد الإلكتروني' : 'Email' ?></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($boutique['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'الهاتف' : 'Téléphone' ?></label>
                    <input type="tel" name="telephone" class="form-control" value="<?= htmlspecialchars($boutique['telephone']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'العنوان' : 'Adresse' ?></label>
                    <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($boutique['adresse']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'الوصف' : 'Description' ?></label>
                    <textarea name="description" class="form-control" required><?= htmlspecialchars($boutique['description']) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'شعار المتجر' : 'Logo de la boutique' ?></label>
                    <div class="file-upload">
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span><?= $lang === 'ar' ? 'انقر لرفع صورة جديدة' : 'Cliquez pour uploader une nouvelle image' ?></span>
                            <input type="file" name="logo" class="file-upload-input" accept="image/*">
                        </label>
                        <?php if ($boutique['logo']): ?>
                            <img src="<?= htmlspecialchars($boutique['logo']) ?>" class="preview-image" id="logo-preview">
                        <?php else: ?>
                            <p><?= $lang === 'ar' ? 'لا يوجد شعار حاليا' : 'Aucun logo actuellement' ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'صورة المتجر' : 'Image de la boutique' ?></label>
                    <div class="file-upload">
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span><?= $lang === 'ar' ? 'انقر لرفع صورة جديدة' : 'Cliquez pour uploader une nouvelle image' ?></span>
                            <input type="file" name="image" class="file-upload-input" accept="image/*">
                        </label>
                        <?php if ($boutique['boutique_image']): ?>
                            <img src="<?= htmlspecialchars($boutique['boutique_image']) ?>" class="preview-image" id="image-preview">
                        <?php else: ?>
                            <p><?= $lang === 'ar' ? 'لا يوجد صورة حاليا' : 'Aucune image actuellement' ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><?= $lang === 'ar' ? 'روابط التواصل الاجتماعي' : 'Réseaux sociaux' ?></label>
                    
                    <div class="social-input-group">
                        <i class="fab fa-facebook-f"></i>
                        <input type="url" name="facebook" class="form-control" 
                               value="<?= htmlspecialchars($boutique['facebook'] ?? '') ?>" 
                               placeholder="<?= $lang === 'ar' ? 'رابط الفيسبوك (اختياري)' : 'Lien Facebook (optionnel)' ?>">
                    </div>
                    
                    <div class="social-input-group">
                        <i class="fab fa-instagram"></i>
                        <input type="url" name="instagram" class="form-control" 
                               value="<?= htmlspecialchars($boutique['instagram'] ?? '') ?>" 
                               placeholder="<?= $lang === 'ar' ? 'رابط الإنستغرام (اختياري)' : 'Lien Instagram (optionnel)' ?>">
                    </div>
                    
                    <div class="social-input-group">
                        <i class="fab fa-twitter"></i>
                        <input type="url" name="twitter" class="form-control" 
                               value="<?= htmlspecialchars($boutique['twitter'] ?? '') ?>" 
                               placeholder="<?= $lang === 'ar' ? 'رابط تويتر (اختياري)' : 'Lien Twitter (optionnel)' ?>">
                    </div>
                    
                    <div class="social-input-group">
                        <i class="fab fa-pinterest"></i>
                        <input type="url" name="pinterest" class="form-control" 
                               value="<?= htmlspecialchars($boutique['pinterest'] ?? '') ?>" 
                               placeholder="<?= $lang === 'ar' ? 'رابط بنترست (اختياري)' : 'Lien Pinterest (optionnel)' ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?= $lang === 'ar' ? 'حفظ التغييرات' : 'Enregistrer les modifications' ?>
                </button>
            </form>
        </div>
    </div>

    <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Menu Burger
    const burgerMenu = document.querySelector('.burger-menu');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
    
    // Initialisation de la sidebar selon la direction
    function initSidebarPosition() {
        if (window.innerWidth <= 992) {
            sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
        } else {
            sidebar.style.transform = 'translateX(0)';
        }
    }
    
    // Initialisation au chargement
    initSidebarPosition();
    
    // Gestion du clic sur le menu burger
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        sidebar.classList.toggle('active');
        
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            sidebar.style.transform = 'translateX(0)';
        } else {
            document.body.style.overflow = '';
            sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
        }
    });
    
    // Fermer le menu lorsqu'on clique sur un lien
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                burgerMenu.classList.remove('active');
                sidebar.classList.remove('active');
                document.body.style.overflow = '';
                sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
            }
        });
    });
    
    // Fermer le menu lorsqu'on clique à l'extérieur
    document.addEventListener('click', function(e) {
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

    // Gestion du redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            burgerMenu.classList.remove('active');
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
            sidebar.style.transform = 'translateX(0)';
        } else {
            if (!sidebar.classList.contains('active')) {
                sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
            }
        }
    });

    // Aperçu des images uploadées
    function previewImage(input, previewId) {
        const fileInput = input;
        const previewContainer = fileInput.closest('.file-upload');
        const existingPreview = previewContainer.querySelector('.preview-image');
        const noImageText = previewContainer.querySelector('p');
        
        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                if (existingPreview) {
                    existingPreview.src = e.target.result;
                } else {
                    if (noImageText) noImageText.style.display = 'none';
                    
                    const newPreview = document.createElement('img');
                    newPreview.id = previewId;
                    newPreview.className = 'preview-image';
                    newPreview.src = e.target.result;
                    previewContainer.appendChild(newPreview);
                }
            }
            
            reader.readAsDataURL(fileInput.files[0]);
        }
    }
    
    // Gestion des changements de fichiers
    const logoInput = document.querySelector('input[name="logo"]');
    const imageInput = document.querySelector('input[name="image"]');
    
    if (logoInput) {
        logoInput.addEventListener('change', function() {
            previewImage(this, 'logo-preview');
        });
    }
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            previewImage(this, 'image-preview');
        });
    }
});
    </script>
</body>
</html>