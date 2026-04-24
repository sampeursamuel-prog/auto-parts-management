<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['user_name'] = 'Test';

define('BASE_PATH', '/auto-parts-management/public');
define('ROOT_PATH', dirname(__DIR__) . '/');
define('APP_PATH', ROOT_PATH . 'app/');
define('CONTROLLER_PATH', APP_PATH . 'Controllers/');

require_once APP_PATH . 'Controllers/InventoryController.php';

echo "<h1>Test du contrôleur InventoryController</h1>";
$controller = new App\Controllers\InventoryController();
echo "<p>Contrôleur chargé avec succès</p>";
echo "<p><a href='index.php?action=inventory'>Aller à l'inventaire</a></p>";