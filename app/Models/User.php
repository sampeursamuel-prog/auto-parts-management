<?php
namespace App\Models;

use App\Config\Database;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id_user';
    protected $fillable = ['username', 'password', 'email', 'telephone', 'nom', 'prenom', 'adresse', 'id_role', 'id_magasin_attache', 'id_magasin_defaut', 'id_manager', 'est_actif', 'notification_email', 'notification_sms', 'deux_facteurs'];
    protected $hidden = ['password'];
    
    /**
     * Authentifier un utilisateur
     */
    public function authenticate($username, $password)
    {
        $user = $this->db->fetchOne(
            "SELECT u.*, r.niveau as role_niveau, r.nom_role
             FROM users u
             JOIN roles r ON u.id_role = r.id_role
             WHERE (u.username = ? OR u.email = ?) AND u.est_actif = 1",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Vérifier si un utilisateur a une permission
     */
    public function hasPermission($userId, $permission)
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM users u
             JOIN roles_permissions rp ON u.id_role = rp.id_role
             JOIN permissions p ON rp.id_permission = p.id_permission
             WHERE u.id_user = ? AND p.nom_permission = ?",
            [$userId, $permission]
        );
        
        return $result['count'] > 0;
    }
    
    /**
     * Récupérer les permissions d'un utilisateur
     */
    public function getPermissions($userId)
    {
        return $this->db->fetchAll(
            "SELECT p.nom_permission, p.module 
             FROM users u
             JOIN roles_permissions rp ON u.id_role = rp.id_role
             JOIN permissions p ON rp.id_permission = p.id_permission
             WHERE u.id_user = ?",
            [$userId]
        );
    }
    
    /**
     * Récupérer les utilisateurs d'un magasin via user_magasin
     */
    public function getUsersByMagasin($magasinId, $roleFilter = null)
    {
        $sql = "SELECT u.*, r.nom_role, r.niveau, um.role_magasin, um.date_affectation
                FROM users u
                JOIN user_magasin um ON u.id_user = um.id_user
                JOIN roles r ON u.id_role = r.id_role
                WHERE um.id_magasin = ? AND u.est_actif = 1";
        $params = [$magasinId];
        
        if ($roleFilter) {
            $sql .= " AND u.id_role = ?";
            $params[] = $roleFilter;
        }
        
        $sql .= " ORDER BY r.niveau, u.nom, u.prenom";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Récupérer les utilisateurs sous la responsabilité d'un manager
     */
    public function getUsersByManager($managerId)
    {
        return $this->db->fetchAll(
            "SELECT u.*, r.nom_role, um.role_magasin
             FROM users u
             JOIN user_magasin um ON u.id_user = um.id_user
             JOIN roles r ON u.id_role = r.id_role
             WHERE u.id_manager = ? AND u.est_actif = 1
             ORDER BY u.nom, u.prenom",
            [$managerId]
        );
    }
    
    /**
     * Vérifier si un utilisateur peut gérer un autre utilisateur
     */
    public function canManageUser($managerId, $targetUserId)
    {
        $manager = $this->find($managerId);
        $target = $this->find($targetUserId);
        
        if (!$manager || !$target) return false;
        
        // Super Admin (niveau 1) peut tout gérer
        if ($manager['id_role'] == 1) return true;
        
        // Admin (niveau 2) peut gérer les utilisateurs de son magasin (niveau > 2)
        if ($manager['id_role'] == 2) {
            return $manager['id_magasin_attache'] == $target['id_magasin_attache'] && 
                   $target['id_role'] > 2;
        }
        
        // Superviseur (niveau 3) peut gérer caissiers et magasiniers (niveau 4 et 5)
        if ($manager['id_role'] == 3) {
            return $manager['id_magasin_attache'] == $target['id_magasin_attache'] && 
                   in_array($target['id_role'], [4, 5]);
        }
        
        return false;
    }
    
    /**
     * Créer un nouvel utilisateur avec mot de passe hashé
     */
    public function createUser($data)
    {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['date_creation'] = date('Y-m-d H:i:s');
        return $this->create($data);
    }
    
    /**
     * Récupérer tous les utilisateurs avec leur rôle et magasin
     */
    public function getAllWithDetails($magasinId = null)
    {
        $sql = "SELECT u.*, r.nom_role, r.niveau, 
                       m.nom_magasin,
                       CONCAT(mgr.nom, ' ', mgr.prenom) as manager_nom,
                       GROUP_CONCAT(DISTINCT um.id_magasin) as magasins_ids,
                       GROUP_CONCAT(DISTINCT m2.nom_magasin SEPARATOR ', ') as magasins_noms
                FROM users u
                JOIN roles r ON u.id_role = r.id_role
                LEFT JOIN user_magasin um ON u.id_user = um.id_user
                LEFT JOIN magasins m ON u.id_magasin_attache = m.id_magasin
                LEFT JOIN magasins m2 ON um.id_magasin = m2.id_magasin
                LEFT JOIN users mgr ON u.id_manager = mgr.id_user
                WHERE u.est_actif = 1";
        $params = [];
        
        if ($magasinId) {
            $sql .= " AND u.id_magasin_attache = ?";
            $params[] = $magasinId;
        }
        
        $sql .= " GROUP BY u.id_user ORDER BY r.niveau, u.nom, u.prenom";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Récupérer les statistiques des utilisateurs
     */
    public function getStats()
    {
        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN est_actif = 1 THEN 1 ELSE 0 END) as actifs,
                SUM(CASE WHEN id_role = 1 THEN 1 ELSE 0 END) as super_admins,
                SUM(CASE WHEN id_role = 2 THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN id_role = 3 THEN 1 ELSE 0 END) as superviseurs,
                SUM(CASE WHEN id_role = 4 THEN 1 ELSE 0 END) as caissiers,
                SUM(CASE WHEN id_role = 5 THEN 1 ELSE 0 END) as magasiniers
             FROM users"
        );
        
        return [
            'total' => $result['total'] ?? 0,
            'actifs' => $result['actifs'] ?? 0,
            'super_admins' => $result['super_admins'] ?? 0,
            'admins' => $result['admins'] ?? 0,
            'superviseurs' => $result['superviseurs'] ?? 0,
            'caissiers' => $result['caissiers'] ?? 0,
            'magasiniers' => $result['magasiniers'] ?? 0
        ];
    }
    
    /**
     * Récupérer les rôles disponibles pour la création
     * selon le rôle du créateur
     */
    public function getAvailableRoles($creatorRoleId)
    {
        $creatorLevel = $this->db->fetchOne("SELECT niveau FROM roles WHERE id_role = ?", [$creatorRoleId]);
        
        if (!$creatorLevel) return [];
        
        $minLevel = $creatorLevel['niveau'] + 1;
        
        return $this->db->fetchAll(
            "SELECT * FROM roles WHERE niveau >= ? AND niveau <= 5 ORDER BY niveau",
            [$minLevel]
        );
    }
    
    /**
     * Récupérer les managers disponibles pour un rôle
     */
    public function getAvailableManagers($roleId, $magasinId)
    {
        // Les managers dépendent du rôle cible
        $managerRoles = [];
        
        if ($roleId == 3) { // Superviseur -> manager = Admin (niveau 2)
            $managerRoles = [2];
        } elseif ($roleId == 4 || $roleId == 5) { // Caissier/Magasinier -> manager = Superviseur (niveau 3)
            $managerRoles = [3];
        }
        
        if (empty($managerRoles)) return [];
        
        return $this->db->fetchAll(
            "SELECT u.* FROM users u
             WHERE u.id_role IN (" . implode(',', $managerRoles) . ")
             AND u.id_magasin_attache = ?
             AND u.est_actif = 1
             ORDER BY u.nom, u.prenom",
            [$magasinId]
        );
    }
    
    /**
     * Récupérer les statistiques des utilisateurs par magasin
     */
    public function getStatsByMagasin($magasinId = null)
    {
        $sql = "SELECT r.nom_role, COUNT(u.id_user) as total
                FROM roles r
                LEFT JOIN users u ON r.id_role = u.id_role AND u.est_actif = 1";
        $params = [];
        
        if ($magasinId) {
            $sql .= " AND u.id_magasin_attache = ?";
            $params[] = $magasinId;
        }
        
        $sql .= " GROUP BY r.id_role ORDER BY r.niveau";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Mettre à jour le mot de passe
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->db->query(
            "UPDATE users SET password = ? WHERE id_user = ?",
            [$hashed, $userId]
        );
    }
    
    /**
     * Enregistrer la dernière connexion
     */
    public function updateLastLogin($userId)
    {
        return $this->db->query(
            "UPDATE users SET derniere_connexion = NOW() WHERE id_user = ?",
            [$userId]
        );
    }
    
    /**
     * Vérifier si un username existe déjà
     */
    public function usernameExists($username, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id_user != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Vérifier si un email existe déjà
     */
    public function emailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id_user != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'] > 0;
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete($id)
    {
        // Supprimer d'abord les associations user_magasin
        $this->db->query("DELETE FROM user_magasin WHERE id_user = ?", [$id]);
        // Puis supprimer l'utilisateur
        return $this->db->query("DELETE FROM users WHERE id_user = ?", [$id]);
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $data['date_modification'] = date('Y-m-d H:i:s');
        
        return $this->db->query(
            "UPDATE users SET " . implode(', ', $fields) . ", date_modification = NOW() WHERE id_user = ?",
            $values
        );
    }
    
    /**
     * Trouver un utilisateur par son ID
     */
    public function find($id)
    {
        return $this->db->fetchOne(
            "SELECT u.*, r.nom_role, r.niveau 
             FROM users u
             JOIN roles r ON u.id_role = r.id_role
             WHERE u.id_user = ?",
            [$id]
        );
    }
    
    /**
     * Ajouter un utilisateur à un magasin
     */
    public function addUserToMagasin($userId, $magasinId, $roleMagasin = 'caissier')
    {
        return $this->db->query(
            "INSERT INTO user_magasin (id_user, id_magasin, role_magasin, date_affectation) 
             VALUES (?, ?, ?, NOW())",
            [$userId, $magasinId, $roleMagasin]
        );
    }
    
    /**
     * Retirer un utilisateur d'un magasin
     */
    public function removeUserFromMagasin($userId, $magasinId)
    {
        return $this->db->query(
            "DELETE FROM user_magasin WHERE id_user = ? AND id_magasin = ?",
            [$userId, $magasinId]
        );
    }
    
    /**
     * Récupérer les magasins d'un utilisateur
     */
    public function getUserMagasins($userId)
    {
        return $this->db->fetchAll(
            "SELECT m.*, um.role_magasin, um.date_affectation
             FROM magasins m
             JOIN user_magasin um ON m.id_magasin = um.id_magasin
             WHERE um.id_user = ?",
            [$userId]
        );
    }
}