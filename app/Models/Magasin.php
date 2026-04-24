<?php
namespace App\Models;

class Magasin extends Model
{
    protected $table = 'magasins';
    protected $primaryKey = 'id_magasin';
    
    /**
     * Récupérer un magasin par son ID
     */
    public function getMagasinById($id)
    {
        return $this->db->fetchOne("SELECT * FROM magasins WHERE id_magasin = ? AND est_actif = 1", [$id]);
    }
    
    /**
     * Récupérer tous les magasins d'un utilisateur selon son rôle
     */
    public function getMagasinsByUser($userId)
    {
        $user = (new User())->find($userId);
        
        if (!$user) return [];
        
        // Super Admin voit tous les magasins
        if ($user['id_role'] == 1) {
            return $this->db->fetchAll("SELECT * FROM magasins WHERE est_actif = 1 ORDER BY nom_magasin");
        }
        
        // Gérant voit son magasin
        if ($user['id_role'] == 2) {
            $magasinId = $user['id_magasin_attache'] ?? null;
            if ($magasinId) {
                return $this->db->fetchAll("SELECT * FROM magasins WHERE id_magasin = ? AND est_actif = 1", [$magasinId]);
            }
        }
        
        // Superviseur, caissier, magasinier voient le magasin où ils sont attachés
        if (in_array($user['id_role'], [3, 4, 5])) {
            $magasinId = $user['id_magasin_attache'] ?? null;
            if ($magasinId) {
                return $this->db->fetchAll("SELECT * FROM magasins WHERE id_magasin = ? AND est_actif = 1", [$magasinId]);
            }
        }
        
        return [];
    }
    
    /**
     * Récupérer le magasin actif de l'utilisateur
     */
    public function getCurrentMagasin()
    {
        // Vérifier d'abord si un magasin est sélectionné en session
        $magasinId = $_SESSION['current_magasin_id'] ?? $_SESSION['magasin_actif'] ?? null;
        
        if (!$magasinId && isset($_SESSION['user_id'])) {
            $user = (new User())->find($_SESSION['user_id']);
            $magasinId = $user['id_magasin_attache'] ?? null;
        }
        
        if ($magasinId) {
            return $this->getMagasinById($magasinId);
        }
        
        // Si aucun magasin trouvé, prendre le premier disponible
        $magasins = $this->getMagasinsByUser($_SESSION['user_id'] ?? 0);
        if (!empty($magasins)) {
            return $magasins[0];
        }
        
        return null;
    }
    
    /**
     * Vérifier si un utilisateur a accès à un magasin
     */
    public function userHasAccess($userId, $magasinId)
    {
        $user = (new User())->find($userId);
        
        if (!$user) return false;
        
        // Super Admin a accès à tous
        if ($user['id_role'] == 1) return true;
        
        // Vérifier si le magasin est celui attaché à l'utilisateur
        return ($user['id_magasin_attache'] ?? null) == $magasinId;
    }
    
    /**
     * Changer de magasin actif
     */
    public function switchMagasin($userId, $magasinId)
    {
        if ($this->userHasAccess($userId, $magasinId)) {
            $_SESSION['current_magasin_id'] = $magasinId;
            $_SESSION['magasin_actif'] = $magasinId;
            return true;
        }
        return false;
    }
    
    /**
     * Récupérer tous les magasins (admin)
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM magasins WHERE est_actif = 1 ORDER BY nom_magasin");
    }
    
    /**
     * Récupérer les statistiques des magasins
     */
    public function getStats()
    {
        $stats = $this->db->fetchAll(
            "SELECT m.*, 
                    COUNT(DISTINCT u.id_user) as nb_employes,
                    COUNT(DISTINCT p.id_produit) as nb_produits,
                    COALESCE(SUM(v.montant_total_ttc), 0) as total_ventes_mois
             FROM magasins m
             LEFT JOIN users u ON u.id_magasin_attache = m.id_magasin AND u.est_actif = 1
             LEFT JOIN produits p ON p.id_magasin = m.id_magasin AND p.est_actif = 1
             LEFT JOIN ventes v ON v.id_magasin = m.id_magasin AND MONTH(v.date_vente) = MONTH(CURDATE()) AND YEAR(v.date_vente) = YEAR(CURDATE())
             WHERE m.est_actif = 1
             GROUP BY m.id_magasin"
        );
        
        return $stats;
    }
    
    /**
     * Créer un nouveau magasin avec son gérant
     */
    public function createMagasin($data, $gerantData = null)
    {
        $this->db->beginTransaction();
        
        try {
            // Créer le magasin
            $magasinId = $this->insert([
                'code_magasin' => $data['code_magasin'] ?? $this->generateCode(),
                'nom_magasin' => $data['nom_magasin'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'email' => $data['email'] ?? null,
                'est_actif' => 1
            ]);
            
            // Créer le gérant si fourni
            if ($gerantData && !empty($gerantData['nom']) && !empty($gerantData['email'])) {
                $userModel = new User();
                $gerantData['id_role'] = 2; // Rôle Gérant
                $gerantData['id_magasin_attache'] = $magasinId;
                $gerantId = $userModel->createUser($gerantData);
                
                // Mettre à jour le magasin avec l'ID du gérant
                $this->update($magasinId, ['id_gerant' => $gerantId]);
            }
            
            $this->db->commit();
            return $magasinId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Générer un code magasin unique
     */
    public function generateCode()
    {
        $prefix = 'AP';
        $year = date('Y');
        $last = $this->db->fetchOne(
            "SELECT code_magasin FROM magasins WHERE code_magasin LIKE ? ORDER BY id_magasin DESC LIMIT 1",
            [$prefix . $year . '%']
        );
        
        if ($last && isset($last['code_magasin'])) {
            $num = (int) substr($last['code_magasin'], -3) + 1;
            $num = str_pad($num, 3, '0', STR_PAD_LEFT);
        } else {
            $num = '001';
        }
        
        return $prefix . $year . $num;
    }
    
    /**
     * Mettre à jour un magasin
     */
    public function updateMagasin($id, $data)
    {
        return $this->update($id, $data);
    }
    
    /**
     * Désactiver un magasin
     */
    public function desactivate($id)
    {
        return $this->update($id, ['est_actif' => 0]);
    }
    
    /**
     * Activer un magasin
     */
    public function activate($id)
    {
        return $this->update($id, ['est_actif' => 1]);
    }
}