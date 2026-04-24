<?php
$title = 'Paramètres des notifications - Auto-Parts';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-cog"></i> Paramètres des notifications</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=notifications" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=notification_update_settings">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notification_email" id="notificationEmail" 
                                    <?php echo ($user['notification_email'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notificationEmail">
                                    <i class="fas fa-envelope"></i> Recevoir les notifications par email
                                </label>
                            </div>
                            <small class="text-muted">Notifications pour : alertes stock, ventes importantes, etc.</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="notification_sms" id="notificationSms" 
                                    <?php echo ($user['notification_sms'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notificationSms">
                                    <i class="fas fa-phone"></i> Recevoir les notifications par SMS
                                </label>
                            </div>
                            <small class="text-muted">Notifications critiques uniquement</small>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Types de notifications :</strong><br>
                            - 📦 Stock bas : quand un produit atteint son seuil minimum<br>
                            - ⚠️ Rupture de stock : quand un produit n'est plus disponible<br>
                            - 📅 Péremption : lots approchant leur date d'expiration<br>
                            - 💰 Caisse : différences anormales en fin de session
                        </div>
                        
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>