<?php
namespace App\Services;

use App\Models\Product;
use App\Models\Lot;
use App\Models\StockMovement;
use App\Config\Database;

class ScannerService
{
    private $db;
    private $productModel;
    private $lotModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
        $this->lotModel = new Lot();
    }
    
    /**
     * Traiter un code-barres scanné
     * @param string $barcode Code-barres scanné
     * @param string $context Contexte (sale, stock_entry, inventory)
     * @return array Résultat du scan
     */
    public function processBarcode($barcode, $context = 'sale')
    {
        // Détecter le type de code-barres
        $type = $this->detectBarcodeType($barcode);
        
        switch ($context) {
            case 'sale':
                return $this->processSaleScan($barcode);
            case 'stock_entry':
                return $this->processStockEntryScan($barcode);
            case 'inventory':
                return $this->processInventoryScan($barcode);
            case 'lot':
                return $this->processLotScan($barcode);
            default:
                return $this->processProductSearch($barcode);
        }
    }
    
    /**
     * Scanner pour la vente
     */
    private function processSaleScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé',
                'barcode' => $barcode
            ];
        }
        
        if ($product['stock_actuel'] <= 0) {
            return [
                'success' => false,
                'message' => 'Produit en rupture de stock',
                'product' => $product
            ];
        }
        
        return [
            'success' => true,
            'type' => 'product',
            'data' => [
                'id' => $product['id_produit'],
                'barcode' => $product['code_barre'],
                'name' => $product['nom_produit'],
                'price' => $product['prix_vente_ttc'],
                'price_ht' => $product['prix_vente_ht'],
                'stock' => $product['stock_actuel'],
                'description' => $product['description'],
                'image' => $product['image_url'] ?? null
            ]
        ];
    }
    
    /**
     * Scanner pour l'entrée de stock
     */
    private function processStockEntryScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé',
                'barcode' => $barcode
            ];
        }
        
        return [
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
        ];
    }
    
    /**
     * Scanner pour l'inventaire
     */
    private function processInventoryScan($barcode)
    {
        $product = $this->productModel->findByBarcode($barcode);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Produit non trouvé',
                'barcode' => $barcode
            ];
        }
        
        return [
            'success' => true,
            'type' => 'product',
            'data' => [
                'id' => $product['id_produit'],
                'barcode' => $product['code_barre'],
                'name' => $product['nom_produit'],
                'theoretical_stock' => $product['stock_actuel'],
                'location' => $product['emplacement']
            ]
        ];
    }
    
    /**
     * Scanner pour un lot
     */
    private function processLotScan($barcode)
    {
        $lot = $this->lotModel->findByNumber($barcode);
        
        if (!$lot) {
            return [
                'success' => false,
                'message' => 'Lot non trouvé',
                'barcode' => $barcode
            ];
        }
        
        $product = $this->productModel->find($lot['id_produit']);
        
        return [
            'success' => true,
            'type' => 'lot',
            'data' => [
                'id' => $lot['id_lot'],
                'number' => $lot['numero_lot'],
                'product_id' => $lot['id_produit'],
                'product_name' => $product['nom_produit'] ?? '',
                'quantity' => $lot['quantite_actuelle'],
                'expiry_date' => $lot['date_peremption'],
                'manufacturing_date' => $lot['date_fabrication']
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
            return [
                'success' => false,
                'message' => 'Produit non trouvé',
                'barcode' => $barcode
            ];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
    }
    
    /**
     * Détecter le type de code-barres
     */
    public function detectBarcodeType($barcode)
    {
        if (preg_match('/^[0-9]{13}$/', $barcode)) {
            return 'EAN13';
        }
        
        if (preg_match('/^[0-9]{12}$/', $barcode)) {
            return 'UPC';
        }
        
        if (preg_match('/^[A-Z0-9\-]{8,}$/', $barcode)) {
            return 'CODE128';
        }
        
        if (preg_match('/^LOT-[0-9]{8}-[0-9]{4}$/', $barcode)) {
            return 'LOT';
        }
        
        if (strlen($barcode) > 50 && strpos($barcode, '{') !== false) {
            return 'QRCODE';
        }
        
        if (strpos($barcode, 'DATAMATRIX') !== false) {
            return 'DATAMATRIX';
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Valider un code-barres
     */
    public function validateBarcode($barcode)
    {
        // Vérifier EAN-13
        if (preg_match('/^[0-9]{13}$/', $barcode)) {
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += $barcode[$i] * ($i % 2 == 0 ? 1 : 3);
            }
            $check = (10 - ($sum % 10)) % 10;
            return $check == $barcode[12];
        }
        
        // Code 128 - validation basique
        if (preg_match('/^[A-Z0-9\-]{8,}$/', $barcode)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Générer un code-barres pour un produit
     */
    public function generateProductBarcode($productId)
    {
        $prefix = '590';
        $timestamp = date('YmdHis');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $barcode = $prefix . $timestamp . $random;
        
        // Vérifier l'unicité
        $check = $this->db->fetchOne(
            "SELECT id_produit FROM produits WHERE code_barre = ?",
            [substr($barcode, 0, 13)]
        );
        
        if ($check) {
            return $this->generateProductBarcode($productId);
        }
        
        return substr($barcode, 0, 13);
    }
    
    /**
     * Configurer le scanner
     */
    public function configureScanner($settings)
    {
        $settingModel = new \App\Models\Setting();
        
        foreach ($settings as $key => $value) {
            $settingModel->set($key, $value);
        }
        
        return true;
    }
    
    /**
     * Tester la connexion du scanner
     */
    public function testConnection()
    {
        // Simuler un test de connexion
        return [
            'success' => true,
            'message' => 'Scanner prêt à l\'emploi',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}