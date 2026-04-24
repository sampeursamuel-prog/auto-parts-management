<?php
$title = 'Gestion des cotations';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-file-alt" style="color: #667eea;"></i> Gestion des cotations
                    </h2>
                    <p class="text-muted">Gérez vos devis et proformas</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nouvelle cotation
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
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> Total cotations</h5>
                    <h2 class="card-text"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-check-circle"></i> Acceptées</h5>
                    <h2 class="card-text">
                        <?php 
                        $acceptees = 0;
                        foreach ($stats['by_status'] as $s) {
                            if ($s['statut'] == 'accepte' || $s['statut'] == 'transforme_vente') $acceptees += $s['total'];
                        }
                        echo $acceptees;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-hourglass-half"></i> En attente</h5>
                    <h2 class="card-text">
                        <?php 
                        $encours = 0;
                        foreach ($stats['by_status'] as $s) {
                            if ($s['statut'] == 'brouillon' || $s['statut'] == 'envoye') $encours += $s['total'];
                        }
                        echo $encours;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Valeur totale</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_value'], 0, ',', ' '); ?> G</h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=cotations" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <option value="">Tous</option>
                                <option value="brouillon" <?php echo ($_GET['statut'] ?? '') == 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                                <option value="envoye" <?php echo ($_GET['statut'] ?? '') == 'envoye' ? 'selected' : ''; ?>>Envoyé</option>
                                <option value="accepte" <?php echo ($_GET['statut'] ?? '') == 'accepte' ? 'selected' : ''; ?>>Accepté</option>
                                <option value="refuse" <?php echo ($_GET['statut'] ?? '') == 'refuse' ? 'selected' : ''; ?>>Refusé</option>
                                <option value="transforme_vente" <?php echo ($_GET['statut'] ?? '') == 'transforme_vente' ? 'selected' : ''; ?>>Transformé en vente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date début</label>
                            <input type="date" name="date_debut" class="form-control" value="<?php echo $_GET['date_debut'] ?? ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date fin</label>
                            <input type="date" name="date_fin" class="form-control" value="<?php echo $_GET['date_fin'] ?? ''; ?>">
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
    
    <!-- Liste des cotations -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Liste des cotations</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>N° Cotation</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Validité</th>
                                    <th>Articles</th>
                                    <th>Total TTC</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cotations)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-file-alt fa-3x mb-3"></i><br>
                                        Aucune cotation trouvée
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($cotations as $c): ?>
                                <tr>
                                    <td><strong><?php echo $c['numero_cotation']; ?></strong></div>
                                    <td><?php echo date('d/m/Y', strtotime($c['date_cotation'])); ?></div>
                                    <td>
                                        <?php if ($c['client_nom']): ?>
                                            <strong><?php echo htmlspecialchars($c['client_nom']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($c['client_tel']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Client non renseigné</span>
                                        <?php endif; ?>
                                     </div>
                                    <td><?php echo date('d/m/Y', strtotime($c['date_validite'])); ?></div>
                                    <td><?php echo $c['nb_articles']; ?> article(s)</div>
                                    <td><strong><?php echo number_format($c['montant_apres_remise'], 0, ',', ' '); ?> G</strong></div>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $c['statut'] == 'accepte' ? 'success' : 
                                                ($c['statut'] == 'transforme_vente' ? 'info' : 
                                                ($c['statut'] == 'refuse' ? 'danger' : 
                                                ($c['statut'] == 'envoye' ? 'warning' : 'secondary'))); 
                                        ?>">
                                            <?php 
                                            $statuts = [
                                                'brouillon' => '📝 Brouillon',
                                                'envoye' => '📧 Envoyé',
                                                'accepte' => '✅ Accepté',
                                                'refuse' => '❌ Refusé',
                                                'transforme_vente' => '🔄 Transformé'
                                            ];
                                            echo $statuts[$c['statut']] ?? $c['statut'];
                                            ?>
                                        </span>
                                     </div>
                                     <div>
                                        <div class="btn-group">
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_show&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-info" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_print&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-secondary" title="Imprimer">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_export_pdf&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-danger" title="PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <?php if ($c['statut'] == 'brouillon' || $c['statut'] == 'envoye'): ?>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_accept&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Accepter cette cotation ?')" title="Accepter">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_refuse&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Refuser cette cotation ?')" title="Refuser">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($c['statut'] == 'accepte'): ?>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_transform_sale&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('Transformer cette cotation en vente ?')" title="Transformer en vente">
                                                <i class="fas fa-shopping-cart"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=cotation_delete&id=<?php echo $c['id_cotation']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cette cotation ?')" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>