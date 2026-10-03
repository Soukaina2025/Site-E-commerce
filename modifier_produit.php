<?php
session_start();

// Vérifier si l'utilisateur est connecté et a une boutique
if (!isset($_SESSION['boutique_id'])) {
    header("Location: admin_login.php");
    exit;
}

$boutique_id = $_SESSION['boutique_id'];

// Gestion de la langue
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr'; // Français par défaut
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirection sans le paramètre lang pour éviter les problèmes
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit;
}

$lang = $_SESSION['lang'];

// Connexion à la base de données avec variables d'environnement
require 'config.php';

// Générer un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id = $_GET['id'] ?? null;

// Récupérer les données du produit avec vérification de la boutique
if ($id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM produits WHERE id = ? AND boutique_id = ?");
        $stmt->execute([$id, $boutique_id]);
        $produit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$produit) {
            die($lang === 'ar' ? "المنتج غير موجود أو ليس لديك صلاحية التعديل عليه" : "Produit introuvable ou vous n'avez pas les droits");
        }
    } catch (PDOException $e) {
        die($lang === 'ar' ? "خطأ في قاعدة البيانات" : "Erreur de base de données");
    }
}

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérification CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die($lang === 'ar' ? "رمز الحماية غير صالح" : "Jeton de sécurité invalide");
    }

    // Récupération et validation des données
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $prix = filter_input(INPUT_POST, 'prix', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    if (!$nom || !$prix || !$stock || !$description) {
        die($lang === 'ar' ? "بيانات غير صالحة" : "Données invalides");
    }

    // Gestion de l'image
    $imagePath = $produit['image']; // Conserver l'image actuelle par défaut
    
    if (!empty($_FILES["image"]["name"])) {
        // Validation du fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES["image"]["tmp_name"]);
        finfo_close($fileInfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            die($lang === 'ar' ? "نوع الملف غير مسموح به" : "Type de fichier non autorisé");
        }
        
        if ($_FILES["image"]["size"] > $maxSize) {
            die($lang === 'ar' ? "حجم الملف كبير جداً" : "Fichier trop volumineux");
        }
        
        // Générer un nom unique
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $imagePath = "images/" . $filename;
        
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {
            die($lang === 'ar' ? "فشل رفع الملف" : "Échec du téléchargement");
        }
        
        // Supprimer l'ancienne image si elle existe
        if (!empty($produit['image']) && file_exists($produit['image'])) {
            unlink($produit['image']);
        }
    }

    // Mise à jour en transaction
    $conn->beginTransaction();
    try {
        $stmt = $conn->prepare("UPDATE produits SET nom=?, prix=?, stock=?, description=?, image=? WHERE id=? AND boutique_id=?");
        $stmt->execute([$nom, $prix, $stock, $description, $imagePath, $id, $boutique_id]);
        
        $conn->commit();
        $_SESSION['success_message'] = $lang === 'ar' ? "تم تحديث المنتج بنجاح" : "Produit mis à jour avec succès";
        header("Location: admin_produits.php");
        exit;
    } catch (PDOException $e) {
        $conn->rollBack();
        die($lang === 'ar' ? "خطأ في تحديث المنتج" : "Erreur lors de la mise à jour");
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'تعديل المنتج' : 'Modifier Produit' ?></title>
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
        }
        
        body {
            font-family: <?= $lang === 'ar' ? "'Segoe UI', Tahoma, sans-serif" : "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" ?>;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            padding: 20px;
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: var(--primary);
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
            position: relative;
            padding-bottom: 15px;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            <?= $lang === 'ar' ? 'right: 50%' : 'left: 50%' ?>;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        input:focus,
        textarea:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(92, 170, 230, 0.2);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .image-preview {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-direction: <?= $lang === 'ar' ? 'row-reverse' : 'row' ?>;
        }
        
        .image-preview img {
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 1px solid #eee;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
            color: var(--primary);
            text-decoration: none;
        }
        
        .back-link:hover {
            transform: translateX(<?= $lang === 'ar' ? '5px' : '-5px' ?>);
            color: var(--secondary);
        }
        
        .error-message {
            color: var(--danger);
            margin: 10px 0;
            padding: 10px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <a href="admin_produits.php" class="back-link">
            <?= $lang === 'ar' ? '← العودة إلى القائمة' : '← Retour à la liste' ?>
        </a>
        
        <h1><?= $lang === 'ar' ? '✏️ تعديل المنتج' : '✏️ Modifier le produit' ?></h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label for="nom"><?= $lang === 'ar' ? 'اسم المنتج' : 'Nom du produit' ?></label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($produit['nom']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="prix"><?= $lang === 'ar' ? 'السعر (درهم)' : 'Prix (DHS)' ?></label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?= htmlspecialchars($produit['prix']) ?>" required>
            </div>

            <div class="form-group">
                <label for="stock"><?= $lang === 'ar' ? 'الكمية المتاحة' : 'Stock' ?></label>
                <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($produit['stock']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description"><?= $lang === 'ar' ? 'الوصف' : 'Description' ?></label>
                <textarea id="description" name="description" required><?= htmlspecialchars($produit['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image"><?= $lang === 'ar' ? 'صورة المنتج' : 'Image du produit' ?></label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
                <div class="image-preview">
                    <span><?= $lang === 'ar' ? 'الصورة الحالية:' : 'Image actuelle :' ?></span>
                    <?php if (!empty($produit['image'])): ?>
                        <img src="<?= htmlspecialchars($produit['image']) ?>" width="80" alt="<?= $lang === 'ar' ? 'الصورة الحالية' : 'Image actuelle' ?>">
                    <?php else: ?>
                        <span><?= $lang === 'ar' ? 'لا توجد صورة' : 'Aucune image' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="btn-container">
                <button type="submit" class="btn">
                    <?= $lang === 'ar' ? '✅ حفظ التعديلات' : '✅ Enregistrer les modifications' ?>
                </button>
            </div>
        </form>
    </div>
</body>
</html>