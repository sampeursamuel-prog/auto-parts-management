<?php
// Vue des rapports d'inventaire
?>
<style>
    .report-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .report-card:hover {
        transform: translateY(-5px);
    }
    .filter-bar {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Rapports d'inventaire</h2>
        <div>
            <button class="btn btn-success" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Exporter Excel
            </button>
            <button class="btn btn-danger" onclick="exportPDF()">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Période</label>
                <select id="periodFilter" class="form-select" onchange="filterReports()">
                    <option value="all">Toutes les périodes</option>
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select id="statusFilter" class="form-select" onchange="filterReports()">
                    <option value="all">Tous</option>
                    <option value="valide">Validés</option>
                    <option value="en_cours">En cours</option>
                    <option value="annule">Annulés</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Référence, créateur..." onkeyup="filterReports()">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100" onclick="refreshReports()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
            </div>
        </div>
    </div>

    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-primary text-white">
                <div class="card-body">
                    <h6 class="mb-0">Total inventaires</h6>
                    <h2 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-success text-white">
                <div class="card-body">
                    <h6 class="mb-0">Taux de validation</h6>
                    <h2 class="mb-0">
                        <?php 
                        $total = $stats['total'] ?? 0;
                        $valide = $stats['valide'] ?? 0;
                        $taux = $total > 0 ? round(($valide / $total) * 100) : 0;
                        echo $taux; ?>%
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-warning text-white">
                <div class="card-body">
                    <h6 class="mb-0">Total écarts</h6>
                    <h2 class="mb-0"><?php echo $stats['corrections'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card report-card bg-info text-white">
                <div class="card-body">
                    <h6 class="mb-0">Valeur des écarts</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['valeur_ecarts'] ?? 0, 0, ',', ' '); ?> G</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des inventaires -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history"></i> Historique des inventaires</h5>
        </div>
        <div class="card-body">
            <?php if (empty($inventories)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                    <p>Aucun inventaire trouvé</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="reportsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Type</th>
                                <th>Date création</th>
                                <th>Date validation</th>
                                <th>Statut</th>
                                <th>Créé par</th>
                                <th>Produits</th>
                                <th>Écarts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventories as $inv): ?>
                            <tr data-status="<?php echo $inv['statut']; ?>" data-date="<?php echo $inv['date_creation']; ?>">
                                <td><strong><?php echo htmlspecialchars($inv['reference']); ?></strong></td>
                                <td><?php echo $inv['type'] == 'complet' ? '📦 Complet' : '🎯 Partiel'; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($inv['date_creation'])); ?></td>
                                <td><?php echo $inv['date_validation'] ? date('d/m/Y', strtotime($inv['date_validation'])) : '-'; ?></td>
                                <td>
                                    <span class="badge <?php echo $inv['statut'] == 'valide' ? 'bg-success' : ($inv['statut'] == 'en_cours' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo $inv['statut'] == 'valide' ? 'Validé' : ($inv['statut'] == 'en_cours' ? 'En cours' : 'Annulé'); ?>
                                    </span>
                                 </td>
                                <td><?php echo htmlspecialchars($inv['createur_nom']); ?></td>
                                <td><?php echo $inv['total_produits']; ?></td>
                                <td>
                                    <?php if ($inv['nb_ecarts'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $inv['nb_ecarts']; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">0</span>
                                    <?php endif; ?>
                                 </td>
                                <td>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_show&id=<?php echo $inv['id_inventaire']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($inv['statut'] == 'valide'): ?>
                                    <button class="btn btn-sm btn-danger" onclick="printReport(<?php echo $inv['id_inventaire']; ?>)">
                                        <i class="fas fa-print"></i>
                                    </button>
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
function filterReports() {
    let period = document.getElementById('periodFilter').value;
    let status = document.getElementById('statusFilter').value;
    let search = document.getElementById('searchInput').value.toLowerCase();
    let rows = document.querySelectorAll('#reportsTable tbody tr');
    
    rows.forEach(row => {
        let show = true;
        let rowStatus = row.dataset.status;
        let rowDate = new Date(row.dataset.date);
        let now = new Date();
        
        // Filtre période
        if (period !== 'all') {
            if (period === 'month') {
                if (rowDate.getMonth() !== now.getMonth() || rowDate.getFullYear() !== now.getFullYear()) show = false;
            } else if (period === 'quarter') {
                let quarter = Math.floor(now.getMonth() / 3);
                let rowQuarter = Math.floor(rowDate.getMonth() / 3);
                if (rowQuarter !== quarter || rowDate.getFullYear() !== now.getFullYear()) show = false;
            } else if (period === 'year') {
                if (rowDate.getFullYear() !== now.getFullYear()) show = false;
            }
        }
        
        // Filtre statut
        if (status !== 'all' && rowStatus !== status) show = false;
        
        // Filtre recherche
        if (search && !row.textContent.toLowerCase().includes(search)) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

function refreshReports() {
    location.reload();
}

function exportExcel() {
    let table = document.getElementById('reportsTable');
    let html = table.outerHTML;
    let blob = new Blob([html], {type: 'application/vnd.ms-excel'});
    let link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'rapports_inventaire.xls';
    link.click();
}

function exportPDF() {
    window.print();
}

function printReport(id) {
    window.open('<?php echo \BASE_PATH; ?>/index.php?action=inventory_show&id=' + id + '&print=1', '_blank');
}
</script>

<style media="print">
    .filter-bar, .btn, .report-card, .card-header .btn {
        display: none !important;
    }
    body {
        background: white;
        padding: 20px;
    }
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
</style>