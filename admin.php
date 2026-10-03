<?php
session_start();

// Vérifier la connexion
if (!isset($_SESSION['boutique_id'])) {
    header("Location: admin_login.php");
    exit;
}

require 'config.php';
$boutique_id = $_SESSION['boutique_id'];

// Vérifier que la boutique existe
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

// Gestion de la période
$periode = $_GET['periode'] ?? 'month'; // week, month, year

$conditions = ["produits.boutique_id = ?"];
$params = [$boutique_id];

switch($periode) {
    case 'week':
        $conditions[] = "commandes.date_commande >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        break;
    case 'month':
        $conditions[] = "commandes.date_commande >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'year':
        $conditions[] = "commandes.date_commande >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
}

// Préparation de la requête des commandes
$query = "SELECT commandes.id, produits.nom AS produit, produits.image, clients.nom AS client, 
          clients.telephone, commandes.statut, commandes.date_commande
          FROM commandes
          JOIN produits ON commandes.produit_id = produits.id
          JOIN clients ON commandes.client_id = clients.id
          WHERE " . implode(' AND ', $conditions) . "
          ORDER BY commandes.date_commande DESC
          Limit 4";

$commandes = $conn->prepare($query);
$commandes->execute($params);
$commandes = $commandes->fetchAll();

// Statistiques de la boutique
$stmt = $conn->prepare("SELECT COUNT(*) FROM produits WHERE boutique_id = ?");
$stmt->execute([$boutique_id]);
$totalProduits = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT client_id) 
    FROM commandes 
    JOIN produits ON commandes.produit_id = produits.id 
    WHERE produits.boutique_id = ?
");
$stmt->execute([$boutique_id]);
$totalClients = $stmt->fetchColumn();

if (isset($_GET['preparer'])) {
    $stmt = $conn->prepare("UPDATE commandes SET statut = 'Prête à retirer' WHERE id = ?");
    $stmt->execute([$_GET['preparer']]);
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title>Admin - <?= htmlspecialchars($boutique['nom']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    padding: 0;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar de base */
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

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0,0,0,0.1);
}

.stat-card .icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.stat-card.primary .icon {
    background: rgba(58, 134, 255, 0.1);
    color: var(--primary);
}

.stat-card.success .icon {
    background: rgba(56, 176, 0, 0.1);
    color: var(--success);
}

.stat-card.warning .icon {
    background: rgba(255, 190, 11, 0.1);
    color: var(--warning);
}

.stat-card.danger .icon {
    background: rgba(239, 35, 60, 0.1);
    color: var(--danger);
}

.stat-card h3 {
    font-size: 1.8rem;
    margin-bottom: 5px;
}

.stat-card p {
    color: var(--gray);
    font-size: 0.9rem;
}

/* Orders Table */
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

.btn-warning {
    background: var(--warning);
    color: var(--dark);
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

.badge-primary {
    background: rgba(58, 134, 255, 0.1);
    color: var(--primary);
}

.badge-success {
    background: rgba(56, 176, 0, 0.1);
    color: var(--success);
}

.badge-warning {
    background: rgba(255, 190, 11, 0.1);
    color: #ff9500;
}

.actions {
    display: flex;
    gap: 10px;
}

/* Styles pour les graphiques */
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.card-footer {
    padding-top: 15px;
    border-top: 1px solid #eee;
    margin-top: 15px;
    font-size: 0.85rem;
    color: var(--gray);
    text-align: right;
}

.chart-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 0.85rem;
}

