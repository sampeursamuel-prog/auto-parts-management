<?php
$title = 'Tableau de bord - Caissier';
include dirname(__DIR__) . '/layouts/header.php';
?>

<style>
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        text-align: center;
    }
    .stats-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #333;
    }
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .quick-action {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        transition: transform 0.3s;
    }
    .quick-action:hover {
        transform: translateY(-5px);
        color: white;
    }
    .quick-action .icon {
        font-size: 48px;
        margin-bottom: 15px;
        display: block;
    }
    .session-info {
        background: #e8f0fe;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="welcome-card" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                <h2>Bonjour, <?php echo htmlspecialchars($userName); ?> !</h2>
                <p>Bienvenue dans votre espace caissier. Gérez vos ventes et votre caisse.</p>
            </div>
        </div>
    </div>
    
    <!-- Session de caisse -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="session-info">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <i class="fas fa-cash-register fa-2x me-2"></i>
                        <strong>Session de caisse</strong>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if ($activeSession): ?>
                            <span class="badge bg-success">Session ouverte</span>
                            <span>depuis <?php echo date('d/m/Y H:i', strtotime($activeSession['date_ouverture'])); ?></span>
                            <button class="btn btn-sm btn-warning ms-2" onclick="closeSession()">
                                <i class="fas fa-lock"></i> Fermer la session
                            </button>
                        <?php else: ?>
                            <span class="badge bg-secondary">Session fermée</span>
                            <button class="btn btn-sm btn-primary ms-2" onclick="openSession()">
                                <i class="fas fa-unlock-alt"></i> Ouvrir la session
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($activeSession): ?>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <small>Montant initial</small>
                        <div class="fw-bold"><?php echo number_format($activeSession['montant_initial'], 0, ',', ' '); ?> G</div>
                    </div>
                    <div class="col-md-3">
                        <small>Ventes du jour</small>
                        <div class="fw-bold"><?php echo number_format($activeSession['montant_total_ventes'], 0, ',', ' '); ?> G</div>
                    </div>
                    <div class="col-md-3">
                        <small>Total attendu</small>
                        <div class="fw-bold"><?php echo number_format($activeSession['montant_initial'] + $activeSession['montant_total_ventes'], 0, ',', ' '); ?> G</div>
                    </div>
                    <div class="col-md-3">
                        <small>Statut</small>
                        <div class="fw-bold text-success">En service</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h4>Actions rapides</h4>
            <div class="quick-actions">
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="quick-action">
                    <span class="icon">🛒</span>
                    <strong>Nouvelle vente</strong>
                    <small>Scanner et vendre</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_cart" class="quick-action">
                    <span class="icon">🛍️</span>
                    <strong>Mon panier</strong>
                    <small>Voir le panier en cours</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_history" class="quick-action">
                    <span class="icon">📜</span>
                    <strong>Mes ventes</strong>
                    <small>Historique des ventes</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=products" class="quick-action">
                    <span class="icon">📦</span>
                    <strong>Produits</strong>
                    <small>Consulter le catalogue</small>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistiques personnelles -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <h3><i class="fas fa-chart-line"></i> Mes ventes aujourd'hui</h3>
                <div class="value"><?php echo number_format($my_stats['today_sales'] ?? 0, 0, ',', ' '); ?> G</div>
                <small><?php echo $my_stats['today_count'] ?? 0; ?> transactions</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h3><i class="fas fa-calendar-week"></i> Cette semaine</h3>
                <div class="value"><?php echo number_format($my_stats['week_sales'] ?? 0, 0, ',', ' '); ?> G</div>
                <small><?php echo $my_stats['week_count'] ?? 0; ?> transactions</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h3><i class="fas fa-chart-simple"></i> Moyenne par vente</h3>
                <div class="value"><?php echo number_format($my_stats['avg_sale'] ?? 0, 0, ',', ' '); ?> G</div>
                <small>panier moyen</small>
            </div>
        </div>
    </div>
    
    <!-- Dernières ventes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Mes dernières ventes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Client</th>
                                    <th>Montant</th>
                                    <th>Paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($my_sales)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-receipt fa-3x mb-3"></i><br>
                                        Aucune vente enregistrée aujourd'hui
                                    </div>
                                 </div>
                                <?php else: ?>
                                <?php foreach ($my_sales as $sale): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($sale['date_vente'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($sale['numero_facture']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($sale['client_nom'] ?? 'Client non renseigné'); ?></td>
                                    <td class="fw-bold text-primary"><?php echo number_format($sale['montant_total_ttc'], 0, ',', ' '); ?> G</td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $sale['mode_paiement'] == 'cash' ? 'success' : ($sale['mode_paiement'] == 'card' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php 
                                            $modes = ['cash' => '💰 Espèces', 'card' => '💳 Carte', 'mobile' => '📱 Mobile Money'];
                                            echo $modes[$sale['mode_paiement']] ?? $sale['mode_paiement'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_invoice&id=<?php echo $sale['id_vente']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                    </td>
                                 </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openSession() {
    let montant = prompt('Montant initial en caisse :', '0');
    if (montant !== null) {
        fetch('<?php echo \BASE_PATH; ?>/index.php?action=open_cash_session', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({montant_initial: parseFloat(montant)})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
}

function closeSession() {
    if (confirm('Fermer la session de caisse ?')) {
        let montant = prompt('Montant réel en caisse :', '0');
        if (montant !== null) {
            fetch('<?php echo \BASE_PATH; ?>/index.php?action=close_cash_session', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({montant_reel: parseFloat(montant)})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Session fermée. Différence: ' + data.difference + ' G');
                    location.reload();
                } else {
                    alert('Erreur: ' + data.message);
                }
            });
        }
    }
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>