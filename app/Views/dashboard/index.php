<?php
// Views/dashboard/index.php
// Ce fichier contient le contenu du tableau de bord
?>

<style>
    .stat-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
    .trend-up { color: #10b981; }
    .trend-down { color: #ef4444; }
    .badge-payment { font-size: 0.8rem; padding: 5px 10px; }
</style>

<div class="container-fluid">
    <!-- Statistiques Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Ventes du jour</h6>
                            <h2 class="mb-0"><?php echo $todaySales['total_ventes'] ?? 0; ?></h2>
                            <?php if (isset($evolution) && $evolution != 0): ?>
                                <small class="text-white-50">
                                    <i class="fas fa-arrow-<?php echo $evolution > 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo $evolution > 0 ? '+' : ''; ?><?php echo round($evolution, 1); ?>% vs hier
                                </small>
                            <?php endif; ?>
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
                            <h2 class="mb-0"><?php echo number_format($todaySales['total_htg'] ?? 0, 0, ',', ' '); ?> G</h2>
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
                            <h2 class="mb-0"><?php echo number_format($monthSales['total_htg'] ?? 0, 0, ',', ' '); ?> G</h2>
                            <?php if (isset($monthEvolution) && $monthEvolution != 0): ?>
                                <small class="text-white-50">
                                    <i class="fas fa-arrow-<?php echo $monthEvolution > 0 ? 'up' : 'down'; ?>"></i>
                                    <?php echo $monthEvolution > 0 ? '+' : ''; ?><?php echo round($monthEvolution, 1); ?>% vs mois dernier
                                </small>
                            <?php endif; ?>
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
                            <h2 class="mb-0"><?php echo $stockStats['low_stock'] ?? 0; ?></h2>
                            <small class="text-white-50">
                                <?php echo $stockStats['out_of_stock'] ?? 0; ?> produits en rupture
                            </small>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Évolution des ventes (7 derniers jours)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Répartition des paiements</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-primary"></i> Espèces</span>
                            <span class="fw-bold"><?php echo number_format($todaySales['cash'] ?? 0, 0, ',', ' '); ?> G</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="fas fa-circle text-success"></i> Carte</span>
                            <span class="fw-bold"><?php echo number_format($todaySales['card'] ?? 0, 0, ',', ' '); ?> G</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="fas fa-circle text-info"></i> Mobile Money</span>
                            <span class="fw-bold"><?php echo number_format($todaySales['mobile'] ?? 0, 0, ',', ' '); ?> G</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span><strong>Total</strong></span>
                            <span><strong><?php echo number_format($todaySales['total_htg'] ?? 0, 0, ',', ' '); ?> G</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top produits et Dernières ventes -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i> Top 5 produits du mois</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Produit</th>
                                    <th>Réf.</th>
                                    <th>Qté</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Aucune vente ce mois-ci
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($topProducts as $index => $product): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($product['nom_produit']); ?></td>
                                    <td><?php echo htmlspecialchars($product['reference'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $product['quantite_vendue']; ?></span>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?php echo number_format($product['chiffre_affaires'], 0, ',', ' '); ?> G
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Dernières ventes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Facture</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentSales)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Aucune vente récente
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=view_sale&id=<?php echo $sale['id_vente']; ?>">
                                            <?php echo htmlspecialchars($sale['numero_facture']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($sale['nom_client']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($sale['date_vente'])); ?></td>
                                    <td class="fw-bold"><?php echo number_format($sale['montant_total_ttc'], 0, ',', ' '); ?> G</td>
                                    <td>
                                        <?php
                                        $badgeClass = 'secondary';
                                        if ($sale['mode_paiement'] == 'cash') $badgeClass = 'success';
                                        elseif ($sale['mode_paiement'] == 'card') $badgeClass = 'primary';
                                        elseif ($sale['mode_paiement'] == 'mobile') $badgeClass = 'info';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?> badge-payment">
                                            <?php echo $sale['mode_paiement']; ?>
                                        </span>
                                    </td>
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
                    label: 'Ventes (G)',
                    data: <?php echo json_encode($weeklySales['values']); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString() + ' G';
                            }
                        }
                    }
                },
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
                        <?php echo $todaySales['cash'] ?? 0; ?>,
                        <?php echo $todaySales['card'] ?? 0; ?>,
                        <?php echo $todaySales['mobile'] ?? 0; ?>
                    ],
                    backgroundColor: ['#667eea', '#10b981', '#0ea5e9'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return context.label + ': ' + context.raw.toLocaleString() + ' G (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>