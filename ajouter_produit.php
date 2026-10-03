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

// Connexion à la base de données
require 'config.php';

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $prix = $_POST['prix'];
    $description = $_POST['description'];
    $stock = $_POST['stock'];

    // Gestion de l'upload d'image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = './uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $conn->prepare("INSERT INTO produits (nom, prix, image, description, stock,boutique_id) VALUES (?, ?, ?, ?, ?,?)");
                $stmt->execute([$nom, $prix, $dest_path, $description, $stock,$boutique_id]);

            } else {
                $error = $lang === 'ar' ? "حدث خطأ أثناء رفع الصورة" : "Erreur lors du téléchargement de l'image";
            }
        } else {
            $error = $lang === 'ar' ? "نوع الملف غير مسموح به" : "Type de fichier non autorisé";
        }
    } else {
        $error = $lang === 'ar' ? "يجب اختيار صورة للمنتج" : "Vous devez sélectionner une image";
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'إضافة منتج جديد' : 'Ajouter un produit' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        body {
            font-family: <?= $lang === 'ar' ? "'Segoe UI', Tahoma, sans-serif" : "'Segoe UI', Tahoma, sans-serif" ?>;
            background: var(--light);
            padding: 30px;
            color: var(--dark);
            line-height: 1.6;
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
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
        }
        
        input, textarea, .file-input {
            width: 95%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .file-input {
            padding: 10px;
            background: #f9f9f9;
            border: 1px dashed #ccc;
        }
        
        button {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 14px 25px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: var(--primary);
            text-decoration: none;
        }
        
        .error {
            color: var(--danger);
            background: #f8d7da;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        

    </style>
</head>
<body>

<div class="container">
    
    <div class="card">
        <a href="admin_produits.php" class="back-link">
            <?= $lang === 'ar' ? '← العودة إلى القائمة' : '← Retour à la liste' ?>
        </a>
        
        <h2><?= $lang === 'ar' ? 'إضافة منتج جديد' : 'Ajouter un produit' ?></h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom"><?= $lang === 'ar' ? 'اسم المنتج' : 'Nom du produit' ?></label>
                <input type="text" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="prix"><?= $lang === 'ar' ? 'السعر (درهم)' : 'Prix (en DHS)' ?></label>
                <input type="number" id="prix" name="prix" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="stock"><?= $lang === 'ar' ? 'الكمية المتاحة' : 'Stock' ?></label>
                <input type="number" id="stock" name="stock" required>
            </div>
            
            <div class="form-group">
                <label for="image"><?= $lang === 'ar' ? 'صورة المنتج' : 'Image du produit' ?></label>
                <input type="file" id="image" name="image" class="file-input" accept="image/*" required>
                <small><?= $lang === 'ar' ? '(JPG, PNG, GIF - الحد الأقصى 5MB)' : '(JPG, PNG, GIF - max 5MB)' ?></small>
            </div>
            
            <div class="form-group">
                <label for="description"><?= $lang === 'ar' ? 'الوصف' : 'Description' ?></label>
                <textarea id="description" name="description" rows="5" required></textarea>
            </div>

            <button type="submit">
                <?= $lang === 'ar' ? 'إضافة المنتج' : 'Ajouter le produit' ?>
            </button>
        </form>
    </div>
</div>

</body>
</html>