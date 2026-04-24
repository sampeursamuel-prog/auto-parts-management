<?php
namespace App\Models;

class Product extends Model
{
    protected $table = 'produits';
    protected $primaryKey = 'id_produit';
    
    public function findByBarcode($barcode)
    {
        return $this->db->fetchOne("SELECT * FROM produits WHERE code_barre = ? AND est_actif = 1", [$barcode]);
    }
    
    public function getLowStock()
    {
        return $this->db->fetchAll("SELECT * FROM produits WHERE stock_actuel <= stock_minimum AND est_actif = 1");
    }
}