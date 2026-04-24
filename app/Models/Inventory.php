<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Inventory
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Récupérer la liste de tous les inventaires
     */
    public function getInventories($magasinId = null, $limit = null, $offset = 0)
    {
        try {
            $sql = "SELECT i.*, 
                           m.nom_magasin,
                           u.nom as user_name,
                           uv.nom as validateur_name,
                           COUNT(DISTINCT di.id_produit) as produits_comptes,
                           (SELECT COUNT(*) FROM produits WHERE id_magasin = i.id_magasin AND est_actif = 1) as total_produits
                    FROM inventaire i
                    LEFT JOIN magasins m ON i.id_magasin = m.id_magasin
                    LEFT JOIN users u ON i.id_user = u.id
                    LEFT JOIN users uv ON i.id_user_validation = uv.id
                    LEFT JOIN detail_inventaire di ON i.id_inventaire = di.id_inventaire";
            
            $params = [];
            
            if ($magasinId) {
                $sql .= " WHERE i.id_magasin = ?";
                $params[] = $magasinId;
            }
            
            $sql .= " GROUP BY i.id_inventaire ORDER BY i.date_debut DESC";
            
            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
            }
            
            $inventories = $this->db->fetchAll($sql, $params);
            
            if (!$inventories) {
                return [];
            }
            
            foreach ($inventories as &$inventory) {
                $total = $inventory['total_produits'] ?? 1;
                $comptes = $inventory['produits_comptes'] ?? 0;
                $inventory['progress'] = $total > 0 ? round(($comptes / $total) * 100) : 0;
                
                switch ($inventory['statut']) {
                    case 'en_cours':
                        $inventory['status_class'] = 'warning';
                        $inventory['status_text'] = 'En cours';
                        break;
                    case 'valide':
                        $inventory['status_class'] = 'success';
                        $inventory['status_text'] = 'Validé';
                        break;
                    case 'annule':
                        $inventory['status_class'] = 'danger';
                        $inventory['status_text'] = 'Annulé';
                        break;
                    default:
                        $inventory['status_class'] = 'secondary';
                        $inventory['status_text'] = $inventory['statut'] ?? 'Planifié';
                }
            }
            
            return $inventories;
            
        } catch (\Exception $e) {
            error_log("Erreur getInventories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les statistiques des inventaires
     */
    public function getStats($magasinId = null)
    {
        try {
            $stats = [
                'total_inventories' => 0,
                'planifies' => 0,
                'en_cours' => 0,
                'valides' => 0,
                'annules' => 0,
                'total_corrections' => 0,
                'total_products_counted' => 0,
                'average_progress' => 0
            ];
            
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN statut = 'planifie' THEN 1 ELSE 0 END) as planifies,
                        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                        SUM(CASE WHEN statut = 'valide' THEN 1 ELSE 0 END) as valides,
                        SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as annules
                    FROM inventaire";
            
            $params = [];
            
            if ($magasinId) {
                $sql .= " WHERE id_magasin = ?";
                $params[] = $magasinId;
            }
            
            $result = $this->db->fetchOne($sql, $params);
            
            if ($result) {
                $stats['total_inventories'] = intval($result['total']);
                $stats['planifies'] = intval($result['planifies']);
                $stats['en_cours'] = intval($result['en_cours']);
                $stats['valides'] = intval($result['valides']);
                $stats['annules'] = intval($result['annules']);
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            error_log("Erreur getStats: " . $e->getMessage());
            return $stats;
        }
    }
    
    /**
     * Créer un inventaire
     */
    public function createInventory($magasinId, $userId, $type = 'complet', $notes = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Générer un numéro d'inventaire unique
            $numero = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Vérifier l'unicité
            $check = $this->db->fetchOne("SELECT id_inventaire FROM inventaire WHERE numero_inventaire = ?", [$numero]);
            while ($check) {
                $numero = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $check = $this->db->fetchOne("SELECT id_inventaire FROM inventaire WHERE numero_inventaire = ?", [$numero]);
            }
            
            // Annuler les inventaires en cours pour ce magasin
            $sql = "UPDATE inventaire SET statut = 'annule', date_fin = NOW() 
                    WHERE id_magasin = ? AND statut = 'en_cours'";
            $this->db->query($sql, [$magasinId]);
            
            // Créer le nouvel inventaire
            $sql = "INSERT INTO inventaire (numero_inventaire, id_user, id_magasin, date_debut, type_inventaire, statut, notes) 
                    VALUES (?, ?, ?, NOW(), ?, 'en_cours', ?)";
            $result = $this->db->query($sql, [$numero, $userId, $magasinId, $type, $notes]);
            
            $inventoryId = $this->db->lastInsertId();
            
            if (!$inventoryId) {
                throw new \Exception("Impossible de créer l'inventaire");
            }
            
            // Créer les lignes de détail pour tous les produits actifs du magasin
            $sql = "INSERT INTO detail_inventaire (id_inventaire, id_produit, id_lot, quantite_theorique, quantite_comptee, id_user_comptage, date_comptage, est_corrige)
                    SELECT ?, p.id_produit, NULL, p.stock_actuel, 0, ?, NOW(), 0
                    FROM produits p
                    WHERE p.id_magasin = ? AND p.est_actif = 1";
            $this->db->query($sql, [$inventoryId, $userId, $magasinId]);
            
            // Archiver dans inventaire_sessions
            $sql = "INSERT INTO inventaire_sessions (reference, id_magasin, id_user, type, status, notes, date_debut) 
                    VALUES (?, ?, ?, ?, 'en_cours', ?, NOW())";
            $this->db->query($sql, [$numero, $magasinId, $userId, $type, $notes]);
            
            $this->db->commit();
            
            return $inventoryId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Erreur createInventory: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtenir les détails d'un inventaire
     */
    public function getInventoryDetails($inventoryId)
    {
        try {
            // Vérifier d'abord que l'inventaire existe
            $sql = "SELECT i.*, m.nom_magasin, u.nom as user_name 
                    FROM inventaire i
                    LEFT JOIN magasins m ON i.id_magasin = m.id_magasin
                    LEFT JOIN users u ON i.id_user = u.id
                    WHERE i.id_inventaire = ?";
            
            $inventory = $this->db->fetchOne($sql, [$inventoryId]);
            
            if (!$inventory) {
                error_log("Inventaire non trouvé: ID " . $inventoryId);
                return null;
            }
            
            // Récupérer les détails
            $sql = "SELECT di.*, 
                           p.nom_produit, p.code_barre, p.stock_actuel as stock_theorique,
                           p.emplacement, p.unite_mesure,
                           l.numero_lot, l.date_expiration
                    FROM detail_inventaire di
                    JOIN produits p ON di.id_produit = p.id_produit
                    LEFT JOIN lots l ON di.id_lot = l.id_lot
                    WHERE di.id_inventaire = ?
                    ORDER BY p.nom_produit";
            
            $details = $this->db->fetchAll($sql, [$inventoryId]);
            
            $totalProducts = count($details);
            $countedProducts = 0;
            
            foreach ($details as &$detail) {
                if ($detail['quantite_comptee'] > 0) {
                    $countedProducts++;
                }
            }
            
            $progress = $totalProducts > 0 ? round(($countedProducts / $totalProducts) * 100) : 0;
            
            return [
                'id' => $inventory['id_inventaire'],
                'numero_inventaire' => $inventory['numero_inventaire'],
                'id_magasin' => $inventory['id_magasin'],
                'nom_magasin' => $inventory['nom_magasin'] ?? '',
                'type_inventaire' => $inventory['type_inventaire'] ?? 'complet',
                'statut' => $inventory['statut'] ?? 'planifie',
                'notes' => $inventory['notes'] ?? '',
                'date_debut' => $inventory['date_debut'],
                'date_fin' => $inventory['date_fin'] ?? null,
                'user_name' => $inventory['user_name'] ?? '',
                'details' => $details,
                'stats' => [
                    'total_products' => $totalProducts,
                    'counted_products' => $countedProducts,
                    'progress' => $progress
                ]
            ];
            
        } catch (\Exception $e) {
            error_log("Erreur getInventoryDetails: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Sauvegarder un comptage
     */
    public function saveCount($detailId, $quantity, $userId, $notes = null)
    {
        try {
            $sql = "UPDATE detail_inventaire 
                    SET quantite_comptee = ?, 
                        id_user_comptage = ?, 
                        date_comptage = NOW(), 
                        notes = ? 
                    WHERE id_detail_inventaire = ?";
            $this->db->query($sql, [$quantity, $userId, $notes, $detailId]);
            
            return true;
            
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Valider un inventaire et appliquer les corrections
     */
    public function validateInventory($inventoryId, $userId)
    {
        try {
            $this->db->beginTransaction();
            
            // Récupérer tous les détails avec écart
            $sql = "SELECT id_produit, quantite_comptee, quantite_theorique 
                    FROM detail_inventaire 
                    WHERE id_inventaire = ? AND quantite_comptee != quantite_theorique";
            $details = $this->db->fetchAll($sql, [$inventoryId]);
            
            $nbCorrections = 0;
            
            foreach ($details as $detail) {
                // Mettre à jour le stock du produit
                $sql = "UPDATE produits SET stock_actuel = ? WHERE id_produit = ?";
                $this->db->query($sql, [$detail['quantite_comptee'], $detail['id_produit']]);
                
                // Marquer comme corrigé
                $sql = "UPDATE detail_inventaire SET est_corrige = 1 
                        WHERE id_inventaire = ? AND id_produit = ?";
                $this->db->query($sql, [$inventoryId, $detail['id_produit']]);
                
                $nbCorrections++;
            }
            
            // Mettre à jour le statut de l'inventaire
            $sql = "UPDATE inventaire 
                    SET statut = 'valide', date_fin = NOW(), id_user_validation = ? 
                    WHERE id_inventaire = ?";
            $this->db->query($sql, [$userId, $inventoryId]);
            
            // Mettre à jour l'archive
            $sql = "UPDATE inventaire_sessions 
                    SET status = 'valide', date_fin = NOW(), id_user_validation = ? 
                    WHERE reference = (SELECT numero_inventaire FROM inventaire WHERE id_inventaire = ?)";
            $this->db->query($sql, [$userId, $inventoryId]);
            
            $this->db->commit();
            
            return $nbCorrections;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Annuler un inventaire
     */
    public function cancelInventory($inventoryId)
    {
        try {
            $sql = "UPDATE inventaire 
                    SET statut = 'annule', date_fin = NOW() 
                    WHERE id_inventaire = ?";
            $this->db->query($sql, [$inventoryId]);
            
            // Mettre à jour l'archive
            $sql = "UPDATE inventaire_sessions 
                    SET status = 'annule', date_fin = NOW() 
                    WHERE reference = (SELECT numero_inventaire FROM inventaire WHERE id_inventaire = ?)";
            $this->db->query($sql, [$inventoryId]);
            
            return true;
            
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Obtenir les écarts d'un inventaire
     */
    public function getDiscrepancies($inventoryId)
    {
        try {
            $sql = "SELECT di.*, 
                           p.nom_produit, p.code_barre,
                           (di.quantite_comptee - di.quantite_theorique) as ecart,
                           CASE 
                               WHEN di.quantite_comptee > di.quantite_theorique THEN 'Surplus'
                               WHEN di.quantite_comptee < di.quantite_theorique THEN 'Manquant'
                               ELSE 'Égal'
                           END as type_ecart
                    FROM detail_inventaire di
                    JOIN produits p ON di.id_produit = p.id_produit
                    WHERE di.id_inventaire = ? AND di.quantite_comptee != di.quantite_theorique
                    ORDER BY ABS(di.quantite_comptee - di.quantite_theorique) DESC";
            
            return $this->db->fetchAll($sql, [$inventoryId]);
            
        } catch (\Exception $e) {
            error_log("Erreur getDiscrepancies: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les produits non encore comptés
     */
    public function getUncountedProducts($inventoryId)
    {
        try {
            $sql = "SELECT di.id_detail_inventaire, di.id_produit, 
                           p.nom_produit, p.code_barre, p.emplacement,
                           di.quantite_theorique
                    FROM detail_inventaire di
                    JOIN produits p ON di.id_produit = p.id_produit
                    WHERE di.id_inventaire = ? 
                    AND (di.quantite_comptee = 0 OR di.date_comptage IS NULL)
                    ORDER BY p.nom_produit";
            
            return $this->db->fetchAll($sql, [$inventoryId]);
            
        } catch (\Exception $e) {
            error_log("Erreur getUncountedProducts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifier si un inventaire existe
     */
    public function inventoryExists($inventoryId)
    {
        try {
            $sql = "SELECT id_inventaire FROM inventaire WHERE id_inventaire = ?";
            $result = $this->db->fetchOne($sql, [$inventoryId]);
            return $result ? true : false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Debug: Afficher le dernier inventaire créé
     */
    public function getLastInventory()
    {
        try {
            $sql = "SELECT * FROM inventaire ORDER BY id_inventaire DESC LIMIT 1";
            return $this->db->fetchOne($sql);
        } catch (\Exception $e) {
            return null;
        }
    }
}