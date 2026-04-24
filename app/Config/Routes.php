<?php
namespace App\Config;

class Routes
{
    public static function getRoutes()
    {
        return [
            // Authentification
            '' => ['AuthController', 'login'],
            'login' => ['AuthController', 'login'],
            'doLogin' => ['AuthController', 'doLogin'],
            'logout' => ['AuthController', 'logout'],
            
            // Dashboard
            'dashboard' => ['DashboardController', 'index'],
            
            // Produits
            'products' => ['ProductController', 'index'],
            'product_add' => ['ProductController', 'add'],
            'product_edit' => ['ProductController', 'edit'],
            'product_update' => ['ProductController', 'update'],
            'product_delete' => ['ProductController', 'delete'],
            'product_get' => ['ProductController', 'get'],
            'product_scan' => ['ProductController', 'scan'],
        ];
    }
}