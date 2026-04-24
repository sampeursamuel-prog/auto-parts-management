<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Helpers\Auth;
use App\Helpers\Session;

class InvoiceController
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
     * Liste des factures
     */
    public function index()
    {
        $this->requireLogin();
        
        $magasinId = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $invoices = $this->getInvoices($magasinId, $search, $status);
        $stats = $this->getInvoiceStats($magasinId);
        
        $data = [
            'title' => 'Gestion des factures',
            'invoices' => $invoices,
            'stats' => $stats,
            'search' => $search,
            'status' => $status
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/invoices/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Formulaire de création de facture
     */
    public function create()
    {
        $this->requireLogin();
        
        $clients = $this->db->fetchAll("SELECT id_client, nom, prenom, code_client FROM clients WHERE est_actif = 1 ORDER BY nom");
        $products = $this->db->fetchAll("SELECT id_produit, nom_produit, code_barre, prix_vente_ttc, stock_actuel FROM produits WHERE est_actif = 1 AND stock_actuel > 0 ORDER BY nom_produit");
        
        $data = [
            'title' => 'Créer une facture',
            'clients' => $clients,
            'products' => $products
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/invoices/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Enregistrer une nouvelle facture
     */
    public function store()
    {
        $this->requireLogin();
        
        $clientId = !empty($_POST['client_id']) ? intval($_POST['client_id']) : null;
        $products = json_decode($_POST['products_json'] ?? '[]', true);
        $paymentMethod = $_POST['payment_method'] ?? 'especes';
        $received = floatval($_POST['amount_received'] ?? 0);
        
        if (empty($products)) {
            Session::setFlash('danger', 'Ajoutez au moins un produit');
            header('Location: ' . \BASE_PATH . '/index.php?action=invoice_create');
            exit;
        }
        
        $total = 0;
        foreach ($products as $product) {
            $total += $product['price'] * $product['quantity'];
        }
        
        $totalHT = $total / 1.18;
        $totalTVA = $total - $totalHT;
        $change = $received - $total;
        $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
        
        try {
            $this->db->beginTransaction();
            
            // Insertion de la vente
            $sql = "INSERT INTO ventes (
                numero_facture, id_user, id_client, id_magasin, type_vente,
                devise, taux_devise, montant_total_ht, montant_tva, montant_total_ttc,
                montant_final, montant_recu, monnaie_rendue, mode_paiement, statut, date_vente
            ) VALUES (
                ?, ?, ?, ?, 'caisse',
                'HTG', 1, ?, ?, ?,
                ?, ?, ?, ?, 'complete', NOW()
            )";
            
            $this->db->query($sql, [
                $invoiceNumber, $_SESSION['user_id'], $clientId, $id_magasin,
                $totalHT, $totalTVA, $total,
                $total, $received, $change, $paymentMethod
            ]);
            
            $saleId = $this->db->lastInsertId();
            
            // Insertion des détails
            foreach ($products as $product) {
                $prixHT = $product['price'] / 1.18;
                $this->db->query(
                    "INSERT INTO details_vente (id_vente, id_produit, quantite, prix_unitaire_ht, tva) 
                     VALUES (?, ?, ?, ?, 18)",
                    [$saleId, $product['id'], $product['quantity'], $prixHT]
                );
                
                // Mise à jour du stock
                $this->db->query(
                    "UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id_produit = ?",
                    [$product['quantity'], $product['id']]
                );
            }
            
            $this->db->commit();
            
            Session::setFlash('success', 'Facture créée avec succès ! N°: ' . $invoiceNumber);
            header('Location: ' . \BASE_PATH . '/index.php?action=invoice_show&id=' . $saleId);
            exit;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
            header('Location: ' . \BASE_PATH . '/index.php?action=invoice_create');
            exit;
        }
    }
    
    /**
     * Détails d'une facture
     */
    public function show()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $invoice = $this->getInvoiceById($id);
        
        if (!$invoice) {
            Session::setFlash('danger', 'Facture non trouvée');
            header('Location: ' . \BASE_PATH . '/index.php?action=invoices');
            exit;
        }
        
        $items = $this->getInvoiceItems($id);
        
        $data = [
            'title' => 'Facture N° ' . $invoice['numero_facture'],
            'invoice' => $invoice,
            'items' => $items
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/invoices/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Imprimer une facture
     */
    public function print()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $invoice = $this->getInvoiceById($id);
        
        if (!$invoice) {
            Session::setFlash('danger', 'Facture non trouvée');
            header('Location: ' . \BASE_PATH . '/index.php?action=invoices');
            exit;
        }
        
        $items = $this->getInvoiceItems($id);
        
        $data = [
            'invoice' => $invoice,
            'items' => $items
        ];
        
        extract($data);
        include __DIR__ . '/../views/invoices/print.php';
        exit;
    }
    
    /**
     * Annuler une facture
     */
    public function cancel()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        try {
            $this->db->query(
                "UPDATE ventes SET statut = 'annulee', date_annulation = NOW() WHERE id_vente = ?",
                [$id]
            );
            Session::setFlash('success', 'Facture annulée avec succès');
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=invoices');
        exit;
    }
    
    // ============================================
    // MÉTHODES PRIVÉES
    // ============================================
    
    private function getInvoices($magasinId, $search = '', $status = '')
    {
        $sql = "SELECT v.*, 
                CONCAT(u.nom, ' ', u.prenom) as caissier,
                c.nom as client_nom, c.prenom as client_prenom
                FROM ventes v
                LEFT JOIN users u ON v.id_user = u.id_user
                LEFT JOIN clients c ON v.id_client = c.id_client
                WHERE 1=1";
        $params = [];
        
        if ($magasinId) {
            $sql .= " AND v.id_magasin = ?";
            $params[] = $magasinId;
        }
        
        if ($search) {
            $sql .= " AND (v.numero_facture LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status && $status != 'all') {
            $sql .= " AND v.statut = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY v.date_vente DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    private function getInvoiceStats($magasinId)
    {
        $magasinFilter = $magasinId ? " AND id_magasin = " . $magasinId : "";
        
        $total = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                COALESCE(SUM(montant_total_ttc), 0) as total_amount,
                COUNT(CASE WHEN statut = 'complete' THEN 1 END) as paid,
                COUNT(CASE WHEN statut = 'annulee' THEN 1 END) as cancelled
             FROM ventes WHERE 1=1" . $magasinFilter
        );
        
        return [
            'total' => $total['total'] ?? 0,
            'total_amount' => $total['total_amount'] ?? 0,
            'paid' => $total['paid'] ?? 0,
            'cancelled' => $total['cancelled'] ?? 0
        ];
    }
    
    private function getInvoiceById($id)
    {
        return $this->db->fetchOne(
            "SELECT v.*, 
                CONCAT(u.nom, ' ', u.prenom) as caissier,
                c.nom as client_nom, c.prenom as client_prenom,
                c.telephone as client_telephone, c.email as client_email,
                c.adresse as client_adresse
             FROM ventes v
             LEFT JOIN users u ON v.id_user = u.id_user
             LEFT JOIN clients c ON v.id_client = c.id_client
             WHERE v.id_vente = ?",
            [$id]
        );
    }
    
    private function getInvoiceItems($invoiceId)
    {
        return $this->db->fetchAll(
            "SELECT dv.*, p.nom_produit, p.code_barre
             FROM details_vente dv
             JOIN produits p ON dv.id_produit = p.id_produit
             WHERE dv.id_vente = ?",
            [$invoiceId]
        );
    }
}