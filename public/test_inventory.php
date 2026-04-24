<?php
// Fichier: public/test_inventory.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Inventory.php';
require_once __DIR__ . '/../app/Models/Magasin.php';
session_start();

// Simuler un utilisateur connecté pour le test
if (!isset($_SESSION['user_id'])) {
    // Récupérer un utilisateur existant
    try {
        $db = Database::getInstance();
        $user = $db->fetchOne("SELECT id FROM users LIMIT 1");
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            echo "Utilisateur trouvé: ID " . $_SESSION['user_id'] . "<br>";
        } else {
            die("Aucun utilisateur trouvé dans la base de données");
        }
    } catch (Exception $e) {
        die("Erreur: " . $e->getMessage());
    }
}

$inventoryModel = new Inventory();
$magasinModel = new Magasin();

// Récupérer le premier magasin disponible
$magasins = $magasinModel->getAllMagasins();
$magasin = $magasins[0] ?? null;

if (!$magasin) {
    die("Aucun magasin trouvé dans la base de données");
}

echo "<h2>Test de création d'inventaire</h2>";
echo "Magasin: " . $magasin['nom_magasin'] . " (ID: " . $magasin['id_magasin'] . ")<br>";
echo "Utilisateur: " . $_SESSION['user_id'] . "<br><br>";

try {
    // Créer un inventaire
    $inventoryId = $inventoryModel->createInventory(
        $magasin['id_magasin'],
        $_SESSION['user_id'],
        'complet',
        'Test création'
    );
    
    echo "✅ Inventaire créé avec ID: " . $inventoryId . "<br>";
    
    // Récupérer les détails
    $inventory = $inventoryModel->getInventoryDetails($inventoryId);
    
    if ($inventory) {
        echo "✅ Détails récupérés avec succès<br>";
        echo "Numéro: " . $inventory['numero_inventaire'] . "<br>";
        echo "Statut: " . $inventory['statut'] . "<br>";
        echo "Nombre de produits: " . $inventory['stats']['total_products'] . "<br>";
    } else {
        echo "❌ Impossible de récupérer les détails de l'inventaire<br>";
    }
    
    // Vérifier dans la base
    $lastInventory = $inventoryModel->getLastInventory();
    if ($lastInventory) {
        echo "<br><strong>Dernier inventaire en base:</strong><br>";
        echo "ID: " . $lastInventory['id_inventaire'] . "<br>";
        echo "Numéro: " . $lastInventory['numero_inventaire'] . "<br>";
        echo "Date: " . $lastInventory['date_debut'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}