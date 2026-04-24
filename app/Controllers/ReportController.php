<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Helpers\Auth;
use App\Helpers\Session;

class ReportController
{
    private $db;
    private $currentMagasin;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $magasinModel = new Magasin();
        $this->currentMagasin = $magasinModel->getCurrentMagasin();
    }
    
    private function requireLogin()
    {
        if (!Auth::check()) {
            Session::setFlash('danger', 'Veuillez vous connecter');
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    /**
     * Page principale des rapports
     */
    public function index()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $todaySales = $this->getTodaySales($magasinId);
        $monthSales = $this->getMonthSales($magasinId);
        $topProducts = $this->getTopProducts($magasinId);
        $stockStats = $this->getStockStats($magasinId);
        $weeklySales = $this->getWeeklySales($magasinId);
        
        $data = [
            'title' => 'Tableau de bord - Rapports',
            'todaySales' => $todaySales,
            'monthSales' => $monthSales,
            'topProducts' => $topProducts,
            'stockStats' => $stockStats,
            'weeklySales' => $weeklySales
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/reports/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rapport des ventes
     */
    public function sales()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $dateStart = $_GET['date_start'] ?? date('Y-m-01');
        $dateEnd = $_GET['date_end'] ?? date('Y-m-d');
        
        $sales = $this->getSalesByPeriod($magasinId, $dateStart, $dateEnd);
        $summary = $this->getSalesSummary($magasinId, $dateStart, $dateEnd);
        
        $data = [
            'title' => 'Rapport des ventes',
            'sales' => $sales,
            'summary' => $summary,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/reports/sales.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rapport des profits
     */
    public function profits()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $dateStart = $_GET['date_start'] ?? date('Y-m-01');
        $dateEnd = $_GET['date_end'] ?? date('Y-m-d');
        
        $profits = $this->getProfitsByPeriod($magasinId, $dateStart, $dateEnd);
        $topProducts = $this->getTopProfitableProducts($magasinId, $dateStart, $dateEnd);
        
        $data = [
            'title' => 'Rapport des profits',
            'profits' => $profits,
            'topProducts' => $topProducts,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/reports/profits.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rapport des stocks
     */
    public function stock()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $stockValue = $this->getStockValue($magasinId);
        $lowStockProducts = $this->getLowStockProducts($magasinId);
        $stockByCategory = $this->getStockByCategory($magasinId);
        $allProducts = $this->getAllProducts($magasinId);
        
        $data = [
            'title' => 'Rapport des stocks',
            'stockValue' => $stockValue,
            'lowStockProducts' => $lowStockProducts,
            'stockByCategory' => $stockByCategory,
            'allProducts' => $allProducts
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/reports/stock.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rapport de caisse
     */
    public function cash()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $dateStart = $_GET['date_start'] ?? date('Y-m-d');
        $dateEnd = $_GET['date_end'] ?? date('Y-m-d');
        
        $cashSessions = $this->getCashSessions($magasinId, $dateStart, $dateEnd);
        
        $data = [
            'title' => 'Rapport de caisse',
            'cashSessions' => $cashSessions,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/reports/cash.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Export des données en CSV
     */
    public function export()
    {
        $this->requireLogin();
        
        $type = $_GET['type'] ?? 'sales';
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        $dateStart = $_GET['date_start'] ?? date('Y-m-01');
        $dateEnd = $_GET['date_end'] ?? date('Y-m-d');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport_' . $type . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'sales':
                fputcsv($output, ['Date', 'Facture', 'Total TTC', 'Mode paiement', 'Caissier']);
                $sales = $this->getSalesByPeriod($magasinId, $dateStart, $dateEnd);
                foreach ($sales as $sale) {
                    fputcsv($output, [
                        $sale['date_vente'],
                        $sale['numero_facture'],
                        number_format($sale['montant_total_ttc'], 0, ',', ' ') . ' G',
                        $sale['mode_paiement'],
                        $sale['caissier']
                    ]);
                }
                break;
                
            case 'stock':
                fputcsv($output, ['Code-barres', 'Produit', 'Catégorie', 'Stock', 'Prix vente', 'Valeur']);
                $products = $this->getAllProducts($magasinId);
                foreach ($products as $product) {
                    fputcsv($output, [
                        $product['code_barre'],
                        $product['nom_produit'],
                        $product['nom_categorie'] ?? '-',
                        $product['stock_actuel'],
                        number_format($product['prix_vente_ttc'], 0, ',', ' ') . ' G',
                        number_format($product['stock_actuel'] * $product['prix_vente_ttc'], 0, ',', ' ') . ' G'
                    ]);
                }
                break;
        }
        
        fclose($output);
        exit;
    }
    
    // ============================================
    // MÉTHODES DE RÉCUPÉRATION DES DONNÉES
    // ============================================
    
    private function getTodaySales($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg,
                COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN montant_total_ttc ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN montant_total_ttc ELSE 0 END), 0) as card,
                COALESCE(SUM(CASE WHEN mode_paiement = 'mobile_money' THEN montant_total_ttc ELSE 0 END), 0) as mobile
             FROM ventes 
             WHERE DATE(date_vente) = CURDATE() 
             AND statut = 'complete'
             " . $magasinFilter
        );
        
        return [
            'total_ventes' => $result['total_ventes'] ?? 0,
            'total_htg' => $result['total_htg'] ?? 0,
            'cash' => $result['cash'] ?? 0,
            'card' => $result['card'] ?? 0,
            'mobile' => $result['mobile'] ?? 0
        ];
    }
    
    private function getMonthSales($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg,
                COALESCE(AVG(montant_total_ttc), 0) as panier_moyen
             FROM ventes 
             WHERE MONTH(date_vente) = MONTH(CURDATE()) 
             AND YEAR(date_vente) = YEAR(CURDATE())
             AND statut = 'complete'
             " . $magasinFilter
        );
        
        return [
            'total_ventes' => $result['total_ventes'] ?? 0,
            'total_htg' => $result['total_htg'] ?? 0,
            'panier_moyen' => $result['panier_moyen'] ?? 0
        ];
    }
    
    private function getWeeklySales($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $results = $this->db->fetchAll(
            "SELECT 
                DATE(date_vente) as date,
                COALESCE(SUM(montant_total_ttc), 0) as total
             FROM ventes 
             WHERE date_vente >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             AND statut = 'complete'
             " . $magasinFilter . "
             GROUP BY DATE(date_vente)
             ORDER BY date ASC"
        );
        
        $labels = [];
        $values = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d/m', strtotime($date));
            $found = false;
            foreach ($results as $r) {
                if ($r['date'] == $date) {
                    $values[] = (float)$r['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $values[] = 0;
            }
        }
        
        return ['labels' => $labels, 'values' => $values];
    }
    
    private function getTopProducts($magasinId)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                p.nom_produit,
                p.code_barre,
                SUM(dv.quantite) as quantite_vendue,
                COALESCE(SUM(dv.quantite * dv.prix_unitaire_ttc), 0) as chiffre_affaires
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             JOIN ventes v ON dv.id_vente = v.id_vente
             WHERE v.date_vente >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             AND v.statut = 'complete'
             " . $magasinFilter . "
             GROUP BY p.id_produit
             ORDER BY quantite_vendue DESC
             LIMIT 10"
        );
    }
    
    private function getStockStats($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $total = $this->db->fetchOne(
            "SELECT 
                COALESCE(SUM(stock_actuel), 0) as total_quantite,
                COALESCE(SUM(stock_actuel * prix_vente_ttc), 0) as valeur_stock,
                COUNT(*) as total_produits
             FROM produits 
             WHERE est_actif = 1" . $magasinFilter
        );
        
        $lowStock = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM produits 
             WHERE est_actif = 1 AND stock_actuel <= stock_minimum" . $magasinFilter
        );
        
        return [
            'total_quantite' => $total['total_quantite'] ?? 0,
            'valeur_stock' => $total['valeur_stock'] ?? 0,
            'total_produits' => $total['total_produits'] ?? 0,
            'low_stock' => $lowStock['total'] ?? 0
        ];
    }
    
    private function getSalesByPeriod($magasinId, $dateStart, $dateEnd)
    {
        $magasinFilter = $magasinId ? " AND v.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                v.*,
                CONCAT(u.nom, ' ', u.prenom) as caissier
             FROM ventes v
             JOIN users u ON v.id_user = u.id_user
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'
             " . $magasinFilter . "
             ORDER BY v.date_vente DESC",
            [$dateStart, $dateEnd]
        );
    }
    
    private function getSalesSummary($magasinId, $dateStart, $dateEnd)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $result = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_ventes,
                COALESCE(SUM(montant_total_ttc), 0) as total_htg,
                COALESCE(AVG(montant_total_ttc), 0) as panier_moyen,
                COALESCE(SUM(CASE WHEN mode_paiement = 'especes' THEN montant_total_ttc ELSE 0 END), 0) as cash,
                COALESCE(SUM(CASE WHEN mode_paiement = 'carte' THEN montant_total_ttc ELSE 0 END), 0) as card,
                COALESCE(SUM(CASE WHEN mode_paiement = 'mobile_money' THEN montant_total_ttc ELSE 0 END), 0) as mobile
             FROM ventes 
             WHERE DATE(date_vente) BETWEEN ? AND ?
             AND statut = 'complete'
             " . $magasinFilter,
            [$dateStart, $dateEnd]
        );
        
        return [
            'total_ventes' => $result['total_ventes'] ?? 0,
            'total_htg' => $result['total_htg'] ?? 0,
            'panier_moyen' => $result['panier_moyen'] ?? 0,
            'cash' => $result['cash'] ?? 0,
            'card' => $result['card'] ?? 0,
            'mobile' => $result['mobile'] ?? 0
        ];
    }
    
    private function getProfitsByPeriod($magasinId, $dateStart, $dateEnd)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                p.nom_produit,
                p.code_barre,
                SUM(dv.quantite) as quantite_vendue,
                COALESCE(SUM(dv.quantite * dv.prix_unitaire_ht), 0) as chiffre_affaires_ht,
                COALESCE(SUM(dv.quantite * p.prix_achat_ht), 0) as cout_achat,
                COALESCE(SUM(dv.quantite * (dv.prix_unitaire_ht - p.prix_achat_ht)), 0) as marge
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             JOIN ventes v ON dv.id_vente = v.id_vente
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'
             " . $magasinFilter . "
             GROUP BY p.id_produit
             ORDER BY marge DESC",
            [$dateStart, $dateEnd]
        );
    }
    
    private function getTopProfitableProducts($magasinId, $dateStart, $dateEnd)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                p.nom_produit,
                p.code_barre,
                SUM(dv.quantite) as quantite_vendue,
                COALESCE(SUM(dv.quantite * (dv.prix_unitaire_ht - p.prix_achat_ht)), 0) as marge_totale,
                CASE 
                    WHEN SUM(dv.quantite * dv.prix_unitaire_ht) > 0 
                    THEN (SUM(dv.quantite * (dv.prix_unitaire_ht - p.prix_achat_ht)) / SUM(dv.quantite * dv.prix_unitaire_ht) * 100)
                    ELSE 0 
                END as marge_pct
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             JOIN ventes v ON dv.id_vente = v.id_vente
             WHERE DATE(v.date_vente) BETWEEN ? AND ?
             AND v.statut = 'complete'
             " . $magasinFilter . "
             GROUP BY p.id_produit
             ORDER BY marge_totale DESC
             LIMIT 10",
            [$dateStart, $dateEnd]
        );
    }
    
    private function getStockValue($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        return $this->db->fetchOne(
            "SELECT 
                COALESCE(SUM(stock_actuel * prix_achat_ht), 0) as cout_achat,
                COALESCE(SUM(stock_actuel * prix_vente_ttc), 0) as valeur_vente,
                COALESCE(SUM(stock_actuel), 0) as total_quantite
             FROM produits 
             WHERE est_actif = 1" . $magasinFilter
        );
    }
    
    private function getLowStockProducts($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT * FROM produits 
             WHERE est_actif = 1 AND stock_actuel <= stock_minimum" . $magasinFilter . "
             ORDER BY stock_actuel ASC"
        );
    }
    
    private function getStockByCategory($magasinId)
    {
        $magasinFilter = $magasinId ? " AND p.id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                COALESCE(c.nom_categorie, 'Sans catégorie') as nom_categorie,
                COUNT(p.id_produit) as nb_produits,
                COALESCE(SUM(p.stock_actuel), 0) as total_stock,
                COALESCE(SUM(p.stock_actuel * p.prix_vente_ttc), 0) as valeur_stock
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1" . $magasinFilter . "
             GROUP BY c.id_categorie
             ORDER BY valeur_stock DESC"
        );
    }
    
    private function getCashSessions($magasinId, $dateStart, $dateEnd)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT 
                s.*,
                CONCAT(u.nom, ' ', u.prenom) as caissier
             FROM sessions_caisse s
             JOIN users u ON s.id_user = u.id_user
             WHERE DATE(s.date_ouverture) BETWEEN ? AND ?
             AND s.statut = 'closed'
             " . $magasinFilter . "
             ORDER BY s.date_ouverture DESC",
            [$dateStart, $dateEnd]
        );
    }
    
    private function getAllProducts($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        return $this->db->fetchAll(
            "SELECT p.*, c.nom_categorie 
             FROM produits p
             LEFT JOIN categories c ON p.id_categorie = c.id_categorie
             WHERE p.est_actif = 1" . $magasinFilter . "
             ORDER BY p.nom_produit"
        );
    }
}