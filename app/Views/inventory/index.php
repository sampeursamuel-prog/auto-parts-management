<?php
// Vue liste des inventaires
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-warehouse"></i> Gestion des inventaires</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvel inventaire
                        </a>
                    </div>
                    
                    <?php if (isset($stats) && $stats && $stats['total_inventories'] > 0): ?>
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total</h6>
                                        <h3><?php echo $stats['total_inventories']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Planifiés</h6>
                                        <h3><?php echo $stats['planifies']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <h6 class="card-title">En cours</h6>
                                        <h3><?php echo $stats['en_cours']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Validés</h6>
                                        <h3><?php echo $stats['valides']; ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Annulés</h6>
                                        <h3><?php echo $stats['annules']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>N° Inventaire</th>
                                    <th>Magasin</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Progression</th>
                                    <th>Date début</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inventories)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun inventaire trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($inventories as $inv): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($inv['numero_inventaire']); ?></td>
                                            <td><?php echo htmlspecialchars($inv['nom_magasin']); ?></td>
                                            <td>
                                                <?php echo $inv['type_inventaire'] == 'complet' ? '<span class="badge bg-primary">Complet</span>' : 
                                                           ($inv['type_inventaire'] == 'cyclique' ? '<span class="badge bg-info">Cyclique</span>' : 
                                                           '<span class="badge bg-secondary">Ciblé</span>'); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $inv['status_class']; ?>">
                                                    <?php echo $inv['status_text']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" style="width: <?php echo $inv['progress']; ?>%">
                                                        <?php echo $inv['progress']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($inv['date_debut'])); ?></td>
                                            <td>
                                                <?php if ($inv['statut'] == 'en_cours'): ?>
                                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_count&id=<?php echo $inv['id_inventaire']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-calculator"></i> Compter
                                                    </a>
                                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_validate&id=<?php echo $inv['id_inventaire']; ?>" 
                                                       class="btn btn-sm btn-success"
                                                       onclick="return confirm('Valider cet inventaire ? Les stocks seront mis à jour.')">
                                                        <i class="fas fa-check"></i> Valider
                                                    </a>
                                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_cancel&id=<?php echo $inv['id_inventaire']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Annuler cet inventaire ?')">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory_show&id=<?php echo $inv['id_inventaire']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </a>
                                                <?php endif; ?>
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