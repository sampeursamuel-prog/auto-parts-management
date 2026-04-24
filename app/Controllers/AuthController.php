<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\User;
use App\Models\Magasin;

class AuthController
{
    private $userModel;
    private $magasinModel;
    private $basePath;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->magasinModel = new Magasin();
        $this->basePath = defined('BASE_PATH') ? BASE_PATH : '/auto-parts-management/public';
    }
    
    private function redirect($path)
    {
        header('Location: ' . $this->basePath . '/index.php?action=' . ltrim($path, '/'));
        exit;
    }
    
    public function login()
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }
        
        include dirname(__DIR__) . '/Views/auth/login.php';
    }
    
    public function doLogin()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            Session::setFlash('danger', 'Veuillez remplir tous les champs');
            $this->redirect('login');
            return;
        }
        
        // Vérifier les identifiants
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            Auth::login($user);
            Session::setFlash('success', 'Bienvenue ' . Auth::name() . ' !');
            $this->redirect('dashboard');
        } else {
            Session::setFlash('danger', 'Identifiants incorrects');
            $this->redirect('login');
        }
    }
    
    public function logout()
    {
        Auth::logout();
        Session::setFlash('success', 'Déconnexion réussie');
        $this->redirect('login');
    }
}