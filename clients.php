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
try {
    
    // Récupérer la liste des clients
    $stmt = $conn->prepare("
        SELECT clients.*, COUNT(commandes.id) as nombre_commandes
        FROM clients
        LEFT JOIN commandes ON clients.id = commandes.client_id
        where commandes.boutique_id=$boutique_id
        GROUP BY clients.id
        ORDER BY clients.nom ASC
    ");
    $stmt->execute();
    $clients = $stmt->fetchAll();

    $stm = $conn->prepare("SELECT nom FROM boutiques WHERE id = ?");
    $stm->execute([$boutique_id]);
    $boutique = $stm->fetch();
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Export PDF avec TCPDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_pdf'])) {
    require_once('tcpdf/tcpdf.php');
    
    // Créer un nouveau document PDF en paysage
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Paramètres du document
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Boutique Admin');
    $pdf->SetTitle($lang === 'ar' ? 'قائمة العملاء' : 'Liste des clients');
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Marges réduites
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    // Ajouter une page
    $pdf->AddPage();
    
    // Titre
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, $lang === 'ar' ? 'قائمة العملاء' : 'Liste des clients', 0, 1, 'C');
    $pdf->Ln(5); // Espacement réduit
    
    // Données du tableau
    $data = json_decode($_POST['export_pdf'], true);
    
    // Définir les largeurs de colonnes (augmentées pour le français)
    $w = $lang === 'ar' ? array(50, 60, 40, 30, 40) : array(50, 80, 50, 30, 40);
    
    // Couleurs et style
    $pdf->SetFillColor(34, 128, 207);
    $pdf->SetTextColor(255);
    $pdf->SetDrawColor(156, 200, 249);
    $pdf->SetLineWidth(0.2); // Ligne plus fine
    $pdf->SetFont('dejavusans', 'B', 10);
    
    // En-têtes
    $header = array(
        $lang === 'ar' ? 'العميل' : 'Client',
        $lang === 'ar' ? 'البريد الإلكتروني' : 'Email',
        $lang === 'ar' ? 'الهاتف' : 'Téléphone',
        $lang === 'ar' ? 'الطلبات' : 'Commandes',
        $lang === 'ar' ? 'تاريخ التسجيل' : 'Inscription'
    );
    
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Données
    $pdf->SetFillColor(224, 235, 255);
    $pdf->SetTextColor(0);
    $pdf->SetFont('dejavusans', '', 8); // Police plus petite
    
    $fill = false;
    foreach($data as $row) {
        $align = $lang === 'ar' ? 'R' : 'L';
        
        // Cellules plus compactes (hauteur réduite à 5)
        $pdf->Cell($w[0], 5, $row['nom'], 'LR', 0, $align, $fill);
        $pdf->Cell($w[1], 5, $row['email'], 'LR', 0, $align, $fill);
        $pdf->Cell($w[2], 5, $row['telephone'], 'LR', 0, $align, $fill);
        $pdf->Cell($w[3], 5, $row['commandes'], 'LR', 0, 'C', $fill);
        $pdf->Cell($w[4], 5, $row['inscription'], 'LR', 0, 'C', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
    
    // Fermeture du tableau
    $pdf->Cell(array_sum($w), 0, '', 'T');
    
    // Nom du fichier
    $filename = $lang === 'ar' ? 'العملاء_' : 'clients_';
    $filename .= date('Y-m-d') . '.pdf';
    
    // Générer le PDF
    $pdf->Output($filename, 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $lang === 'ar' ? 'لوحة التحكم - العملاء' : 'Admin - Gestion des Clients' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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

.client-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    text-transform: uppercase;
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
    

    
    .btn {
        width: 100%;
        justify-content: center;
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
                    <a href="commandes.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?= $lang === 'ar' ? 'الطلبات' : 'Commandes' ?></span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="clients.php" class="nav-link active">
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
                        <i class="fas fa-users"></i> <?= $lang === 'ar' ? 'إدارة العملاء' : 'Gestion des Clients' ?>
                    </h1>
                    <p><?= htmlspecialchars($boutique['nom']) ?></p>
                </div>
            </div>
            
            <!-- Clients Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><?= $lang === 'ar' ? 'قائمة العملاء' : 'Liste des clients' ?></h2>
                    <div class="actions">
                        <button onclick="exportToExcel()" class="btn btn-outline">
                            <i class="fas fa-file-excel"></i> <?= $lang === 'ar' ? 'إكسل' : 'Excel' ?>
                        </button>
                        <button onclick="exportToPDF()" class="btn btn-outline">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'ar' ? 'العميل' : 'Client' ?></th>
                                <th><?= $lang === 'ar' ? 'البريد الإلكتروني' : 'Email' ?></th>
                                <th><?= $lang === 'ar' ? 'الاتصال' : 'Contact' ?></th>
                                <th><?= $lang === 'ar' ? 'الطلبات' : 'Commandes' ?></th>
                                <th><?= $lang === 'ar' ? 'تاريخ التسجيل' : 'Inscription' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($client['nom']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($client['email']) ?></td>
                                <td>
                                    <?= htmlspecialchars($client['telephone']) ?><br>
                                </td>
                                <td>
                                    <span class="badge badge-primary">
                                        <?= $client['nombre_commandes'] ?> <?= $lang === 'ar' ? 'طلب' : 'commande(s)' ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($client['date_inscription'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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

    // Fonction pour exporter vers Excel
    function exportToExcel() {
        // Créer un tableau de données structuré
        const data = [
            [
                "<?= $lang === 'ar' ? 'الاسم' : 'Nom' ?>",
                "<?= $lang === 'ar' ? 'البريد الإلكتروني' : 'Email' ?>", 
                "<?= $lang === 'ar' ? 'الهاتف' : 'Téléphone' ?>",
                "<?= $lang === 'ar' ? 'عدد الطلبات' : 'Commandes' ?>",
                "<?= $lang === 'ar' ? 'تاريخ التسجيل' : 'Date inscription' ?>"
            ]
        ];

        // Récupérer les données du tableau HTML
        document.querySelectorAll('table tbody tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            data.push([
                cells[0].querySelector('strong').textContent.trim(), // Nom
                cells[1].textContent.trim(),                          // Email
                cells[2].textContent.trim(),                          // Téléphone
                cells[3].querySelector('.badge').textContent.trim(),  // Commandes (nombre seulement)
                cells[4].textContent.trim()                           // Date
            ]);
        });

        // Créer un classeur Excel
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(data);

        // Définir les largeurs de colonnes
        ws['!cols'] = [
            { wch: 25 }, // Nom
            { wch: 30 }, // Email
            { wch: 15 }, // Téléphone
            { wch: 10 }, // Commandes
            { wch: 12 }  // Date
        ];

        // Ajouter le worksheet au workbook
        XLSX.utils.book_append_sheet(wb, ws, "Clients");

        // Exporter le fichier
        const fileName = "clients_<?= date('Y-m-d') ?>.xlsx";
        XLSX.writeFile(wb, fileName);
        
        // Afficher une notification
        showNotification('<?= $lang === "ar" ? "تم تصدير البيانات إلى ملف إكسل بنجاح" : "Export Excel réussi" ?>');
    }

    // Fonction pour exporter vers PDF
    function exportToPDF() {
        // Préparer les données pour l'export PDF
        const rows = [];
        const tableRows = document.querySelectorAll('table tbody tr');
        
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            rows.push({
                nom: cells[0].querySelector('strong').textContent.trim(),
                email: cells[1].textContent.trim(),
                telephone: cells[2].textContent.trim(),
                commandes: cells[3].querySelector('.badge').textContent.trim(),
                inscription: cells[4].textContent.trim()
            });
        });

        // Envoyer les données au serveur
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'export_pdf';
        input.value = JSON.stringify(rows);
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Fonction pour afficher les notifications
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.style.display = 'block';
        notification.style.background = type === 'success' ? '#38b000' : '#ef233c';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Exposer les fonctions au scope global
    window.exportToExcel = exportToExcel;
    window.exportToPDF = exportToPDF;
});
    </script>
</body>
</html>