<?php
// Vue liste des clients
?>
<style>
    .stat-card {
        transition: transform 0.3s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .client-row:hover {
        background: #f8f9fa;
        cursor: pointer;
    }
    .fidelity-points {
        font-size: 18px;
        font-weight: bold;
        color: #f59e0b;
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
                            <h6 class="mb-0">Total clients</h6>
                            <h2 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Clients actifs</h6>
                            <h2 class="mb-0"><?php echo $stats['actifs'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-user-check fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Points distribués</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['total_points'] ?? 0, 0, ',', ' '); ?></h2>
                        </div>
                        <i class="fas fa-star fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">CA total</h6>
                            <h2 class="mb-0"><?php echo number_format($stats['ca_total'] ?? 0, 0, ',', ' '); ?> G</h2>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> Liste des clients</h5>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=customer_create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nouveau client
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Barre de recherche -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher par nom, téléphone, email..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" onclick="searchCustomers()">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <button class="btn btn-secondary" onclick="resetSearch()">
                            <i class="fas fa-sync-alt"></i> Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <?php if (empty($clients)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <p>Aucun client trouvé</p>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=customer_create" class="btn btn-primary">
                        Ajouter un client
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Nom complet</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Dernier achat</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                            <tr class="client-row" onclick="window.location='<?php echo \BASE_PATH; ?>/index.php?action=customer_show&id=<?php echo $client['id_client']; ?>'">
                                <td><code><?php echo htmlspecialchars($client['code_client']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['nom']); ?></strong>
                                    <?php if ($client['prenom']): ?>
                                        <br><small><?php echo htmlspecialchars($client['prenom']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['telephone']): ?>
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['telephone']); ?><br>
                                    <?php endif; ?>
                                    <?php if ($client['email']): ?>
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $client['type_client'] == 'professionnel' ? 'bg-info' : 'bg-secondary'; ?>">
                                        <?php echo $client['type_client'] == 'professionnel' ? 'Pro' : 'Particulier'; ?>
                                    </span>
                                 </td>
                                <td class="fidelity-points">
                                    <?php echo number_format($client['points_fidelite'], 0, ',', ' '); ?>
                                    <i class="fas fa-star" style="font-size: 12px;"></i>
                                 </td>
                                <td>
                                    <?php echo $client['date_dernier_achat'] ? date('d/m/Y', strtotime($client['date_dernier_achat'])) : '-'; ?>
                                 </td>
                                <td>
                                    <?php if ($client['est_actif']): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                 </td>
                                <td>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=customer_edit&id=<?php echo $client['id_client']; ?>" 
                                       class="btn btn-sm btn-primary" onclick="event.stopPropagation()">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($client['est_actif']): ?>
                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=customer_delete&id=<?php echo $client['id_client']; ?>" 
                                       class="btn btn-sm btn-danger" onclick="event.stopPropagation(); return confirm('Supprimer ce client ?')">
                                        <i class="fas fa-trash"></i>
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
function searchCustomers() {
    let search = document.getElementById('searchInput').value;
    window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=customers&search=' + encodeURIComponent(search);
}

function resetSearch() {
    window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=customers';
}

document.getElementById('searchInput')?.addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        searchCustomers();
    }
});
</script>