<?php
namespace App\Helpers;

use App\Models\User;

class Auth
{
    private static $user = null;
    private static $permissions = null;
    private static $userModel = null;
    
    /**
     * Initialiser le modèle User
     */
    private static function initUserModel()
    {
        if (self::$userModel === null) {
            self::$userModel = new User();
        }
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function check()
    {
        Session::start();
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Récupérer l'utilisateur connecté
     */
    public static function user()
    {
        if (self::$user === null && self::check()) {
            self::initUserModel();
            self::$user = self::$userModel->find($_SESSION['user_id']);
        }
        return self::$user;
    }
    
    /**
     * Récupérer l'ID de l'utilisateur connecté
     */
    public static function userId()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Récupérer le rôle de l'utilisateur
     */
    public static function role()
    {
        $user = self::user();
        return $user ? $user['id_role'] : null;
    }
    
    /**
     * Récupérer le niveau du rôle de l'utilisateur
     */
    public static function roleLevel()
    {
        $user = self::user();
        return $user ? ($user['role_niveau'] ?? null) : null;
    }
    
    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public static function hasRole($roleId)
    {
        return self::role() == $roleId;
    }
    
    /**
     * Vérifier si l'utilisateur a une permission
     */
    public static function hasPermission($permission)
    {
        if (!self::check()) {
            return false;
        }
        
        // Si les permissions sont déjà en cache
        if (self::$permissions === null) {
            self::initUserModel();
            $permissionsList = self::$userModel->getPermissions(self::userId());
            self::$permissions = array_column($permissionsList, 'nom_permission');
        }
        
        return in_array($permission, self::$permissions);
    }
    
    /**
     * Vérifier si l'utilisateur a plusieurs permissions
     */
    public static function hasAnyPermission($permissions)
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Vérifier si l'utilisateur a toutes les permissions
     */
    public static function hasAllPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Vérifier si l'utilisateur est super admin
     */
    public static function isSuperAdmin()
    {
        return self::hasRole(1);
    }
    
    /**
     * Vérifier si l'utilisateur est admin (super admin ou admin)
     */
    public static function isAdmin()
    {
        return self::hasRole(1) || self::hasRole(2);
    }
    
    /**
     * Vérifier si l'utilisateur est gérant
     */
    public static function isGerant()
    {
        return self::hasRole(2);
    }
    
    /**
     * Vérifier si l'utilisateur est superviseur
     */
    public static function isSupervisor()
    {
        return self::hasRole(3);
    }
    
    /**
     * Vérifier si l'utilisateur est caissier
     */
    public static function isCashier()
    {
        return self::hasRole(4);
    }
    
    /**
     * Vérifier si l'utilisateur est magasinier
     */
    public static function isStoreKeeper()
    {
        return self::hasRole(5);
    }
    
    /**
     * Connecter un utilisateur
     */
    public static function login($user)
    {
        Session::start();
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['nom'] . ' ' . ($user['prenom'] ?? '');
        $_SESSION['user_role'] = $user['id_role'];
        $_SESSION['user_role_niveau'] = $user['role_niveau'] ?? null;
        $_SESSION['user_magasin'] = $user['id_magasin_attache'] ?? null;
        $_SESSION['logged_in'] = true;
        
        // Mettre à jour la dernière connexion
        self::initUserModel();
        self::$userModel->updateLastLogin($user['id_user']);
        
        return true;
    }
    
    /**
     * Déconnecter l'utilisateur
     */
    public static function logout()
    {
        Session::start();
        session_destroy();
        return true;
    }
    
    /**
     * Récupérer le magasin d'attache de l'utilisateur
     */
    public static function getAttachedMagasin()
    {
        $user = self::user();
        return $user ? $user['id_magasin_attache'] : null;
    }
    
    /**
     * Récupérer le nom de l'utilisateur connecté
     */
    public static function name()
    {
        return $_SESSION['user_name'] ?? null;
    }
}