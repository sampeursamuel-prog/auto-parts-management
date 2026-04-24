<?php
/**
 * Auto-Parts Management System
 */
// Charger l'autoloader de Composer
require_once dirname(__DIR__) . '/vendor/autoload.php';

session_start();

// ============================================
// CONSTANTES - CORRIGÉES
// ============================================
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__) . DS);

// Détection automatique du dossier App (app ou App)
$appPath = ROOT_PATH . 'App' . DS;
if (!is_dir($appPath)) {
    $appPath = ROOT_PATH . 'app' . DS;
}
define('APP_PATH', $appPath);

define('CONFIG_PATH', APP_PATH . 'Config' . DS);
define('CONTROLLER_PATH', APP_PATH . 'Controllers' . DS);
define('MODEL_PATH', APP_PATH . 'Models' . DS);
define('VIEW_PATH', APP_PATH . 'Views' . DS);
define('HELPER_PATH', APP_PATH . 'Helpers' . DS);
define('BASE_PATH', '/auto-parts-management/public');

// ============================================
// AUTOLOADER
// ============================================
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH;
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// ============================================
// CHARGEMENT DES HELPERS
// ============================================
require_once HELPER_PATH . 'Session.php';
require_once HELPER_PATH . 'Auth.php';
require_once HELPER_PATH . 'Formatter.php';

use App\Helpers\Session;
use App\Helpers\Auth;

// ============================================
// FONCTIONS GLOBALES
// ============================================

function url($action = '') {
    return BASE_PATH . '/index.php?action=' . $action;
}

function redirect($action) {
    header("Location: " . url($action));
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true &&
           $_SESSION['user_id'] > 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        Session::setFlash('danger', 'Veuillez vous connecter pour accéder à cette page');
        redirect('login');
    }
}

// ============================================
// RÉCUPÉRATION DE L'ACTION
// ============================================
$action = $_GET['action'] ?? '';
$action = trim($action);

// DEBUG
if ($action === 'debug') {
    echo "<pre>";
    echo "Session status:\n";
    echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    echo "logged_in: " . ($_SESSION['logged_in'] ?? 'NOT SET') . "\n";
    echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
    echo "APP_PATH: " . APP_PATH . "\n";
    echo "CONTROLLER_PATH: " . CONTROLLER_PATH . "\n";
    echo "Session array:\n";
    print_r($_SESSION);
    echo "</pre>";
    echo '<a href="' . url('logout-force') . '">Cliquez ici pour forcer la déconnexion</a>';
    exit;
}

// Action pour forcer la déconnexion
if ($action === 'logout-force') {
    session_destroy();
    session_start();
    Session::setFlash('success', 'Session réinitialisée');
    redirect('login');
}

// Si aucune action n'est spécifiée
if ($action === '') {
    if (isset($_SESSION['user_id']) && !isset($_SESSION['logged_in'])) {
        session_destroy();
        session_start();
    }
    
    if (isLoggedIn()) {
        redirect('dashboard');
    } else {
        redirect('login');
    }
}

// ============================================
// LISTE DES ROUTES PUBLIQUES
// ============================================
$publicRoutes = [
    'login', 'doLogin', 'logout', 'logout-force', 'debug',
    'register', 'doRegister', 
    'forgot-password', 'doForgotPassword',
    'reset-password', 'doResetPassword'
];

// ============================================
// VÉRIFICATION AUTHENTIFICATION
// ============================================
if (!in_array($action, $publicRoutes) && !isLoggedIn()) {
    Session::setFlash('danger', 'Veuillez vous connecter pour accéder à cette page');
    redirect('login');
}

// ============================================
// ROUTER PRINCIPAL
// ============================================

