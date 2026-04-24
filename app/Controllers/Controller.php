<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Magasin;
use App\Helpers\Session;
use App\Helpers\Auth;

class UserController
{
    private $userModel;
    private $roleModel;
    private $magasinModel;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->magasinModel = new Magasin();
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
     * Vérifier si l'utilisateur peut gérer un autre utilisateur
     */
    private function canManage($targetUserId)
    {
        return $this->userModel->canManageUser($_SESSION['user_id'], $targetUserId);
    }
    
    /**
     * Liste des utilisateurs du magasin actif
     */
    public function index()
    {
        $this->requirePermission('user_read');
        
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        $users = $this->userModel->getAllWithDetails($currentMagasin ? $currentMagasin['id_magasin'] : null);
        $roles = $this->roleModel->getAll();
        
        include dirname(__DIR__) . '/Views/users/index.php';
    }
    
    /**
     * Formulaire de création d'utilisateur
     */
    public function create()
    {
        $this->requirePermission('user_create');
        
        $currentUser = $this->userModel->find($_SESSION['user_id']);
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        
        // Rôles disponibles selon le rôle du créateur
        $availableRoles = $this->userModel->getAvailableRoles($currentUser['id_role']);
        
        // Managers disponibles selon le rôle cible
        $availableManagers = [];
        if (isset($_GET['role_id'])) {
            $availableManagers = $this->userModel->getAvailableManagers($_GET['role_id'], $currentMagasin['id_magasin']);
        }
        
        include dirname(__DIR__) . '/Views/users/create.php';
    }
    
    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store()
    {
        $this->requirePermission('user_create');
        
        $currentUser = $this->userModel->find($_SESSION['user_id']);
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $id_role = intval($_POST['id_role'] ?? 0);
        $id_manager = intval($_POST['id_manager'] ?? 0);
        
        // Validation
        $errors = [];
        
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if ($this->userModel->usernameExists($username)) $errors[] = "Ce nom d'utilisateur existe déjà";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if ($this->userModel->emailExists($email)) $errors[] = "Cet email existe déjà";
        if (empty($password)) $errors[] = "Le mot de passe est requis";
        if (strlen($password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        if ($password !== $confirm) $errors[] = "Les mots de passe ne correspondent pas";
        if (empty($nom)) $errors[] = "Le nom est requis";
        if ($id_role <= 0) $errors[] = "Le rôle est requis";
        
        // Vérifier que le rôle est disponible pour ce créateur
        $availableRoles = $this->userModel->getAvailableRoles($currentUser['id_role']);
        $roleIds = array_column($availableRoles, 'id_role');
        if (!in_array($id_role, $roleIds)) {
            $errors[] = "Vous ne pouvez pas créer un utilisateur avec ce rôle";
        }
        
        // Vérifier que le manager est valide
        if ($id_manager > 0) {
            $manager = $this->userModel->find($id_manager);
            if (!$manager || !$this->userModel->canManageUser($id_manager, 0)) {
                $errors[] = "Manager invalide";
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . $this->basePath . '/index.php?action=user_create');
            exit;
        }
        
        // Créer l'utilisateur
        $userId = $this->userModel->createUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'telephone' => $telephone,
            'nom' => $nom,
            'prenom' => $prenom,
            'adresse' => $adresse,
            'id_role' => $id_role,
            'id_magasin_attache' => $currentMagasin['id_magasin'],
            'id_manager' => $id_manager ?: null,
            'est_actif' => 1
        ]);
        
        Session::setFlash('success', 'Utilisateur créé avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=users');
        exit;
    }
    
