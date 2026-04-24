<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

echo "<h1>Test TCPDF</h1>";

if (class_exists('TCPDF')) {
    echo "<p style='color:green'>✅ TCPDF est installé !</p>";
    
    // Vérifier si la constante existe, sinon l'afficher autrement
    if (defined('TCPDF_VERSION')) {
        echo "<p>Version: " . TCPDF_VERSION . "</p>";
    } else {
        // Créer une instance pour vérifier
        $pdf = new TCPDF();
        echo "<p>TCPDF est disponible</p>";
    }
} else {
    echo "<p style='color:red'>❌ TCPDF n'est pas installé</p>";
    echo "<p>Exécutez: composer require tecnickcom/tcpdf</p>";
}

echo "<p>Chemin vendor: " . dirname(__DIR__) . '/vendor/' . "</p>";
echo "<p>Fichier tcpdf.php existe: " . (file_exists(dirname(__DIR__) . '/vendor/tecnickcom/tcpdf/tcpdf.php') ? 'OUI' : 'NON') . "</p>";