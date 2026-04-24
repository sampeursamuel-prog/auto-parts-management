<?php
// Vue des paramètres système
?>
<style>
    .settings-card {
        margin-bottom: 25px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 15px;
        overflow: hidden;
    }
    .settings-card .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom: none;
        padding: 15px 20px;
    }
    .settings-card .card-header h5 {
        margin: 0;
        font-weight: 600;
    }
    .settings-card .card-body {
        padding: 25px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    .form-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
    }
    .badge-devise {
        background: #e9ecef;
        color: #495057;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 12px;
    }
    .action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
        }
        .action-buttons .ms-auto {
            margin-left: 0 !important;
        }
    }
</style>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-cog" style="color: #667eea;"></i> Paramètres système
                    </h2>
                    <p class="text-muted">Configurez votre application Auto-Parts</p>
                </div>
                <div>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php $flash = \App\Helpers\Session::getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=settings_update">
        <div class="row">
            <!-- Colonne de gauche -->
            <div class="col-md-6">
                <!-- Général -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-globe me-2"></i> Général</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Nom de la société</label>
                            <input type="text" name="settings[company_name]" class="form-control" 
                                   value="<?php echo htmlspecialchars($params['general']['company_name']['valeur_param'] ?? 'Total Family Multi-Services'); ?>">
                            <small class="form-text">Nom affiché sur les factures et rapports</small>
                        </div>
                        <div class="form-group">
                            <label>Adresse</label>
                            <textarea name="settings[company_address]" class="form-control" rows="2"><?php echo htmlspecialchars($params['general']['company_address']['valeur_param'] ?? 'Rue Principale, Port-au-Prince'); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input type="text" name="settings[company_phone]" class="form-control" 
                                           value="<?php echo htmlspecialchars($params['general']['company_phone']['valeur_param'] ?? '+509 1234 5678'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="settings[company_email]" class="form-control" 
                                           value="<?php echo htmlspecialchars($params['general']['company_email']['valeur_param'] ?? 'contact@totalfamily.ht'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Taux TVA (%)</label>
                                    <input type="number" name="settings[tax_rate]" class="form-control" step="0.01" 
                                           value="<?php echo $params['general']['tax_rate']['valeur_param'] ?? 18; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Devise par défaut</label>
                                    <select name="settings[default_currency]" class="form-select">
                                        <option value="HTG" <?php echo (($params['general']['default_currency']['valeur_param'] ?? 'HTG') == 'HTG') ? 'selected' : ''; ?>>HTG - Gourde</option>
                                        <option value="USD" <?php echo (($params['general']['default_currency']['valeur_param'] ?? 'HTG') == 'USD') ? 'selected' : ''; ?>>USD - Dollar US</option>
                                        <option value="EUR" <?php echo (($params['general']['default_currency']['valeur_param'] ?? 'HTG') == 'EUR') ? 'selected' : ''; ?>>EUR - Euro</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[maintenance_mode]" class="form-check-input" value="true" 
                                       <?php echo (($params['general']['maintenance_mode']['valeur_param'] ?? 'false') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Mode maintenance</label>
                                <small class="form-text d-block">Activez pour restreindre l'accès aux administrateurs uniquement</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Caisse -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Caisse</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[cash_drawer_enabled]" class="form-check-input" value="true"
                                       <?php echo (($params['cash']['cash_drawer_enabled']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Activer le tiroir-caisse</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Port du tiroir-caisse</label>
                            <input type="text" name="settings[cash_drawer_port]" class="form-control" 
                                   value="<?php echo htmlspecialchars($params['cash']['cash_drawer_port']['valeur_param'] ?? 'COM1'); ?>">
                            <small class="form-text">Port série (COM1, COM2, LPT1) ou réseau (9100)</small>
                        </div>
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[require_cashier_login]" class="form-check-input" value="true"
                                       <?php echo (($params['cash']['require_cashier_login']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Obliger la connexion du caissier</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stock -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Stock</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[low_stock_alert]" class="form-check-input" value="true"
                                       <?php echo (($params['stock']['low_stock_alert']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Activer les alertes stock bas</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Seuil alerte stock (%)</label>
                                    <input type="number" name="settings[low_stock_threshold]" class="form-control" 
                                           value="<?php echo $params['stock']['low_stock_threshold']['valeur_param'] ?? 20; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Seuil stock minimum (unité)</label>
                                    <input type="number" name="settings[default_min_stock]" class="form-control" 
                                           value="<?php echo $params['stock']['default_min_stock']['valeur_param'] ?? 5; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[auto_update_stock]" class="form-check-input" value="true"
                                       <?php echo (($params['stock']['auto_update_stock']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Mise à jour automatique des stocks</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonne de droite -->
            <div class="col-md-6">
                <!-- Impression -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-print me-2"></i> Impression</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Type d'imprimante</label>
                            <select name="settings[printer_type]" class="form-select" id="printerType">
                                <option value="browser" <?php echo (($params['impression']['printer_type']['valeur_param'] ?? 'browser') == 'browser') ? 'selected' : ''; ?>>Impression navigateur</option>
                                <option value="network" <?php echo (($params['impression']['printer_type']['valeur_param'] ?? 'browser') == 'network') ? 'selected' : ''; ?>>Imprimante réseau</option>
                                <option value="usb" <?php echo (($params['impression']['printer_type']['valeur_param'] ?? 'browser') == 'usb') ? 'selected' : ''; ?>>Imprimante USB</option>
                            </select>
                        </div>
                        <div id="networkPrinterFields" style="display: <?php echo (($params['impression']['printer_type']['valeur_param'] ?? 'browser') == 'network') ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <label>Adresse IP de l'imprimante</label>
                                <input type="text" name="settings[printer_ip]" class="form-control" 
                                       value="<?php echo htmlspecialchars($params['impression']['printer_ip']['valeur_param'] ?? '192.168.1.100'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Port</label>
                                <input type="text" name="settings[printer_port]" class="form-control" 
                                       value="<?php echo htmlspecialchars($params['impression']['printer_port']['valeur_param'] ?? '9100'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>En-tête du ticket</label>
                            <textarea name="settings[receipt_header]" class="form-control" rows="2"><?php echo htmlspecialchars($params['impression']['receipt_header']['valeur_param'] ?? 'MERCI DE VOTRE VISITE'); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Pied du ticket</label>
                            <textarea name="settings[receipt_footer]" class="form-control" rows="2"><?php echo htmlspecialchars($params['impression']['receipt_footer']['valeur_param'] ?? 'Cet article ne peut être échangé sans ticket'); ?></textarea>
                        </div>
                        <div class="mt-3">
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings_test_print" class="btn btn-info btn-sm">
                                <i class="fas fa-print"></i> Tester l'impression
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Scanner -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i> Scanner</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Type de scanner</label>
                            <select name="settings[scanner_type]" class="form-select">
                                <option value="keyboard" <?php echo (($params['scanner']['scanner_type']['valeur_param'] ?? 'keyboard') == 'keyboard') ? 'selected' : ''; ?>>Clavier (émulation USB)</option>
                                <option value="serial" <?php echo (($params['scanner']['scanner_type']['valeur_param'] ?? 'keyboard') == 'serial') ? 'selected' : ''; ?>>Port série</option>
                                <option value="network" <?php echo (($params['scanner']['scanner_type']['valeur_param'] ?? 'keyboard') == 'network') ? 'selected' : ''; ?>>Scanner réseau</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Port / Adresse</label>
                            <input type="text" name="settings[scanner_port]" class="form-control" 
                                   value="<?php echo htmlspecialchars($params['scanner']['scanner_port']['valeur_param'] ?? 'COM3'); ?>">
                            <small class="form-text">Pour scanner USB: laisser vide ou COMx / IP:port</small>
                        </div>
                        <div class="form-group">
                            <label>Prefixe du code-barres</label>
                            <input type="text" name="settings[barcode_prefix]" class="form-control" 
                                   value="<?php echo htmlspecialchars($params['scanner']['barcode_prefix']['valeur_param'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Suffixe du code-barres</label>
                            <select name="settings[barcode_suffix]" class="form-select">
                                <option value="enter" <?php echo (($params['scanner']['barcode_suffix']['valeur_param'] ?? 'enter') == 'enter') ? 'selected' : ''; ?>>Entrée (Enter)</option>
                                <option value="tab" <?php echo (($params['scanner']['barcode_suffix']['valeur_param'] ?? 'enter') == 'tab') ? 'selected' : ''; ?>>Tabulation</option>
                                <option value="none" <?php echo (($params['scanner']['barcode_suffix']['valeur_param'] ?? 'enter') == 'none') ? 'selected' : ''; ?>>Aucun</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> Notifications</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[notify_low_stock]" class="form-check-input" value="true"
                                       <?php echo (($params['notifications']['notify_low_stock']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Notification stock bas</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[notify_expiring_products]" class="form-check-input" value="true"
                                       <?php echo (($params['notifications']['notify_expiring_products']['valeur_param'] ?? 'false') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Notification produits expirés</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="settings[notify_daily_sales]" class="form-check-input" value="true"
                                       <?php echo (($params['notifications']['notify_daily_sales']['valeur_param'] ?? 'true') == 'true') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Rapport quotidien des ventes</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Taux de change -->
                <div class="card settings-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i> Taux de change</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Devise</th>
                                        <th>Taux (1 devise = ? HTG)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devises as $d): ?>
                                    <tr>
                                        <td><strong><?php echo $d['code']; ?></strong></td>
                                        <td>
                                            <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=settings_update_rate" class="d-flex gap-2">
                                                <input type="hidden" name="code" value="<?php echo $d['code']; ?>">
                                                <input type="number" name="taux" class="form-control form-control-sm" style="width: 120px;" step="0.0001" value="<?php echo $d['taux_htg']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Mettre à jour</button>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if ($d['code'] != 'HTG'): ?>
                                            <span class="badge-devise">1 <?php echo $d['code']; ?> = <?php echo number_format($d['taux_htg'], 2); ?> HTG</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings_update&update_exchange_rate=1" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-sync-alt"></i> Mettre à jour tous les taux (API)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Boutons d'action -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings_reset" class="btn btn-warning" onclick="return confirm('Réinitialiser tous les paramètres ?')">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </a>
                            <div class="ms-auto">
                                <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings_export" class="btn btn-info">
                                    <i class="fas fa-download"></i> Exporter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Formulaire d'import -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Importer une configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=settings_import" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="file" name="config_file" class="form-control" accept=".json" required>
                                <small class="form-text">Fichier JSON exporté depuis les paramètres</small>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-secondary w-100">
                                    <i class="fas fa-upload"></i> Importer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestion de l'affichage des champs imprimante réseau
document.getElementById('printerType').addEventListener('change', function() {
    const networkFields = document.getElementById('networkPrinterFields');
    if (this.value === 'network') {
        networkFields.style.display = 'block';
    } else {
        networkFields.style.display = 'none';
    }
});
</script>