try {
    switch ($action) {
        // ============================================
        // AUTHENTIFICATION
        // ============================================
        case 'login':
            if (isset($_SESSION['user_id']) && !isLoggedIn()) {
                session_destroy();
                session_start();
            }
            if (isLoggedIn()) {
                redirect('dashboard');
            }
            include VIEW_PATH . 'auth/login.php';
            break;
            
        case 'doLogin':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (isset($_SESSION['user_id'])) {
                session_destroy();
                session_start();
            }
            
            if ($username === 'superadmin' && $password === 'Admin@123') {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'Super Admin';
                $_SESSION['user_role'] = 'admin';
                $_SESSION['logged_in'] = true;
                
                Session::setFlash('success', 'Connexion réussie ! Bienvenue ' . $username);
                redirect('dashboard');
            } else {
                Session::setFlash('danger', 'Nom d\'utilisateur ou mot de passe incorrect');
                redirect('login');
            }
            break;

        case 'logout':
            session_destroy();
            session_start();
            Session::setFlash('success', 'Déconnexion réussie');
            redirect('login');
            break;
            
        // ============================================
        // DASHBOARD
        // ============================================
        case 'dashboard':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'DashboardController.php')) {
                include CONTROLLER_PATH . 'DashboardController.php';
                $controller = new App\Controllers\DashboardController();
                $controller->index();
            } else {
                $title = 'Tableau de bord';
                $content = '<div class="alert alert-danger">DashboardController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // POINT DE VENTE (POS)
        // ============================================
        case 'pos':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->pos();
            } else {
                $title = 'Point de Vente';
                $content = '<div class="alert alert-danger">SaleController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // PROCESS SALE
        // ============================================
        case 'process_sale':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->processSale();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'SaleController non trouvé']);
            }
            break;
            
        // ============================================
        // SAVE CART
        // ============================================
        case 'save_cart':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->saveCart();
            }
            break;
            
        // ============================================
        // SWITCH MAGASIN
        // ============================================
        case 'switch_magasin':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->switchMagasin();
            }
            break;
            
        // ============================================
        // SWITCH DEVISE
        // ============================================
        case 'switch_devise':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->switchDevise();
            }
            break;
            
        // ============================================
        // CLIENTS (API)
        // ============================================
        case 'search_client':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->searchClient();
            }
            break;
            
        case 'create_client':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SaleController.php')) {
                include CONTROLLER_PATH . 'SaleController.php';
                $controller = new App\Controllers\SaleController();
                $controller->createClient();
            }
            break;
            
        // ============================================
        // GESTION DES STOCKS
        // ============================================
        case 'stock':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->index();
            } else {
                $title = 'Gestion des stocks';
                $content = '<div class="alert alert-danger">StockController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'stock_entry':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->createEntry();
            } else {
                $title = 'Entrée de stock';
                $content = '<div class="alert alert-danger">StockController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'stock_adjust':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->adjust();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'stock_transfer':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->transfer();
            } else {
                $title = 'Transfert de stock';
                $content = '<div class="alert alert-danger">StockController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'stock_movements':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->movements();
            } else {
                $title = 'Mouvements de stock';
                $content = '<div class="alert alert-danger">StockController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'stock_alerts':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->alerts();
            } else {
                $title = 'Alertes stock';
                $content = '<div class="alert alert-danger">StockController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        // ============================================
        // API ROUTES POUR LE SCANNER (STOCK)
        // ============================================
        case 'api_scan_product':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->scanProduct();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'api_scan_lot':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->scanLot();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'api_generate_barcode':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->generateBarcode();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'api_get_product':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->getProductInfo();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'api_stock_stats':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->getStats();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        case 'api_search_products':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'StockController.php')) {
                include CONTROLLER_PATH . 'StockController.php';
                $controller = new App\Controllers\StockController();
                $controller->searchProducts();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'StockController non trouvé']);
            }
            break;

        // ============================================
        // GESTION DES PRODUITS
        // ============================================
        case 'products':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->index();
            } else {
                $title = 'Gestion des produits';
                $content = '<div class="alert alert-danger">ProductController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        case 'product_add':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->add();
            }
            break;
            
        case 'product_edit':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->edit();
            }
            break;
            
        case 'product_delete':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->delete();
            }
            break;
            
        case 'product_get':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->get();
            }
            break;
            
        case 'product_scan':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ProductController.php')) {
                include CONTROLLER_PATH . 'ProductController.php';
                $controller = new App\Controllers\ProductController();
                $controller->scan();
            }
            break;
            
        // ============================================
        // GESTION DES INVENTAIRES
        // ============================================
        case 'inventory':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->index();
            } else {
                $title = 'Gestion des inventaires';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'inventory_create':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->create();
            } else {
                $title = 'Créer un inventaire';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'inventory_count':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->count();
            } else {
                $title = 'Comptage inventaire';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'inventory_save_count':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->saveCount();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'InventoryController non trouvé']);
            }
            break;

        case 'inventory_validate':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->validate();
            } else {
                $title = 'Validation inventaire';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'inventory_cancel':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->cancel();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'InventoryController non trouvé']);
            }
            break;

        case 'inventory_show':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->show();
            } else {
                $title = 'Détails inventaire';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;

        case 'inventory_reports':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InventoryController.php')) {
                include CONTROLLER_PATH . 'InventoryController.php';
                $controller = new App\Controllers\InventoryController();
                $controller->reports();
            } else {
                $title = 'Rapports inventaire';
                $content = '<div class="alert alert-danger">InventoryController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // GESTION DES CLIENTS
        // ============================================
        case 'clients':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ClientController.php')) {
                include CONTROLLER_PATH . 'ClientController.php';
                $controller = new App\Controllers\ClientController();
                $controller->index();
            } else {
                $title = 'Gestion des clients';
                $content = '<div class="alert alert-danger">ClientController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // GESTION DES FACTURES
        // ============================================
        case 'invoices':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'InvoiceController.php')) {
                include CONTROLLER_PATH . 'InvoiceController.php';
                $controller = new App\Controllers\InvoiceController();
                $controller->index();
            } else {
                $title = 'Gestion des factures';
                $content = '<div class="alert alert-danger">InvoiceController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // RAPPORTS
        // ============================================
        case 'reports':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'ReportController.php')) {
                include CONTROLLER_PATH . 'ReportController.php';
                $controller = new App\Controllers\ReportController();
                $controller->index();
            } else {
                $title = 'Rapports';
                $content = '<div class="alert alert-danger">ReportController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // PARAMÈTRES
        // ============================================
        case 'settings':
            requireLogin();
            if (file_exists(CONTROLLER_PATH . 'SettingController.php')) {
                include CONTROLLER_PATH . 'SettingController.php';
                $controller = new App\Controllers\SettingController();
                $controller->index();
            } else {
                $title = 'Paramètres';
                $content = '<div class="alert alert-danger">SettingController non trouvé</div>';
                include VIEW_PATH . 'layouts/main.php';
            }
            break;
            
        // ============================================
        // PAGE PAR DÉFAUT
        // ============================================
        default:
            if (isLoggedIn()) {
                redirect('dashboard');
            } else {
                redirect('login');
            }
            break;
    }
} catch (Exception $e) {
    echo "<h1>Erreur</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
}