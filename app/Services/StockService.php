<?php
namespace App\Services;

use App\Config\Database;
use App\Models\Product;
use App\Models\StockMovement;

class StockService
{
    private $db;
    private $productModel;
    private $stockMovementModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
        $this->stockMovementModel = new StockMovement();
    }
    
    /**
     * Mettre à jour le stock d'un produit
     */
    public function updateStock($productId, $quantity, $type, $userId, $reason = null)
    {
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            throw new \Exception("Produit non trouvé");
        }
        
        $oldStock = $product['stock_actuel'];
        $newStock = $type === 'entree' ? $oldStock + $quantity : $oldStock - $quantity;
        
        if ($newStock < 0) {
            throw new \Exception("Stock insuffisant");
        }
        
        // Mettre à jour le stock
        $this->db->query(
            "UPDATE produits SET stock_actuel = ?, date_modification = NOW() WHERE id_produit = ?",
            [$newStock, $productId]
        );
        
        // Enregistrer le mouvement
        $this->stockMovementModel->create([
            'id_produit' => $productId,
            'id_user' => $userId,
            'type_mouvement' => $type,
            'quantite' => $quantity,
            'stock_avant' => $oldStock,
            'stock_apres' => $newStock,
            'raison' => $reason
        ]);
        
        // Vérifier les alertes
        if ($newStock <= $product['stock_minimum']) {
            $notificationService = new NotificationService();
            if ($newStock == 0) {
                $notificationService->sendOutOfStockAlert($productId, $product['nom_produit']);
            } else {
                $notificationService->sendLowStockAlert($productId, $product['nom_produit'], $newStock);
            }
        }
        
        return $newStock;
    }
    
    /**
     * Transférer du stock entre magasins
     */
    public function transferStock($productId, $fromMagasinId, $toMagasinId, $quantity, $userId, $reason = null)
    {
        // Sortie du magasin source
        $this->updateStock($productId, $quantity, 'sortie', $userId, "Transfert vers magasin {$toMagasinId} - {$reason}");
        
        // Entrée dans le magasin destination
        // Récupérer ou créer le produit dans le magasin destination
        $targetProduct = $this->db->fetchOne(
            "SELECT id_produit FROM produits WHERE id_produit = ? AND id_magasin = ?",
            [$productId, $toMagasinId]
        );
        
        if ($targetProduct) {
            $this->updateStock($productId, $quantity, 'entree', $userId, "Transfert depuis magasin {$fromMagasinId} - {$reason}");
        } else {
            // Copier le produit vers le nouveau magasin
            $sourceProduct = $this->db->fetchOne(
                "SELECT * FROM produits WHERE id_produit = ?",
                [$productId]
            );
            
            unset($sourceProduct['id_produit']);
            $sourceProduct['id_magasin'] = $toMagasinId;
            $sourceProduct['stock_actuel'] = $quantity;
            
            $this->db->insert('produits', $sourceProduct);
        }
        
        return true;
    }
    
    /**
     * Obtenir l'historique des mouvements d'un produit
     */
    public function getProductMovements($productId, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT m.*, 
                    CONCAT(u.nom, ' ', u.prenom) as user_name
             FROM mouvements_stock m
             LEFT JOIN users u ON m.id_user = u.id_user
             WHERE m.id_produit = ?
             ORDER BY m.date_mouvement DESC
             LIMIT ?",
            [$productId, $limit]
        );
    }
    
    /**
     * Obtenir la valeur totale du stock
     */
    public function getTotalStockValue($magasinId = null)
    {
        $magasinFilter = $magasinId ? " WHERE id_magasin = " . $magasinId : "";
        
        return $this->db->fetchOne(
            "SELECT 
                SUM(stock_actuel * prix_achat_ht) as cout_achat,
                SUM(stock_actuel * prix_vente_ttc) as valeur_vente,
                SUM(stock_actuel) as total_quantite
             FROM produits" . $magasinFilter . " AND est_actif = 1"
        );
    }
    
    /**
     * Obtenir les produits avec stock bas
     */
    public function getLowStockProducts($magasinId = null, $threshold = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        $thresholdFilter = $threshold ? " AND stock_actuel <= " . $threshold : "";
        
        return $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1 AND p.stock_actuel <= p.stock_minimum" . $magasinFilter . $thresholdFilter . "
             ORDER BY p.stock_actuel ASC"
        );
    }
    
    /**
     * Réapprovisionner un produit
     */
    public function restockProduct($productId, $quantity, $userId, $supplierId = null, $notes = null)
    {
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            throw new \Exception("Produit non trouvé");
        }
        
        // Mettre à jour le stock
        $newStock = $product['stock_actuel'] + $quantity;
        
        $this->db->query(
            "UPDATE produits SET stock_actuel = ?, date_modification = NOW() WHERE id_produit = ?",
            [$newStock, $productId]
        );
        
        // Enregistrer le mouvement
        $this->stockMovementModel->create([
            'id_produit' => $productId,
            'id_user' => $userId,
            'type_mouvement' => 'entree',
            'quantite' => $quantity,
            'stock_avant' => $product['stock_actuel'],
            'stock_apres' => $newStock,
            'raison' => $notes ?: "Réapprovisionnement" . ($supplierId ? " (Fournisseur ID: {$supplierId})" : "")
        ]);
        
        return $newStock;
    }
}