<?php
namespace App\Controllers;

use App\Config\Database;
use App\Models\Magasin;
use App\Helpers\Auth;
use App\Helpers\Session;

class SaleController
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
    
    private function calculateTotal($cart)
    {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    
    public function pos()
    {
        $this->requireLogin();
        
        $sessionMagasinId = $_SESSION['current_magasin_id'] ?? null;
        
        if ($sessionMagasinId) {
            $magasinModel = new Magasin();
            $this->currentMagasin = $magasinModel->getMagasinById($sessionMagasinId);
        }
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $cart = $_SESSION['cart'];
        $total = $this->calculateTotal($cart);
        $tva = $total * 0.18;
        $totalTTC = $total + $tva;
        $userName = $_SESSION['user_name'] ?? 'Caissier';
        
        // Récupérer les devises
        $devises = $this->db->fetchAll("SELECT * FROM devises WHERE est_actif = 1");
        if (empty($devises)) {
            $devises = [['code' => 'HTG', 'taux_htg' => 1]];
        }
        
        $currentDevise = $_SESSION['current_devise'] ?? 'HTG';
        
        // Récupérer les produits
        $products = [];
        if ($this->currentMagasin) {
            $products = $this->db->fetchAll(
                "SELECT id_produit, code_barre, nom_produit, prix_vente_ttc, stock_actuel 
                 FROM produits WHERE est_actif = 1 AND id_magasin = ? 
                 ORDER BY nom_produit",
                [$this->currentMagasin['id_magasin']]
            );
        }
        
        $magasinModel = new Magasin();
        $userMagasins = $magasinModel->getMagasinsByUser($_SESSION['user_id']);
        
        $noProductsMessage = '';
        if (!$this->currentMagasin) {
            $noProductsMessage = 'Aucun magasin sélectionné.';
        } elseif (empty($products)) {
            $noProductsMessage = 'Aucun produit trouvé dans ce magasin.';
        }
        
        ob_start();
        include __DIR__ . '/../views/pos/index.php';
        $content = ob_get_clean();
        
        $data = [
            'title' => 'Point de Vente',
            'content' => $content,
            'userMagasins' => $userMagasins,
            'currentMagasin' => $this->currentMagasin
        ];
        
        extract($data);
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    public function saveCart()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['cart'])) {
            $_SESSION['cart'] = $data['cart'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function processSale()
    {
        // Forcer l'en-tête JSON
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        try {
            // Lire les données brutes
            $rawInput = file_get_contents('php://input');
            
            if (empty($rawInput)) {
                echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue']);
                exit;
            }
            
            // Décoder JSON
            $data = json_decode($rawInput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'message' => 'JSON invalide: ' . json_last_error_msg()]);
                exit;
            }
            
            $cart = $data['cart'] ?? [];
            $total = floatval($data['total'] ?? 0);
            $received = floatval($data['received'] ?? 0);
            $change = floatval($data['change'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $clientId = !empty($data['client_id']) ? intval($data['client_id']) : null;
            
            if (empty($cart)) {
                echo json_encode(['success' => false, 'message' => 'Panier vide']);
                exit;
            }
            
            // Calcul des montants
            $totalHT = $total / 1.18;
            $totalTVA = $total - $totalHT;
            
            // Générer le numéro de facture
            $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            $id_magasin = $this->currentMagasin ? $this->currentMagasin['id_magasin'] : null;
            $userId = $_SESSION['user_id'] ?? 1;
            
            // Convertir le mode de paiement
            $paymentMap = ['cash' => 'especes', 'card' => 'carte', 'mobile' => 'mobile_money'];
            $dbPayment = $paymentMap[$paymentMethod] ?? 'especes';
            
            $this->db->beginTransaction();
            
            // 1. Insérer la vente
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
                $invoiceNumber, $userId, $clientId, $id_magasin,
                $totalHT, $totalTVA, $total,
                $total, $received, $change, $dbPayment
            ]);
            
            $saleId = $this->db->lastInsertId();
            
            if (!$saleId) {
                throw new \Exception('Erreur lors de l\'insertion de la vente');
            }
            
            // 2. Insérer les détails
            foreach ($cart as $item) {
                $produitId = intval($item['id']);
                $quantite = intval($item['quantity']);
                $prixUnitaireTTC = floatval($item['price']);
                $prixUnitaireHT = $prixUnitaireTTC / 1.18;
                $codeBarre = $item['barcode'] ?? '';
                
                $this->db->query(
                    "INSERT INTO details_vente (
                        id_vente, id_produit, code_barre_scanne, quantite, 
                        prix_unitaire_ht, tva, remise_ligne
                    ) VALUES (?, ?, ?, ?, ?, 18, 0)",
                    [$saleId, $produitId, $codeBarre, $quantite, $prixUnitaireHT]
                );
                
                // Mise à jour du stock
                $this->db->query(
                    "UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id_produit = ?",
                    [$quantite, $produitId]
                );
            }
            
            $this->db->commit();
            
            // Vider le panier
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'success' => true,
                'invoice_number' => $invoiceNumber,
                'sale_id' => $saleId,
                'total' => $total,
                'received' => $received,
                'change' => $change
            ]);
            
        } catch (\Exception $e) {
            if ($this->db) {
                $this->db->rollBack();
            }
            error_log("Erreur processSale: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    public function switchMagasin()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $magasinId = $data['magasin_id'] ?? null;
        
        if ($magasinId) {
            $_SESSION['current_magasin_id'] = $magasinId;
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    public function switchDevise()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $devise = $data['devise'] ?? 'HTG';
        $_SESSION['current_devise'] = $devise;
        echo json_encode(['success' => true]);
        exit;
    }
    
    public function searchClient()
    {
        header('Content-Type: application/json');
        $term = $_GET['term'] ?? '';
        if (empty($term)) {
            echo json_encode([]);
            exit;
        }
        
        $clients = $this->db->fetchAll(
            "SELECT id_client, nom, prenom, telephone, email, code_client 
             FROM clients 
             WHERE est_actif = 1 
             AND (nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? OR code_client LIKE ?)
             LIMIT 10",
            ["%$term%", "%$term%", "%$term%", "%$term%"]
        );
        
        echo json_encode($clients);
        exit;
    }
    
    public function createClient()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['nom'])) {
            echo json_encode(['success' => false, 'message' => 'Le nom est requis']);
            exit;
        }
        
        $code = 'CLT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $result = $this->db->insert('clients', [
            'code_client' => $code,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'type_client' => 'particulier',
            'est_actif' => 1,
            'date_creation' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'code' => $code]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
        exit;
    }
}