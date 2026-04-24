<?php
namespace App\Controllers;

use App\Models\Inventory;
use App\Models\Magasin;
use App\Helpers\Auth;

class InventoryController
{
    private $inventoryModel;
    private $magasinModel;
    
    public function __construct()
    {
        $this->inventoryModel = new Inventory();
        $this->magasinModel = new Magasin();
    }
    
    private function requireLogin()
    {
        if (!Auth::check()) {
            $_SESSION['flash_message'] = 'Veuillez vous connecter';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    private function setFlash($type, $message)
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    /**
     * Liste des inventaires
     */
    public function index()
    {
        $this->requireLogin();
        
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        
        $inventories = $this->inventoryModel->getInventories($currentMagasin['id_magasin'] ?? null);
        $stats = $this->inventoryModel->getStats($currentMagasin['id_magasin'] ?? null);
        
        $data = [
            'title' => 'Gestion des inventaires',
            'currentMagasin' => $currentMagasin,
            'inventories' => $inventories,
            'stats' => $stats
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/inventory/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
   /**
 * Créer un nouvel inventaire
 */
public function create()
{
    $this->requireLogin();
    
    $currentMagasin = $this->magasinModel->getCurrentMagasin();
    
    if (!$currentMagasin) {
        $this->setFlash('danger', 'Aucun magasin actif sélectionné');
        header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $type = $_POST['type'] ?? 'complet';
        $notes = $_POST['notes'] ?? null;
        
        try {
            // Créer l'inventaire
            $inventoryId = $this->inventoryModel->createInventory(
                $currentMagasin['id_magasin'],
                $_SESSION['user_id'],
                $type,
                $notes
            );
            
            // Vérifier que l'inventaire a bien été créé
            if (!$inventoryId) {
                throw new \Exception("L'inventaire n'a pas pu être créé");
            }
            
            // Vérifier que l'inventaire existe dans la base
            $exists = $this->inventoryModel->inventoryExists($inventoryId);
            if (!$exists) {
                throw new \Exception("L'inventaire créé n'existe pas dans la base");
            }
            
            $this->setFlash('success', 'Inventaire créé avec succès. ID: ' . $inventoryId);
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory_count&id=' . $inventoryId);
            exit;
            
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Erreur: ' . $e->getMessage());
            error_log("Erreur création inventaire: " . $e->getMessage());
        }
    }
    
    $data = [
        'title' => 'Créer un inventaire',
        'currentMagasin' => $currentMagasin
    ];
    
    ob_start();
    extract($data);
    include __DIR__ . '/../views/inventory/create.php';
    $content = ob_get_clean();
    include __DIR__ . '/../views/layouts/main.php';
}
    /**
     * Comptage d'inventaire
     */
    public function count()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $inventory = $this->inventoryModel->getInventoryDetails($id);
        
        if (!$inventory) {
            $this->setFlash('danger', 'Inventaire non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
        }
        
        if ($inventory['statut'] != 'en_cours') {
            $this->setFlash('danger', 'Cet inventaire n\'est plus modifiable');
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
        }
        
        $data = [
            'title' => 'Comptage inventaire - ' . $inventory['numero_inventaire'],
            'inventory' => $inventory
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/inventory/count.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Sauvegarder un comptage (AJAX)
     */
    public function saveCount()
    {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $detailId = intval($data['detail_id'] ?? 0);
        $quantity = intval($data['quantity'] ?? 0);
        $notes = $data['notes'] ?? null;
        
        try {
            $this->inventoryModel->saveCount($detailId, $quantity, $_SESSION['user_id'], $notes);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    /**
     * Valider un inventaire
     */
    public function validate()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $inventory = $this->inventoryModel->getInventoryDetails($id);
        
        if (!$inventory) {
            $this->setFlash('danger', 'Inventaire non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
        }
        
        if ($inventory['statut'] != 'en_cours') {
            $this->setFlash('danger', 'Cet inventaire ne peut plus être validé');
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
        }
        
        try {
            $nbCorrections = $this->inventoryModel->validateInventory($id, $_SESSION['user_id']);
            
            if ($nbCorrections > 0) {
                $this->setFlash('success', 'Inventaire validé avec succès. ' . $nbCorrections . ' corrections appliquées.');
            } else {
                $this->setFlash('success', 'Inventaire validé. Aucune correction nécessaire.');
            }
            
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
            
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Erreur: ' . $e->getMessage());
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory_count&id=' . $id);
            exit;
        }
    }
    
    /**
     * Annuler un inventaire
     */
    public function cancel()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        
        try {
            $this->inventoryModel->cancelInventory($id);
            $this->setFlash('success', 'Inventaire annulé avec succès');
        } catch (\Exception $e) {
            $this->setFlash('danger', 'Erreur: ' . $e->getMessage());
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
        exit;
    }
    
    /**
     * Voir les détails d'un inventaire
     */
    public function show()
    {
        $this->requireLogin();
        
        $id = intval($_GET['id'] ?? 0);
        $inventory = $this->inventoryModel->getInventoryDetails($id);
        
        if (!$inventory) {
            $this->setFlash('danger', 'Inventaire non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=inventory');
            exit;
        }
        
        $discrepancies = $this->inventoryModel->getDiscrepancies($id);
        $uncounted = $this->inventoryModel->getUncountedProducts($id);
        
        $data = [
            'title' => 'Détails inventaire - ' . $inventory['numero_inventaire'],
            'inventory' => $inventory,
            'discrepancies' => $discrepancies,
            'uncounted' => $uncounted
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/inventory/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rapport d'inventaire
     */
    public function reports()
    {
        $this->requireLogin();
        
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        
        $inventories = $this->inventoryModel->getInventories($currentMagasin['id_magasin'] ?? null, 100);
        $stats = $this->inventoryModel->getStats($currentMagasin['id_magasin'] ?? null);
        
        $data = [
            'title' => 'Rapports d\'inventaire',
            'currentMagasin' => $currentMagasin,
            'inventories' => $inventories,
            'stats' => $stats
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/inventory/reports.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
}