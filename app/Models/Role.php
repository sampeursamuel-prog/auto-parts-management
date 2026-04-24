<?php
namespace App\Models;

use App\Config\Database;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id_role';
    
    /**
     * Récupérer tous les rôles
     */
    public function getAll()
    {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY niveau");
    }
    
    /**
     * Trouver un rôle par son ID
     */
    public function find($id)
    {
        return $this->db->fetchOne("SELECT * FROM roles WHERE id_role = ?", [$id]);
    }
    
    /**
     * Récupérer les permissions d'un rôle
     */
    public function getPermissions($roleId)
    {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p
             JOIN roles_permissions rp ON p.id_permission = rp.id_permission
             WHERE rp.id_role = ?",
            [$roleId]
        );
    }
}