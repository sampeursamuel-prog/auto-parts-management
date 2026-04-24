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
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
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
    
    private function requireAdmin()
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Session::setFlash('danger', 'Accès réservé aux administrateurs');
            header('Location: ' . \BASE_PATH . '/index.php?action=dashboard');
            exit;
        }
    }
    
    /**
     * Liste des utilisateurs
     */
    public function index()
    {
        $this->requireAdmin();
        
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        $users = $this->userModel->getAllWithDetails($currentMagasin ? $currentMagasin['id_magasin'] : null);
        $roles = $this->roleModel->getAll();
        $stats = $this->userModel->getStats();
        
        $data = [
            'title' => 'Gestion des utilisateurs',
            'users' => $users,
            'roles' => $roles,
            'stats' => $stats
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/users/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Formulaire de création d'utilisateur
     */
    public function create()
    {
        $this->requireAdmin();
        
        $roles = $this->roleModel->getAll();
        $magasins = $this->magasinModel->getAll();
        
        $data = [
            'title' => 'Créer un utilisateur',
            'roles' => $roles,
            'magasins' => $magasins
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/users/create.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Enregistrer un nouvel utilisateur
     */
    public function store()
    {
        $this->requireAdmin();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $id_role = intval($_POST['id_role'] ?? 0);
        $id_magasin_attache = intval($_POST['id_magasin_attache'] ?? 0);
        $id_magasin_defaut = intval($_POST['id_magasin_defaut'] ?? 0);
        $id_manager = !empty($_POST['id_manager']) ? intval($_POST['id_manager']) : null;
        $notification_email = isset($_POST['notification_email']) ? 1 : 0;
        $notification_sms = isset($_POST['notification_sms']) ? 1 : 0;
        $deux_facteurs = isset($_POST['deux_facteurs']) ? 1 : 0;
        
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
        if ($id_magasin_attache <= 0) $errors[] = "Le magasin d'attache est requis";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . \BASE_PATH . '/index.php?action=user_create');
            exit;
        }
        
        // Récupérer le niveau du rôle
        $role = $this->roleModel->find($id_role);
        $role_niveau = $role ? $role['niveau'] : null;
        
        $userId = $this->userModel->createUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'telephone' => $telephone,
            'nom' => $nom,
            'prenom' => $prenom,
            'adresse' => $adresse,
            'id_role' => $id_role,
            'role_niveau' => $role_niveau,
            'id_magasin_attache' => $id_magasin_attache,
            'id_magasin_defaut' => $id_magasin_defaut ?: $id_magasin_attache,
            'id_manager' => $id_manager,
            'est_actif' => 1,
            'notification_email' => $notification_email,
            'notification_sms' => $notification_sms,
            'deux_facteurs' => $deux_facteurs
        ]);
        
        if ($userId) {
            // Ajouter l'utilisateur à la table user_magasin
            $roleMagasin = $this->getRoleMagasinName($id_role);
            $this->userModel->addUserToMagasin($userId, $id_magasin_attache, $roleMagasin);
        }
        
        Session::setFlash('success', 'Utilisateur créé avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=users');
        exit;
    }
    
    /**
     * Obtenir le nom du rôle pour la table user_magasin
     */
    private function getRoleMagasinName($roleId)
    {
        $roles = [
            1 => 'gerant',
            2 => 'gerant',
            3 => 'superviseur',
            4 => 'caissier',
            5 => 'magasinier'
        ];
        return $roles[$roleId] ?? 'caissier';
    }
    
    /**
     * Modifier un utilisateur
     */
    public function edit()
    {
        $this->requireAdmin();
        
        $id = intval($_GET['id'] ?? 0);
        $user = $this->userModel->find($id);
        
        if (!$user) {
            Session::setFlash('danger', 'Utilisateur non trouvé');
            header('Location: ' . \BASE_PATH . '/index.php?action=users');
            exit;
        }
        
        $roles = $this->roleModel->getAll();
        $magasins = $this->magasinModel->getAll();
        $userMagasins = $this->userModel->getUserMagasins($id);
        
        $data = [
            'title' => 'Modifier utilisateur',
            'user' => $user,
            'roles' => $roles,
            'magasins' => $magasins,
            'userMagasins' => $userMagasins
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/users/edit.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update()
    {
        $this->requireAdmin();
        
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $id_role = intval($_POST['id_role'] ?? 0);
        $id_magasin_attache = intval($_POST['id_magasin_attache'] ?? 0);
        $id_magasin_defaut = intval($_POST['id_magasin_defaut'] ?? 0);
        $id_manager = !empty($_POST['id_manager']) ? intval($_POST['id_manager']) : null;
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;
        $notification_email = isset($_POST['notification_email']) ? 1 : 0;
        $notification_sms = isset($_POST['notification_sms']) ? 1 : 0;
        $deux_facteurs = isset($_POST['deux_facteurs']) ? 1 : 0;
        
        $errors = [];
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if ($this->userModel->usernameExists($username, $id)) $errors[] = "Ce nom d'utilisateur existe déjà";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if ($this->userModel->emailExists($email, $id)) $errors[] = "Cet email existe déjà";
        if (empty($nom)) $errors[] = "Le nom est requis";
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            header('Location: ' . \BASE_PATH . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        // Récupérer le niveau du rôle
        $role = $this->roleModel->find($id_role);
        $role_niveau = $role ? $role['niveau'] : null;
        
        $this->userModel->update($id, [
            'username' => $username,
            'email' => $email,
            'telephone' => $telephone,
            'nom' => $nom,
            'prenom' => $prenom,
            'adresse' => $adresse,
            'id_role' => $id_role,
            'role_niveau' => $role_niveau,
            'id_magasin_attache' => $id_magasin_attache,
            'id_magasin_defaut' => $id_magasin_defaut ?: $id_magasin_attache,
            'id_manager' => $id_manager,
            'est_actif' => $est_actif,
            'notification_email' => $notification_email,
            'notification_sms' => $notification_sms,
            'deux_facteurs' => $deux_facteurs
        ]);
        
        // Mettre à jour la table user_magasin
        $roleMagasin = $this->getRoleMagasinName($id_role);
        $this->userModel->removeUserFromMagasin($id, $id_magasin_attache);
        $this->userModel->addUserToMagasin($id, $id_magasin_attache, $roleMagasin);
        
        Session::setFlash('success', 'Utilisateur modifié avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=users');
        exit;
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete()
    {
        $this->requireAdmin();
        
        $id = intval($_GET['id'] ?? 0);
        
        if ($id == Auth::userId()) {
            Session::setFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte');
            header('Location: ' . \BASE_PATH . '/index.php?action=users');
            exit;
        }
        
        $this->userModel->delete($id);
        Session::setFlash('success', 'Utilisateur supprimé avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=users');
        exit;
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword()
    {
        $this->requireLogin();
        
        $id = intval($_POST['id'] ?? Auth::userId());
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $user = $this->userModel->find($id);
        
        if ($id == Auth::userId()) {
            if (empty($currentPassword) || !password_verify($currentPassword, $user['password'])) {
                Session::setFlash('danger', 'Mot de passe actuel incorrect');
                header('Location: ' . \BASE_PATH . '/index.php?action=user_edit&id=' . $id);
                exit;
            }
        }
        
        if (empty($newPassword)) {
            Session::setFlash('danger', 'Le nouveau mot de passe est requis');
            header('Location: ' . \BASE_PATH . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            Session::setFlash('danger', 'Le mot de passe doit contenir au moins 6 caractères');
            header('Location: ' . \BASE_PATH . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        if ($newPassword !== $confirmPassword) {
            Session::setFlash('danger', 'Les mots de passe ne correspondent pas');
            header('Location: ' . \BASE_PATH . '/index.php?action=user_edit&id=' . $id);
            exit;
        }
        
        $this->userModel->updatePassword($id, $newPassword);
        Session::setFlash('success', 'Mot de passe modifié avec succès');
        header('Location: ' . \BASE_PATH . '/index.php?action=users');
        exit;
    }
    
    /**
     * Mon profil
     */
    public function profile()
    {
        $this->requireLogin();
        
        $user = $this->userModel->find(Auth::userId());
        $userMagasins = $this->userModel->getUserMagasins(Auth::userId());
        
        $data = [
            'title' => 'Mon profil',
            'user' => $user,
            'userMagasins' => $userMagasins
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/users/profile.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
}