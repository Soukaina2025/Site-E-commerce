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

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$lang = $_SESSION['lang'];

require 'config.php';
// Modifier la requête pour ne récupérer que les produits de la boutique connectée
$stmt = $conn->prepare("SELECT * FROM produits WHERE boutique_id = ?");
$stmt->execute([$boutique_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les informations de la boutique pour l'affichage
$stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
$stmt->execute([$boutique_id]);
$boutique = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'لوحة التحكم - المنتجات' : 'Admin - Produits' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
}

/* Gestion de la direction */
html[dir="rtl"] {
    direction: rtl;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

/* ==================== */
/* SIDEBAR - Version de base (LTR) */
/* ==================== */
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

/* ==================== */
/* SIDEBAR - Version RTL */
/* ==================== */
html[dir="rtl"] .sidebar {
    right: 0;
    left: auto;
    transform: translateX(100%);
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
}

html[dir="rtl"] .sidebar.active {
    transform: translateX(0);
}

/* Éléments communs de la sidebar */
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

/* Adaptation RTL pour les liens */
html[dir="rtl"] .nav-link {
    border-left: none;
    border-right: 3px solid transparent;
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

/* ==================== */
/* MAIN CONTENT - Version de base (LTR) */
/* ==================== */
.main-content {
    flex: 1;
    padding: 30px;
    margin-left: 250px;
    width: calc(100% - 250px);
    transition: margin-left 0.3s;
}

/* ==================== */
/* MAIN CONTENT - Version RTL */
/* ==================== */
html[dir="rtl"] .main-content {
    margin-right: 250px;
    margin-left: 0;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    margin-top: 20px;
}

.page-title {
    font-size: 1.8rem;
    color: var(--dark);
    font-weight: 600;
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

/* Card Style */
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    padding: 20px;
    margin-bottom: 30px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.card-title {
    font-size: 1.3rem;
    font-weight: 600;
}

/* Table Style */
.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

html[dir="rtl"] th,
html[dir="rtl"] td {
    text-align: right;
}

th {
    background-color: #f8fafc;
    color: var(--gray);
    font-weight: 500;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

tr:hover {
    background-color: #f9f9f9;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Buttons & Actions */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-sm {
    padding: 8px 15px;
    font-size: 0.9rem;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: #2a75e6;
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-danger {
    background: var(--danger);
    color: white;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--gray);
    color: var(--gray);
}

.btn-outline:hover {
    background: #f8f9fa;
}

.actions {
    display: flex;
    gap: 10px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    text-decoration: none;
    transition: all 0.2s;
}

.edit-btn {
    background: var(--success);
    color: white;
}

.delete-btn {
    background: var(--danger);
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Search Input */
.search-input {
    padding: 10px 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
    min-width: 250px;
    transition: all 0.3s;
}

.search-input:focus {
    border-color: var(--primary);
    outline: none;
    box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
}

/* ==================== */
/* MENU BURGER - Version de base (LTR) */
/* ==================== */
.burger-menu {
    display: none;
    cursor: pointer;
    padding: 15px;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1100;
    background: rgba(255,255,255,0.9);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    align-items: center;
    justify-content: center;
}

/* ==================== */
/* MENU BURGER - Version RTL */
/* ==================== */
html[dir="rtl"] .burger-menu {
    right: 20px;
    left: auto;
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

/* ==================== */
/* SELECTEUR DE LANGUE */
/* ==================== */
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

/* Adaptations RTL pour le sélecteur de langue */
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

/* ==================== */
/* RESPONSIVE DESIGN */
/* ==================== */
@media (max-width: 1200px) {
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

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
    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        margin-top: 20px;
    }
    
    .user-info {
        margin-top: 10px;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .search-input {
        width: 100%;
        min-width: auto;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    th, td {
        padding: 10px 5px;
    }
    
    .actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .product-image {
        width: 40px;
        height: 40px;
    }
}

@media (max-width: 576px) {
    .main-content {
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .card {
        padding: 15px;
    }
    
    .action-btn {
        padding: 6px 10px;
        font-size: 0.8rem;
    }
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
                    <a href="admin_produits.php" class="nav-link active">
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
                    <h1 class="page-title">
                        <i class="fas fa-box-open"></i> <?= $lang === 'ar' ? 'إدارة المنتجات' : 'Gestion des produits' ?>
                    </h1>
                    <p><?= htmlspecialchars($boutique['nom']) ?></p>
                </div>
                <div class="user-info">
                    <a href="ajouter_produit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?= $lang === 'ar' ? 'إضافة منتج' : 'Ajouter un produit' ?>
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?= $lang === 'ar' ? 'قائمة المنتجات' : 'Liste des produits' ?></h2>
                    <div class="actions">
                        <input type="text" class="search-input" placeholder="<?= $lang === 'ar' ? 'بحث...' : 'Rechercher...' ?>">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'ar' ? 'المعرف' : 'ID' ?></th>
                                <th><?= $lang === 'ar' ? 'الاسم' : 'Nom' ?></th>
                                <th><?= $lang === 'ar' ? 'السعر' : 'Prix' ?></th>
                                <th><?= $lang === 'ar' ? 'الكمية المتاحة' : 'Stock' ?></th>
                                <th><?= $lang === 'ar' ? 'الصورة' : 'Image' ?></th>
                                <th><?= $lang === 'ar' ? 'الوصف' : 'Description' ?></th>
                                <th><?= $lang === 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produits)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;"><?= $lang === 'ar' ? 'لا توجد منتجات متاحة' : 'Aucun produit disponible' ?></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($produits as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['nom']) ?></td>
                                <td><?= number_format($p['prix'], 2) ?> DH</td>
                                <td><?= number_format($p['stock']) ?></td>
                                <td>
                                    <?php if (!empty($p['image'])): ?>
                                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" class="product-image">
                                    <?php else: ?>
                                    <span><?= $lang === 'ar' ? 'لا توجد صورة' : 'Pas d\'image' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 50, '...')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="modifier_produit.php?id=<?= $p['id'] ?>" class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i> <?= $lang === 'ar' ? 'تعديل' : 'Modifier' ?>
                                        </a>
                                        <a href="supprimer_produit.php?id=<?= $p['id'] ?>" class="action-btn delete-btn" onclick="return confirm('<?= $lang === 'ar' ? 'هل أنت متأكد من الحذف؟' : 'Êtes-vous sûr de vouloir supprimer ?' ?>')">
                                            <i class="fas fa-trash-alt"></i> <?= $lang === 'ar' ? 'حذف' : 'Supprimer' ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

    // Fonction de recherche
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Confirmation avant suppression
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const confirmMsg = <?= $lang === 'ar' ? "'هل أنت متأكد من الحذف؟'" : "'Êtes-vous sûr de vouloir supprimer ?'"; ?>;
            if (!confirm(confirmMsg)) {
                e.preventDefault();
            }
        });
    });
});
    </script>
</body>
</html>