<?php
// debug_inventory.php
require_once 'config/database.php';
session_start();

$db = Database::getInstance();

// 1. Vérifier le dernier inventaire créé
echo "<h2>Dernier inventaire créé</h2>";
$last = $db->fetchOne("SELECT * FROM inventaire ORDER BY id_inventaire DESC LIMIT 1");
if ($last) {
    echo "<pre>";
    print_r($last);
    echo "</pre>";
    
    // 2. Vérifier les détails
    $details = $db->fetchAll("SELECT COUNT(*) as total FROM detail_inventaire WHERE id_inventaire = ?", [$last['id_inventaire']]);
    echo "Nombre de détails: " . $details[0]['total'] . "<br>";
    
    // 3. Tester getInventoryDetails
    require_once 'app/Models/Inventory.php';
    $inventoryModel = new Inventory();
    $inventory = $inventoryModel->getInventoryDetails($last['id_inventaire']);
    
    if ($inventory) {
        echo "✅ getInventoryDetails fonctionne<br>";
        echo "Numéro: " . $inventory['numero_inventaire'] . "<br>";
        echo "Statut: " . $inventory['statut'] . "<br>";
    } else {
        echo "❌ getInventoryDetails retourne null<br>";
    }
} else {
    echo "Aucun inventaire trouvé<br>";
}

// 4. Vérifier la session
echo "<h2>Session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";