.legend-color {
    width: 15px;
    height: 15px;
    border-radius: 3px;
    margin-right: 8px;
    display: inline-block;
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

/* Sélecteur de période */
.period-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.period-selector a {
    padding: 8px 15px;
    border-radius: 4px;
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s;
}

.period-selector a.active, .period-selector a:hover {
    background: var(--primary);
    color: white;
}

/* Styles du sélecteur de langue */
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

/* Pour le mode RTL (arabe) */
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

html[dir="rtl"] .product-info {
    flex-direction: row-reverse;
}

html[dir="rtl"] .legend-color {
    margin-right: 0;
    margin-left: 8px;
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

html[dir="rtl"] .card-footer,
html[dir="rtl"] th, 
html[dir="rtl"] td {
    text-align: right;
}

.header h1 {
    margin-top: 20px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .chart-row {
        grid-template-columns: 1fr;
    }
    
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
    
    .chart-container {
        height: 250px;
    }
}

@media (max-width: 768px) {
        .logo h2{
        font-size:20px;
        margin-left:30px;
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
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .period-selector {
        flex-wrap: wrap;
    }
    
    .period-selector a {
        flex: 1;
        text-align: center;
        min-width: 80px;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    th, td {
        padding: 8px 5px;
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
    
    .actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 4px 8px;
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
    
    .chart-container {
        height: 200px;
    }
    
    .stat-card h3 {
        font-size: 1.5rem;
    }
    
    .stat-card .icon {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
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
                    <a href="admin.php" class="nav-link active">
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
                    <a href="admin_profile.php" class="nav-link">
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
                    <h1 class="page-title"><?= htmlspecialchars($boutique['nom']) ?></h1>
                    <p><?= $lang === 'ar' ? 'لوحة التحكم الإدارية' : 'Tableau de bord administratif' ?></p>
                </div>
            </div>
            
            <!-- Sélecteur de période -->
            <div class="period-selector">
                <a href="?periode=week" class="<?= $periode === 'week' ? 'active' : '' ?>">
                    <?= $lang === 'ar' ? 'أسبوع' : 'Semaine' ?>
                </a>
                <a href="?periode=month" class="<?= $periode === 'month' ? 'active' : '' ?>">
                    <?= $lang === 'ar' ? 'شهر' : 'Mois' ?>
                </a>
                <a href="?periode=year" class="<?= $periode === 'year' ? 'active' : '' ?>">
                    <?= $lang === 'ar' ? 'سنة' : 'Année' ?>
                </a>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card primary">
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3><?= count($commandes) ?></h3>
                    <p><?= $lang === 'ar' ? 'إجمالي الطلبات' : 'Commandes totales' ?></p>
                </div>
                
                <div class="stat-card success">
                    <div class="icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3><?= $totalProduits ?></h3>
                    <p><?= $lang === 'ar' ? 'المنتجات' : 'Produits' ?></p>
                </div>
                
                <div class="stat-card warning">
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?= $totalClients ?></h3>
                    <p><?= $lang === 'ar' ? 'العملاء' : 'Clients' ?></p>
                </div>
                
                <div class="stat-card success">
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3><?= count(array_filter($commandes, fn($c) => $c['statut'] === 'Prête à retirer')) ?></h3>
                    <p><?= $lang === 'ar' ? 'جاهزة للتسليم' : 'Prêtes à livrer' ?></p>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="chart-row">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><?= $lang === 'ar' ? 'توزيع الطلبات' : 'Répartition des commandes' ?></h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="ordersChart"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <span class="legend-color" style="background: rgba(56, 176, 0, 0.7)"></span>
                            <span><?= $lang === 'ar' ? 'جاهزة للاستلام' : 'Prêtes à retirer' ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color" style="background: rgba(255, 190, 11, 0.7)"></span>
                            <span><?= $lang === 'ar' ? 'في انتظار' : 'En attente' ?></span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <?= $lang === 'ar' ? 'آخر تحديث:' : 'Dernière mise à jour:' ?> <?= date('d/m/Y H:i') ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><?= $lang === 'ar' ? 'تطور الطلبات' : 'Évolution des commandes' ?></h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="timelineChart"></canvas>
                    </div>
                    <div class="card-footer">
                        <?= $lang === 'ar' ? 'الفترة:' : 'Période:' ?> 
                        <?php if(!empty($commandes)): ?>
                            <?= date('d/m/Y', strtotime(reset($commandes)['date_commande'])) ?> - <?= date('d/m/Y', strtotime(end($commandes)['date_commande'])) ?>
                        <?php else: ?>
                            <?= $lang === 'ar' ? 'لا توجد بيانات' : 'Aucune donnée' ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Dernières commandes -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?= $lang === 'ar' ? 'آخر 4 الطلبات' : 'Dernières 4 commandes' ?></h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'ar' ? 'المنتج' : 'Produit' ?></th>
                                <th><?= $lang === 'ar' ? 'العميل' : 'Client' ?></th>
                                <th><?= $lang === 'ar' ? 'الهاتف' : 'Téléphone' ?></th>
                                <th><?= $lang === 'ar' ? 'الحالة' : 'Statut' ?></th>
                                <th><?= $lang === 'ar' ? 'التاريخ' : 'Date' ?></th>
                                <th><?= $lang === 'ar' ? 'إجراءات' : 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice($commandes, 0, 5) as $commande): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="<?= htmlspecialchars($commande['image']) ?>" alt="<?= htmlspecialchars($commande['produit']) ?>" class="product-image">
                                        <span><?= htmlspecialchars($commande['produit']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($commande['client']) ?></td>
                                <td><?= htmlspecialchars($commande['telephone']) ?></td>
                                <td>
                                    <span class="status <?= $commande['statut'] === 'Prête à retirer' ? 'status-ready' : 'status-pending' ?>">
                                        <?= $commande['statut'] === 'Prête à retirer' ? 
                                            ($lang === 'ar' ? 'جاهزة' : 'Prête') : 
                                            ($lang === 'ar' ? 'في الانتظار' : 'En attente') ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if($commande['statut'] !== 'Prête à retirer'): ?>
                                            <a href="?preparer=<?= $commande['id'] ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                                <?= $lang === 'ar' ? 'تم التحضير' : 'Préparer' ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($commandes)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">
                                    <?= $lang === 'ar' ? 'لا توجد طلبات حاليا' : 'Aucune commande pour le moment' ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="commandes.php" class="btn btn-outline">
                        <i class="fas fa-list"></i>
                        <?= $lang === 'ar' ? 'عرض جميع الطلبات' : 'Voir toutes les commandes' ?>
                    </a>
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

    // Graphique circulaire (Doughnut)
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const commandes = <?= json_encode($commandes) ?>;
    
    const stats = {
        'Prête à retirer': 0,
        'En attente': 0,
    };
    
    commandes.forEach(commande => {
        stats[commande.statut] = (stats[commande.statut] || 0) + 1;
    });
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                '<?= $lang === "ar" ? "جاهزة للاستلام" : "Prêtes à retirer" ?>', 
                '<?= $lang === "ar" ? "في انتظار" : "En attente" ?>'
            ],
            datasets: [{
                data: [stats['Prête à retirer'], stats['En attente']],
                backgroundColor: [
                    'rgba(56, 176, 0, 0.7)',
                    'rgba(255, 190, 11, 0.7)'
                ],
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Graphique d'évolution (Line)
    const timelineCtx = document.getElementById('timelineChart').getContext('2d');
    const commandesParDate = {};
    
    commandes.forEach(commande => {
        const date = new Date(commande.date_commande);
        let dateKey;
        
        switch('<?= $periode ?>') {
            case 'year':
                dateKey = date.getFullYear() + '-' + (date.getMonth() + 1);
                break;
            case 'month':
                dateKey = (date.getMonth() + 1) + '-' + date.getDate();
                break;
            case 'week':
            default:
                dateKey = date.getDate() + '/' + (date.getMonth() + 1);
                break;
        }
        
        commandesParDate[dateKey] = (commandesParDate[dateKey] || 0) + 1;
    });
    
    const dates = Object.keys(commandesParDate).sort();
    const counts = dates.map(date => commandesParDate[date]);
    
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: '<?= $lang === "ar" ? "عدد الطلبات" : "Nombre de commandes" ?>',
                data: counts,
                backgroundColor: 'rgba(58, 134, 255, 0.1)',
                borderColor: 'rgba(58, 134, 255, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(58, 134, 255, 1)',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
    </script>
</body>
</html>