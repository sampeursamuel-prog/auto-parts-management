<?php
namespace App\Controllers;

use App\Models\Magasin;
use App\Models\User;
use App\Helpers\Session;

class MagasinController
{
    private $magasinModel;
    private $userModel;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->magasinModel = new Magasin();
        $this->userModel = new User();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . $this->basePath . '/index.php?action=login');
            exit;
        }
    }
    
    private function requirePermission($permission)
    {
        $this->requireLogin();
        if (!$this->userModel->hasPermission($_SESSION['user_id'], $permission)) {
            Session::setFlash('danger', 'Vous n\'avez pas les droits nécessaires');
            header('Location: ' . $this->basePath . '/index.php?action=dashboard');
            exit;
        }
    }
    
    /**
     * Liste des magasins
     */
    public function index()
    {
        $this->requirePermission('magasin_read');
        
        $magasins = $this->magasinModel->getStats();
        
        include dirname(__DIR__) . '/Views/magasins/index.php';
    }
    
    /**
     * Formulaire de création de magasin
     */
    public function create()
    {
        $this->requirePermission('magasin_create');
        
        include dirname(__DIR__) . '/Views/magasins/create.php';
    }
    
    /**
     * Enregistrer un nouveau magasin
     */
    public function store()
    {
        $this->requirePermission('magasin_create');
        
        $nom_magasin = trim($_POST['nom_magasin'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Données du gérant
        $gerant = [
            'username' => trim($_POST['gerant_username'] ?? ''),
            'password' => $_POST['gerant_password'] ?? '',
            'email' => trim($_POST['gerant_email'] ?? ''),
            'telephone' => trim($_POST['gerant_telephone'] ?? ''),
            'nom' => trim($_POST['gerant_nom'] ?? ''),
            'prenom' => trim($_POST['gerant_prenom'] ?? ''),
            'adresse' => trim($_POST['gerant_adresse'] ?? '')
        ];
        
        $errors = [];
        
        if (empty($nom_magasin)) $errors[] = "Le nom du magasin est requis";
        if (empty($gerant['username'])) $errors[] = "Le nom d'utilisateur du gérant est requis";
        if (empty($gerant['password'])) $errors[] = "Le mot de passe du gérant est requis";
        if (empty($gerant['nom'])) $errors[] = "Le nom du gérant est requis";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: ' . $this->basePath . '/index.php?action=magasin_create');
            exit;
        }
        
        try {
            $magasinId = $this->magasinModel->createMagasin(
                ['nom_magasin' => $nom_magasin, 'adresse' => $adresse, 'ville' => $ville, 'telephone' => $telephone, 'email' => $email],
                $gerant
            );
            
            Session::setFlash('success', 'Magasin créé avec succès');
            header('Location: ' . $this->basePath . '/index.php?action=magasins');
            exit;
            
        } catch (\Exception $e) {
            Session::setFlash('danger', 'Erreur: ' . $e->getMessage());
            header('Location: ' . $this->basePath . '/index.php?action=magasin_create');
            exit;
        }
    }
    
    /**
     * Détails d'un magasin
     */
    public function show()
    {
        $this->requirePermission('magasin_read');
        
        $id = intval($_GET['id'] ?? 0);
        $magasin = $this->magasinModel->find($id);
        
        if (!$magasin) {
            Session::setFlash('danger', 'Magasin non trouvé');
            header('Location: ' . $this->basePath . '/index.php?action=magasins');
            exit;
        }
        
        $employes = $this->userModel->getUsersByMagasin($id);
        $stats = $this->userModel->getStatsByMagasin($id);
        
        include dirname(__DIR__) . '/Views/magasins/show.php';
    }
    
    /**
     * Modifier un magasin
     */
    public function edit()
    {
        $this->requirePermission('magasin_update');
        
        $id = intval($_GET['id'] ?? 0);
        $magasin = $this->magasinModel->find($id);
        
        if (!$magasin) {
            Session::setFlash('danger', 'Magasin non trouvé');
            header('Location: ' . $this->basePath . '/index.php?action=magasins');
            exit;
        }
        
        include dirname(__DIR__) . '/Views/magasins/edit.php';
    }
    
    /**
     * Mettre à jour un magasin
     */
    public function update()
    {
        $this->requirePermission('magasin_update');
        
        $id = intval($_POST['id'] ?? 0);
        $nom_magasin = trim($_POST['nom_magasin'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $ville = trim($_POST['ville'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        
        $this->magasinModel->update($id, [
            'nom_magasin' => $nom_magasin,
            'adresse' => $adresse,
            'ville' => $ville,
            'telephone' => $telephone,
            'email' => $email,
            'est_actif' => $est_actif
        ]);
        
        Session::setFlash('success', 'Magasin modifié avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=magasins');
        exit;
    }
    
    /**
     * Supprimer un magasin
     */
    public function delete()
    {
        $this->requirePermission('magasin_delete');
        
        $id = intval($_GET['id'] ?? 0);
        
        $this->magasinModel->update($id, ['est_actif' => 0]);
        
        Session::setFlash('success', 'Magasin désactivé avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=magasins');
        exit;
    }
}