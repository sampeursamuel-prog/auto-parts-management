<?php
namespace App\Services;

use TCPDF;

class PDFService
{
    private $pdf;
    private $title;
    private $magasin;
    private $dateRange;
    
    // Informations de l'entreprise
    private $companyName;
    private $companySlogan;
    private $companyAddress;
    private $companyPhone;
    private $companyEmail;
    private $companyWebsite;
    private $currencySymbol;
    
    public function __construct($title = 'Rapport Total Family')
    {
        // Initialiser les informations de l'entreprise
        $this->companyName = 'Total Family';
        $this->companySlogan = 'Votre partenaire auto de confiance';
        $this->companyAddress = 'Bon Repos, Route de Frères, Port-au-Prince, Haïti';
        $this->companyPhone = '+509 1234 5678';
        $this->companyEmail = 'contact@totalfamily.com';
        $this->companyWebsite = 'www.totalfamily.com';
        $this->currencySymbol = 'G';
        
        $this->title = $title;
        $this->magasin = $this->getCurrentMagasin();
        $this->dateRange = $this->getDateRange();
        
        // Créer le document PDF avec UTF-8
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration
        $this->pdf->SetCreator($this->companyName);
        $this->pdf->SetAuthor($this->companyName);
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject('Rapport de gestion');
        
        // Forcer l'utilisation de polices UTF-8
        $this->pdf->SetFont('dejavusans', '', 10);
        
        // Supprimer les en-têtes/footers par défaut
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Ajouter une page
        $this->pdf->AddPage();
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
     * Ajouter l'en-tête du rapport avec le logo Total Family
     */
    private function addHeader()
    {
        // Logo de l'entreprise
        $logoPath = dirname(__DIR__, 2) . '/public/assets/images/logo_total_family.png';
        if (file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 15, 10, 35, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Titre de l'entreprise
        $this->pdf->SetFont('dejavusans', 'B', 16);
        $this->pdf->SetY(12);
        $this->pdf->Cell(0, 8, $this->utf8Encode($this->companyName), 0, 1, 'C');
        
        $this->pdf->SetFont('dejavusans', 'I', 10);
        $this->pdf->Cell(0, 5, $this->utf8Encode($this->companySlogan), 0, 1, 'C');
        
        $this->pdf->SetFont('dejavusans', '', 8);
        $this->pdf->Cell(0, 4, $this->utf8Encode($this->companyAddress), 0, 1, 'C');
        $this->pdf->Cell(0, 4, "Tél: " . $this->companyPhone . " | Email: " . $this->companyEmail, 0, 1, 'C');
        
        $this->pdf->Ln(5);
        
        // Titre du rapport
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 8, $this->utf8Encode($this->title), 0, 1, 'C');
        
        // Sous-titre
        $this->pdf->SetFont('dejavusans', '', 9);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 5, 'Date d\'édition: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Magasin: ' . $this->utf8Encode($this->magasin), 0, 1, 'C');
        $this->pdf->Cell(0, 5, 'Période: ' . $this->utf8Encode($this->dateRange), 0, 1, 'C');
        
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(8);
        
        // Ligne de séparation
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(5);
    }
    
    /**
     * Ajouter le pied de page
     */
    private function addFooter()
    {
        $this->pdf->SetY(-25);
        $this->pdf->SetFont('dejavusans', 'I', 7);
        $this->pdf->SetTextColor(128, 128, 128);
        
        // Ligne de séparation
        $this->pdf->Line(15, $this->pdf->GetY(), 195, $this->pdf->GetY());
        $this->pdf->Ln(3);
        
        // Informations de l'entreprise
        $this->pdf->Cell(0, 4, $this->utf8Encode($this->companyName . " - " . $this->companyAddress), 0, 1, 'C');
        $this->pdf->Cell(0, 4, "Tél: " . $this->companyPhone . " | Email: " . $this->companyEmail . " | " . $this->companyWebsite, 0, 1, 'C');
        
        // Numéro de page
        $this->pdf->Cell(0, 4, 'Page ' . $this->pdf->getPage() . '/' . $this->pdf->getAliasNbPages(), 0, 0, 'C');
    }
    
