<?php
namespace App\Services;

use App\Models\User;
use App\Models\Alert;
use App\Models\Notification;
use App\Config\Mailer;
use App\Config\Database;

class NotificationService
{
    private $db;
    private $userModel;
    private $alertModel;
    private $notificationModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->alertModel = new Alert();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendNotification($userId, $title, $message, $type = 'info')
    {
        // Enregistrer dans la base de données
        $this->notificationModel->create([
            'id_user' => $userId,
            'titre' => $title,
            'message' => $message,
            'type' => $type
        ]);
        
        // Récupérer les préférences de l'utilisateur
        $user = $this->userModel->find($userId);
        
        // Envoyer par email si activé
        if ($user['notification_email']) {
            $this->sendEmail($user['email'], $title, $message);
        }
        
        // Envoyer par SMS si activé
        if ($user['notification_sms'] && !empty($user['telephone'])) {
            $this->sendSMS($user['telephone'], $title . ': ' . $message);
        }
        
        return true;
    }
    
    /**
     * Envoyer une notification à tous les administrateurs
     */
    public function sendToAdmins($title, $message, $type = 'info')
    {
        $admins = $this->db->fetchAll(
            "SELECT u.* FROM users u
             JOIN roles r ON u.id_role = r.id_role
             WHERE r.niveau <= 2 AND u.est_actif = 1"
        );
        
        foreach ($admins as $admin) {
            $this->sendNotification($admin['id_user'], $title, $message, $type);
        }
        
        return count($admins);
    }
    
    /**
     * Envoyer une alerte de stock bas
     */
    public function sendLowStockAlert($productId, $productName, $currentStock)
    {
        $title = '⚠️ Alerte stock bas';
        $message = "Le produit '{$productName}' a atteint un stock de {$currentStock} unités.";
        
        // Créer une alerte dans le système
        $this->alertModel->createLowStockAlert($productId, $productName, $currentStock);
        
        // Envoyer aux administrateurs
        return $this->sendToAdmins($title, $message, 'warning');
    }
    
    /**
     * Envoyer une alerte de rupture de stock
     */
    public function sendOutOfStockAlert($productId, $productName)
    {
        $title = '❌ Rupture de stock';
        $message = "Le produit '{$productName}' est en rupture de stock.";
        
        $this->alertModel->createOutOfStockAlert($productId, $productName);
        
        return $this->sendToAdmins($title, $message, 'critical');
    }
    
    /**
     * Envoyer une alerte de péremption
     */
    public function sendExpiryAlert($lotId, $productName, $expiryDate)
    {
        $title = '📅 Péremption proche';
        $message = "Le lot {$lotId} du produit '{$productName}' expire le {$expiryDate}.";
        
        $this->alertModel->createExpiryAlert($lotId, $productName, $expiryDate);
        
        return $this->sendToAdmins($title, $message, 'warning');
    }
    
    /**
     * Envoyer un rapport de fin de journée
     */
    public function sendDailyReport($magasinId, $date)
    {
        // Récupérer les statistiques du jour
        $stats = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total_ventes,
                SUM(montant_total_ttc) as chiffre_affaires,
                COUNT(DISTINCT id_user) as nb_caissiers
             FROM ventes 
             WHERE DATE(date_vente) = ? AND id_magasin = ? AND statut = 'complete'",
            [$date, $magasinId]
        );
        
        $title = "Rapport quotidien - " . date('d/m/Y', strtotime($date));
        $message = "Ventes: {$stats['total_ventes']}\n";
        $message .= "CA: " . number_format($stats['chiffre_affaires'], 0, ',', ' ') . " G\n";
        $message .= "Caissiers actifs: {$stats['nb_caissiers']}";
        
        return $this->sendToAdmins($title, $message, 'info');
    }
    
    /**
     * Envoyer un email
     */
    private function sendEmail($to, $subject, $message)
    {
        try {
            Mailer::send($to, $subject, nl2br($message), $message);
            return true;
        } catch (\Exception $e) {
            error_log("Erreur envoi email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoyer un SMS
     */
    private function sendSMS($phone, $message)
    {
        // API SMS à configurer selon le fournisseur
        // Exemple avec Twilio ou autre service
        $apiKey = $_ENV['SMS_API_KEY'] ?? '';
        
        if (empty($apiKey)) {
            return false;
        }
        
        // Simulation d'envoi
        error_log("SMS envoyé à {$phone}: {$message}");
        
        return true;
    }
    
    /**
     * Vérifier les stocks et envoyer des alertes
     */
    public function checkStockAlerts()
    {
        $lowStockProducts = $this->db->fetchAll(
            "SELECT * FROM produits 
             WHERE est_actif = 1 AND stock_actuel <= stock_minimum"
        );
        
        foreach ($lowStockProducts as $product) {
            if ($product['stock_actuel'] == 0) {
                $this->sendOutOfStockAlert($product['id_produit'], $product['nom_produit']);
            } else {
                $this->sendLowStockAlert($product['id_produit'], $product['nom_produit'], $product['stock_actuel']);
            }
        }
        
        return count($lowStockProducts);
    }
    
    /**
     * Vérifier les lots qui expirent bientôt
     */
    public function checkExpiryAlerts()
    {
        $expiringLots = $this->db->fetchAll(
            "SELECT l.*, p.nom_produit 
             FROM lots_produits l
             JOIN produits p ON l.id_produit = p.id_produit
             WHERE l.date_peremption IS NOT NULL 
             AND l.date_peremption <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             AND l.quantite_actuelle > 0"
        );
        
        foreach ($expiringLots as $lot) {
            $this->sendExpiryAlert(
                $lot['numero_lot'],
                $lot['nom_produit'],
                date('d/m/Y', strtotime($lot['date_peremption']))
            );
        }
        
        return count($expiringLots);
    }
    
    /**
     * Envoyer une notification de vente importante
     */
    public function sendLargeSaleAlert($saleId, $amount, $clientName = null)
    {
        if ($amount >= 100000) { // Seuil à 100k HTG
            $title = "💰 Vente importante";
            $message = "Une vente de " . number_format($amount, 0, ',', ' ') . " HTG a été enregistrée.";
            if ($clientName) {
                $message .= " Client: {$clientName}";
            }
            
            return $this->sendToAdmins($title, $message, 'success');
        }
        
        return false;
    }
}