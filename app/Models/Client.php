<?php
namespace App\Models;

class Client extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'id_client';
    
    /**
     * Trouver un client par son ID (alias de getById pour compatibilité)
     */
    public function find($id)
    {
        return $this->getById($id);
    }
    
    /**
     * Générer un code client unique
     */
    public function generateCode()
    {
        $prefix = 'CLT';
        $year = date('Y');
        $last = $this->db->fetchOne(
            "SELECT code_client FROM clients WHERE code_client LIKE ? ORDER BY id_client DESC LIMIT 1",
            [$prefix . $year . '%']
        );
        
        if ($last && isset($last['code_client'])) {
            $num = (int) substr($last['code_client'], -4) + 1;
            $num = str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $num = '0001';
        }
        
        return $prefix . $year . $num;
    }
    
    /**
     * Récupérer tous les clients
     */
    public function getAll($search = '')
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM ventes WHERE id_client = c.id_client) as nb_achats,
                       (SELECT SUM(montant_total_ttc) FROM ventes WHERE id_client = c.id_client AND statut = 'complete') as total_achats
                FROM clients c
                WHERE c.est_actif = 1";
        
        if (!empty($search)) {
            $sql .= " AND (c.nom LIKE '%$search%' OR c.prenom LIKE '%$search%' OR c.telephone LIKE '%$search%' OR c.code_client LIKE '%$search%')";
        }
        
        $sql .= " ORDER BY c.date_dernier_achat DESC, c.nom";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupérer un client par son ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM ventes WHERE id_client = c.id_client) as nb_achats,
                    (SELECT SUM(montant_total_ttc) FROM ventes WHERE id_client = c.id_client AND statut = 'complete') as total_achats
             FROM clients c
             WHERE c.id_client = ? AND c.est_actif = 1",
            [$id]
        );
    }
    
    /**
     * Rechercher des clients (AJAX) - Utilisé par SaleController
     */
    public function search($term)
    {
        if (empty($term)) {
            return [];
        }
        
        return $this->db->fetchAll(
            "SELECT c.id_client, c.code_client, c.nom, c.prenom, c.telephone, c.email, c.adresse, 
                    c.categorie_client, c.points_fidelite, c.remise_automatique,
                    (SELECT COUNT(*) FROM ventes WHERE id_client = c.id_client) as nb_achats
             FROM clients c
             WHERE c.est_actif = 1 
             AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.telephone LIKE ? OR c.email LIKE ? OR c.code_client LIKE ?)
             ORDER BY c.nom, c.prenom
             LIMIT 20",
            ["%$term%", "%$term%", "%$term%", "%$term%", "%$term%"]
        );
    }
    
    /**
     * Récupérer l'historique des achats d'un client
     */
    public function getPurchaseHistory($clientId)
    {
        return $this->db->fetchAll(
            "SELECT v.*, 
                    (SELECT COUNT(*) FROM details_vente WHERE id_vente = v.id_vente) as nb_articles,
                    u.nom as caissier_nom,
                    u.prenom as caissier_prenom
             FROM ventes v
             LEFT JOIN users u ON v.id_user = u.id_user
             WHERE v.id_client = ? AND v.statut = 'complete'
             ORDER BY v.date_vente DESC
             LIMIT 50",
            [$clientId]
        );
    }
    
    /**
     * Mettre à jour les points de fidélité
     */
    public function updatePoints($clientId, $points)
    {
        return $this->db->query(
            "UPDATE clients SET points_fidelite = points_fidelite + ? WHERE id_client = ?",
            [$points, $clientId]
        );
    }
    
    /**
     * Mettre à jour la catégorie du client selon ses achats
     */
    public function updateCategory($clientId)
    {
        $total = $this->db->fetchOne(
            "SELECT SUM(montant_total_ttc) as total FROM ventes 
             WHERE id_client = ? AND statut = 'complete'",
            [$clientId]
        );
        
        $totalAchats = $total['total'] ?? 0;
        $newCategory = 'bronze';
        $remise = 0;
        
        if ($totalAchats >= 100000) {
            $newCategory = 'platine';
            $remise = 15;
        } elseif ($totalAchats >= 50000) {
            $newCategory = 'or';
            $remise = 10;
        } elseif ($totalAchats >= 10000) {
            $newCategory = 'argent';
            $remise = 5;
        }
        
        $this->db->query(
            "UPDATE clients SET categorie_client = ?, remise_automatique = ? WHERE id_client = ?",
            [$newCategory, $remise, $clientId]
        );
        
        return $newCategory;
    }
    
    /**
     * Créer un nouveau client
     */
    public function createClient($data)
    {
        $data['code_client'] = $this->generateCode();
        return $this->create($data);
    }
    
    /**
     * Créer un client (méthode générique pour compatibilité)
     */
    public function create($data)
    {
        if (empty($data['code_client'])) {
            $data['code_client'] = $this->generateCode();
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->db->query($sql, array_values($data));
        return $this->db->lastInsertId();
    }
    
    /**
     * Mettre à jour la date du dernier achat
     */
    public function updateLastPurchaseDate($clientId)
    {
        return $this->db->query(
            "UPDATE clients SET date_dernier_achat = NOW() WHERE id_client = ?",
            [$clientId]
        );
    }
    
    /**
     * Obtenir les statistiques des clients
     */
    public function getStats()
    {
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM clients WHERE est_actif = 1");
        
        $byCategory = $this->db->fetchAll(
            "SELECT categorie_client, COUNT(*) as total FROM clients 
             WHERE est_actif = 1 GROUP BY categorie_client"
        );
        
        $totalPoints = $this->db->fetchOne(
            "SELECT SUM(points_fidelite) as total FROM clients WHERE est_actif = 1"
        );
        
        return [
            'total' => $total['total'] ?? 0,
            'by_category' => $byCategory,
            'total_points' => $totalPoints['total'] ?? 0
        ];
    }
    
    /**
     * Ajouter des points de fidélité après un achat
     */
    public function addPurchasePoints($clientId, $montant)
    {
        $points = floor($montant / 100); // 1 point pour 100 HTG
        return $this->updatePoints($clientId, $points);
    }
    
    /**
     * Utiliser des points de fidélité
     */
    public function usePoints($clientId, $points)
    {
        $client = $this->getById($clientId);
        if ($client && $client['points_fidelite'] >= $points) {
            return $this->db->query(
                "UPDATE clients SET points_fidelite = points_fidelite - ? WHERE id_client = ?",
                [$points, $clientId]
            );
        }
        return false;
    }
    
    /**
     * Vérifier si un client existe
     */
    public function exists($id)
    {
        $client = $this->getById($id);
        return !empty($client);
    }
    
    /**
     * Mettre à jour un client
     */
    public function updateClient($id, $data)
    {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                WHERE {$this->primaryKey} = ?";
        
        $values = array_merge(array_values($data), [$id]);
        return $this->db->query($sql, $values);
    }
    
    /**
     * Désactiver un client
     */
    public function desactivate($id)
    {
        return $this->db->query(
            "UPDATE {$this->table} SET est_actif = 0 WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }
    
    /**
     * Activer un client
     */
    public function activate($id)
    {
        return $this->db->query(
            "UPDATE {$this->table} SET est_actif = 1 WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }
    
    /**
     * Obtenir la remise d'un client
     */
    public function getRemise($clientId)
    {
        $client = $this->getById($clientId);
        return $client ? ($client['remise_automatique'] ?? 0) : 0;
    }
    
    /**
     * Obtenir les points de fidélité d'un client
     */
    public function getPoints($clientId)
    {
        $client = $this->getById($clientId);
        return $client ? ($client['points_fidelite'] ?? 0) : 0;
    }
}