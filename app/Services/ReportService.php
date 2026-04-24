<?php
namespace App\Services;

use App\Config\Database;

class ReportService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Générer un rapport des ventes
     */
    public function getSalesReport($dateStart, $dateEnd, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $sales = $this->db->fetchAll(
            "SELECT v.*, 
                    CONCAT(u.nom, ' ', u.prenom) as caissier,
                    CONCAT(c.nom, ' ', c.prenom) as client_nom
             FROM ventes v
             LEFT JOIN users u ON v.id_user = u.id_user
             LEFT JOIN clients c ON v.id_client = c.id_client
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'" . $magasinFilter . "
             ORDER BY v.date_vente DESC",
            [$dateStart, $dateEnd]
        );
        
        $summary = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_ventes,
                SUM(montant_total_ttc) as total_htg,
                AVG(montant_total_ttc) as panier_moyen,
                SUM(CASE WHEN mode_paiement = 'cash' THEN montant_total_ttc ELSE 0 END) as cash,
                SUM(CASE WHEN mode_paiement = 'card' THEN montant_total_ttc ELSE 0 END) as card,
                SUM(CASE WHEN mode_paiement = 'mobile' THEN montant_total_ttc ELSE 0 END) as mobile
             FROM ventes 
             WHERE DATE(date_vente) BETWEEN ? AND ?
             AND statut = 'complete'" . $magasinFilter,
            [$dateStart, $dateEnd]
        );
        
        return [
            'sales' => $sales,
            'summary' => $summary
        ];
    }
    
    /**
     * Générer un rapport des profits
     */
    public function getProfitReport($dateStart, $dateEnd, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        $products = $this->db->fetchAll(
            "SELECT 
                p.id_produit,
                p.nom_produit,
                p.code_barre,
                c.nom_categorie,
                SUM(dv.quantite) as quantite_vendue,
                SUM(dv.sous_total_ht) as chiffre_affaires_ht,
                SUM(dv.quantite * p.prix_achat_ht) as cout_achat,
                SUM(dv.sous_total_ht - (dv.quantite * p.prix_achat_ht)) as marge_totale,
                (SUM(dv.sous_total_ht - (dv.quantite * p.prix_achat_ht)) / NULLIF(SUM(dv.sous_total_ht), 0) * 100) as taux_marge
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             JOIN ventes v ON dv.id_vente = v.id_vente
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'" . $magasinFilter . "
             GROUP BY p.id_produit
             ORDER BY marge_totale DESC",
            [$dateStart, $dateEnd]
        );
        
        $totals = [
            'total_ca' => array_sum(array_column($products, 'chiffre_affaires_ht')),
            'total_cout' => array_sum(array_column($products, 'cout_achat')),
            'total_marge' => array_sum(array_column($products, 'marge_totale')),
            'avg_margin' => count($products) > 0 ? array_sum(array_column($products, 'taux_marge')) / count($products) : 0
        ];
        
        return [
            'products' => $products,
            'totals' => $totals
        ];
    }
    
    /**
     * Générer un rapport des stocks
     */
    public function getStockReport($magasinId = null)
    {
        $magasinFilter = $magasinId ? " WHERE id_magasin = " . $magasinId : "";
        
        $stockValue = $this->db->fetchOne(
            "SELECT 
                SUM(stock_actuel * prix_achat_ht) as cout_achat,
                SUM(stock_actuel * prix_vente_ttc) as valeur_vente,
                SUM(stock_actuel) as total_quantite,
                COUNT(*) as total_produits
             FROM produits" . $magasinFilter . " AND est_actif = 1"
        );
        
        $lowStock = $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie 
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1 AND p.stock_actuel <= p.stock_minimum" . str_replace("WHERE", "AND", $magasinFilter) . "
             ORDER BY p.stock_actuel ASC"
        );
        
        $byCategory = $this->db->fetchAll(
            "SELECT 
                c.nom_categorie,
                COUNT(p.id_produit) as nb_produits,
                SUM(p.stock_actuel) as total_stock,
                SUM(p.stock_actuel * p.prix_vente_ttc) as valeur_stock
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1" . str_replace("WHERE", "AND", $magasinFilter) . "
             GROUP BY c.id_categorie
             ORDER BY valeur_stock DESC"
        );
        
        return [
            'stock_value' => $stockValue,
            'low_stock' => $lowStock,
            'by_category' => $byCategory
        ];
    }
    
    /**
     * Générer un rapport de caisse
     */
    public function getCashReport($dateStart, $dateEnd, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $sessions = $this->db->fetchAll(
            "SELECT s.*, 
                    CONCAT(u.nom, ' ', u.prenom) as caissier
             FROM sessions_caisse s
             JOIN users u ON s.id_user = u.id_user
             WHERE DATE(s.date_ouverture) BETWEEN ? AND ?
             AND s.statut = 'fermee'" . $magasinFilter . "
             ORDER BY s.date_ouverture DESC",
            [$dateStart, $dateEnd]
        );
        
        $totals = [
            'total_ventes' => array_sum(array_column($sessions, 'montant_total_ventes')),
            'total_difference' => array_sum(array_column($sessions, 'difference')),
            'nb_sessions' => count($sessions)
        ];
        
        return [
            'sessions' => $sessions,
            'totals' => $totals
        ];
    }
    
    /**
     * Générer un rapport des meilleures ventes
     */
    public function getTopProductsReport($limit = 10, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                p.id_produit,
                p.nom_produit,
                p.code_barre,
                c.nom_categorie,
                SUM(dv.quantite) as quantite_vendue,
                SUM(dv.sous_total_ht) as chiffre_affaires,
                SUM(dv.quantite * p.prix_achat_ht) as cout_achat,
                SUM(dv.sous_total_ht - (dv.quantite * p.prix_achat_ht)) as marge
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             JOIN ventes v ON dv.id_vente = v.id_vente
             WHERE v.date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND v.statut = 'complete'" . $magasinFilter . "
             GROUP BY p.id_produit
             ORDER BY quantite_vendue DESC
             LIMIT " . (int)$limit
        );
    }
    
    /**
     * Générer un rapport des clients
     */
    public function getClientReport($magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND v.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                c.id_client,
                c.code_client,
                c.nom,
                c.prenom,
                c.telephone,
                c.email,
                c.categorie_client,
                c.points_fidelite,
                COUNT(v.id_vente) as nb_achats,
                COALESCE(SUM(v.montant_total_ttc), 0) as total_achats,
                MAX(v.date_vente) as dernier_achat
             FROM clients c
             LEFT JOIN ventes v ON c.id_client = v.id_client AND v.statut = 'complete'" . $magasinFilter . "
             WHERE c.est_actif = 1
             GROUP BY c.id_client
             ORDER BY total_achats DESC"
        );
    }
}