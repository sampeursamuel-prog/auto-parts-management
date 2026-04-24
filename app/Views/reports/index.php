<?php
// Vue principale des rapports
?>
<style>
    .report-card {
        transition: transform 0.3s, box-shadow 0.3s;
        cursor: pointer;
        border: none;
        border-radius: 15px;
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .stat-card {
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>

<div class="container-fluid">
    <?php $flash = \App\Helpers\Session::getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Cartes de navigation rapide -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-primary text-white" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=reports_sales'">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-2"></i>
                    <h5 class="mb-0">Rapport des ventes</h5>
                    <small>Analyser les ventes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-success text-white" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=reports_profits'">
                <div class="card-body text-center">
                    <i class="fas fa-chart-pie fa-3x mb-2"></i>
                    <h5 class="mb-0">Rapport des profits</h5>
                    <small>Analyser la rentabilité</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-info text-white" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=reports_stock'">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-3x mb-2"></i>
                    <h5 class="mb-0">Rapport des stocks</h5>
                    <small>État du stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-warning text-white" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=reports_cash'">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-3x mb-2"></i>
                    <h5 class="mb-0">Rapport de caisse</h5>
                    <small>Mouvements de caisse</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques du jour -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Ventes du jour</h6>
                            <h2 class="mb-0"><?php echo $todaySales['total_ventes']; ?></h2>
                        </div>
                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">CA du jour</h6>
                            <h2 class="mb-0"><?php echo number_format($todaySales['total_htg'], 0, ',', ' '); ?> G</h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">CA du mois</h6>
                            <h2 class="mb-0"><?php echo number_format($monthSales['total_htg'], 0, ',', ' '); ?> G</h2>
                        </div>
                        <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Stock bas</h6>
                            <h2 class="mb-0"><?php echo $stockStats['low_stock']; ?></h2>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique des ventes -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Évolution des ventes (7 derniers jours)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Répartition des paiements</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-primary"></i> Espèces</span>
                            <span><?php echo number_format($todaySales['cash'], 0, ',', ' '); ?> G</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-success"></i> Carte</span>
                            <span><?php echo number_format($todaySales['card'], 0, ',', ' '); ?> G</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-circle text-info"></i> Mobile Money</span>
                            <span><?php echo number_format($todaySales['mobile'], 0, ',', ' '); ?> G</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top produits -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 produits du mois</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th>Code-barres</th>
                                    <th>Quantité vendue</th>
                                    <th>Chiffre d'affaires</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Aucune donnée</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($topProducts as $index => $product): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                                    <td><?php echo $product['quantite_vendue']; ?></td>
                                    <td class="fw-bold text-success"><?php echo number_format($product['chiffre_affaires'], 0, ',', ' '); ?> G</td>
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
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des ventes
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weeklySales['labels']); ?>,
                datasets: [{
                    label: 'Ventes (Gourdes)',
                    data: <?php echo json_encode($weeklySales['values']); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' G';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Graphique des paiements
    const paymentCtx = document.getElementById('paymentChart');
    if (paymentCtx) {
        new Chart(paymentCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Espèces', 'Carte', 'Mobile Money'],
                datasets: [{
                    data: [
                        <?php echo $todaySales['cash']; ?>,
                        <?php echo $todaySales['card']; ?>,
                        <?php echo $todaySales['mobile']; ?>
                    ],
                    backgroundColor: ['#667eea', '#10b981', '#0ea5e9']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>