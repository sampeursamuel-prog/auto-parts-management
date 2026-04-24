<?php
namespace App\Models;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id_notification';
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getByUser($userId, $limit = 50)
    {
        $userId = (int)$userId;
        $limit = (int)$limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE id_user = $userId 
             ORDER BY date_creation DESC 
             LIMIT $limit"
        );
    }
    
    /**
     * Compter les notifications non lues
     */
    public function getUnreadCount($userId)
    {
        $userId = (int)$userId;
        
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM notifications 
             WHERE id_user = $userId AND est_lue = 0"
        );
        
        return $result['total'] ?? 0;
    }
    
    /**
     * Marquer comme lue
     */
    public function markAsRead($id, $userId)
    {
        $id = (int)$id;
        $userId = (int)$userId;
        
        return $this->db->query(
            "UPDATE notifications SET est_lue = 1, date_lecture = NOW() 
             WHERE id_notification = $id AND id_user = $userId"
        );
    }
    
    /**
     * Marquer toutes comme lues
     */
    public function markAllAsRead($userId)
    {
        $userId = (int)$userId;
        
        return $this->db->query(
            "UPDATE notifications SET est_lue = 1, date_lecture = NOW() 
             WHERE id_user = $userId AND est_lue = 0"
        );
    }
    
    /**
     * Créer une notification
     */
    public function create($data)
    {
        $userId = (int)$data['id_user'];
        $titre = addslashes($data['titre']);
        $message = addslashes($data['message']);
        $type = $data['type'];
        
        $this->db->query(
            "INSERT INTO notifications (id_user, titre, message, type, est_lue, date_creation) 
             VALUES ($userId, '$titre', '$message', '$type', 0, NOW())"
        );
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Supprimer une notification
     */
    public function delete($id, $userId)
    {
        $id = (int)$id;
        $userId = (int)$userId;
        
        return $this->db->query(
            "DELETE FROM notifications WHERE id_notification = $id AND id_user = $userId"
        );
    }
}