<?php
namespace App\Controllers;

use App\Models\Notification;
use App\Models\Alert;
use App\Models\User;
use App\Helpers\Session;
use App\Helpers\Auth;

class NotificationController
{
    private $notificationModel;
    private $alertModel;
    private $userModel;
    private $basePath = '/auto-parts-management/public';
    
    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->alertModel = new Alert();
        $this->userModel = new User();
    }
    
    private function requireLogin()
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: ' . $this->basePath . '/index.php?action=login');
            exit;
        }
    }
    
    /**
     * Liste des notifications
     */
    public function index()
    {
        $this->requireLogin();
        
        $notifications = $this->notificationModel->getByUser($_SESSION['user_id']);
        $alerts = $this->alertModel->getUnread();
        
        include dirname(__DIR__) . '/Views/notifications/index.php';
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markRead()
    {
        $this->requireLogin();
        
        $id = $_POST['id'] ?? 0;
        
        if ($id) {
            $this->notificationModel->markAsRead($id, $_SESSION['user_id']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID invalide']);
        }
        exit;
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllRead()
    {
        $this->requireLogin();
        
        $this->notificationModel->markAllAsRead($_SESSION['user_id']);
        
        Session::setFlash('success', 'Toutes les notifications ont été marquées comme lues');
        header('Location: ' . $this->basePath . '/index.php?action=notifications');
        exit;
    }
    
    /**
     * Compter les notifications non lues
     */
    public function unreadCount()
    {
        $this->requireLogin();
        
        $count = $this->notificationModel->getUnreadCount($_SESSION['user_id']);
        
        echo json_encode(['count' => $count]);
        exit;
    }
    
    /**
     * Paramètres des notifications
     */
    public function settings()
    {
        $this->requireLogin();
        
        $user = $this->userModel->find($_SESSION['user_id']);
        
        include dirname(__DIR__) . '/Views/notifications/settings.php';
    }
    
    /**
     * Mettre à jour les paramètres des notifications
     */
    public function updateSettings()
    {
        $this->requireLogin();
        
        $notificationEmail = isset($_POST['notification_email']) ? 1 : 0;
        $notificationSms = isset($_POST['notification_sms']) ? 1 : 0;
        
        $this->userModel->update($_SESSION['user_id'], [
            'notification_email' => $notificationEmail,
            'notification_sms' => $notificationSms
        ]);
        
        Session::setFlash('success', 'Paramètres des notifications mis à jour');
        header('Location: ' . $this->basePath . '/index.php?action=notification_settings');
        exit;
    }
    
    /**
     * Supprimer une notification
     */
    public function delete()
    {
        $this->requireLogin();
        
        $id = $_GET['id'] ?? 0;
        
        if ($id) {
            $this->notificationModel->delete($id, $_SESSION['user_id']);
            Session::setFlash('success', 'Notification supprimée');
        }
        
        header('Location: ' . $this->basePath . '/index.php?action=notifications');
        exit;
    }
    
    /**
     * Envoyer une notification (API interne)
     */
    public function send()
    {
        $this->requireLogin();
        
        $userId = $_POST['user_id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $type = $_POST['type'] ?? 'info';
        
        if (empty($title) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Titre et message requis']);
            exit;
        }
        
        $notificationId = $this->notificationModel->create([
            'id_user' => $userId,
            'titre' => $title,
            'message' => $message,
            'type' => $type
        ]);
        
        echo json_encode(['success' => true, 'notification_id' => $notificationId]);
        exit;
    }
    
    /**
     * Créer une alerte système
     */
    public function createAlert()
    {
        $this->requirePermission('user_update');
        
        $type = $_POST['type'] ?? '';
        $message = $_POST['message'] ?? '';
        $niveau = $_POST['niveau'] ?? 'warning';
        
        if (empty($message)) {
            Session::setFlash('danger', 'Message requis');
            header('Location: ' . $this->basePath . '/index.php?action=notifications');
            exit;
        }
        
        $alertId = $this->alertModel->create([
            'type_alerte' => $type,
            'message' => $message,
            'niveau' => $niveau
        ]);
        
        Session::setFlash('success', 'Alerte créée');
        header('Location: ' . $this->basePath . '/index.php?action=notifications');
        exit;
    }
    
    /**
     * Marquer une alerte comme lue
     */
    public function markAlertRead()
    {
        $this->requireLogin();
        
        $id = $_POST['id'] ?? 0;
        
        if ($id) {
            $this->alertModel->markAsRead($id, $_SESSION['user_id']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    /**
     * Supprimer une alerte
     */
    public function deleteAlert()
    {
        $this->requirePermission('user_update');
        
        $id = $_GET['id'] ?? 0;
        
        if ($id) {
            $this->alertModel->delete($id);
            Session::setFlash('success', 'Alerte supprimée');
        }
        
        header('Location: ' . $this->basePath . '/index.php?action=notifications');
        exit;
    }
}