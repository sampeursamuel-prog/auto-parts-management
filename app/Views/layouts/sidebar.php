<?php
// Ne pas inclure ce fichier directement
if (!defined('LAYOUT_LOADED')) {
    return;
}
?>

<style>
    /* ============================================
       SIDEBAR STYLES
    ============================================ */
    .sidebar {
        position: fixed;
        top: 80px;
        left: 0;
        width: 280px;
        height: calc(100% - 80px);
        background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
        color: white;
        transition: all 0.3s ease;
        z-index: 999;
        overflow-y: auto;
        box-shadow: 2px 0 10px rgba(0,0,0,0.2);
    }
    
    @media (max-width: 768px) {
        .sidebar {
            left: -280px;
        }
        .sidebar.active {
            left: 0;
        }
    }
    
    .sidebar-header {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: rgba(0,0,0,0.2);
    }
    
    .sidebar-logo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-bottom: 10px;
        object-fit: cover;
    }
    
    .sidebar-header h4 {
        font-size: 16px;
        margin: 10px 0 5px;
    }
    
    .sidebar-header p {
        font-size: 12px;
        opacity: 0.8;
        margin: 0;
    }
    
    .sidebar-menu {
        padding: 20px 0;
    }
    
    .sidebar-item {
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #ecf0f1;
        text-decoration: none;
        transition: all 0.3s;
        border-left: 3px solid transparent;
    }
    
    .sidebar-item:hover {
        background: rgba(255,255,255,0.1);
        border-left-color: #667eea;
        padding-left: 25px;
        color: white;
    }
    
    .sidebar-item.active {
        background: rgba(102, 126, 234, 0.2);
        border-left-color: #667eea;
    }
    
    .sidebar-item i {
        width: 24px;
        font-size: 18px;
    }
    
    .sidebar-item span {
        font-size: 14px;
        flex: 1;
    }
    
    .badge-new {
        background: #ef4444;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: auto;
    }
    
    .sidebar-divider {
        height: 1px;
        background: rgba(255,255,255,0.1);
        margin: 10px 20px;
    }
    
    .sidebar-section-title {
        padding: 10px 20px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.5);
        margin-top: 10px;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 998;
        display: none;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    @media (min-width: 769px) {
        .sidebar-overlay {
            display: none !important;
        }
    }
</style>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <?php
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $projectRoot = dirname(__DIR__, 2);
        $logoPaths = [
            $projectRoot . '/public/assets/images/logo_total_family.png',
            $projectRoot . '/assets/images/logo_total_family.png',
            $projectRoot . '/public/images/logo_total_family.png',
            $projectRoot . '/images/logo_total_family.png',
        ];
        $logoUrl = '';
        foreach ($logoPaths as $path) {
            if (file_exists($path)) {
                $relativePath = str_replace($documentRoot, '', $path);
                $relativePath = str_replace('\\', '/', $relativePath);
                $logoUrl = $relativePath;
                break;
            }
        }
        if (empty($logoUrl)) {
            $logoUrl = '/auto-parts-management/public/assets/images/logo_total_family.png';
        }
        ?>
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
        <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur'); ?></h4>
        <p><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Employé'); ?></p>
    </div>
    
    <div class="sidebar-menu">
        <?php
        $currentAction = $_GET['action'] ?? 'dashboard';
        $basePath = \BASE_PATH;
        ?>
        
        <!-- Section Principale -->
        <div class="sidebar-section-title">Navigation</div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=dashboard" class="sidebar-item <?php echo $currentAction == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard / Home</span>
        </a>
        
        <a href="<?php echo $basePath; ?>/index.php?action=pos" class="sidebar-item <?php echo $currentAction == 'pos' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Sales / POS</span>
            <?php if($currentAction == 'pos'): ?>
            <span class="badge-new">Active</span>
            <?php endif; ?>
        </a>
        
        <!-- Section Produits & Stock -->
        <div class="sidebar-section-title">Produits & Stock</div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=products" class="sidebar-item <?php echo $currentAction == 'products' ? 'active' : ''; ?>">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        
        <a href="<?php echo $basePath; ?>/index.php?action=inventory" class="sidebar-item <?php echo $currentAction == 'inventory' ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i>
            <span>Inventory</span>
        </a>
        
        <!-- Section Ventes & Facturation -->
        <div class="sidebar-section-title">Ventes & Facturation</div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=invoices" class="sidebar-item <?php echo $currentAction == 'invoices' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice"></i>
            <span>Invoices</span>
        </a>
        
        <!-- Section Gestion -->
        <div class="sidebar-section-title">Gestion</div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=reports" class="sidebar-item <?php echo $currentAction == 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        
        <a href="<?php echo $basePath; ?>/index.php?action=customers" class="sidebar-item <?php echo $currentAction == 'customers' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        
        <!-- Section Configuration -->
        <div class="sidebar-section-title">Configuration</div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=users" class="sidebar-item <?php echo $currentAction == 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <span>Gestion utilisateurs</span>
        </a>
        
        <a href="<?php echo $basePath; ?>/index.php?action=settings" class="sidebar-item <?php echo $currentAction == 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        
        <div class="sidebar-divider"></div>
        
        <a href="<?php echo $basePath; ?>/index.php?action=logout" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>