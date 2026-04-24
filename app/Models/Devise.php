<?php
namespace App\Models;

class Devise extends Model
{
    protected $table = 'devises';
    protected $primaryKey = 'id_devise';
    
    /**
     * Récupérer la devise par défaut
     */
    public function getDefaultDevise()
    {
        return $this->db->fetchOne(
            "SELECT * FROM devises WHERE est_defaut = 1 AND est_actif = 1"
        );
    }
    
    /**
     * Récupérer toutes les devises actives
     */
    public function getAllDevises()
    {
        return $this->db->fetchAll(
            "SELECT * FROM devises WHERE est_actif = 1 ORDER BY code"
        );
    }
    
    /**
     * Mettre à jour le taux de change
     */
    public function updateTaux($code, $taux)
    {
        return $this->db->query(
            "UPDATE devises SET taux_htg = ?, date_mise_a_jour = NOW() WHERE code = ?",
            [$taux, $code]
        );
    }
    
    /**
     * Convertir un montant en Gourdes
     */
    public function convertToHTG($amount, $deviseCode)
    {
        if ($deviseCode === 'HTG') {
            return $amount;
        }
        
        $devise = $this->db->fetchOne(
            "SELECT taux_htg FROM devises WHERE code = ? AND est_actif = 1",
            [$deviseCode]
        );
        
        if ($devise) {
            return $amount * $devise['taux_htg'];
        }
        
        return $amount;
    }
    
    /**
     * Convertir un montant depuis les Gourdes
     */
    public function convertFromHTG($amount, $deviseCode)
    {
        if ($deviseCode === 'HTG') {
            return $amount;
        }
        
        $devise = $this->db->fetchOne(
            "SELECT taux_htg FROM devises WHERE code = ? AND est_actif = 1",
            [$deviseCode]
        );
        
        if ($devise) {
            return $amount / $devise['taux_htg'];
        }
        
        return $amount;
    }
}