<?php
// Liste des factures
?>
<style>
    .stat-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .filter-bar {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .invoice-row:hover {
        background: #f8f9fa;
        cursor: pointer;
    }
</style>

<div class="container-fluid">
    <?php $flash = \App\Helpers\Session::getFlash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total factures</h6>
                            <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                        </div>
                        <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Montant total</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_amount'], 0, ',', ' '); ?> G</h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Payées</h6>
                            <h2 class="mb-0"><?php echo $stats['paid']; ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Annulées</h6>
                            <h2 class="mb-0"><?php echo $stats['cancelled']; ?></h2>
                        </div>
                        <i class="fas fa-times-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=invoices" class="row">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                    <option value="complete" <?php echo $status == 'complete' ? 'selected' : ''; ?>>Payées</option>
                    <option value="annulee" <?php echo $status == 'annulee' ? 'selected' : ''; ?>>Annulées</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-secondary w-100" onclick="resetFilters()">Réinitialiser</button>
            </div>
        </form>
    </div>

    <!-- Liste des factures -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Liste des factures</h5>
        </div>
        <div class="card-body">
            <?php if (empty($invoices)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                    <p>Aucune facture trouvée</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>N° Facture</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Montant TTC</th>
                                <th>Mode paiement</th>
                                <th>Statut</th>
                                <th>Caissier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                            <tr class="invoice-row" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=invoice_show&id=<?php echo $inv['id_vente']; ?>'">
                                <td><code><?php echo htmlspecialchars($inv['numero_facture']); ?></code></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($inv['date_vente'])); ?></td>
                                <td>
                                    <?php if ($inv['client_nom']): ?>
                                        <?php echo htmlspecialchars($inv['client_nom'] . ' ' . ($inv['client_prenom'] ?? '')); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Client comptoir</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success"><?php echo number_format($inv['montant_total_ttc'], 0, ',', ' '); ?> G</td>
                                <td>
                                    <span class="badge <?php echo $inv['mode_paiement'] == 'especes' ? 'bg-primary' : ($inv['mode_paiement'] == 'carte' ? 'bg-success' : 'bg-info'); ?>">
                                        <?php echo $inv['mode_paiement'] == 'especes' ? '💰 Espèces' : ($inv['mode_paiement'] == 'carte' ? '💳 Carte' : '📱 Mobile'); ?>
                                    </span>
                                 </td>
                                <td>
                                    <?php if ($inv['statut'] == 'complete'): ?>
                                        <span class="badge bg-success">Payée</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Annulée</span>
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo htmlspecialchars($inv['caissier']); ?></td>
                                <td>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=invoice_print&id=<?php echo $inv['id_vente']; ?>" 
                                       class="btn btn-sm btn-secondary" onclick="event.stopPropagation()" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if ($inv['statut'] == 'complete'): ?>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=invoice_cancel&id=<?php echo $inv['id_vente']; ?>" 
                                       class="btn btn-sm btn-danger" onclick="event.stopPropagation(); return confirm('Annuler cette facture ?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function resetFilters() {
    window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=invoices';
}
</script>