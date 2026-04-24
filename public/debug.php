<?php
// Fichier: public/debug.php
require_once dirname(__DIR__) . '/app/Config/database.php';
require_once dirname(__DIR__) . '/app/Models/Inventory.php';
require_once dirname(__DIR__) . '/app/Models/Magasin.php';

use App\Config\Database;
use App\Models\Inventory;
use App\Models\Magasin;

session_start();

echo "<h1>Diagnostic de la structure</h1>";
echo "<p>Chemin APP: " . dirname(__DIR__) . "</p>";

// Tester la connexion
try {
    $db = Database::getInstance();
    echo "✅ Connexion BDD réussie<br>";
} catch (Exception $e) {
    die("❌ Erreur: " . $e->getMessage());
}

// Vérifier les tables
$tables = ['inventaire', 'detail_inventaire', 'inventaire_sessions'];
foreach ($tables as $table) {
    $result = $db->fetchOne("SHOW TABLES LIKE '$table'");
    if ($result) {
        echo "✅ Table '$table' existe<br>";
    } else {
        echo "❌ Table '$table' n'existe PAS<br>";
    }
}

// Vérifier le dernier inventaire
$last = $db->fetchOne("SELECT * FROM inventaire ORDER BY id_inventaire DESC LIMIT 1");
if ($last) {
    echo "<h2>Dernier inventaire</h2>";
    echo "<pre>";
    print_r($last);
    echo "</pre>";
    
    // Tester le modèle
    $inventoryModel = new Inventory();
    $inventory = $inventoryModel->getInventoryDetails($last['id_inventaire']);
    
    if ($inventory) {
        echo "✅ getInventoryDetails() fonctionne<br>";
        echo "Numéro: " . $inventory['numero_inventaire'] . "<br>";
        echo "Produits: " . $inventory['stats']['total_products'] . "<br>";
    } else {
        echo "❌ getInventoryDetails() retourne null<br>";
        
        // Vérification directe
        $direct = $db->fetchOne("SELECT COUNT(*) as total FROM detail_inventaire WHERE id_inventaire = ?", [$last['id_inventaire']]);
        echo "Détails en base: " . $direct['total'] . "<br>";
    }
} else {
    echo "❌ Aucun inventaire trouvé<br>";
}

echo "<h2>Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";