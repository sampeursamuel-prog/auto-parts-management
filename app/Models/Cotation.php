<?php
namespace App\Models;

class Cotation extends Model
{
    protected $table = 'cotations';
    protected $primaryKey = 'id_cotation';
    
    /**
     * Générer un numéro de cotation unique
     */
    public function generateNumber()
    {
        $prefix = 'DEV';
        $year = date('Y');
        $month = date('m');
        $last = $this->db->fetchOne(
            "SELECT numero_cotation FROM cotations WHERE numero_cotation LIKE ? ORDER BY id_cotation DESC LIMIT 1",
            [$prefix . $year . $month . '%']
        );
        
        if ($last) {
            $num = (int) substr($last['numero_cotation'], -4) + 1;
            $num = str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $num = '0001';
        }
        
        return $prefix . $year . $month . $num;
    }
    
    /**
     * Créer une cotation
     */
    public function createCotation($data, $items)
    {
        $numero = $this->generateNumber();
        $clientId = $data['id_client'] ?? null;
        $validite = $data['date_validite'] ?? date('Y-m-d', strtotime('+30 days'));
        $notes = addslashes($data['notes'] ?? '');
        $userId = (int)$data['id_user'];
        $remiseGlobale = (float)($data['remise_globale'] ?? 0);
        
        // Calculer les totaux
        $totalHT = 0;
        foreach ($items as $item) {
            $totalHT += $item['quantite'] * $item['prix_unitaire_ht'] * (1 - ($item['remise_ligne'] ?? 0) / 100);
        }
        
        $totalTVA = $totalHT * 0.18;
        $totalTTC = $totalHT + $totalTVA;
        $montantApresRemise = $totalTTC * (1 - $remiseGlobale / 100);
        
        $this->db->beginTransaction();
        
        try {
            // Créer la cotation
            $sql = "INSERT INTO cotations (numero_cotation, id_client, date_validite, montant_total_ht, montant_tva, 
                    montant_total_ttc, remise_globale, montant_apres_remise, statut, notes, id_user_creation) 
                    VALUES ('$numero', " . ($clientId ?: 'NULL') . ", '$validite', $totalHT, $totalTVA, $totalTTC, 
                    $remiseGlobale, $montantApresRemise, 'brouillon', '$notes', $userId)";
            
            $this->db->query($sql);
            $cotationId = $this->db->lastInsertId();
            
            // Ajouter les détails
            foreach ($items as $item) {
                $remiseLigne = (float)($item['remise_ligne'] ?? 0);
                $sql = "INSERT INTO details_cotation (id_cotation, id_produit, code_barre, quantite, 
                        prix_unitaire_ht, remise_ligne, tva, notes) 
                        VALUES ($cotationId, {$item['id_produit']}, '{$item['code_barre']}', {$item['quantite']}, 
                        {$item['prix_unitaire_ht']}, $remiseLigne, 18, '{$item['notes']}')";
                $this->db->query($sql);
            }
            