    /**
     * Convertir une chaîne en UTF-8
     */
    private function utf8Encode($string)
    {
        if ($string === null) {
            return '';
        }
        // Convertir en UTF-8 si nécessaire
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = utf8_encode($string);
        }
        return $string;
    }
    
    /**
     * Générer le rapport des ventes
     */
    public function generateSalesReport($sales, $summary)
    {
        $this->addHeader();
        
        // Statistiques
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📊 Résumé des ventes', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        
        $this->pdf->Cell(0, 6, 'Nombre total de ventes: ' . number_format($summary['total_ventes'], 0, ',', ' '), 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires total: ' . number_format($summary['total_htg'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Panier moyen: ' . number_format($summary['panier_moyen'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        
        $this->pdf->Ln(5);
        
        // Détail des ventes
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 8, '📋 Détail des ventes', 0, 1, 'L');
        
        // Tableau des ventes
        $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #f0f0f0;">Date</th>
                    <th style="background-color: #f0f0f0;">Facture</th>
                    <th style="background-color: #f0f0f0;">Total TTC</th>
                    <th style="background-color: #f0f0f0;">Paiement</th>
                    <th style="background-color: #f0f0f0;">Caissier</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach (array_slice($sales, 0, 50) as $sale) {
            $html .= '<tr>
                <td>' . $this->utf8Encode(date('d/m/Y H:i', strtotime($sale['date_vente']))) . '</td>
                <td>' . $this->utf8Encode($sale['numero_facture']) . '</td>
                <td style="text-align: right;">' . number_format($sale['montant_total_ttc'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
                <td>' . $this->utf8Encode($sale['mode_paiement'] == 'cash' ? '💰 Espèces' : ($sale['mode_paiement'] == 'card' ? '💳 Carte' : '📱 Mobile Money')) . '</td>
                <td>' . $this->utf8Encode($sale['caissier']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
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
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '💰 Résumé des profits', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires HT: ' . number_format($totalCA, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Coût d\'achat: ' . number_format($totalCout, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Marge totale: ' . number_format($totalMarge, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Taux de marge moyen: ' . number_format($margePct, 1) . '%', 0, 1);
        
        $this->pdf->Ln(5);
        
        // Top produits
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 8, '🏆 Top 10 produits les plus rentables', 0, 1, 'L');
        
        $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #f0f0f0;">#</th>
                    <th style="background-color: #f0f0f0;">Produit</th>
                    <th style="background-color: #f0f0f0;">Quantité</th>
                    <th style="background-color: #f0f0f0;">CA HT</th>
                    <th style="background-color: #f0f0f0;">Marge</th>
                    <th style="background-color: #f0f0f0;">Taux</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($topProducts as $index => $product) {
            $html .= '<tr>
                <td style="text-align: center;">' . ($index + 1) . '</td>
                <td>' . $this->utf8Encode($product['nom_produit']) . '</td>
                <td style="text-align: center;">' . $product['quantite_vendue'] . '</td>
                <td style="text-align: right;">' . number_format($product['chiffre_affaires_ht'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
                <td style="text-align: right;">' . number_format($product['marge_totale'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
                <td style="text-align: right;">' . number_format($product['marge_pct'], 1) . '%</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
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
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📦 Résumé du stock', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        
        $this->pdf->Cell(0, 6, 'Valeur du stock (vente): ' . number_format($stockValue['valeur_vente'] ?? 0, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Valeur du stock (coût): ' . number_format($stockValue['cout_achat'] ?? 0, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Quantité totale: ' . number_format($stockValue['total_quantite'] ?? 0, 0, ',', ' '), 0, 1);
        $this->pdf->Cell(0, 6, 'Produits en stock bas: ' . count($lowStockProducts), 0, 1);
        
        $this->pdf->Ln(5);
        
        // Alertes stock
        if (!empty($lowStockProducts)) {
            $this->pdf->SetFont('dejavusans', 'B', 11);
            $this->pdf->SetTextColor(255, 100, 0);
            $this->pdf->Cell(0, 8, '⚠️ Alertes stock', 0, 1, 'L');
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('dejavusans', '', 10);
            
            $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="background-color: #f0f0f0;">Produit</th>
                        <th style="background-color: #f0f0f0;">Code-barres</th>
                        <th style="background-color: #f0f0f0;">Stock</th>
                        <th style="background-color: #f0f0f0;">Stock min</th>
                        <th style="background-color: #f0f0f0;">Statut</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($lowStockProducts as $product) {
                $status = $product['stock_actuel'] == 0 ? 'RUPTURE' : 'STOCK BAS';
                $html .= '<tr>
                    <td>' . $this->utf8Encode($product['nom_produit']) . '</td>
                    <td>' . $this->utf8Encode($product['code_barre']) . '</td>
                    <td style="text-align: center;">' . $product['stock_actuel'] . '</td>
                    <td style="text-align: center;">' . $product['stock_minimum'] . '</td>
                    <td style="color: #856404;">' . $status . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
            </table>';
            $this->pdf->writeHTML($html, true, false, true, false, '');
            $this->pdf->Ln(5);
        }
        
        // Répartition par catégorie
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 8, '📊 Répartition par catégorie', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        
        $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #f0f0f0;">Catégorie</th>
                    <th style="background-color: #f0f0f0;">Produits</th>
                    <th style="background-color: #f0f0f0;">Quantité</th>
                    <th style="background-color: #f0f0f0;">Valeur</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($stockByCategory as $cat) {
            $html .= '<tr>
                <td>' . $this->utf8Encode($cat['nom_categorie'] ?? 'Non catégorisé') . '</td>
                <td style="text-align: center;">' . $cat['nb_produits'] . '</td>
                <td style="text-align: center;">' . $cat['total_stock'] . '</td>
                <td style="text-align: right;">' . number_format($cat['valeur_stock'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
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
        
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '💰 Résumé des sessions de caisse', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        
        $this->pdf->Cell(0, 6, 'Nombre de sessions: ' . count($cashSessions), 0, 1);
        $this->pdf->Cell(0, 6, 'Total encaissé: ' . number_format($totalEncaisse, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Différence totale: ' . ($totalDifference >= 0 ? '+' : '') . number_format($totalDifference, 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        
        $this->pdf->Ln(5);
        
        // Détail des sessions
        $this->pdf->SetFont('dejavusans', 'B', 11);
        $this->pdf->Cell(0, 8, '📋 Détail des sessions', 0, 1, 'L');
        
        $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #f0f0f0;">Session</th>
                    <th style="background-color: #f0f0f0;">Caissier</th>
                    <th style="background-color: #f0f0f0;">Ouverture</th>
                    <th style="background-color: #f0f0f0;">Fermeture</th>
                    <th style="background-color: #f0f0f0;">Ventes</th>
                    <th style="background-color: #f0f0f0;">Différence</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($cashSessions as $session) {
            $html .= '<tr>
                <td style="text-align: center;">#' . $session['id_session'] . '</td>
                <td>' . $this->utf8Encode($session['caissier']) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($session['date_ouverture'])) . '</td>
                <td>' . date('d/m/Y H:i', strtotime($session['date_fermeture'])) . '</td>
                <td style="text-align: right;">' . number_format($session['montant_total_ventes'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
                <td style="text-align: right; color: ' . ($session['difference'] >= 0 ? '#10b981' : '#ef4444') . ';">' . ($session['difference'] >= 0 ? '+' : '') . number_format($session['difference'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
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
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📊 Ventes du jour', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 6, 'Nombre de ventes: ' . $todaySales['total_ventes'], 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires: ' . number_format($todaySales['total_htg'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Espèces: ' . number_format($todaySales['cash'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Carte: ' . number_format($todaySales['card'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Mobile Money: ' . number_format($todaySales['mobile'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        
        $this->pdf->Ln(5);
        
        // Ventes du mois
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📈 Ventes du mois', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 6, 'Nombre de ventes: ' . $monthSales['total_ventes'], 0, 1);
        $this->pdf->Cell(0, 6, 'Chiffre d\'affaires: ' . number_format($monthSales['total_htg'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Panier moyen: ' . number_format($monthSales['panier_moyen'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        
        $this->pdf->Ln(5);
        
        // Évolution des ventes (7 derniers jours)
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📉 Évolution des ventes (7 derniers jours)', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 9);
        
        $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #f0f0f0;">Date</th>
                    <th style="background-color: #f0f0f0;">Ventes</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($weeklySales['labels'] as $index => $label) {
            $value = $weeklySales['values'][$index];
            $html .= '<tr>
                <td>' . $this->utf8Encode($label) . '</td>
                <td style="text-align: right;">' . number_format($value, 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
        $this->pdf->writeHTML($html, true, false, true, false, '');
        $this->pdf->Ln(5);
        
        // Top produits
        if (!empty($topProducts)) {
            $this->pdf->SetFont('dejavusans', 'B', 12);
            $this->pdf->Cell(0, 8, '🏆 Top 5 produits', 0, 1, 'L');
            $this->pdf->SetFont('dejavusans', '', 10);
            
            $html = '<table border="1" cellpadding="4" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr>
                        <th style="background-color: #f0f0f0;">#</th>
                        <th style="background-color: #f0f0f0;">Produit</th>
                        <th style="background-color: #f0f0f0;">Quantité</th>
                        <th style="background-color: #f0f0f0;">CA</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach (array_slice($topProducts, 0, 5) as $index => $product) {
                $html .= '<tr>
                    <td style="text-align: center;">' . ($index + 1) . '</td>
                    <td>' . $this->utf8Encode($product['nom_produit']) . '</td>
                    <td style="text-align: center;">' . $product['quantite_vendue'] . '</td>
                    <td style="text-align: right;">' . number_format($product['chiffre_affaires'], 0, ',', ' ') . ' ' . $this->currencySymbol . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
            </table>';
            $this->pdf->writeHTML($html, true, false, true, false, '');
        }
        
        // Stock
        $this->pdf->Ln(5);
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 8, '📦 État du stock', 0, 1, 'L');
        $this->pdf->SetFont('dejavusans', '', 10);
        $this->pdf->Cell(0, 6, 'Valeur du stock: ' . number_format($stockStats['valeur_stock'], 0, ',', ' ') . ' ' . $this->currencySymbol, 0, 1);
        $this->pdf->Cell(0, 6, 'Produits en stock bas: ' . $stockStats['low_stock'], 0, 1);
        
        $this->addFooter();
        
        return $this->pdf->Output('rapport_dashboard_' . date('Y-m-d') . '.pdf', 'D');
    }
}