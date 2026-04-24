<?php
namespace App\Controllers;

use App\Models\Setting;
use App\Models\Magasin;
use App\Models\User;
use App\Helpers\Session;
use App\Helpers\Auth;

class SettingController
{
    private $settingModel;
    private $magasinModel;
    private $userModel;
    
    public function __construct()
    {
        $this->settingModel = new Setting();
        $this->magasinModel = new Magasin();
        $this->userModel = new User();
    }
    
    private function requireLogin()
    {
        if (!Auth::check()) {
            Session::setFlash('danger', 'Veuillez vous connecter');
            header('Location: ' . \BASE_PATH . '/index.php?action=login');
            exit;
        }
    }
    
    private function requireAdmin()
    {
        $this->requireLogin();
        if (!Auth::isAdmin()) {
            Session::setFlash('danger', 'Accès réservé aux administrateurs');
            header('Location: ' . \BASE_PATH . '/index.php?action=dashboard');
            exit;
        }
    }
    
    /**
     * Page des paramètres
     */
    public function index()
    {
        $this->requireAdmin();
        
        // Initialiser les paramètres par défaut si nécessaire
        $this->settingModel->initDefaults();
        
        $params = $this->settingModel->getGrouped();
        $currentMagasin = $this->magasinModel->getCurrentMagasin();
        $devises = $this->settingModel->getDevises();
        
        $data = [
            'title' => 'Paramètres système',
            'params' => $params,
            'currentMagasin' => $currentMagasin,
            'devises' => $devises
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/settings/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/main.php';
    }
    
    /**
     * Sauvegarder les paramètres
     */
    public function update()
    {
        $this->requireAdmin();
        
        $settings = $_POST['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            $this->settingModel->set($key, $value);
        }
        
        // Mettre à jour le taux de change si demandé
        if (isset($_POST['update_exchange_rate'])) {
            $newRate = $this->settingModel->updateExchangeRates();
            Session::setFlash('success', 'Taux de change mis à jour : 1 USD = ' . $newRate . ' HTG');
        } else {
            Session::setFlash('success', 'Paramètres sauvegardés avec succès');
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=settings');
        exit;
    }
    
    /**
     * Réinitialiser les paramètres par défaut
     */
    public function reset()
    {
        $this->requireAdmin();
        
        $this->settingModel->initDefaults();
        Session::setFlash('success', 'Paramètres réinitialisés avec succès');
        
        header('Location: ' . \BASE_PATH . '/index.php?action=settings');
        exit;
    }
    
    /**
     * Mettre à jour le taux de change d'une devise
     */
    public function updateExchangeRate()
    {
        $this->requireAdmin();
        
        $code = $_POST['code'] ?? '';
        $taux = floatval($_POST['taux'] ?? 0);
        
        if ($code && $taux > 0) {
            $this->settingModel->updateDeviseRate($code, $taux);
            Session::setFlash('success', 'Taux de change mis à jour : 1 ' . $code . ' = ' . $taux . ' HTG');
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=settings');
        exit;
    }
    
    /**
     * Sauvegarder la configuration de l'imprimante
     */
    public function savePrinter()
    {
        $this->requireAdmin();
        
        $printerType = $_POST['printer_type'] ?? 'network';
        $printerIp = $_POST['printer_ip'] ?? '';
        $printerPort = $_POST['printer_port'] ?? '9100';
        
        $this->settingModel->set('printer_type', $printerType);
        $this->settingModel->set('printer_ip', $printerIp);
        $this->settingModel->set('printer_port', $printerPort);
        
        Session::setFlash('success', 'Configuration imprimante sauvegardée');
        header('Location: ' . \BASE_PATH . '/index.php?action=settings');
        exit;
    }
    
    /**
     * Tester l'impression
     */
    public function testPrint()
    {
        $this->requireAdmin();
        
        $printerType = $this->settingModel->get('printer_type', 'network');
        $printerIp = $this->settingModel->get('printer_ip', '');
        $printerPort = $this->settingModel->get('printer_port', '9100');
        $companyName = $this->settingModel->get('company_name', 'Total Family Multi-Services');
        $companyAddress = $this->settingModel->get('company_address', 'Rue Principale, Port-au-Prince');
        $companyPhone = $this->settingModel->get('company_phone', '+509 1234 5678');
        
        // Générer le ticket de test
        $testTicket = "=" . str_repeat("=", 32) . "\n";
        $testTicket .= "     " . strtoupper($companyName) . "\n";
        $testTicket .= "=" . str_repeat("=", 32) . "\n";
        $testTicket .= "     TEST D'IMPRESSION\n";
        $testTicket .= str_repeat("-", 32) . "\n";
        $testTicket .= "Date: " . date('d/m/Y H:i:s') . "\n";
        $testTicket .= "Magasin: " . ($this->magasinModel->getCurrentMagasin()['nom_magasin'] ?? 'Principal') . "\n";
        $testTicket .= str_repeat("-", 32) . "\n";
        $testTicket .= "Type d'imprimante: " . ucfirst($printerType) . "\n";
        $testTicket .= "Adresse IP: " . ($printerIp ?: 'Non configurée') . "\n";
        $testTicket .= "Port: " . ($printerPort ?: 'Non configuré') . "\n";
        $testTicket .= str_repeat("-", 32) . "\n";
        $testTicket .= "Message: Test d'impression réussi !\n";
        $testTicket .= "Si vous voyez ce message,\n";
        $testTicket .= "l'impression fonctionne correctement.\n";
        $testTicket .= str_repeat("-", 32) . "\n";
        $testTicket .= "     MERCI POUR VOTRE TEST\n";
        $testTicket .= str_repeat("=", 32) . "\n";
        
        // Vérifier si une imprimante réseau est configurée
        $printSuccess = false;
        $errorMessage = '';
        
        if ($printerType == 'network' && !empty($printerIp)) {
            $timeout = 3;
            $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, $timeout);
            
            if ($fp) {
                fwrite($fp, $testTicket);
                fclose($fp);
                $printSuccess = true;
                Session::setFlash('success', 'Test d\'impression envoyé avec succès à ' . $printerIp . ':' . $printerPort);
                header('Location: ' . \BASE_PATH . '/index.php?action=settings');
                exit;
            } else {
                $errorMessage = "Impossible de se connecter à l'imprimante réseau: " . $errstr;
            }
        }
        
        // Si l'impression réseau a échoué, afficher la page d'impression navigateur
        $data = [
            'title' => 'Test d\'impression',
            'testTicket' => $testTicket,
            'printerType' => $printerType,
            'printerIp' => $printerIp,
            'printerPort' => $printerPort,
            'errorMessage' => $errorMessage
        ];
        
        ob_start();
        extract($data);
        include __DIR__ . '/../views/settings/test_print.php';
        $content = ob_get_clean();
        include __DIR__ . '/../views/layouts/print_layout.php';
        exit;
    }
    
    /**
     * Exporter la configuration
     */
    public function export()
    {
        $this->requireAdmin();
        
        $settings = $this->settingModel->getAll();
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="config_' . date('Y-m-d') . '.json"');
        
        $export = [];
        foreach ($settings as $s) {
            $export[$s['nom_param']] = [
                'valeur' => $s['valeur_param'],
                'type' => $s['type_param'],
                'description' => $s['description']
            ];
        }
        
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Importer la configuration
     */
    public function import()
    {
        $this->requireAdmin();
        
        if (isset($_FILES['config_file']) && $_FILES['config_file']['error'] == 0) {
            $content = file_get_contents($_FILES['config_file']['tmp_name']);
            $data = json_decode($content, true);
            
            if ($data) {
                foreach ($data as $key => $value) {
                    if (is_array($value) && isset($value['valeur'])) {
                        $this->settingModel->set($key, $value['valeur']);
                    } else {
                        $this->settingModel->set($key, $value);
                    }
                }
                Session::setFlash('success', 'Configuration importée avec succès');
            } else {
                Session::setFlash('danger', 'Fichier de configuration invalide');
            }
        } else {
            Session::setFlash('danger', 'Aucun fichier sélectionné');
        }
        
        header('Location: ' . \BASE_PATH . '/index.php?action=settings');
        exit;
    }
}