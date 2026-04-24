<?php
namespace App\Services;

use App\Config\Database;

class BarcodeService
{
    private $db;
    private $qrGenerator;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->qrGenerator = new \App\Helpers\QRGenerator();
    }
    
    /**
     * Générer un code-barres EAN-13
     */
    public function generateEAN13($code = null)
    {
        if ($code) {
            return $code;
        }
        
        $prefix = '590';
        $timestamp = date('YmdHis');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        $barcode = $prefix . $timestamp . $random;
        
        // Calculer la somme de contrôle
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $barcode[$i] * ($i % 2 == 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        
        return substr($barcode, 0, 12) . $check;
    }
    
    /**
     * Générer un code-barres Code 128
     */
    public function generateCode128($prefix = 'PROD')
    {
        $timestamp = date('Ymd');
        $random = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return $prefix . '-' . $timestamp . '-' . $random;
    }
    
    /**
     * Générer un code-barres pour un lot
     */
    public function generateLotNumber($productId)
    {
        $prefix = 'LOT';
        $date = date('Ymd');
        $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        return $prefix . '-' . $date . '-' . $random . '-' . $productId;
    }
    
    /**
     * Générer un QR code pour un produit
     */
    public function generateProductQRCode($product)
    {
        $data = [
            'type' => 'product',
            'id' => $product['id_produit'],
            'code' => $product['code_barre'],
            'name' => $product['nom_produit'],
            'price' => $product['prix_vente_ttc']
        ];
        
        return $this->qrGenerator->generateQR(json_encode($data));
    }
    
    /**
     * Générer un QR code pour un lot
     */
    public function generateLotQRCode($lot)
    {
        $data = [
            'type' => 'lot',
            'id' => $lot['id_lot'],
            'number' => $lot['numero_lot'],
            'product_id' => $lot['id_produit'],
            'expiry_date' => $lot['date_peremption']
        ];
        
        return $this->qrGenerator->generateQR(json_encode($data));
    }
    
    /**
     * Décoder un QR code
     */
    public function decodeQRCode($imagePath)
    {
        return $this->qrGenerator->scanQRCode($imagePath);
    }
    
    /**
     * Valider un code-barres EAN-13
     */
    public function validateEAN13($barcode)
    {
        if (!preg_match('/^[0-9]{13}$/', $barcode)) {
            return false;
        }
        
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $barcode[$i] * ($i % 2 == 0 ? 1 : 3);
        }
        $check = (10 - ($sum % 10)) % 10;
        
        return $check == $barcode[12];
    }
    
    /**
     * Générer une étiquette produit
     */
    public function generateProductLabel($product)
    {
        return $this->qrGenerator->generateProductLabel($product);
    }
    
    /**
     * Imprimer un code-barres
     */
    public function printBarcode($barcode, $printerIp = null)
    {
        $printerIp = $printerIp ?: ($_ENV['PRINTER_IP'] ?? '192.168.1.100');
        $printerPort = $_ENV['PRINTER_PORT'] ?? 9100;
        
        $label = "^XA\n";
        $label .= "^FO50,50^BY3^BCN,100,Y,N,N^FD{$barcode}^FS\n";
        $label .= "^FO50,180^A0N,30,30^FD{$barcode}^FS\n";
        $label .= "^XZ\n";
        
        $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        
        if ($fp) {
            fwrite($fp, $label);
            fclose($fp);
            return true;
        }
        
        return false;
    }
}