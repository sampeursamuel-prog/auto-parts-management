<?php
namespace App\Models;

use App\Config\Database;

class Achat extends Model
{
    protected $table = 'achats';
    protected $primaryKey = 'id_achat';
    
    /**
     * Créer un nouvel achat
     */
    public function createAchat($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Insérer l'achat principal
            $sql = "INSERT INTO achats (numero_facture, id_fournisseur, id_magasin, date_achat, 
                    devise_achat, taux_change, montant_total_usd, montant_total_htg, statut, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'recu', ?, ?)";
            
            $this->db->query($sql, [
                $data['numero_facture'],
                $data['id_fournisseur'],
                $data['id_magasin'],
                $data['date_achat'],
                $data['devise_achat'],
                $data['taux_change'],
                $data['montant_total_usd'],
                $data['montant_total_htg'],
                $data['notes'],
                $data['created_by']
            ]);
            
            $achatId = $this->db->lastInsertId();
            
            // Insérer les détails et mettre à jour les produits
            foreach ($data['details'] as $detail) {
                // Insérer le détail
                $sqlDetail = "INSERT INTO achat_details (id_achat, id_produit, quantite, 
                              prix_unitaire_usd, prix_unitaire_htg, sous_total_usd, sous_total_htg, tva) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $this->db->query($sqlDetail, [
                    $achatId,
                    $detail['id_produit'],
                    $detail['quantite'],
                    $detail['prix_unitaire_usd'],
                    $detail['prix_unitaire_htg'],
                    $detail['sous_total_usd'],
                    $detail['sous_total_htg'],
                    $detail['tva'] ?? 0
                ]);
                
                // Mettre à jour le prix d'achat du produit (méthode FIFO ou prix moyen)
                $this->updateProductCost($detail['id_produit'], $detail['prix_unitaire_usd'], $detail['quantite'], $detail['prix_unitaire_htg']);
            }
            
            $this->db->commit();
            return $achatId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Mettre à jour le coût du produit
     */
    private function updateProductCost($productId, $newCostUSD, $quantity, $newCostHTG)
    {
        $product = $this->db->fetchOne("SELECT prix_achat_usd, stock_actuel FROM produits WHERE id_produit = ?", [$productId]);
        
        if ($product && $product['stock_actuel'] > 0) {
            // Calcul du prix moyen pondéré
            $totalValueUSD = ($product['prix_achat_usd'] * $product['stock_actuel']) + ($newCostUSD * $quantity);
            $totalQuantity = $product['stock_actuel'] + $quantity;
            $avgCostUSD = $totalValueUSD / $totalQuantity;
            
            $this->db->query(
                "UPDATE produits SET prix_achat_usd = ?, prix_achat_ht = ? WHERE id_produit = ?",
                [$avgCostUSD, $newCostHTG, $productId]
            );
        } else {
            $this->db->query(
                "UPDATE produits SET prix_achat_usd = ?, prix_achat_ht = ? WHERE id_produit = ?",
                [$newCostUSD, $newCostHTG, $productId]
            );
        }
        
        // Calculer la marge estimée
        $this->calculateProductMargin($productId);
    }
    
    /**
     * Calculer la marge d'un produit
     */
    public function calculateProductMargin($productId)
    {
        $product = $this->db->fetchOne(
            "SELECT prix_achat_usd, prix_vente_ttc, taux_change_achat FROM produits WHERE id_produit = ?",
            [$productId]
        );
        
        if ($product) {
            $coutHTG = $product['prix_achat_usd'] * $product['taux_change_achat'];
            $marge = $product['prix_vente_ttc'] - $coutHTG;
            $margePct = $coutHTG > 0 ? ($marge / $coutHTG) * 100 : 0;
            
            $this->db->query(
                "UPDATE produits SET marge_estimee = ?, marge_pourcentage = ? WHERE id_produit = ?",
                [$marge, $margePct, $productId]
            );
            
            return ['marge' => $marge, 'marge_pct' => $margePct, 'cout_htg' => $coutHTG];
        }
        
        return null;
    }
    
    /**
     * Récupérer les achats
     */
    public function getAchats($magasinId = null, $dateStart = null, $dateEnd = null)
    {
        $sql = "SELECT a.*, f.nom as fournisseur_nom, 
                CONCAT(u.nom, ' ', u.prenom) as createur
                FROM achats a
                JOIN fournisseurs f ON a.id_fournisseur = f.id_fournisseur
                JOIN users u ON a.created_by = u.id_user
                WHERE 1=1";
        $params = [];
        
        if ($magasinId) {
            $sql .= " AND a.id_magasin = ?";
            $params[] = $magasinId;
        }
        
        if ($dateStart) {
            $sql .= " AND DATE(a.date_achat) >= ?";
            $params[] = $dateStart;
        }
        
        if ($dateEnd) {
            $sql .= " AND DATE(a.date_achat) <= ?";
            $params[] = $dateEnd;
        }
        
        $sql .= " ORDER BY a.date_achat DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Récupérer les détails d'un achat
     */
    public function getAchatDetails($achatId)
    {
        return $this->db->fetchAll(
            "SELECT ad.*, p.nom_produit, p.code_barre 
             FROM achat_details ad
             JOIN produits p ON ad.id_produit = p.id_produit
             WHERE ad.id_achat = ?",
            [$achatId]
        );
    }
    
    /**
     * Récupérer les statistiques des achats
     */
    public function getStats($magasinId = null)
    {
        $sql = "SELECT 
                    COUNT(*) as total_achats,
                    SUM(montant_total_usd) as total_usd,
                    SUM(montant_total_htg) as total_htg,
                    AVG(taux_change) as taux_moyen
                FROM achats WHERE 1=1";
        $params = [];
        
        if ($magasinId) {
            $sql .= " AND id_magasin = ?";
            $params[] = $magasinId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
}