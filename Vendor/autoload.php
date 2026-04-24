<?php
// Autoloader simple pour TCPDF
spl_autoload_register(function ($class) {
    $prefix = 'TCPDF';
    $base_dir = __DIR__ . '/tecnickcom/tcpdf/';
    
    if (strpos($class, $prefix) === 0) {
        $file = $base_dir . strtolower($class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Inclure TCPDF directement
if (file_exists(__DIR__ . '/tecnickcom/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
}