<?php
// Ne pas inclure ce fichier directement
if (!defined('LAYOUT_LOADED')) {
    return;
}
?>

<style>
    /* ============================================
       FOOTER STYLES
    ============================================ */
    .footer {
        background: white;
        text-align: center;
        padding: 20px;
        color: #666;
        margin-top: 40px;
        border-top: 1px solid #e0e0e0;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.05);
    }
    
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .footer-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .footer-links a {
        color: #667eea;
        text-decoration: none;
        font-size: 14px;
    }
    
    .footer-links a:hover {
        text-decoration: underline;
    }
    
    .copyright {
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
        }
        .footer-links {
            justify-content: center;
        }
    }
</style>

<!-- Notifications Panel -->
<div class="notifications-panel" id="notificationsPanel">
    <div class="notifications-header">
        <i class="fas fa-bell"></i> Notifications
        <span style="float: right; font-size: 12px; cursor: pointer;" onclick="markAllAsRead()">Tout marquer comme lu</span>
    </div>
    <div class="notifications-list" id="notificationsList">
        <!-- Les notifications seront chargées dynamiquement -->
    </div>
</div>

<footer class="footer">
    <div class="footer-content">
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Total Family Multi-Services. Tous droits réservés.
        </div>
        <div class="footer-links">
            <a href="#">À propos</a>
            <a href="#">Confidentialité</a>
            <a href="#">Conditions d'utilisation</a>
            <a href="#">Contact</a>
        </div>
    </div>
</footer>

<style>
    /* Notifications panel styles */
    .notifications-panel {
        position: fixed;
        top: 80px;
        right: 20px;
        width: 380px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 1000;
        display: none;
        max-height: 500px;
        overflow-y: auto;
    }
    
    .notifications-panel.active {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .notifications-header {
        padding: 15px;
        border-bottom: 1px solid #e0e0e0;
        font-weight: bold;
        color: #333;
        position: sticky;
        top: 0;
        background: white;
        z-index: 1;
    }
    
    .notifications-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .notification-item:hover {
        background: #f8f9fa;
    }
    
    .notification-item.unread {
        background: #e3f2fd;
    }
    
    .notification-title {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    
    .notification-message {
        color: #666;
        font-size: 12px;
        margin-top: 5px;
    }
    
    .notification-time {
        color: #999;
        font-size: 10px;
        margin-top: 5px;
    }
    
    .no-notifications {
        text-align: center;
        padding: 40px;
        color: #999;
    }
    
    @media (max-width: 768px) {
        .notifications-panel {
            width: calc(100% - 40px);
            right: 20px;
            left: 20px;
            top: 70px;
        }
    }
</style>

<script>
// Gestion du sidebar
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function toggleSidebar() {
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
}

if (menuToggle) {
    menuToggle.addEventListener('click', toggleSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', toggleSidebar);
}

// Fermer le sidebar avec la touche Echap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('active')) {
        toggleSidebar();
    }
});

// Gestion des notifications
const notificationBtn = document.getElementById('notificationBtn');
const notificationsPanel = document.getElementById('notificationsPanel');
let notifications = [];
let notificationTimeout;

// Charger les notifications depuis le serveur
function loadNotifications() {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=get-notifications', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notifications = data.notifications;
            displayNotifications();
            updateNotificationBadge();
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Afficher les notifications
function displayNotifications() {
    const notificationsList = document.getElementById('notificationsList');
    if (!notificationsList) return;
    
    if (notifications.length === 0) {
        notificationsList.innerHTML = '<div class="no-notifications"><i class="fas fa-bell-slash"></i><br>Aucune notification</div>';
        return;
    }
    
    notificationsList.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.read ? '' : 'unread'}" onclick="markAsRead(${notif.id})">
            <div class="notification-title">${notif.title}</div>
            <div class="notification-message">${notif.message}</div>
            <div class="notification-time">${notif.time_ago}</div>
        </div>
    `).join('');
}

// Mettre à jour le badge
function updateNotificationBadge() {
    const badge = document.getElementById('notificationBadge');
    if (!badge) return;
    
    const unreadCount = notifications.filter(n => !n.read).length;
    if (unreadCount > 0) {
        badge.style.display = 'inline-block';
        badge.textContent = unreadCount;
    } else {
        badge.style.display = 'none';
    }
}

// Marquer une notification comme lue
function markAsRead(notificationId) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=mark-notification-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications(); // Recharger les notifications
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Marquer toutes les notifications comme lues
function markAllAsRead() {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=mark-all-notifications-read', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Ouvrir/Fermer le panneau de notifications
if (notificationBtn) {
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsPanel.classList.toggle('active');
        
        if (notificationsPanel.classList.contains('active')) {
            loadNotifications();
            clearTimeout(notificationTimeout);
            notificationTimeout = setTimeout(() => {
                // Optionnel: marquer comme lues après ouverture
            }, 5000);
        }
    });
}

// Fermer les notifications en cliquant ailleurs
document.addEventListener('click', function(e) {
    if (notificationsPanel && notificationBtn && 
        !notificationsPanel.contains(e.target) && 
        !notificationBtn.contains(e.target)) {
        notificationsPanel.classList.remove('active');
    }
});

// Fonction pour changer de magasin
function switchMagasin() {
    const magasinSelect = document.getElementById('magasinSelect');
    if (!magasinSelect) return;
    
    const magasinId = magasinSelect.value;
    
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=switch-magasin', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'magasin_id=' + magasinId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            console.error('Erreur lors du changement de magasin');
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Rafraîchir les notifications périodiquement
setInterval(() => {
    if (notificationsPanel && !notificationsPanel.classList.contains('active')) {
        loadNotifications();
    }
}, 30000); // Toutes les 30 secondes

// Charger les notifications au démarrage
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadNotifications);
} else {
    loadNotifications();
}
</script>

</body>
</html>