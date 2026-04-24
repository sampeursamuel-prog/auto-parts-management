<?php
namespace App\Services;

use TCPDF;

class PDFService
{
    private $pdf;
    private $title;
    private $magasin;
    private $dateRange;
    
    public function __construct($title = 'Rapport Auto-Parts')
    {
        $this->title = $title;
        $this->magasin = $this->getCurrentMagasin();
        $this->dateRange = $this->getDateRange();
        
        // Créer le document PDF
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration
        $this->pdf->SetCreator('Auto-Parts Management');
        $this->pdf->SetAuthor('Auto-Parts System');
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject('Rapport de gestion');
        
        // Supprimer les en-têtes/footers par défaut
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Ajouter une page
        $this->pdf->AddPage();
        
        // Définir la police
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    private function getCurrentMagasin()
    {
        if (isset($_SESSION['magasin_actif'])) {
            $db = \App\Config\Database::getInstance();
            $magasin = $db->fetchOne("SELECT nom_magasin, ville FROM magasins WHERE id_magasin = ?", [$_SESSION['magasin_actif']]);
            return $magasin ? $magasin['nom_magasin'] : 'Tous les magasins';
        }
        return 'Tous les magasins';
    }
    
    private function getDateRange()
    {
        $start = $_GET['date_start'] ?? date('Y-m-01');
        $end = $_GET['date_end'] ?? date('Y-m-d');
        return date('d/m/Y', strtotime($start)) . ' - ' . date('d/m/Y', strtotime($end));
    }
    
    /**
     * Ajouter l'en-tête du rapport
     */
    private function addHeader()
    {
        // Logo (si disponible)
        $logoPath = dirname(__DIR__, 2) . '/public/assets/images/logo.png';
        if (file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 15, 10, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Titre
        $this->pdf->SetFont('helvetica', 'B', 18);
        $this->pdf->SetY(15);
        $this->pdf->Cell(0, 10, $this->title, 0, 1, 'C');
        
        // Sous-titre
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 6, 'Auto-Parts Management System', 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Date: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Magasin: ' . $this->magasin, 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Période: ' . $this->dateRange, 0, 1, 'C');
        
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(10);
        
        // Ligne de séparation
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(5);
    }
    
    /**
     * Ajouter le pied de page
     */
    private function addFooter()
    {
        $this->pdf->SetY(-20);
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->SetTextColor(128, 128, 128);
        $this->pdf->Cell(0, 10, 'Auto-Parts Management System - ' . date('Y'), 0, 0, 'C');
        $this->pdf->Cell(0, 10, 'Page ' . $this->pdf->getPageNum() . '/' . $this->pdf->getAliasNbPages(), 0, 0, 'R');
    }
    
    /**
     * Générer le rapport des ventes
     */
    public function generateSalesReport($sales, $summary)
    {
        $this->addHeader();
        
        // Statistiques
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Résumé des ventes', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $this->pdf->Cell(0, 6, 'Nombre total de ventes: ' . number_format($summary['total_ventes'], 0, ',', ' '), 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires total: ' . number_format($summary['total_htg'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Panier moyen: ' . number_format($summary['panier_moyen'], 0, ',', ' ') . ' G', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Détail des ventes
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Détail des ventes', 0, 1, 'L');
        
        // Tableau des ventes
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background: #f0f0f0;">Date</th>
                    <th style="background: #f0f0f0;">Facture</th>
                    <th style="background: #f0f0f0;">Total TTC</th>
                    <th style="background: #f0f0f0;">Paiement</th>
                    <th style="background: #f0f0f0;">Caissier</th>
                </thead>
            <tbody>';
        
        foreach (array_slice($sales, 0, 50) as $sale) {
            $html .= '<tr>
                <td>' . date('d/m/Y H:i', strtotime($sale['date_vente'])) . '</td>
                <td>' . htmlspecialchars($sale['numero_facture']) . '</td>
                <td style="text-align: right;">' . number_format($sale['montant_total_ttc'], 0, ',', ' ') . ' G</td>
                <td>' . ($sale['mode_paiement'] == 'cash' ? 'Espèces' : ($sale['mode_paiement'] == 'card' ? 'Carte' : 'Mobile Money')) . '</td>
                <td>' . htmlspecialchars($sale['caissier']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        if (count($sales) > 50) {
            $html .= '<p style="margin-top: 10px;"><em>Seules les 50 premières ventes sont affichées.</em></p>';
        }
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_ventes_' . date('Y-m-d') . '.pdf', 'D');
    }
    
    /**
     * Générer le rapport des profits
     */
    public function generateProfitsReport($profits, $topProducts)
    {
        $this->addHeader();
        
        // Calcul des totaux
        $totalCA = array_sum(array_column($profits, 'chiffre_affaires_ht'));
        $totalCout = array_sum(array_column($profits, 'cout_achat'));
        $totalMarge = array_sum(array_column($profits, 'marge'));
        $margePct = $totalCA > 0 ? ($totalMarge / $totalCA) * 100 : 0;
        
        // Statistiques
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Résumé des profits', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires HT: ' . number_format($totalCA, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Coût d\'achat: ' . number_format($totalCout, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Marge totale: ' . number_format($totalMarge, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Taux de marge moyen: ' . number_format($margePct, 1) . '%', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Top produits
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Top 10 produits les plus rentables', 0, 1, 'L');
        
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background: #f0f0f0;">#</th>
                    <th style="background: #f0f0f0;">Produit</th>
                    <th style="background: #f0f0f0;">Quantité</th>
                    <th style="background: #f0f0f0;">CA HT</th>
                    <th style="background: #f0f0f0;">Marge</th>
                    <th style="background: #f0f0f0;">Taux</th>
                </thead>
            <tbody>';
        
        foreach ($topProducts as $index => $product) {
            $html .= '<tr>
                <td style="text-align: center;">' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($product['nom_produit']) . '</td>
                <td style="text-align: center;">' . $product['quantite_vendue'] . '</td>
                <td style="text-align: right;">' . number_format($product['chiffre_affaires_ht'], 0, ',', ' ') . ' G</td>
                <td style="text-align: right;">' . number_format($product['marge_totale'], 0, ',', ' ') . ' G</td>
                <td style="text-align: right;">' . number_format($product['marge_pct'], 1) . '%</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_profits_' . date('Y-m-d') . '.pdf', 'D');
    }
    
    /**
     * Générer le rapport des stocks
     */
    public function generateStockReport($stockValue, $lowStockProducts, $stockByCategory, $allProducts)
    {
        $this->addHeader();
        
        // Statistiques
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Résumé du stock', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $this->pdf->Cell(0, 6, 'Valeur du stock (vente): ' . number_format($stockValue['valeur_vente'] ?? 0, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Valeur du stock (coût): ' . number_format($stockValue['cout_achat'] ?? 0, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Quantité totale: ' . number_format($stockValue['total_quantite'] ?? 0, 0, ',', ' '), 0, 1);
        $this->pdf->Cell(0, 6, 'Produits en stock bas: ' . count($lowStockProducts), 0, 1);
        
        $this->pdf->Ln(5);
        
        // Alertes stock
        if (!empty($lowStockProducts)) {
            $this->pdf->SetFont('helvetica', 'B', 11);
            $this->pdf->SetTextColor(255, 100, 0);
            $this->pdf->Cell(0, 8, 'Alertes stock', 0, 1, 'L');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('helvetica', '', 10);
            
            $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="background: #f0f0f0;">Produit</th>
                        <th style="background: #f0f0f0;">Code-barres</th>
                        <th style="background: #f0f0f0;">Stock</th>
                        <th style="background: #f0f0f0;">Stock min</th>
                        <th style="background: #f0f0f0;">Statut</th>
                    </thead>
                <tbody>';
            
            foreach ($lowStockProducts as $product) {
                $status = $product['stock_actuel'] == 0 ? 'RUPTURE' : 'STOCK BAS';
                $color = $product['stock_actuel'] == 0 ? '#721c24' : '#856404';
                $html .= '<tr>
                    <td>' . htmlspecialchars($product['nom_produit']) . '</td>
                    <td>' . htmlspecialchars($product['code_barre']) . '</td>
                    <td style="text-align: center;">' . $product['stock_actuel'] . '</td>
                    <td style="text-align: center;">' . $product['stock_minimum'] . '</td>
                    <td style="color: ' . $color . ';">' . $status . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
            $this->pdf->writeHTML($html, true, false, true, false, '');
            $this->pdf->Ln(5);
        }
        
        // Répartition par catégorie
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Répartition par catégorie', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background: #f0f0f0;">Catégorie</th>
                    <th style="background: #f0f0f0;">Produits</th>
                    <th style="background: #f0f0f0;">Quantité</th>
                    <th style="background: #f0f0f0;">Valeur</th>
                </thead>
            <tbody>';
        
        foreach ($stockByCategory as $cat) {
            $html .= '<tr>
                <td>' . htmlspecialchars($cat['nom_categorie'] ?? 'Non catégorisé') . '</td>
                <td style="text-align: center;">' . $cat['nb_produits'] . '</td>
                <td style="text-align: center;">' . $cat['total_stock'] . '</td>
                <td style="text-align: right;">' . number_format($cat['valeur_stock'], 0, ',', ' ') . ' G</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_stock_' . date('Y-m-d') . '.pdf', 'D');
    }
    
    /**
     * Générer le rapport de caisse
     */
    public function generateCashReport($cashSessions)
    {
        $this->addHeader();
        
        // Statistiques
        $totalEncaisse = array_sum(array_column($cashSessions, 'montant_total_ventes'));
        $totalDifference = array_sum(array_column($cashSessions, 'difference'));
        
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Résumé des sessions de caisse', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        
        $this->pdf->Cell(0, 6, 'Nombre de sessions: ' . count($cashSessions), 0, 1);
        $this->pdf->Cell(0, 6, 'Total encaissé: ' . number_format($totalEncaisse, 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Différence totale: ' . ($totalDifference >= 0 ? '+' : '') . number_format($totalDifference, 0, ',', ' ') . ' G', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Détail des sessions
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'Détail des sessions', 0, 1, 'L');
        
        $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background: #f0f0f0;">Session</th>
                    <th style="background: #f0f0f0;">Caissier</th>
                    <th style="background: #f0f0f0;">Ouverture</th>
                    <th style="background: #f0f0f0;">Fermeture</th>
                    <th style="background: #f0f0f0;">Ventes</th>
                    <th style="background: #f0f0f0;">Différence</th>
                </thead>
            <tbody>';
        
        foreach ($cashSessions as $session) {
            $diffClass = $session['difference'] >= 0 ? 'green' : 'red';
            $html .= '<tr>
                <td style="text-align: center;">#' . $session['id_session'] . '</td>
                <td>' . htmlspecialchars($session['caissier']) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($session['date_ouverture'])) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($session['date_fermeture'])) . '</td>
                <td style="text-align: right;">' . number_format($session['montant_total_ventes'], 0, ',', ' ') . ' G</td>
                <td style="text-align: right; color: ' . ($session['difference'] >= 0 ? '#10b981' : '#ef4444') . ';">' . ($session['difference'] >= 0 ? '+' : '') . number_format($session['difference'], 0, ',', ' ') . ' G</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_caisse_' . date('Y-m-d') . '.pdf', 'D');
    }
    
    /**
     * Générer le rapport du dashboard
     */
    public function generateDashboardReport($todaySales, $monthSales, $weeklySales, $topProducts, $stockStats)
    {
        $this->addHeader();
        
        // Ventes du jour
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Ventes du jour', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, 'Nombre de ventes: ' . $todaySales['total_ventes'], 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires: ' . number_format($todaySales['total_htg'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Espèces: ' . number_format($todaySales['cash'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Carte: ' . number_format($todaySales['card'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Mobile Money: ' . number_format($todaySales['mobile'], 0, ',', ' ') . ' G', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Ventes du mois
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Ventes du mois', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, 'Nombre de ventes: ' . $monthSales['total_ventes'], 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires: ' . number_format($monthSales['total_htg'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Panier moyen: ' . number_format($monthSales['panier_moyen'], 0, ',', ' ') . ' G', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Évolution des ventes (7 derniers jours)
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'Évolution des ventes (7 derniers jours)', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);
        
        $html = '<table border="1" cellpadding="3" cellspacing="0" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background: #f0f0f0;">Date</th>
                    <th style="background: #f0f0f0;">Ventes</th>
                </thead>
            <tbody>';
        
        foreach ($weeklySales['labels'] as $index => $label) {
            $value = $weeklySales['values'][$index];
            $html .= '<tr>
                <td>' . $label . '</td>
                <td style="text-align: right;">' . number_format($value, 0, ',', ' ') . ' G</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(5);
        
        // Top produits
        if (!empty($topProducts)) {
            $this->pdf->SetFont('helvetica', 'B', 12);
            $this->pdf->Cell(0, 8, 'Top 5 produits', 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
            
            $html = '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="background: #f0f0f0;">#</th>
                        <th style="background: #f0f0f0;">Produit</th>
                        <th style="background: #f0f0f0;">Quantité</th>
                        <th style="background: #f0f0f0;">CA</th>
                    </thead>
                <tbody>';
            
            foreach (array_slice($topProducts, 0, 5) as $index => $product) {
                $html .= '<tr>
                    <td style="text-align: center;">' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($product['nom_produit']) . '</td>
                    <td style="text-align: center;">' . $product['quantite_vendue'] . '</td>
                    <td style="text-align: right;">' . number_format($product['chiffre_affaires'], 0, ',', ' ') . ' G</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
            $this->pdf->writeHTML($html, true, false, true, false, '');
        }
        
        // Stock
        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'État du stock', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->Cell(0, 6, 'Valeur du stock: ' . number_format($stockStats['valeur_stock'], 0, ',', ' ') . ' G', 0, 1);
        $this->pdf->Cell(0, 6, 'Produits en stock bas: ' . $stockStats['low_stock'], 0, 1);
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_dashboard_' . date('Y-m-d') . '.pdf', 'D');
    }
}