            $this->db->commit();
            return $cotationId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Récupérer toutes les cotations
     */
    public function getAll($filtres = [])
    {
        $sql = "SELECT c.*, 
                       CONCAT(cl.nom, ' ', COALESCE(cl.prenom, '')) as client_nom,
                       cl.code_client,
                       cl.telephone as client_tel,
                       (SELECT COUNT(*) FROM details_cotation WHERE id_cotation = c.id_cotation) as nb_articles
                FROM cotations c
                LEFT JOIN clients cl ON c.id_client = cl.id_client
                WHERE 1=1";
        
        if (!empty($filtres['statut'])) {
            $sql .= " AND c.statut = '{$filtres['statut']}'";
        }
        
        if (!empty($filtres['date_debut'])) {
            $sql .= " AND DATE(c.date_cotation) >= '{$filtres['date_debut']}'";
        }
        
        if (!empty($filtres['date_fin'])) {
            $sql .= " AND DATE(c.date_cotation) <= '{$filtres['date_fin']}'";
        }
        
        $sql .= " ORDER BY c.date_cotation DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Récupérer une cotation avec ses détails
     */
    public function getDetails($id)
    {
        $id = (int)$id;
        
        $cotation = $this->db->fetchOne(
            "SELECT c.*, 
                    CONCAT(cl.nom, ' ', COALESCE(cl.prenom, '')) as client_nom,
                    cl.telephone as client_tel,
                    cl.email as client_email,
                    cl.adresse as client_adresse,
                    cl.categorie_client,
                    cl.remise_automatique
             FROM cotations c
             LEFT JOIN clients cl ON c.id_client = cl.id_client
             WHERE c.id_cotation = $id"
        );
        
        if (!$cotation) return null;
        
        $details = $this->db->fetchAll(
            "SELECT d.*, p.nom_produit, p.code_barre, p.prix_vente_ttc
             FROM details_cotation d
             JOIN produits p ON d.id_produit = p.id_produit
             WHERE d.id_cotation = $id"
        );
        
        $cotation['details'] = $details;
        
        return $cotation;
    }
    
    /**
     * Mettre à jour le statut
     */
    public function updateStatut($id, $statut)
    {
        $id = (int)$id;
        return $this->db->query(
            "UPDATE cotations SET statut = '$statut' WHERE id_cotation = $id"
        );
    }
    
    /**
     * Transformer une cotation en vente
     */
    public function transformToSale($cotationId, $userId)
    {
        $cotationId = (int)$cotationId;
        $userId = (int)$userId;
        
        $cotation = $this->getDetails($cotationId);
        if (!$cotation) return false;
        
        $this->db->beginTransaction();
        
        try {
            // Générer le numéro de facture
            $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Créer la vente
            $sql = "INSERT INTO ventes (numero_facture, id_user, id_client, montant_total_ht, montant_tva, 
                    montant_total_ttc, montant_remise, montant_final, mode_paiement, statut, remise_globale) 
                    VALUES ('$invoiceNumber', $userId, " . ($cotation['id_client'] ?: 'NULL') . ", 
                    {$cotation['montant_total_ht']}, {$cotation['montant_tva']}, {$cotation['montant_total_ttc']}, 
                    {$cotation['remise_globale']}, {$cotation['montant_apres_remise']}, 'cash', 'complete', 
                    {$cotation['remise_globale']})";
            
            $this->db->query($sql);
            $saleId = $this->db->lastInsertId();
            
            // Ajouter les détails de vente
            foreach ($cotation['details'] as $item) {
                $sql = "INSERT INTO details_vente (id_vente, id_produit, code_barre_scanne, quantite, prix_unitaire_ht, tva) 
                        VALUES ($saleId, {$item['id_produit']}, '{$item['code_barre']}', {$item['quantite']}, 
                        {$item['prix_unitaire_ht']}, 18)";
                $this->db->query($sql);
                
                // Mettre à jour le stock
                $this->db->query(
                    "UPDATE produits SET stock_actuel = stock_actuel - {$item['quantite']} WHERE id_produit = {$item['id_produit']}"
                );
            }
            
            // Mettre à jour les points du client
            if ($cotation['id_client']) {
                $pointsGagnes = floor($cotation['montant_apres_remise'] / 100);
                $this->db->query(
                    "UPDATE clients SET points_fidelite = points_fidelite + $pointsGagnes, 
                     date_dernier_achat = NOW() WHERE id_client = {$cotation['id_client']}"
                );
            }
            
            // Marquer la cotation comme transformée
            $this->db->query(
                "UPDATE cotations SET statut = 'transforme_vente', date_transformation = NOW(), id_vente = $saleId 
                 WHERE id_cotation = $cotationId"
            );
            
            $this->db->commit();
            return $saleId;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Supprimer une cotation
     */
    public function delete($id)
    {
        $id = (int)$id;
        return $this->db->query("DELETE FROM cotations WHERE id_cotation = $id");
    }
    
    /**
     * Obtenir les statistiques des cotations
     */
    public function getStats()
    {
        $total = $this->db->fetchOne("SELECT COUNT(*) as total FROM cotations");
        
        $byStatus = $this->db->fetchAll(
            "SELECT statut, COUNT(*) as total FROM cotations GROUP BY statut"
        );
        
        $totalValue = $this->db->fetchOne(
            "SELECT SUM(montant_apres_remise) as total FROM cotations WHERE statut = 'accepte' OR statut = 'transforme_vente'"
        );
        
        return [
            'total' => $total['total'] ?? 0,
            'by_status' => $byStatus,
            'total_value' => $totalValue['total'] ?? 0
        ];
    }
}