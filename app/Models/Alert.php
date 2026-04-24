<?php
namespace App\Models;

class Alert extends Model
{
    protected $table = 'alertes';
    protected $primaryKey = 'id_alerte';
    
    /**
     * Récupérer toutes les alertes non lues
     */
    public function getUnread()
    {
        return $this->db->fetchAll(
            "SELECT * FROM alertes WHERE est_lue = 0 ORDER BY date_creation DESC"
        );
    }
    
    /**
     * Récupérer toutes les alertes
     */
    public function getAll($limit = 100)
    {
        $limit = (int)$limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM alertes ORDER BY date_creation DESC LIMIT $limit"
        );
    }
    
    /**
     * Créer une alerte
     */
    public function create($data)
    {
        $type = $data['type_alerte'];
        $message = addslashes($data['message']);
        $niveau = $data['niveau'];
        $idProduit = isset($data['id_produit']) ? (int)$data['id_produit'] : 'NULL';
        
        $this->db->query(
            "INSERT INTO alertes (type_alerte, id_produit, message, niveau, date_creation) 
             VALUES ('$type', $idProduit, '$message', '$niveau', NOW())"
        );
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Marquer comme lue
     */
    public function markAsRead($id, $userId)
    {
        $id = (int)$id;
        $userId = (int)$userId;
        
        return $this->db->query(
            "UPDATE alertes SET est_lue = 1, date_traitement = NOW(), id_user_traitement = $userId 
             WHERE id_alerte = $id"
        );
    }
    
    /**
     * Supprimer une alerte
     */
    public function delete($id)
    {
        $id = (int)$id;
        
        return $this->db->query("DELETE FROM alertes WHERE id_alerte = $id");
    }
    
    /**
     * Créer une alerte de stock bas
     */
    public function createLowStockAlert($productId, $productName, $currentStock)
    {
        $message = "Stock bas pour $productName : $currentStock unités restantes";
        
        return $this->create([
            'type_alerte' => 'stock_minimum',
            'id_produit' => $productId,
            'message' => $message,
            'niveau' => 'warning'
        ]);
    }
    
    /**
     * Créer une alerte de rupture de stock
     */
    public function createOutOfStockAlert($productId, $productName)
    {
        $message = "RUPTURE DE STOCK : $productName n'est plus disponible";
        
        return $this->create([
            'type_alerte' => 'stock_rupture',
            'id_produit' => $productId,
            'message' => $message,
            'niveau' => 'critical'
        ]);
    }
    
    /**
     * Créer une alerte de péremption
     */
    public function createExpiryAlert($lotId, $productName, $expiryDate)
    {
        $message = "Lot $lotId de $productName expire le $expiryDate";
        
        return $this->create([
            'type_alerte' => 'peremption',
            'message' => $message,
            'niveau' => 'warning'
        ]);
    }
}