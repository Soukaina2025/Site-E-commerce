<?php
date_default_timezone_set('Africa/Casablanca');
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

if (isset($_GET['preparer'])) {
    $stmt = $conn->prepare("UPDATE commandes SET statut = 'Prête à retirer' WHERE id = ?");
    $stmt->execute([$_GET['preparer']]);
    exit; // On termine le script après la mise à jour pour la requête AJAX
}

$commandes = $conn->query("
    SELECT commandes.id, produits.nom AS produit, produits.image, clients.nom AS client, 
           clients.telephone, commandes.statut, commandes.date_commande, commandes.quantity
    FROM commandes
    JOIN produits ON commandes.produit_id = produits.id
    JOIN clients ON commandes.client_id = clients.id
    JOIN boutiques ON commandes.boutique_id = boutiques.id
    WHERE commandes.boutique_id = $boutique_id
    ORDER BY commandes.date_commande DESC
")->fetchAll();

// Récupérer les informations de la boutique pour l'affichage
$stmt = $conn->prepare("SELECT * FROM boutiques WHERE id = ?");
$stmt->execute([$boutique_id]);
$boutique = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'لوحة التحكم - الطلبات' : 'Admin - Commandes' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Pour l'export Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <!-- Pour l'export PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
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

.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

html[dir="rtl"] .product-info {
    flex-direction: row-reverse;
}

.product-image {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-ready {
    background-color: #d4edda;
    color: #155724;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}

.btn-primary {
    background: var(--primary);
    color: white;
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

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background: rgba(56, 176, 0, 0.1);
    color: var(--success);
}

.actions {
    display: flex;
    gap: 10px;
}

/* Notification */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    background: var(--success);
    color: white;
    border-radius: 5px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 1000;
    display: none;
    animation: slideIn 0.3s ease-out;
}

html[dir="rtl"] .notification {
    right: auto;
    left: 20px;
}

@keyframes slideIn {
    from { 
        transform: translateX(100%); 
        opacity: 0; 
    }
    to { 
        transform: translateX(0); 
        opacity: 1; 
    }
}

html[dir="rtl"] @keyframes slideIn {
    from { 
        transform: translateX(-100%); 
        opacity: 0; 
    }
    to { 
        transform: translateX(0); 
        opacity: 1; 
    }
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
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    th, td {
        padding: 10px 5px;
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
    
    .product-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    html[dir="rtl"] .product-info {
        align-items: flex-end;
    }
    
    .product-image {
        width: 40px;
        height: 40px;
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
                    <a href="admin_produits.php" class="nav-link">
                        <i class="fas fa-box-open"></i>
                        <span><?= $lang === 'ar' ? 'المنتجات' : 'Produits' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="commandes.php" class="nav-link active">
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
                        <i class="fas fa-shopping-cart"></i> <?= $lang === 'ar' ? 'إدارة الطلبات' : 'Gestion des commandes' ?>
                    </h1>
                    <p><?= htmlspecialchars($boutique['nom']) ?></p>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?= $lang === 'ar' ? 'قائمة الطلبات' : 'Liste des commandes' ?></h2>
                    <div class="actions">
                        <button class="btn btn-outline" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> <?= $lang === 'ar' ? 'إكسل' : 'Excel' ?>
                        </button>
                        <button class="btn btn-outline" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> <?= $lang === 'ar' ? 'PDF' : 'PDF' ?>
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'ar' ? 'المنتج' : 'Produit' ?></th>
                                <th><?= $lang === 'ar' ? 'الكمية' : 'Quantité' ?></th>
                                <th><?= $lang === 'ar' ? 'العميل' : 'Client' ?></th>
                                <th><?= $lang === 'ar' ? 'الاتصال' : 'Contact' ?></th>
                                <th><?= $lang === 'ar' ? 'التاريخ' : 'Date' ?></th>
                                <th><?= $lang === 'ar' ? 'الحالة' : 'Statut' ?></th>
                                <th><?= $lang === 'ar' ? 'الإجراءات' : 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($commandes)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;"><?= $lang === 'ar' ? 'لا توجد طلبات حاليا' : 'Aucune commande pour le moment' ?></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($commandes as $c): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?= htmlspecialchars($c['image']) ?>" alt="<?= htmlspecialchars($c['produit']) ?>" class="product-image">
                                        <span><?= htmlspecialchars($c['produit']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($c['quantity']) ?></td>
                                <td><?= htmlspecialchars($c['client']) ?></td>
                                <td><?= htmlspecialchars($c['telephone']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($c['date_commande'])) ?></td>
                                <td>
                                    <span class="status status-<?= $c['statut'] == 'En attente' ? 'pending' : 'ready' ?>">
                                        <?= $c['statut'] == 'En attente' ? ($lang === 'ar' ? 'في انتظار' : 'En attente') : ($lang === 'ar' ? 'جاهزة' : 'Prête à retirer') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($c['statut'] == 'En attente'): ?>
                                            <a href="#" onclick="preparerCommande(<?= $c['id'] ?>)" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> <?= $lang === 'ar' ? 'تحضير' : 'Préparer' ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> <?= $lang === 'ar' ? 'جاهزة' : 'Prête' ?>
                                            </span>
                                        <?php endif; ?>
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

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // Menu Burger
    const burgerMenu = document.querySelector('.burger-menu');
    const sidebar = document.querySelector('.sidebar');
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

    // Fonction pour préparer une commande
    window.preparerCommande = function(commandeId) {
        const confirmMsg = <?= $lang === 'ar' ? "'هل تريد تمييز هذا الطلب كجاهز للاستلام؟'" : "'Marquer cette commande comme prête à retirer ?'"; ?>;
        const successMsg = <?= $lang === 'ar' ? "'تم تمييز الطلب كجاهز'" : "'Commande marquée comme prête'"; ?>;
        const errorMsg = <?= $lang === 'ar' ? "'خطأ في التحديث'" : "'Erreur lors de la mise à jour'"; ?>;
        
        if(confirm(confirmMsg)) {
            fetch(`?preparer=${commandeId}`)
                .then(response => {
                    if(response.ok) {
                        const btn = document.querySelector(`a[onclick="preparerCommande(${commandeId})"]`);
                        const row = btn.closest('tr');
                        
                        // Mise à jour de l'interface
                        btn.outerHTML = `
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> <?= $lang === 'ar' ? 'جاهزة' : 'Prête' ?>
                            </span>
                        `;
                        
                        const statusCell = row.querySelector('.status');
                        statusCell.className = 'status status-ready';
                        statusCell.textContent = '<?= $lang === 'ar' ? 'جاهزة' : 'Prête à retirer' ?>';
                        
                        showNotification(successMsg);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification(errorMsg, 'error');
                });
        }
    };
    
    // Fonction pour exporter vers Excel
    window.exportToExcel = function() {
        try {
            const data = [
                [
                    <?= $lang === 'ar' ? "'المنتج'" : "'Produit'"; ?>, 
                    <?= $lang === 'ar' ? "'الكمية'" : "'Quantité'"; ?>, 
                    <?= $lang === 'ar' ? "'العميل'" : "'Client'"; ?>, 
                    <?= $lang === 'ar' ? "'الاتصال'" : "'Contact'"; ?>, 
                    <?= $lang === 'ar' ? "'التاريخ'" : "'Date'"; ?>, 
                    <?= $lang === 'ar' ? "'الحالة'" : "'Statut'"; ?>
                ]
            ];
            
            document.querySelectorAll('table tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                data.push([
                    cells[0].querySelector('.product-info span').textContent.trim(),
                    cells[1].textContent.trim(),
                    cells[2].textContent.trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.trim(),
                    cells[5].textContent.trim()
                ]);
            });
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(data);
            XLSX.utils.book_append_sheet(wb, ws, "Commandes");
            
            const fileName = <?= $lang === 'ar' ? "'الطلبات.xlsx'" : "'Commandes.xlsx'"; ?>;
            XLSX.writeFile(wb, fileName);
            
            showNotification(<?= $lang === 'ar' ? "'تم تصدير البيانات إلى ملف إكسل بنجاح'" : "'Export Excel réussi'"; ?>);
        } catch (error) {
            console.error('Erreur:', error);
            showNotification(<?= $lang === 'ar' ? "'خطأ في تصدير ملف إكسل'" : "'Erreur lors de l\'export Excel'"; ?>, 'error');
        }
    };

    // Fonction pour exporter vers PDF
    window.exportToPDF = function() {
        const rows = [];
        document.querySelectorAll('table tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            rows.push({
                produit: cells[0].querySelector('.product-info span').textContent.trim(),
                quantite: cells[1].textContent.trim(),
                client: cells[2].textContent.trim(),
                contact: cells[3].textContent.trim(),
                date: cells[4].textContent.trim(),
                statut: cells[5].textContent.trim()
            });
        });

        fetch('generate_pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                lang: '<?= $lang ?>',
                title: '<?= $lang === "ar" ? "قائمة الطلبات" : "Liste des commandes"; ?>',
                headers: [
                    '<?= $lang === "ar" ? "المنتج" : "Produit"; ?>',
                    '<?= $lang === "ar" ? "الكمية" : "Quantité"; ?>',
                    '<?= $lang === "ar" ? "العميل" : "Client"; ?>',
                    '<?= $lang === "ar" ? "الاتصال" : "Contact"; ?>',
                    '<?= $lang === "ar" ? "التاريخ" : "Date"; ?>',
                    '<?= $lang === "ar" ? "الحالة" : "Statut"; ?>'
                ],
                rows: rows
            })
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '<?= $lang === "ar" ? "الطلبات.pdf" : "Commandes.pdf"; ?>';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            showNotification('<?= $lang === "ar" ? "تم تصدير البيانات إلى ملف PDF بنجاح" : "Export PDF réussi"; ?>');
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('<?= $lang === "ar" ? "خطأ في تصدير ملف PDF" : "Erreur lors de l\'export PDF"; ?>', 'error');
        });
    };
    
    // Fonction pour afficher les notifications
    window.showNotification = function(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.style.display = 'block';
        notification.style.background = type === 'success' ? '#38b000' : '#ef233c';
        
        // Positionnement RTL/LTR
        if (isRTL) {
            notification.style.right = 'auto';
            notification.style.left = '20px';
        } else {
            notification.style.right = '20px';
            notification.style.left = 'auto';
        }
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    };
});
    </script>
</body>
</html>