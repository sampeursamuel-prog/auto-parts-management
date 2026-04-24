<?php
namespace App\Controllers;

use App\Models\Client;
use App\Models\Magasin;
use App\Helpers\Session;
use App\Helpers\Auth;

class ClientController
{
    private $clientModel;
    private $magasinModel;
    
    public function __construct()
    {
        $this->clientModel = new Client();
        $this->magasinModel = new Magasin();
    }
    
    private function requireLogin()
    {
        if (!Auth::check()) {
            Session::setFlash('danger', 'Veuillez vous connecter');
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    private function requirePermission($permission)
    {
        $this->requireLogin();
        if (!Auth::hasPermission($permission)) {
            Session::setFlash('danger', 'Vous n\'avez pas les droits nécessaires');
            header('Location: ' . \BASE_PATH . '/index.php?action=dashboard');
            exit;
        }
    }
    
    /**
     * Liste des clients
     */
    public function index()
    {
        $this->requirePermission('sale_read');
        
        $search = $_GET['search'] ?? '';
        $clients = $this->clientModel->getAll($search);
        $stats = $this->clientModel->getStats();
        
        $data = [
            'title' => 'Gestion des clients',
            'clients' => $clients,
            'stats' => $stats,
            'search' => $search
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/customers/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Formulaire de création de client
     */
    public function create()
    {
        $this->requirePermission('sale_create');
        
        $data = [
            'title' => 'Nouveau client'
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/customers/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Enregistrer un nouveau client
     */
    public function store()
    {
        $this->requirePermission('sale_create');
        
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $plaque = trim($_POST['plaque_immatriculation'] ?? '');
        $type_client = $_POST['type_client'] ?? 'particulier';
        
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($telephone) && empty($email)) $errors[] = "Au moins un moyen de contact est requis";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . \BASE_PATH . '/index.php?action=customer_create');
            exit;
        }
        
        $clientId = $this->clientModel->createClient([
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email,
            'adresse' => $adresse,
            'plaque_immatriculation' => $plaque,
            'type_client' => $type_client,
            'points_fidelite' => 0,
            'est_actif' => 1
        ]);
        
        Session::setFlash('success', 'Client créé avec succès. Code: ' . $this->clientModel->generateCode());
        header('Location: ' . \BASE_PATH . '/index.php?action=customers');
        exit;
    }
    
    /**
     * Modifier un client
     */
    public function edit()
    {
        $this->requirePermission('sale_update');
        
        $id = intval($_GET['id'] ?? 0);
        $client = $this->clientModel->getById($id);
        
        if (!$client) {
            Session::setFlash('danger', 'Client non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=customers');
            exit;
        }
        
        $data = [
            'title' => 'Modifier client',
            'client' => $client
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/customers/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Mettre à jour un client
     */
    public function update()
    {
        $this->requirePermission('sale_update');
        
        $id = intval($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $plaque = trim($_POST['plaque_immatriculation'] ?? '');
        $type_client = $_POST['type_client'] ?? 'particulier';
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        
        $this->clientModel->update($id, [
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email,
            'adresse' => $adresse,
            'plaque_immatriculation' => $plaque,
            'type_client' => $type_client,
            'est_actif' => $est_actif
        ]);
        
        Session::setFlash('success', 'Client modifié avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=customers');
        exit;
    }
    
    /**
     * Supprimer un client (désactiver)
     */
    public function delete()
    {
        $this->requirePermission('sale_delete');
        
        $id = intval($_GET['id'] ?? 0);
        $this->clientModel->update($id, ['est_actif' => 0]);
        
        Session::setFlash('success', 'Client supprimé avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=customers');
        exit;
    }
    
    /**
     * Détails d'un client
     */
    public function show()
    {
        $this->requirePermission('sale_read');
        
        $id = intval($_GET['id'] ?? 0);
        $client = $this->clientModel->getById($id);
        
        if (!$client) {
            Session::setFlash('danger', 'Client non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=customers');
            exit;
        }
        
        $historique = $this->clientModel->getPurchaseHistory($id);
        
        $data = [
            'title' => 'Détails client - ' . $client['nom'] . ' ' . ($client['prenom'] ?? ''),
            'client' => $client,
            'historique' => $historique
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/customers/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Rechercher des clients (AJAX)
     */
    public function search()
    {
        $this->requireLogin();
        
        $term = $_GET['term'] ?? '';
        $clients = $this->clientModel->search($term);
        
        header('Content-Type: application/json');
        echo json_encode($clients);
        exit;
    }
    
    /**
     * Ajouter des points de fidélité
     */
    public function addPoints()
    {
        $this->requirePermission('sale_update');
        
        $id = intval($_POST['id'] ?? 0);
        $points = intval($_POST['points'] ?? 0);
        
        if ($points > 0) {
            $this->clientModel->updatePoints($id, $points);
            Session::setFlash('success', "$points points ajoutés au client");
        } else {
            Session::setFlash('danger', 'Points invalides');
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=customer_show&id=' . $id);
        exit;
    }
}