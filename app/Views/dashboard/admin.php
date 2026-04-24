<?php
$title = 'Tableau de bord - Administrateur';
include dirname(__DIR__) . '/layouts/header.php';
?>

<style>
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-card h3 {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
        text-transform: uppercase;
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
        padding: 20px;
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
        font-size: 40px;
        margin-bottom: 10px;
        display: block;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="welcome-card" style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                <h2>Bonjour, <?php echo htmlspecialchars($userName); ?> !</h2>
                <p>Bienvenue dans votre espace administrateur. Gérez l'ensemble du système depuis ce tableau de bord.</p>
                <?php if ($currentMagasin): ?>
                <p class="text-muted mt-2">
                    <i class="fas fa-store"></i> Magasin actif : <strong><?php echo htmlspecialchars($currentMagasin['nom_magasin']); ?></strong>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-boxes"></i> Produits</h3>
                <div class="value"><?php echo $stats['total_products'] ?? 0; ?></div>
                <small class="text-muted">en stock</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-shopping-cart"></i> Ventes du jour</h3>
                <div class="value"><?php echo number_format($stats['today_sales'] ?? 0, 0, ',', ' '); ?> G</div>
                <small class="text-muted"><?php echo $stats['today_ventes'] ?? 0; ?> transactions</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-users"></i> Utilisateurs</h3>
                <div class="value"><?php echo $stats['total_users'] ?? 0; ?></div>
                <small class="text-muted">comptes actifs</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3><i class="fas fa-chart-line"></i> CA mensuel</h3>
                <div class="value"><?php echo number_format($stats['monthly_ca'] ?? 0, 0, ',', ' '); ?> G</div>
                <small class="text-muted">ce mois</small>
            </div>
        </div>
    </div>
    
    <!-- Graphiques rapides -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Évolution des ventes (7 jours)</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Répartition par catégorie</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h4>Actions rapides</h4>
            <div class="quick-actions">
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=user_create" class="quick-action">
                    <span class="icon">👤</span>
                    <strong>Ajouter un utilisateur</strong>
                    <small>Créer un nouveau compte</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=magasin_create" class="quick-action">
                    <span class="icon">🏪</span>
                    <strong>Ajouter un magasin</strong>
                    <small>Créer un nouveau point de vente</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=reports_export" class="quick-action">
                    <span class="icon">📊</span>
                    <strong>Exporter rapports</strong>
                    <small>Générer des rapports</small>
                </a>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=settings" class="quick-action">
                    <span class="icon">⚙️</span>
                    <strong>Paramètres</strong>
                    <small>Configuration système</small>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Dernières activités -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Dernières activités</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Détails</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aucune activité récente</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($activity['date_action'])); ?></td>
                                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                    <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique des ventes
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($weekly_labels ?? []); ?>,
        datasets: [{
            label: 'Ventes (G)',
            data: <?php echo json_encode($weekly_values ?? []); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        }
    }
});

// Graphique des catégories
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($category_labels ?? []); ?>,
        datasets: [{
            data: <?php echo json_encode($category_values ?? []); ?>,
            backgroundColor: ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>