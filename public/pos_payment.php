<?php
session_start();
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

function sendResponse($success, $message, $extra = []) {
    $response = ['success' => $success, 'message' => $message];
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    echo json_encode($response);
    exit;
}

// Vérifier la session
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Session expirée');
}

// Lire les données
$input = file_get_contents('php://input');
if (empty($input)) {
    sendResponse(false, 'Aucune donnée reçue');
}

$data = json_decode($input, true);
if (!$data || empty($data['cart'])) {
    sendResponse(false, 'Panier vide');
}

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=autoparts_db;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $cart = $data['cart'];
    $total = floatval($data['total']);
    $received = floatval($data['received']);
    $change = floatval($data['change']);
    $paymentMethod = $data['payment_method'];
    $clientId = !empty($data['client_id']) ? intval($data['client_id']) : null;
    
    // Mapping du mode de paiement
    $paymentMap = ['cash' => 'especes', 'card' => 'carte', 'mobile' => 'mobile_money'];
    $dbPayment = $paymentMap[$paymentMethod] ?? 'especes';
    
    // Calculs
    $totalHT = $total / 1.18;
    $totalTVA = $total - $totalHT;
    $invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Créer une session de caisse si nécessaire
    $sessionId = null;
    
    // Vérifier si la table sessions_caisse existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions_caisse'");
    if ($stmt->rowCount() > 0) {
        // Chercher une session active ou en créer une nouvelle
        $stmt = $pdo->prepare("SELECT id_session FROM sessions_caisse WHERE id_user = ? AND statut = 'active' ORDER BY id_session DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            $sessionId = $session['id_session'];
        } else {
            // Créer une nouvelle session de caisse
            $stmt = $pdo->prepare("INSERT INTO sessions_caisse (id_user, date_ouverture, statut) VALUES (?, NOW(), 'active')");
            $stmt->execute([$_SESSION['user_id']]);
            $sessionId = $pdo->lastInsertId();
        }
    }
    
    $pdo->beginTransaction();
    
    // Vérifier les colonnes de la table ventes
    $stmt = $pdo->query("SHOW COLUMNS FROM ventes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construire la requête d'insertion dynamiquement
    $fields = ['numero_facture', 'id_user', 'id_client', 'type_vente', 'statut'];
    $values = [$invoiceNumber, $_SESSION['user_id'], $clientId, 'caisse', 'complete'];
    
    // Ajouter id_session si la colonne existe
    if (in_array('id_session', $columns)) {
        $fields[] = 'id_session';
        $values[] = $sessionId;
    }
    
    if (in_array('devise', $columns)) {
        $fields[] = 'devise';
        $values[] = 'HTG';
    }
    if (in_array('montant_total_ht', $columns)) {
        $fields[] = 'montant_total_ht';
        $values[] = $totalHT;
    }
    if (in_array('montant_tva', $columns)) {
        $fields[] = 'montant_tva';
        $values[] = $totalTVA;
    }
    if (in_array('montant_total_ttc', $columns)) {
        $fields[] = 'montant_total_ttc';
        $values[] = $total;
    }
    if (in_array('montant_final', $columns)) {
        $fields[] = 'montant_final';
        $values[] = $total;
    }
    if (in_array('montant_recu', $columns)) {
        $fields[] = 'montant_recu';
        $values[] = $received;
    }
    if (in_array('monnaie_rendue', $columns)) {
        $fields[] = 'monnaie_rendue';
        $values[] = $change;
    }
    if (in_array('mode_paiement', $columns)) {
        $fields[] = 'mode_paiement';
        $values[] = $dbPayment;
    }
    if (in_array('date_vente', $columns)) {
        $fields[] = 'date_vente';
        $values[] = date('Y-m-d H:i:s');
    }
    
    $placeholders = implode(',', array_fill(0, count($fields), '?'));
    $sql = "INSERT INTO ventes (" . implode(',', $fields) . ") VALUES (" . $placeholders . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    $saleId = $pdo->lastInsertId();
    
    // Insérer les détails et mettre à jour les stocks
    foreach ($cart as $item) {
        $produitId = intval($item['id']);
        $quantite = intval($item['quantity']);
        $prixUnitaireTTC = floatval($item['price']);
        $prixUnitaireHT = $prixUnitaireTTC / 1.18;
        
        // Insérer dans details_vente
        $stmt = $pdo->prepare("INSERT INTO details_vente (id_vente, id_produit, quantite, prix_unitaire_ht, tva) 
                               VALUES (?, ?, ?, ?, 18)");
        $stmt->execute([$saleId, $produitId, $quantite, $prixUnitaireHT]);
        
        // Mettre à jour le stock
        $stmt = $pdo->prepare("UPDATE produits SET stock_actuel = stock_actuel - ? WHERE id_produit = ?");
        $stmt->execute([$quantite, $produitId]);
    }
    
    $pdo->commit();
    
    // Vider le panier de la session
    $_SESSION['cart'] = [];
    
    sendResponse(true, 'Vente effectuée avec succès', [
        'invoice_number' => $invoiceNumber,
        'sale_id' => $saleId,
        'total' => $total,
        'received' => $received,
        'change' => $change
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    sendResponse(false, 'Erreur: ' . $e->getMessage());
}