    /**
     * Formulaire de modification d'utilisateur
     */
    public function edit()
    {
        $this->requirePermission('user_update');
        
        $id = intval($_GET['id'] ?? 0);
        $user = $this->userModel->find($id);
        
        if (!$user) {
            Session::setFlash('danger', 'Utilisateur non trouvé');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        // Vérifier les droits
        if (!$this->canManage($id)) {
            Session::setFlash('danger', 'Vous ne pouvez pas modifier cet utilisateur');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        $roles = $this->roleModel->getAll();
        $magasins = $this->magasinModel->getAll();
        
        include dirname(__DIR__) . '/Views/users/edit.php';
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update()
    {
        $this->requirePermission('user_update');
        
        $id = intval($_POST['id'] ?? 0);
        
        // Vérifier les droits
        if (!$this->canManage($id)) {
            Session::setFlash('danger', 'Vous ne pouvez pas modifier cet utilisateur');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $id_role = intval($_POST['id_role'] ?? 0);
        $id_manager = intval($_POST['id_manager'] ?? 0);
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        
        // Validation
        $errors = [];
        
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if ($this->userModel->usernameExists($username, $id)) $errors[] = "Ce nom d'utilisateur existe déjà";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if ($this->userModel->emailExists($email, $id)) $errors[] = "Cet email existe déjà";
        if (empty($nom)) $errors[] = "Le nom est requis";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: ' . $this->basePath . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        // Mettre à jour
        $this->userModel->update($id, [
            'username' => $username,
            'email' => $email,
            'telephone' => $telephone,
            'nom' => $nom,
            'prenom' => $prenom,
            'adresse' => $adresse,
            'id_role' => $id_role,
            'id_manager' => $id_manager ?: null,
            'est_actif' => $est_actif
        ]);
        
        Session::setFlash('success', 'Utilisateur modifié avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=users');
        exit;
    }
    
    /**
     * Supprimer un utilisateur (désactiver)
     */
    public function delete()
    {
        $this->requirePermission('user_delete');
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id == $_SESSION['user_id']) {
            Session::setFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        // Vérifier les droits
        if (!$this->canManage($id)) {
            Session::setFlash('danger', 'Vous ne pouvez pas supprimer cet utilisateur');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        $this->userModel->update($id, ['est_actif' => 0]);
        
        Session::setFlash('success', 'Utilisateur désactivé avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=users');
        exit;
    }
    
    /**
     * Activer un utilisateur
     */
    public function activate()
    {
        $this->requirePermission('user_update');
        
        $id = intval($_GET['id'] ?? 0);
        
        // Vérifier les droits
        if (!$this->canManage($id)) {
            Session::setFlash('danger', 'Vous ne pouvez pas activer cet utilisateur');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        $this->userModel->update($id, ['est_actif' => 1]);
        
        Session::setFlash('success', 'Utilisateur activé avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=users');
        exit;
    }
    
    /**
     * Mon profil
     */
    public function profile()
    {
        $this->requireLogin();
        
        $user = $this->userModel->find($_SESSION['user_id']);
        $role = $this->roleModel->find($user['id_role']);
        $permissions = $this->userModel->getPermissions($_SESSION['user_id']);
        $magasin = $this->magasinModel->find($user['id_magasin_attache']);
        
        include dirname(__DIR__) . '/Views/users/profile.php';
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword()
    {
        $this->requireLogin();
        
        $id = intval($_POST['id'] ?? $_SESSION['user_id']);
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $user = $this->userModel->find($id);
        
        // Vérifier les droits
        if ($id != $_SESSION['user_id'] && !$this->canManage($id)) {
            Session::setFlash('danger', 'Vous ne pouvez pas modifier le mot de passe de cet utilisateur');
            header('Location: ' . $this->basePath . '/index.php?action=users');
            exit;
        }
        
        // Validation
        $errors = [];
        
        if ($id == $_SESSION['user_id']) {
            if (empty($currentPassword) || !password_verify($currentPassword, $user['password'])) {
                $errors[] = "Mot de passe actuel incorrect";
            }
        }
        
        if (empty($newPassword)) $errors[] = "Le nouveau mot de passe est requis";
        if (strlen($newPassword) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        if ($newPassword !== $confirmPassword) $errors[] = "Les mots de passe ne correspondent pas";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: ' . $this->basePath . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        $this->userModel->updatePassword($id, $newPassword);
        
        Session::setFlash('success', 'Mot de passe modifié avec succès');
        header('Location: ' . $this->basePath . '/index.php?action=users');
        exit;
    }
}