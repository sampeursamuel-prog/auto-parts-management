<?php
echo "<pre>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current dir: " . __DIR__ . "\n";
echo "Parent dir: " . dirname(__DIR__) . "\n";

$paths = [
    dirname(__DIR__) . '/app/Controllers/SaleController.php',
    dirname(__DIR__) . '/App/Controllers/SaleController.php',
    dirname(__DIR__) . '/app/Controllers/',
    dirname(__DIR__) . '/App/Controllers/',
];

foreach ($paths as $path) {
    echo "\n" . $path . " - " . (file_exists($path) ? 'EXISTS' : 'NOT FOUND');
    if (is_dir($path)) {
        echo " (DIRECTORY)";
        $files = scandir($path);
        echo "\n   Files: " . implode(', ', array_diff($files, ['.', '..']));
    }
}
echo "</pre>";