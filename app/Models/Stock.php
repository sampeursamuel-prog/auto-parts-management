<?php
namespace App\Models;

use App\Config\Database;

class Stock extends Model
{
    protected $table = 'produits';
    protected $primaryKey = 'id_produit';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Récupérer tous les produits avec leurs stocks
     */
    public function getAllStock($magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . (int)$magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie 
             FROM produits p 
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
             WHERE p.est_actif = 1" . $magasinFilter . " 
             ORDER BY p.stock_actuel ASC, p.nom_produit ASC"
        );
    }
    
    /**
     * Récupérer les produits avec stock bas
     */
    public function getLowStock($magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . (int)$magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie 
             FROM produits p 
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
             WHERE p.est_actif = 1 
             AND p.stock_actuel <= p.stock_minimum" . $magasinFilter . " 
             ORDER BY p.stock_actuel ASC"
        );
    }
    
    /**
     * Récupérer les produits en rupture
     */
    public function getOutOfStock($magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . (int)$magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie 
             FROM produits p 
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
             WHERE p.est_actif = 1 
             AND p.stock_actuel = 0" . $magasinFilter . " 
             ORDER BY p.nom_produit ASC"
        );
    }
    
    /**
     * Récupérer les mouvements de stock
     */
    public function getStockMovements($magasinId = null, $limit = 100)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . (int)$magasinId : "";
        $limit = (int)$limit;
        
        $sql = "SELECT m.*, p.nom_produit, p.code_barre, u.nom as user_nom, u.prenom as user_prenom
                FROM mouvements_stock m
                JOIN produits p ON m.id_produit = p.id_produit
                LEFT JOIN users u ON m.id_user = u.id_user
                WHERE p.est_actif = 1" . $magasinFilter . "
                ORDER BY m.date_mouvement DESC
                LIMIT " . $limit;
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Enregistrer un mouvement de stock
     */
    public function addMovement($data)
    {
        return $this->db->insert('mouvements_stock', [
            'id_produit' => $data['id_produit'],
            'id_user' => $data['id_user'],
            'type_mouvement' => $data['type_mouvement'],
            'quantite' => $data['quantite'],
            'stock_avant' => $data['stock_avant'],
            'stock_apres' => $data['stock_apres'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'raison' => $data['raison'] ?? null
        ]);
    }
    
    /**
     * Mettre à jour le stock d'un produit
     */
    public function updateStock($productId, $newQuantity, $userId, $type, $raison = null)
    {
        // Récupérer l'ancien stock
        $product = $this->db->fetchOne(
            "SELECT stock_actuel FROM produits WHERE id_produit = ?",
            [$productId]
        );
        
        $oldStock = $product['stock_actuel'];
        $quantity = abs($newQuantity - $oldStock);
        
        // Mettre à jour le stock
        $this->db->query(
            "UPDATE produits SET stock_actuel = ?, date_modification = NOW() WHERE id_produit = ?",
            [$newQuantity, $productId]
        );
        
        // Enregistrer le mouvement
        $this->addMovement([
            'id_produit' => $productId,
            'id_user' => $userId,
            'type_mouvement' => $type,
            'quantite' => $quantity,
            'stock_avant' => $oldStock,
            'stock_apres' => $newQuantity,
            'raison' => $raison
        ]);
        
        return true;
    }
    
    /**
     * Ajuster le stock (entrée/sortie manuelle)
     */
    public function adjustStock($productId, $quantity, $type, $userId, $raison = null)
    {
        $product = $this->db->fetchOne(
            "SELECT stock_actuel FROM produits WHERE id_produit = ?",
            [$productId]
        );
        
        $oldStock = $product['stock_actuel'];
        $newStock = $type === 'entree' ? $oldStock + $quantity : $oldStock - $quantity;
        
        if ($newStock < 0) {
            throw new \Exception("Stock insuffisant pour effectuer cette sortie");
        }
        
        $this->updateStock($productId, $newStock, $userId, $type, $raison);
        
        return $newStock;
    }
    
    /**
     * Créer une entrée de stock (réception)
     */
    public function createStockEntry($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Créer le bon d'entrée
            $entryId = $this->db->insert('entrees_stock', [
                'numero_bon_entree' => $data['numero_bon'],
                'id_fournisseur' => $data['id_fournisseur'] ?? null,
                'id_user' => $data['id_user'],
                'date_facture' => $data['date_facture'] ?? null,
                'numero_facture' => $data['numero_facture'] ?? null,
                'notes' => $data['notes'] ?? null,
                'statut' => 'validee'
            ]);
            
            // Ajouter les détails et mettre à jour les stocks
            foreach ($data['items'] as $item) {
                // Ajouter le détail
                $this->db->insert('details_entrees_stock', [
                    'id_entree' => $entryId,
                    'id_produit' => $item['id_produit'],
                    'code_barre_scanne' => $item['code_barre'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire_ht' => $item['prix_unitaire'],
                    'emplacement_stockage' => $item['emplacement'] ?? null
                ]);
                
                // Mettre à jour le stock
                $product = $this->db->fetchOne(
                    "SELECT stock_actuel FROM produits WHERE id_produit = ?",
                    [$item['id_produit']]
                );
                
                $newStock = $product['stock_actuel'] + $item['quantite'];
                
                $this->updateStock(
                    $item['id_produit'],
                    $newStock,
                    $data['id_user'],
                    'entree',
                    "Bon d'entrée N°" . $data['numero_bon']
                );
            }
            
            $this->db->commit();
            return $entryId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Transférer du stock entre magasins
     */
    public function transferStock($productId, $fromMagasinId, $toMagasinId, $quantity, $userId, $raison = null)
    {
        $this->db->beginTransaction();
        
        try {
            // Sortie du magasin source
            $fromProduct = $this->db->fetchOne(
                "SELECT stock_actuel FROM produits WHERE id_produit = ? AND id_magasin = ?",
                [$productId, $fromMagasinId]
            );
            
            if (!$fromProduct || $fromProduct['stock_actuel'] < $quantity) {
                throw new \Exception("Stock insuffisant dans le magasin source");
            }
            
            $newFromStock = $fromProduct['stock_actuel'] - $quantity;
            $this->db->query(
                "UPDATE produits SET stock_actuel = ? WHERE id_produit = ? AND id_magasin = ?",
                [$newFromStock, $productId, $fromMagasinId]
            );
            
            // Entrée dans le magasin destination
            $toProduct = $this->db->fetchOne(
                "SELECT stock_actuel, id_produit FROM produits WHERE id_produit = ? AND id_magasin = ?",
                [$productId, $toMagasinId]
            );
            
            if ($toProduct) {
                $newToStock = $toProduct['stock_actuel'] + $quantity;
                $this->db->query(
                    "UPDATE produits SET stock_actuel = ? WHERE id_produit = ? AND id_magasin = ?",
                    [$newToStock, $productId, $toMagasinId]
                );
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
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Statistiques de stock
     */
    public function getStockStats($magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . (int)$magasinId : "";
        
        $totalProducts = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM produits WHERE est_actif = 1" . $magasinFilter
        );
        
        $totalValue = $this->db->fetchOne(
            "SELECT SUM(stock_actuel * prix_achat_ht) as total FROM produits WHERE est_actif = 1" . $magasinFilter
        );
        
        $lowStock = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM produits WHERE est_actif = 1 AND stock_actuel <= stock_minimum" . $magasinFilter
        );
        
        $outOfStock = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM produits WHERE est_actif = 1 AND stock_actuel = 0" . $magasinFilter
        );
        
        return [
            'total_products' => (int)($totalProducts['total'] ?? 0),
            'total_value' => (float)($totalValue['total'] ?? 0),
            'low_stock' => (int)($lowStock['total'] ?? 0),
            'out_of_stock' => (int)($outOfStock['total'] ?? 0)
        ];
    }
}