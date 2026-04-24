<?php
$title = 'Notifications - Auto-Parts';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-bell"></i> Notifications</h2>
                <div>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=notification_mark_all_read" class="btn btn-outline-primary">
                        <i class="fas fa-check-double"></i> Tout marquer comme lu
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=notification_settings" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-cog"></i> Paramètres
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Notifications -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Vos notifications</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <p>Aucune notification</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notif): ?>
                            <div class="list-group-item <?php echo $notif['est_lue'] ? '' : 'list-group-item-primary'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">
                                            <?php echo htmlspecialchars($notif['titre']); ?>
                                            <?php if (!$notif['est_lue']): ?>
                                                <span class="badge bg-primary ms-2">Nouveau</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if (!$notif['est_lue']): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="markRead(<?php echo $notif['id_notification']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNotif(<?php echo $notif['id_notification']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Alertes système -->
            <div class="card mt-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Alertes système</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($alerts)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <p>Aucune alerte</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($alerts as $alert): ?>
                            <div class="alert alert-<?php echo $alert['niveau'] == 'critical' ? 'danger' : ($alert['niveau'] == 'warning' ? 'warning' : 'info'); ?> alert-dismissible">
                                <strong>
                                    <?php 
                                    $icons = [
                                        'stock_minimum' => '📦',
                                        'stock_rupture' => '⚠️',
                                        'peremption' => '📅',
                                        'caisse_difference' => '💰'
                                    ];
                                    echo $icons[$alert['type_alerte']] ?? '🔔';
                                    ?>
                                </strong>
                                <?php echo htmlspecialchars($alert['message']); ?>
                                <button type="button" class="btn-close" onclick="markAlertRead(<?php echo $alert['id_alerte']; ?>)"></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markRead(id) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=notification_mark_read', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(() => location.reload());
}

function deleteNotif(id) {
    if (confirm('Supprimer cette notification ?')) {
        window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=notification_delete&id=' + id;
    }
}

function markAlertRead(id) {
    fetch('<?php echo \BASE_PATH; ?>/index.php?action=notification_mark_alert_read', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(() => location.reload());
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>