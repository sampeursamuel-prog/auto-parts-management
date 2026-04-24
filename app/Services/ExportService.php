<?php
namespace App\Services;

use App\Config\Database;

class ExportService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Exporter les ventes en CSV
     */
    public function exportSalesToCSV($dateStart, $dateEnd, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $sales = $this->db->fetchAll(
            "SELECT 
                v.date_vente,
                v.numero_facture,
                v.montant_total_ht,
                v.montant_tva,
                v.montant_total_ttc,
                v.mode_paiement,
                CONCAT(u.nom, ' ', u.prenom) as caissier,
                CONCAT(c.nom, ' ', c.prenom) as client
             FROM ventes v
             LEFT JOIN users u ON v.id_user = u.id_user
             LEFT JOIN clients c ON v.id_client = c.id_client
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'" . $magasinFilter . "
             ORDER BY v.date_vente DESC",
            [$dateStart, $dateEnd]
        );
        
        $filename = "rapport_ventes_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Ajouter BOM pour UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes
        fputcsv($output, [
            'Date', 'Facture', 'Total HT', 'TVA', 'Total TTC', 
            'Paiement', 'Caissier', 'Client'
        ]);
        
        // Données
        foreach ($sales as $sale) {
            fputcsv($output, [
                $sale['date_vente'],
                $sale['numero_facture'],
                number_format($sale['montant_total_ht'], 0, ',', ' '),
                number_format($sale['montant_tva'], 0, ',', ' '),
                number_format($sale['montant_total_ttc'], 0, ',', ' '),
                $sale['mode_paiement'],
                $sale['caissier'],
                $sale['client'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporter les produits en CSV
     */
    public function exportProductsToCSV($magasinId = null)
    {
        $magasinFilter = $magasinId ? " WHERE id_magasin = " . $magasinId : "";
        
        $products = $this->db->fetchAll(
            "SELECT 
                p.code_barre,
                p.nom_produit,
                p.description,
                c.nom_categorie,
                p.prix_achat_ht,
                p.prix_vente_ht,
                p.prix_vente_ttc,
                p.stock_actuel,
                p.stock_minimum,
                p.emplacement,
                p.unite_mesure
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1" . str_replace("WHERE", "AND", $magasinFilter) . "
             ORDER BY p.nom_produit"
        );
        
        $filename = "rapport_produits_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Code-barres', 'Produit', 'Description', 'Catégorie',
            'Prix achat HT', 'Prix vente HT', 'Prix vente TTC',
            'Stock', 'Stock min', 'Emplacement', 'Unité'
        ]);
        
        foreach ($products as $product) {
            fputcsv($output, [
                $product['code_barre'],
                $product['nom_produit'],
                $product['description'],
                $product['nom_categorie'],
                number_format($product['prix_achat_ht'], 0, ',', ' '),
                number_format($product['prix_vente_ht'], 0, ',', ' '),
                number_format($product['prix_vente_ttc'], 0, ',', ' '),
                $product['stock_actuel'],
                $product['stock_minimum'],
                $product['emplacement'],
                $product['unite_mesure']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporter les clients en CSV
     */
    public function exportClientsToCSV()
    {
        $clients = $this->db->fetchAll(
            "SELECT 
                c.code_client,
                c.nom,
                c.prenom,
                c.telephone,
                c.email,
                c.adresse,
                c.type_client,
                c.categorie_client,
                c.points_fidelite,
                c.date_inscription,
                COUNT(v.id_vente) as nb_achats,
                COALESCE(SUM(v.montant_total_ttc), 0) as total_achats
             FROM clients c
             LEFT JOIN ventes v ON c.id_client = v.id_client AND v.statut = 'complete'
             WHERE c.est_actif = 1
             GROUP BY c.id_client
             ORDER BY c.nom"
        );
        
        $filename = "rapport_clients_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Code', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Adresse',
            'Type', 'Catégorie', 'Points', 'Date inscription',
            'Nb achats', 'Total achats'
        ]);
        
        foreach ($clients as $client) {
            fputcsv($output, [
                $client['code_client'],
                $client['nom'],
                $client['prenom'],
                $client['telephone'],
                $client['email'],
                $client['adresse'],
                $client['type_client'],
                $client['categorie_client'],
                $client['points_fidelite'],
                date('d/m/Y', strtotime($client['date_inscription'])),
                $client['nb_achats'],
                number_format($client['total_achats'], 0, ',', ' ')
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporter le stock en Excel (CSV)
     */
    public function exportStockToCSV($magasinId = null)
    {
        $magasinFilter = $magasinId ? " WHERE id_magasin = " . $magasinId : "";
        
        $products = $this->db->fetchAll(
            "SELECT 
                p.code_barre,
                p.nom_produit,
                c.nom_categorie,
                p.stock_actuel,
                p.stock_minimum,
                p.prix_achat_ht,
                p.prix_vente_ttc,
                (p.stock_actuel * p.prix_vente_ttc) as valeur_stock,
                p.emplacement
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1" . str_replace("WHERE", "AND", $magasinFilter) . "
             ORDER BY p.nom_produit"
        );
        
        $filename = "rapport_stock_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Code-barres', 'Produit', 'Catégorie', 'Stock actuel',
            'Stock minimum', 'Prix achat', 'Prix vente TTC',
            'Valeur stock', 'Emplacement'
        ]);
        
        foreach ($products as $product) {
            fputcsv($output, [
                $product['code_barre'],
                $product['nom_produit'],
                $product['nom_categorie'],
                $product['stock_actuel'],
                $product['stock_minimum'],
                number_format($product['prix_achat_ht'], 0, ',', ' '),
                number_format($product['prix_vente_ttc'], 0, ',', ' '),
                number_format($product['valeur_stock'], 0, ',', ' '),
                $product['emplacement']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exporter les mouvements de stock
     */
    public function exportStockMovementsToCSV($dateStart, $dateEnd, $magasinId = null)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        $movements = $this->db->fetchAll(
            "SELECT 
                m.date_mouvement,
                p.nom_produit,
                p.code_barre,
                m.type_mouvement,
                m.quantite,
                m.stock_avant,
                m.stock_apres,
                CONCAT(u.nom, ' ', u.prenom) as user_name,
                m.raison
             FROM mouvements_stock m
             JOIN produits p ON m.id_produit = p.id_produit
             LEFT JOIN users u ON m.id_user = u.id_user
             WHERE DATE(m.date_mouvement) BETWEEN ? AND ?" . $magasinFilter . "
             ORDER BY m.date_mouvement DESC",
            [$dateStart, $dateEnd]
        );
        
        $filename = "rapport_mouvements_stock_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Date', 'Produit', 'Code-barres', 'Type', 'Quantité',
            'Stock avant', 'Stock après', 'Utilisateur', 'Raison'
        ]);
        
        foreach ($movements as $movement) {
            fputcsv($output, [
                $movement['date_mouvement'],
                $movement['nom_produit'],
                $movement['code_barre'],
                $movement['type_mouvement'],
                $movement['quantite'],
                $movement['stock_avant'],
                $movement['stock_apres'],
                $movement['user_name'],
                $movement['raison']
            ]);
        }
        
        fclose($output);
        exit;
    }
}