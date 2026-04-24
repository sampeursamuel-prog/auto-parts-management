<?php
session_start();

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__) . DS);
define('APP_PATH', ROOT_PATH . 'app' . DS);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = APP_PATH;
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

echo "<h1>Test du modèle Inventory</h1>";

if (class_exists('App\Models\Inventory')) {
    echo "<p style='color:green'>✅ La classe Inventory existe</p>";
    $inv = new App\Models\Inventory();
    echo "<p>Objet créé avec succès</p>";
} else {
    echo "<p style='color:red'>❌ La classe Inventory n'existe pas</p>";
    echo "<p>Vérifiez que le fichier existe: " . APP_PATH . "Models/Inventory.php</p>";
}