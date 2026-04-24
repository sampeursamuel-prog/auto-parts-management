<?php
// Rapport des ventes
?>
<style>
    .summary-card {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .filter-bar {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Rapport des ventes</h2>
        <div>
            <button class="btn btn-success" onclick="exportCSV()">
                <i class="fas fa-file-excel"></i> Exporter CSV
            </button>
            <button class="btn btn-danger" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=reports_sales" class="row">
            <div class="col-md-4">
                <label class="form-label">Date début</label>
                <input type="date" name="date_start" class="form-control" value="<?php echo $dateStart; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_end" class="form-control" value="<?php echo $dateEnd; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Résumé -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="summary-card">
                <h6>Total ventes</h6>
                <h2><?php echo $summary['total_ventes']; ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <h6>Chiffre d'affaires</h6>
                <h2><?php echo number_format($summary['total_htg'], 0, ',', ' '); ?> G</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <h6>Panier moyen</h6>
                <h2><?php echo number_format($summary['panier_moyen'], 0, ',', ' '); ?> G</h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <h6>Répartition</h6>
                <small>💰 Espèces: <?php echo number_format($summary['cash'], 0, ',', ' '); ?> G<br>
                💳 Carte: <?php echo number_format($summary['card'], 0, ',', ' '); ?> G<br>
                📱 Mobile: <?php echo number_format($summary['mobile'], 0, ',', ' '); ?> G</small>
            </div>
        </div>
    </div>

    <!-- Tableau des ventes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Liste des ventes</h5>
        </div>
        <div class="card-body">
            <?php if (empty($sales)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                    <p>Aucune vente trouvée pour cette période</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="salesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Facture</th>
                                <th>Montant TTC</th>
                                <th>Mode paiement</th>
                                <th>Caissier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($sale['date_vente'])); ?></td>
                                <td><code><?php echo htmlspecialchars($sale['numero_facture']); ?></code></td>
                                <td class="fw-bold text-success"><?php echo number_format($sale['montant_total_ttc'], 0, ',', ' '); ?> G</td>
                                <td>
                                    <span class="badge <?php echo $sale['mode_paiement'] == 'especes' ? 'bg-primary' : ($sale['mode_paiement'] == 'carte' ? 'bg-success' : 'bg-info'); ?>">
                                        <?php echo $sale['mode_paiement'] == 'especes' ? '💰 Espèces' : ($sale['mode_paiement'] == 'carte' ? '💳 Carte' : '📱 Mobile Money'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($sale['caissier']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total:</th>
                                <th class="text-success"><?php echo number_format($summary['total_htg'], 0, ',', ' '); ?> G</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportCSV() {
    window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=reports_export&type=sales&date_start=' + 
        document.querySelector('input[name="date_start"]').value + 
        '&date_end=' + document.querySelector('input[name="date_end"]').value;
}
</script>