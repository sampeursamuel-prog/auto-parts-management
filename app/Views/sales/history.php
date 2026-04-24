<?php
$title = 'Historique des ventes';
include dirname(__DIR__) . '/layouts/header.php';

// Récupérer l'historique des ventes
$db = App\Config\Database::getInstance();
$magasinModel = new App\Models\Magasin();
$currentMagasin = $magasinModel->getCurrentMagasin();

$whereMagasin = $currentMagasin ? " AND id_magasin = " . $currentMagasin['id_magasin'] : "";

$ventes = $db->fetchAll(
    "SELECT v.*, 
            CONCAT(u.nom, ' ', u.prenom) as caissier,
            CONCAT(c.nom, ' ', c.prenom) as client_nom,
            (SELECT COUNT(*) FROM details_vente WHERE id_vente = v.id_vente) as nb_articles
     FROM ventes v
     LEFT JOIN users u ON v.id_user = u.id_user
     LEFT JOIN clients c ON v.id_client = c.id_client
     WHERE v.statut = 'complete'" . $whereMagasin . "
     ORDER BY v.date_vente DESC
     LIMIT 200"
);

$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_ventes,
        SUM(montant_total_ttc) as total_ca,
        AVG(montant_total_ttc) as panier_moyen,
        SUM(CASE WHEN mode_paiement = 'cash' THEN montant_total_ttc ELSE 0 END) as total_cash,
        SUM(CASE WHEN mode_paiement = 'card' THEN montant_total_ttc ELSE 0 END) as total_card,
        SUM(CASE WHEN mode_paiement = 'mobile' THEN montant_total_ttc ELSE 0 END) as total_mobile
     FROM ventes 
     WHERE statut = 'complete'" . $whereMagasin
);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-history" style="color: #667eea;"></i> Historique des ventes
                    </h2>
                    <p class="text-muted">Consultez l'historique complet de vos transactions</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=reports_export&type=sales" class="btn btn-success">
                        <i class="fas fa-download"></i> Exporter CSV
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle vente
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Total ventes</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_ventes'] ?? 0, 0, ',', ' '); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Chiffre d'affaires</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_ca'] ?? 0, 0, ',', ' '); ?> G</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-simple"></i> Panier moyen</h5>
                    <h2 class="card-text"><?php echo number_format($stats['panier_moyen'] ?? 0, 0, ',', ' '); ?> G</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-credit-card"></i> Transactions</h5>
                    <h2 class="card-text">
                        💵 <?php echo number_format($stats['total_cash'] ?? 0, 0, ',', ' '); ?> G<br>
                        💳 <?php echo number_format($stats['total_card'] ?? 0, 0, ',', ' '); ?> G<br>
                        📱 <?php echo number_format($stats['total_mobile'] ?? 0, 0, ',', ' '); ?> G
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=sales_history" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_debut" class="form-control" value="<?php echo $_GET['date_debut'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control" value="<?php echo $_GET['date_fin'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mode de paiement</label>
                            <select name="mode_paiement" class="form-select">
                                <option value="">Tous</option>
                                <option value="cash" <?php echo ($_GET['mode_paiement'] ?? '') == 'cash' ? 'selected' : ''; ?>>Espèces</option>
                                <option value="card" <?php echo ($_GET['mode_paiement'] ?? '') == 'card' ? 'selected' : ''; ?>>Carte</option>
                                <option value="mobile" <?php echo ($_GET['mode_paiement'] ?? '') == 'mobile' ? 'selected' : ''; ?>>Mobile Money</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des ventes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Liste des transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Client</th>
                                    <th>Articles</th>
                                    <th>Total TTC</th>
                                    <th>Paiement</th>
                                    <th>Caissier</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ventes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-receipt fa-3x mb-3"></i><br>
                                        Aucune vente enregistrée
                                    </div>
                                 </div>
                                <?php else: ?>
                                <?php foreach ($ventes as $vente): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($vente['date_vente'])); ?></div>
                                    <td><strong><?php echo htmlspecialchars($vente['numero_facture']); ?></strong></div>
                                    <td>
                                        <?php if ($vente['client_nom']): ?>
                                            <?php echo htmlspecialchars($vente['client_nom']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Client non renseigné</span>
                                        <?php endif; ?>
                                     </div>
                                    <td><?php echo $vente['nb_articles']; ?> article(s)</div>
                                    <td class="fw-bold text-primary"><?php echo number_format($vente['montant_total_ttc'], 0, ',', ' '); ?> G</div>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $vente['mode_paiement'] == 'cash' ? 'success' : ($vente['mode_paiement'] == 'card' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php 
                                            $modes = ['cash' => '💰 Espèces', 'card' => '💳 Carte', 'mobile' => '📱 Mobile Money'];
                                            echo $modes[$vente['mode_paiement']] ?? $vente['mode_paiement'];
                                            ?>
                                        </span>
                                     </div>
                                    <td><?php echo htmlspecialchars($vente['caissier']); ?></div>
                                     <div>
                                        <div class="btn-group">
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_invoice&id=<?php echo $vente['id_vente']; ?>" class="btn btn-sm btn-info" title="Voir facture">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                            <button onclick="printInvoice(<?php echo $vente['id_vente']; ?>)" class="btn btn-sm btn-secondary" title="Imprimer">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <?php if (date('Y-m-d', strtotime($vente['date_vente'])) == date('Y-m-d')): ?>
                                            <button onclick="returnSale(<?php echo $vente['id_vente']; ?>)" class="btn btn-sm btn-warning" title="Retour">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                     </div>
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
function printInvoice(id) {
    window.open('<?php echo \BASE_PATH; ?>/index.php?action=sales_invoice&id=' + id + '&print=1', '_blank');
}

function returnSale(id) {
    if (confirm('Effectuer un retour sur cette vente ?')) {
        window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=sales_return&id=' + id;
    }
}

// Recherche dans le tableau
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    let search = this.value.toLowerCase();
    let rows = document.querySelectorAll('#salesTable tbody tr');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
    });
});
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>