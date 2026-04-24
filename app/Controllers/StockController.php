<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Models\Stock;
use App\Models\Product;
use App\Services\ScannerService;
use App\Helpers\Session;
use App\Helpers\Auth;

class StockController
{
    private $db;
    private $stockModel;
    private $productModel;
    private $scannerService;
    private $currentMagasin;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->stockModel = new Stock();
        $this->productModel = new Product();
        $this->scannerService = new ScannerService();
        $magasinModel = new Magasin();
        $this->currentMagasin = $magasinModel->getCurrentMagasin();
    }
    
    /**
     * Vérifier que l'utilisateur est connecté
     */
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . $this->basePath . '/index.php?action=login');
            exit;
        }
    }
    
    /**
     * Vérifier que l'utilisateur a une permission
     */
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
     * Page principale de gestion des stocks
     */
    public function index()
    {
        $this->requirePermission('stock_view');
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        // Récupérer les produits
        $products = $this->stockModel->getAllStock($magasinId);
        
        // Récupérer les statistiques
        $stats = $this->stockModel->getStockStats($magasinId);
        
        // Récupérer les produits en alerte
        $lowStockProducts = $this->stockModel->getLowStock($magasinId);
        $outOfStockProducts = $this->stockModel->getOutOfStock($magasinId);
        
        // Récupérer les derniers mouvements
        $movements = $this->stockModel->getStockMovements($magasinId, 20);
        
        $userName = $_SESSION['user_name'] ?? 'Utilisateur';
        
        include dirname(__DIR__) . '/Views/stock/index.php';
    }
    
    /**
     * Ajuster le stock d'un produit
     */
    public function adjust()
    {
        $this->requirePermission('stock_adjust');
        
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $type = $_POST['type'] ?? 'entree';
        $raison = trim($_POST['raison'] ?? '');
        
        if (!$productId || $quantity <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Données invalides'];
            header('Location: ' . $this->basePath . '/index.php?action=stock');
            exit;
        }
        
        try {
            $this->stockModel->adjustStock(
                $productId,
                $quantity,
                $type,
                $_SESSION['user_id'],
                $raison
            );
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Stock ajusté avec succès'];
        } catch (\Exception $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $e->getMessage()];
        }
        
        header('Location: ' . $this->basePath . '/index.php?action=stock');
        exit;
    }
    
    /**
     * Créer une entrée de stock (réception) - VERSION AMÉLIORÉE AVEC SCANNER
     */
    public function createEntry()
    {
        $this->requirePermission('stock_entry');
        
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifier si c'est une requête AJAX (API)
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $this->saveStockEntryAPI();
                return;
            }
            
            // Soumission normale du formulaire
            $items = json_decode($_POST['items'] ?? '[]', true);
            
            if (empty($items)) {
                Session::setFlash('danger', 'Aucun produit à enregistrer');
                header('Location: ' . $this->basePath . '/index.php?action=stock_entry');
                exit;
            }
            
            $data = [
                'numero_bon' => 'BON-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'id_fournisseur' => !empty($_POST['fournisseur']) ? intval($_POST['fournisseur']) : null,
                'id_user' => $_SESSION['user_id'],
                'date_facture' => $_POST['date_facture'] ?? null,
                'numero_facture' => $_POST['numero_facture'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'items' => $items
            ];
            
            try {
                $entryId = $this->stockModel->createStockEntry($data);
                Session::setFlash('success', 'Entrée de stock enregistrée avec succès');
                header('Location: ' . $this->basePath . '/index.php?action=stock');
                exit;
            } catch (\Exception $e) {
                Session::setFlash('danger', $e->getMessage());
            }
        }
        
        // Récupérer les fournisseurs
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        $suppliers = $this->db->fetchAll(
            "SELECT * FROM fournisseurs WHERE est_actif = 1 ORDER BY nom_fournisseur"
        );
        
        // Récupérer les produits avec codes-barres pour la liste déroulante
        $products = $this->db->fetchAll(
            "SELECT id_produit, nom_produit, code_barre, prix_achat_ht, stock_actuel, unite_mesure 
             FROM produits 
             WHERE id_magasin = ? AND est_actif = 1 
             ORDER BY nom_produit",
            [$magasinId]
        );
        
        include dirname(__DIR__) . '/Views/stock/entry.php';
    }
    
    /**
     * API pour enregistrer une entrée de stock (AJAX)
     */
    private function saveStockEntryAPI()
    {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            return;
        }
        
        $productId = intval($input['product_id'] ?? 0);
        $quantity = intval($input['quantity'] ?? 0);
        $purchasePrice = floatval($input['purchase_price'] ?? 0);
        $supplierRef = trim($input['supplier_ref'] ?? '');
        $expiryDate = $input['expiry_date'] ?? null;
        $note = trim($input['note'] ?? '');
        
        if (!$productId || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Données invalides: produit ou quantité manquant']);
            return;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Créer l'entrée de stock
            $bonNumber = 'ENT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO entrees_stock (id_produit, quantite, prix_unitaire, numero_bon, reference_fournisseur, date_expiration, note, id_user, date_entree) 
                    VALUES (:product_id, :quantity, :price, :bon_number, :supplier_ref, :expiry_date, :note, :user_id, NOW())";
            
            $this->db->execute($sql, [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $purchasePrice,
                'bon_number' => $bonNumber,
                'supplier_ref' => $supplierRef,
                'expiry_date' => $expiryDate,
                'note' => $note,
                'user_id' => $_SESSION['user_id']
            ]);
            
            // Mettre à jour le stock du produit
            $this->db->execute(
                "UPDATE produits SET stock_actuel = stock_actuel + :quantity WHERE id_produit = :id",
                ['quantity' => $quantity, 'id' => $productId]
            );
            
            // Enregistrer le mouvement
            $this->db->execute(
                "INSERT INTO mouvements_stock (id_produit, type_mouvement, quantite, reference, note, id_user, date_mouvement) 
                 VALUES (:product_id, 'entree', :quantity, :reference, :note, :user_id, NOW())",
                [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reference' => $bonNumber,
                    'note' => $note,
                    'user_id' => $_SESSION['user_id']
                ]
            );
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Entrée de stock enregistrée avec succès',
                'bon_number' => $bonNumber
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Erreur stock entry: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }
    
    /**
     * API pour scanner un produit (entrée de stock) - VERSION AMÉLIORÉE
     */
    public function scanProduct()
    {
        $this->requireLogin();
        
        header('Content-Type: application/json');
        
        // Accepter les méthodes GET et POST
        $barcode = trim($_GET['barcode'] ?? $_POST['barcode'] ?? '');
        $context = $_GET['context'] ?? $_POST['context'] ?? 'stock_entry';
        
        if (empty($barcode)) {
            echo json_encode(['success' => false, 'message' => 'Code-barres vide']);
            exit;
        }
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        // Utiliser le ScannerService
        $result = $this->scannerService->processBarcode($barcode, $context);
        
        if ($result['success']) {
            // Ajouter des informations supplémentaires spécifiques au magasin
            if ($context === 'stock_entry') {
                // Vérifier le stock actuel dans ce magasin
                $currentStock = $this->db->fetchOne(
                    "SELECT stock_actuel FROM produits WHERE id_produit = ? AND id_magasin = ?",
                    [$result['data']['id'], $magasinId]
                );
                
                if ($currentStock) {
                    $result['data']['current_stock'] = $currentStock['stock_actuel'];
                }
            }
            
            echo json_encode($result);
        } else {
            // Essayer de chercher par ID produit (fallback)
            $product = $this->db->fetchOne(
                "SELECT id_produit, code_barre, nom_produit, prix_achat_ht, unite_mesure, emplacement, stock_actuel 
                 FROM produits 
                 WHERE (code_barre = :barcode OR id_produit = :barcode) 
                 AND id_magasin = :magasin 
                 AND est_actif = 1",
                ['barcode' => $barcode, 'magasin' => $magasinId]
            );
            
            if ($product) {
                echo json_encode([
                    'success' => true,
                    'type' => 'product',
                    'data' => [
                        'id' => $product['id_produit'],
                        'barcode' => $product['code_barre'],
                        'name' => $product['nom_produit'],
                        'current_stock' => $product['stock_actuel'],
                        'price_achat' => $product['prix_achat_ht'],
                        'unit' => $product['unite_mesure'],
                        'location' => $product['emplacement']
                    ]
                ]);
            } else {
                echo json_encode($result);
            }
        }
        exit;
    }
    
    /**
     * API pour scanner un lot
     */
    public function scanLot()
    {
        $this->requireLogin();
        
        header('Content-Type: application/json');
        
        $lotNumber = trim($_GET['lot'] ?? $_POST['lot'] ?? '');
        
        if (empty($lotNumber)) {
            echo json_encode(['success' => false, 'message' => 'Numéro de lot vide']);
            exit;
        }
        
        $result = $this->scannerService->processBarcode($lotNumber, 'lot');
        echo json_encode($result);
        exit;
    }
    
    /**
     * API pour générer un code-barres
     */
    public function generateBarcode()
    {
        $this->requirePermission('product_edit');
        
        header('Content-Type: application/json');
        
        $productId = intval($_GET['product_id'] ?? 0);
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'ID produit requis']);
            exit;
        }
        
        $barcode = $this->scannerService->generateProductBarcode($productId);
        
        if ($barcode) {
            // Mettre à jour le produit
            $this->db->execute(
                "UPDATE produits SET code_barre = :barcode WHERE id_produit = :id",
                ['barcode' => $barcode, 'id' => $productId]
            );
            
            echo json_encode(['success' => true, 'barcode' => $barcode]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la génération']);
        }
        exit;
    }
    
    /**
     * API pour obtenir les informations d'un produit par ID
     */
    public function getProductInfo()
    {
        $this->requireLogin();
        
        header('Content-Type: application/json');
        
        $productId = intval($_GET['id'] ?? 0);
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'ID produit requis']);
            exit;
        }
        
        $product = $this->db->fetchOne(
            "SELECT id_produit, code_barre, nom_produit, prix_achat_ht, prix_vente_ttc, 
                    stock_actuel, unite_mesure, emplacement, description 
             FROM produits 
             WHERE id_produit = ? AND id_magasin = ? AND est_actif = 1",
            [$productId, $magasinId]
        );
        
        if ($product) {
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        }
        exit;
    }
    
    /**
     * Afficher l'historique des mouvements
     */
    public function movements()
    {
        $this->requirePermission('stock_view');
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        $movements = $this->stockModel->getStockMovements($magasinId, 200);
        
        include dirname(__DIR__) . '/Views/stock/movements.php';
    }
    
    /**
     * Afficher les alertes stock
     */
    public function alerts()
    {
        $this->requirePermission('stock_view');
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        $lowStock = $this->stockModel->getLowStock($magasinId);
        $outOfStock = $this->stockModel->getOutOfStock($magasinId);
        
        include dirname(__DIR__) . '/Views/stock/alerts.php';
    }
    
    /**
     * Transférer du stock
     */
    public function transfer()
    {
        $this->requirePermission('stock_adjust');
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productId = intval($_POST['product_id'] ?? 0);
            $toMagasinId = intval($_POST['to_magasin'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            $raison = trim($_POST['raison'] ?? '');
            
            try {
                $this->stockModel->transferStock(
                    $productId,
                    $magasinId,
                    $toMagasinId,
                    $quantity,
                    $_SESSION['user_id'],
                    $raison
                );
                
                Session::setFlash('success', 'Transfert effectué avec succès');
                header('Location: ' . $this->basePath . '/index.php?action=stock');
                exit;
            } catch (\Exception $e) {
                Session::setFlash('danger', $e->getMessage());
            }
        }
        
        // Récupérer les produits
        $products = $this->db->fetchAll(
            "SELECT id_produit, nom_produit, code_barre, stock_actuel 
             FROM produits 
             WHERE id_magasin = ? AND est_actif = 1 AND stock_actuel > 0 
             ORDER BY nom_produit",
            [$magasinId]
        );
        
        // Récupérer les autres magasins
        $magasinModel = new Magasin();
        $otherMagasins = $magasinModel->getMagasinsByUser($_SESSION['user_id']);
        
        include dirname(__DIR__) . '/Views/stock/transfer.php';
    }
    
    /**
     * API pour obtenir les statistiques de stock
     */
    public function getStats()
    {
        $this->requirePermission('stock_view');
        
        header('Content-Type: application/json');
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        $stats = $this->stockModel->getStockStats($magasinId);
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }
    
    /**
     * API pour rechercher des produits (auto-complétion)
     */
    public function searchProducts()
    {
        $this->requireLogin();
        
        header('Content-Type: application/json');
        
        $search = trim($_GET['search'] ?? '');
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        if (strlen($search) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $products = $this->db->fetchAll(
            "SELECT id_produit, nom_produit, code_barre, prix_achat_ht, stock_actuel 
             FROM produits 
             WHERE id_magasin = ? AND est_actif = 1 
             AND (nom_produit LIKE :search OR code_barre LIKE :search)
             LIMIT 20",
            ['search' => "%$search%", 'magasin' => $magasinId]
        );
        
        echo json_encode($products);
        exit;
    }
}