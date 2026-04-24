<?php
namespace App\Controllers;

use App\Models\Product;
use App\Models\Lot;
use App\Models\StockMovement;
use App\Helpers\Session;
use App\Helpers\Auth;

class ScannerController
{
    private $productModel;
    private $lotModel;
    private $stockMovementModel;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->productModel = new Product();
        $this->lotModel = new Lot();
        $this->stockMovementModel = new StockMovement();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . $this->basePath . '/index.php?action=login');
            exit;
        }
    }
    
    private function requirePermission($permission)
    {
        $this->requireLogin();
        if (!Auth::hasPermission($permission)) {
            Session::setFlash('danger', 'Vous n\'avez pas les droits nécessaires');
            header('Location: ' . $this->basePath . '/index.php?action=dashboard');
            exit;
        }
    }
    
    /**
     * Page principale du scanner
     */
    public function index()
    {
        $this->requirePermission('product_read');
        
        $scanMode = $_GET['mode'] ?? 'sale'; // sale, stock_entry, inventory
        
        include dirname(__DIR__) . '/Views/scanner/index.php';
    }
    
    /**
     * Traiter un scan de code-barres
     */
    public function process()
    {
        $this->requireLogin();
        
        $barcode = $_POST['barcode'] ?? $_GET['barcode'] ?? '';
        $context = $_POST['context'] ?? $_GET['context'] ?? 'sale';
        
        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Code-barres vide']);
            exit;
        }
        
        // Détecter le type de code-barres
        $type = $this->detectBarcodeType($barcode);
        
        switch ($context) {
            case 'sale':
                $result = $this->processSaleScan($barcode);
                break;
            case 'stock_entry':
                $result = $this->processStockEntryScan($barcode);
                break;
            case 'inventory':
                $result = $this->processInventoryScan($barcode);
                break;
            case 'product_search':
                $result = $this->processProductSearch($barcode);
                break;
            default:
                $result = ['success' => false, 'message' => 'Contexte invalide'];
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    /**
     * Traiter un scan pour la vente
     */
    private function processSaleScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }
        
        if ($product['stock_actuel'] <= 0) {
            return ['success' => false, 'message' => 'Produit en rupture de stock'];
        }
        
        return [
            'success' => true,
            'type' => 'product',
            'data' => [
                'id' => $product['id_produit'],
                'barcode' => $product['code_barre'],
                'name' => $product['nom_produit'],
                'price' => $product['prix_vente_ttc'],
                'stock' => $product['stock_actuel'],
                'description' => $product['description']
            ]
        ];
    }
    
    /**
     * Traiter un scan pour l'entrée de stock
     */
    private function processStockEntryScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }
        
        return [
            'success' => true,
            'type' => 'product',
            'data' => [
                'id' => $product['id_produit'],
                'barcode' => $product['code_barre'],
                'name' => $product['nom_produit'],
                'price' => $product['prix_achat_ht'],
                'unit' => $product['unite_mesure'],
                'location' => $product['emplacement']
            ]
        ];
    }
    
    /**
     * Traiter un scan pour l'inventaire
     */
    private function processInventoryScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }
        
        return [
            'success' => true,
            'type' => 'product',
            'data' => [
                'id' => $product['id_produit'],
                'barcode' => $product['code_barre'],
                'name' => $product['nom_produit'],
                'current_stock' => $product['stock_actuel']
            ]
        ];
    }
    
    /**
     * Rechercher un produit par code-barres
     */
    private function processProductSearch($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Produit non trouvé'];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
    }
    
    /**
     * Scanner un lot (code-barres de lot)
     */
    public function scanLot()
    {
        $this->requireLogin();
        
        $barcode = $_POST['barcode'] ?? $_GET['barcode'] ?? '';
        
        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Code-barres vide']);
            exit;
        }
        
        $lot = $this->lotModel->findByNumber($barcode);
        
        if (!$lot) {
            echo json_encode(['success' => false, 'message' => 'Lot non trouvé']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'lot' => $lot
        ]);
        exit;
    }
    
    /**
     * Détecter le type de code-barres
     */
    private function detectBarcodeType($barcode)
    {
        if (preg_match('/^[0-9]{13}$/', $barcode)) {
            return 'EAN13';
        }
        
        if (preg_match('/^[A-Z0-9\-]{8,}$/', $barcode)) {
            return 'CODE128';
        }
        
        if (strlen($barcode) > 50 && strpos($barcode, '{') !== false) {
            return 'QRCODE';
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Configurer le scanner (paramètres)
     */
    public function configure()
    {
        $this->requirePermission('user_update');
        
        $scannerType = $_POST['scanner_type'] ?? 'usb';
        $scannerPort = $_POST['scanner_port'] ?? '/dev/ttyUSB0';
        
        // Sauvegarder dans les paramètres système
        $settingModel = new \App\Models\Setting();
        $settingModel->set('scanner_type', $scannerType);
        $settingModel->set('scanner_port', $scannerPort);
        
        Session::setFlash('success', 'Configuration du scanner sauvegardée');
        header('Location: ' . $this->basePath . '/index.php?action=scanner');
        exit;
    }
    
    /**
     * Obtenir le statut du scanner
     */
    public function status()
    {
        $this->requireLogin();
        
        $settingModel = new \App\Models\Setting();
        $scannerType = $settingModel->get('scanner_type');
        $scannerPort = $settingModel->get('scanner_port');
        
        echo json_encode([
            'success' => true,
            'status' => 'connected',
            'type' => $scannerType,
            'port' => $scannerPort,
            'is_configured' => !empty($scannerPort)
        ]);
        exit;
    }
    
    /**
     * Tester la connexion du scanner
     */
    public function test()
    {
        $this->requireLogin();
        
        // Simulation de test
        echo json_encode([
            'success' => true,
            'message' => 'Scanner prêt à l\'emploi',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}