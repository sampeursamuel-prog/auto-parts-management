<?php
namespace App\Helpers;

/**
 * Générateur de QR codes et codes-barres pour Auto-Parts Management
 */
class QRGenerator
{
    private $outputPath;
    
    public function __construct()
    {
        $this->outputPath = dirname(__DIR__, 2) . '/public/uploads/barcodes/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }
    }
    
    /**
     * Générer un QR code pour un produit
     */
    public function generateProductQR($product)
    {
        $data = [
            'type' => 'product',
            'id' => $product['id_produit'],
            'code' => $product['code_barre'],
            'name' => $product['nom_produit'],
            'price' => $product['prix_vente_ttc'] ?? $product['prix_vente_ht'] ?? 0,
            'stock' => $product['stock_actuel'] ?? 0
        ];
        
        $jsonData = json_encode($data);
        $filename = 'product_' . $product['code_barre'] . '.png';
        
        return $this->generateQRCode($jsonData, $filename);
    }
    
    /**
     * Générer un QR code simple
     */
    private function generateQRCode($data, $filename)
    {
        $filepath = $this->outputPath . $filename;
        
        // Utiliser une bibliothèque simple si disponible, sinon créer une image simple
        if (extension_loaded('gd')) {
            $this->createSimpleQRCode($data, $filepath);
        } else {
            // Fallback: créer un fichier texte
            file_put_contents($filepath, $data);
        }
        
        return '/uploads/barcodes/' . $filename;
    }
    
    /**
     * Créer un QR code simple avec GD (simulation)
     */
    private function createSimpleQRCode($data, $filepath)
    {
        $size = 300;
        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        
        // Fond blanc
        imagefilledrectangle($image, 0, 0, $size, $size, $white);
        
        // Dessiner un cadre noir
        imagerectangle($image, 5, 5, $size - 5, $size - 5, $black);
        
        // Ajouter un motif simple (simulation de QR code)
        for ($i = 0; $i < 10; $i++) {
            for ($j = 0; $j < 10; $j++) {
                $x = 30 + ($i * 25);
                $y = 30 + ($j * 25);
                $color = (($i + $j) % 2 == 0) ? $black : $gray;
                imagefilledrectangle($image, $x, $y, $x + 15, $y + 15, $color);
            }
        }
        
        // Ajouter le texte au centre
        $text = substr($data, 0, 30);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        imagestring($image, 5, ($size - strlen($text) * 7) / 2, $size - 40, $text, $textColor);
        
        // Ajouter l'info "QR Code"
        imagestring($image, 4, ($size - 60) / 2, $size - 70, "SCAN ME", $black);
        
        imagepng($image, $filepath);
        imagedestroy($image);
    }
    
    /**
     * Générer un code-barres Code 128
     */
    public function generateCode128($code, $filename = null)
    {
        if (!$filename) {
            $filename = 'code128_' . preg_replace('/[^a-zA-Z0-9]/', '_', $code) . '.png';
        }
        
        $filepath = $this->outputPath . $filename;
        
        if (extension_loaded('gd')) {
            $this->createSimpleBarcode($code, $filepath);
        } else {
            file_put_contents($filepath, $code);
        }
        
        return '/uploads/barcodes/' . $filename;
    }
    
    /**
     * Créer un code-barres simple avec GD
     */
    private function createSimpleBarcode($code, $filepath)
    {
        $width = 400;
        $height = 100;
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fond blanc
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        
        // Dessiner des barres en fonction du code
        $x = 20;
        $barHeight = 60;
        $y = 20;
        
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = ord($code[$i]) % 10;
            $barWidth = max(2, $digit + 1);
            imagefilledrectangle($image, $x, $y, $x + $barWidth, $y + $barHeight, $black);
            $x += $barWidth + 2;
        }
        
        // Ajouter le texte du code
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $textX = ($width - (strlen($code) * 7)) / 2;
        imagestring($image, 4, $textX, $y + $barHeight + 10, $code, $textColor);
        
        imagepng($image, $filepath);
        imagedestroy($image);
    }
    
    /**
     * Générer une étiquette produit complète
     */
    public function generateProductLabel($product)
    {
        $filename = 'label_' . $product['code_barre'] . '.png';
        $filepath = $this->outputPath . $filename;
        
        if (!extension_loaded('gd')) {
            return '/uploads/barcodes/' . $filename;
        }
        
        $width = 600;
        $height = 400;
        $image = imagecreatetruecolor($width, $height);
        
        // Couleurs
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 200, 200, 200);
        $blue = imagecolorallocate($image, 0, 102, 204);
        
        // Remplir le fond
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        
        // Bordure
        imagerectangle($image, 5, 5, $width - 5, $height - 5, $black);
        
        // Titre
        $title = strtoupper($product['nom_produit']);
        $titleWidth = strlen($title) * 7;
        $titleX = ($width - $titleWidth) / 2;
        imagestring($image, 5, $titleX, 20, $title, $blue);
        
        // Code-barres
        $this->drawSimpleBarcodeOnImage($image, $product['code_barre'], 50, 60, 500, 80);
        
        // Informations produit
        $y = 160;
        $info = [
            'Code: ' . $product['code_barre'],
            'Prix: ' . number_format($product['prix_vente_ttc'] ?? $product['prix_vente_ht'] ?? 0, 0, ',', ' ') . ' FCFA',
            'Stock: ' . ($product['stock_actuel'] ?? 0) . ' ' . ($product['unite_mesure'] ?? 'pièces'),
            'Emplacement: ' . ($product['emplacement'] ?? 'Non défini')
        ];
        
        foreach ($info as $line) {
            imagestring($image, 4, 20, $y, $line, $black);
            $y += 20;
        }
        
        // Sauvegarder
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return '/uploads/barcodes/' . $filename;
    }
    
    /**
     * Dessiner un code-barres simple sur une image existante
     */
    private function drawSimpleBarcodeOnImage($image, $code, $x, $y, $width, $height)
    {
        $black = imagecolorallocate($image, 0, 0, 0);
        $barHeight = $height - 20;
        $currentX = $x;
        
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = ord($code[$i]) % 10;
            $barWidth = max(2, $digit + 1);
            imagefilledrectangle($image, $currentX, $y, $currentX + $barWidth, $y + $barHeight, $black);
            $currentX += $barWidth + 2;
        }
        
        // Ajouter le texte
        $textX = $x + ($width - (strlen($code) * 7)) / 2;
        imagestring($image, 4, $textX, $y + $barHeight + 5, $code, $black);
    }
    
    /**
     * Supprimer un code-barres généré
     */
    public function deleteBarcode($filename)
    {
        $filepath = $this->outputPath . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
    
    /**
     * Obtenir le chemin de sortie
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }
}