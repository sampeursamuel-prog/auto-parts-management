<?php
echo "<pre>";
echo "Liste des bases de données :\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8', 'root', '');
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($databases as $db) {
        echo "- " . $db . "\n";
    }
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
echo "</pre>";