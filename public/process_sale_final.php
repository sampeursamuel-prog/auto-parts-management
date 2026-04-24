<?php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Vérifier la connexion
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Non authentifié');
    }
    
    // Connexion à la base de données - CORRIGÉ AVEC LE BON NOM
    $pdo = new PDO('mysql:host=localhost;dbname=autoparts_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lire les données
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (!$data || empty($data['cart'])) {
        throw new Exception('Données invalides ou panier vide');
    }
    
    $cart = $data['cart'];
    $total = floatval($data['total']);
    $received = floatval($data['received']);
    $change = floatval($data['change']);
    $paymentMethod = $data['payment_method'];
    $clientId = !empty($data['client_id']) ? intval($data['client_id']) : null;
    
    // Calculs
    $totalHT = $total / 1.18;
    $totalTVA = $total - $totalHT;
    $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $pdo->beginTransaction();
    
    // Insertion vente
    $sql = "INSERT INTO ventes (numero_facture, id_user, id_client, type_vente, devise, 
            montant_total_ht, montant_tva, montant_total_ttc, montant_final, 
            montant_recu, monnaie_rendue, mode_paiement, statut, date_vente) 
            VALUES (?, ?, ?, 'caisse', 'HTG', ?, ?, ?, ?, ?, ?, ?, 'complete', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $invoiceNumber, $_SESSION['user_id'], $clientId,
        $totalHT, $totalTVA, $total, $total, $received, $change, $paymentMethod
    ]);
    
    $saleId = $pdo->lastInsertId();
    
    // Insertion détails
    foreach ($cart as $item) {
        $prixHT = floatval($item['price']) / 1.18;
        $stmt = $pdo->prepare("INSERT INTO details_vente (id_vente, id_produit, quantite, prix_unitaire_ht, tva) 
                               VALUES (?, ?, ?, ?, 18)");
        $stmt->execute([$saleId, $item['id'], $item['quantity'], $prixHT]);
        
        // Mise à jour stock
        $stmt = $pdo->prepare("UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id_produit = ?");
        $stmt->execute([$item['quantity'], $item['id']]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'invoice_number' => $invoiceNumber,
        'sale_id' => $saleId,
        'total' => $total,
        'received' => $received,
        'change' => $change
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}