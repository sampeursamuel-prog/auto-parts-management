<?php
namespace App\Controllers;

use App\Models\Client;
use App\Models\Cotation;
use App\Models\Product;
use App\Helpers\Session;
use App\Helpers\Auth;

class CotationController
{
    private $clientModel;
    private $cotationModel;
    private $productModel;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->clientModel = new Client();
        $this->cotationModel = new Cotation();
        $this->productModel = new Product();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . $this->basePath . '/index.php?action=login');
            exit;
        }
    }
    
    /**
     * Liste des cotations
     */
    public function index()
    {
        $this->requireLogin();
        
        $filtres = [
            'statut' => $_GET['statut'] ?? '',
            'date_debut' => $_GET['date_debut'] ?? '',
            'date_fin' => $_GET['date_fin'] ?? ''
        ];
        
        $cotations = $this->cotationModel->getAll($filtres);
        $stats = $this->cotationModel->getStats();
        
        include dirname(__DIR__) . '/Views/cotations/index.php';
    }
    
    /**
     * Créer une nouvelle cotation
     */
    public function create()
    {
        $this->requireLogin();
        
        $products = $this->productModel->all();
        $clients = $this->clientModel->getAll();
        
        include dirname(__DIR__) . '/Views/cotations/create.php';
    }
    
    /**
     * Enregistrer une cotation
     */
    public function store()
    {
        $this->requireLogin();
        
        $data = [
            'id_client' => intval($_POST['id_client'] ?? 0),
            'date_validite' => $_POST['date_validite'] ?? date('Y-m-d', strtotime('+30 days')),
            'remise_globale' => floatval($_POST['remise_globale'] ?? 0),
            'notes' => $_POST['notes'] ?? '',
            'id_user' => $_SESSION['user_id']
        ];
        
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        if (empty($items)) {
            Session::setFlash('danger', 'Ajoutez au moins un produit');
            header('Location: ' . $this->basePath . '/index.php?action=cotation_create');
            exit;
        }
        
        try {
            $cotationId = $this->cotationModel->createCotation($data, $items);
            Session::setFlash('success', 'Cotation créée avec succès');
            header('Location: ' . $this->basePath . '/index.php?action=cotation_show&id=' . $cotationId);
            exit;
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
            header('Location: ' . $this->basePath . '/index.php?action=cotation_create');
            exit;
        }
    }
    
    /**
     * Voir une cotation
     */
    public function show()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $cotation = $this->cotationModel->getDetails($id);
        
        if (!$cotation) {
            Session::setFlash('danger', 'Cotation non trouvée');
            header('Location: ' . $this->basePath . '/index.php?action=cotations');
            exit;
        }
        
        include dirname(__DIR__) . '/Views/cotations/show.php';
    }
    
    /**
     * Imprimer une cotation (PDF)
     */
    public function print()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $cotation = $this->cotationModel->getDetails($id);
        
        if (!$cotation) {
            Session::setFlash('danger', 'Cotation non trouvée');
            header('Location: ' . $this->basePath . '/index.php?action=cotations');
            exit;
        }
        
        include dirname(__DIR__) . '/Views/cotations/print.php';
    }
    
    /**
     * Exporter en PDF
     */
    public function exportPDF()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $cotation = $this->cotationModel->getDetails($id);
        
        if (!$cotation) {
            Session::setFlash('danger', 'Cotation non trouvée');
            header('Location: ' . $this->basePath . '/index.php?action=cotations');
            exit;
        }
        
        // Générer le PDF
        require_once dirname(__DIR__, 2) . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Auto-Parts Management');
        $pdf->SetAuthor('Auto-Parts System');
        $pdf->SetTitle('Cotation ' . $cotation['numero_cotation']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        
        // Logo
        $html = '<h1 style="text-align: center;">AUTO-PARTS MANAGEMENT</h1>';
        $html .= '<h2 style="text-align: center;">COTATION / PROFORMA</h2>';
        $html .= '<hr>';
        $html .= '<table width="100%">';
        $html .= '<tr><td width="50%"><strong>N° Cotation:</strong> ' . $cotation['numero_cotation'] . '</td>';
        $html .= '<td><strong>Date:</strong> ' . date('d/m/Y', strtotime($cotation['date_cotation'])) . '</td></tr>';
        $html .= '<tr><td><strong>Validité:</strong> ' . date('d/m/Y', strtotime($cotation['date_validite'])) . '</td>';
        $html .= '<td><strong>Statut:</strong> ' . ucfirst($cotation['statut']) . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<h3>Client</h3>';
        $html .= '<table width="100%" border="1" cellpadding="5">';
        $html .= '<tr><td width="30%"><strong>Nom:</strong></td><td>' . ($cotation['client_nom'] ?? 'Client non renseigné') . '</td></tr>';
        $html .= '<tr><td><strong>Téléphone:</strong></td><td>' . ($cotation['client_tel'] ?? '-') . '</td></tr>';
        $html .= '<tr><td><strong>Email:</strong></td><td>' . ($cotation['client_email'] ?? '-') . '</td></tr>';
        $html .= '</table>';
        
        $html .= '<h3>Détail des articles</h3>';
        $html .= '<table border="1" cellpadding="5" width="100%">';
        $html .= '<thead>
                    <tr style="background-color: #f0f0f0;">
                        <th>Réf</th>
                        <th>Produit</th>
                        <th>Qté</th>
                        <th>Prix unitaire</th>
                        <th>Remise</th>
                        <th>Total HT</th>
                    </tr>
                  </thead>
                  <tbody>';
        
        foreach ($cotation['details'] as $item) {
            $html .= '<tr>
                        <td>' . $item['code_barre'] . '</td>
                        <td>' . $item['nom_produit'] . '</td>
                        <td style="text-align: center;">' . $item['quantite'] . '</td>
                        <td style="text-align: right;">' . number_format($item['prix_unitaire_ht'], 0, ',', ' ') . ' G</td>
                        <td style="text-align: center;">' . ($item['remise_ligne'] ? $item['remise_ligne'] . '%' : '-') . '</td>
                        <td style="text-align: right;">' . number_format($item['sous_total_ht'], 0, ',', ' ') . ' G</td>
                      </tr>';
        }
        
        $html .= '</tbody>';
        $html .= '<tfoot>';
        $html .= '<tr><td colspan="5" style="text-align: right;"><strong>Total HT:</strong></td><td style="text-align: right;">' . number_format($cotation['montant_total_ht'], 0, ',', ' ') . ' G</td></tr>';
        $html .= '<tr><td colspan="5" style="text-align: right;"><strong>TVA (18%):</strong></td><td style="text-align: right;">' . number_format($cotation['montant_tva'], 0, ',', ' ') . ' G</td></tr>';
        $html .= '<tr><td colspan="5" style="text-align: right;"><strong>Total TTC:</strong></td><td style="text-align: right;">' . number_format($cotation['montant_total_ttc'], 0, ',', ' ') . ' G</td></tr>';
        
        if ($cotation['remise_globale'] > 0) {
            $html .= '<tr><td colspan="5" style="text-align: right;"><strong>Remise ' . $cotation['remise_globale'] . '%:</strong></td><td style="text-align: right;">- ' . number_format($cotation['montant_total_ttc'] - $cotation['montant_apres_remise'], 0, ',', ' ') . ' G</td></tr>';
            $html .= '<tr style="background-color: #d4edda;"><td colspan="5" style="text-align: right;"><strong>Net à payer:</strong></td><td style="text-align: right;"><strong>' . number_format($cotation['montant_apres_remise'], 0, ',', ' ') . ' G</strong></td></tr>';
        } else {
            $html .= '<tr style="background-color: #d4edda;"><td colspan="5" style="text-align: right;"><strong>Net à payer:</strong></td><td style="text-align: right;"><strong>' . number_format($cotation['montant_total_ttc'], 0, ',', ' ') . ' G</strong></td></tr>';
        }
        
        $html .= '</tfoot>';
        $html .= '</table>';
        
        if ($cotation['notes']) {
            $html .= '<h3>Notes</h3>';
            $html .= '<p>' . nl2br($cotation['notes']) . '</p>';
        }
        
        $html .= '<hr>';
        $html .= '<p style="text-align: center; font-size: 10px;">Document généré par Auto-Parts Management System</p>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('cotation_' . $cotation['numero_cotation'] . '.pdf', 'D');
        exit;
    }
    
    /**
     * Accepter une cotation
     */
    public function accept()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $this->cotationModel->updateStatut($id, 'accepte');
        
        Session::setFlash('success', 'Cotation acceptée');
        header('Location: ' . $this->basePath . '/index.php?action=cotation_show&id=' . $id);
        exit;
    }
    
    /**
     * Refuser une cotation
     */
    public function refuse()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $this->cotationModel->updateStatut($id, 'refuse');
        
        Session::setFlash('success', 'Cotation refusée');
        header('Location: ' . $this->basePath . '/index.php?action=cotation_show&id=' . $id);
        exit;
    }
    
    /**
     * Transformer une cotation en vente
     */
    public function transformToSale()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        try {
            $saleId = $this->cotationModel->transformToSale($id, $_SESSION['user_id']);
            Session::setFlash('success', 'Cotation transformée en vente avec succès');
            header('Location: ' . $this->basePath . '/index.php?action=sales_invoice&id=' . $saleId);
            exit;
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
            header('Location: ' . $this->basePath . '/index.php?action=cotation_show&id=' . $id);
            exit;
        }
    }
    
    /**
     * Supprimer une cotation
     */
    public function delete()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $this->cotationModel->delete($id);
        
        Session::setFlash('success', 'Cotation supprimée');
        header('Location: ' . $this->basePath . '/index.php?action=cotations');
        exit;
    }
}