<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

// Simuler une vente réussie
echo json_encode([
    'success' => true,
    'invoice_number' => 'TEST-' . date('YmdHis'),
    'sale_id' => 999,
    'total' => $_POST['total'] ?? 0,
    'received' => $_POST['received'] ?? 0,
    'change' => $_POST['change'] ?? 0
]);