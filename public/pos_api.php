<?php
session_start();
header('Content-Type: application/json');

// Réponse simple pour tester
$response = [
    'success' => true,
    'message' => 'Test réussi',
    'invoice_number' => 'TEST-' . date('YmdHis'),
    'sale_id' => 12345
];

echo json_encode($response);