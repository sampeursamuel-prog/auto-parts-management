<?php
session_start();
header('Content-Type: application/json');

// Activer les erreurs pour déboguer
error_reporting(E_ALL);
ini_set('display_errors', 0); // Ne pas afficher les erreurs dans le JSON

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Lire les données
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides ou panier vide']);
    exit;
}

// Simuler une vente réussie
$invoiceNumber = 'FACT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

echo json_encode([
    'success' => true,
    'invoice_number' => $invoiceNumber,
    'sale_id' => rand(1000, 9999),
    'total' => $data['total'] ?? 0,
    'received' => $data['received'] ?? 0,
    'change' => $data['change'] ?? 0
]);