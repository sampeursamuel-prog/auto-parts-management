<?php
$title = 'Alertes stock';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-bell"></i> Alertes stock</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <!-- Produits en rupture -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Rupture de stock</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($outOfStock)): ?>
                        <p class="text-muted text-center py-3">Aucun produit en rupture</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-danger">
                                <thead>
                                    <tr>
                                        <th>Code-barres</th>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th>Stock</th>
                                        <th>Emplacement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outOfStock as $p): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($p['code_barre']); ?></code></td>
                                        <td><?php echo htmlspecialchars($p['nom_produit']); ?></td>
                                        <td><?php echo htmlspecialchars($p['nom_categorie'] ?? '-'); ?></td>
                                        <td class="fw-bold text-danger">0</td>
                                        <td><?php echo htmlspecialchars($p['emplacement'] ?? '-'); ?></td>
                                        <td>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_entry&barcode=<?php echo urlencode($p['code_barre']); ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-plus"></i> Réapprovisionner
                                            </a>
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
    </div>
    
    <!-- Produits avec stock bas -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Stock bas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStock)): ?>
                        <p class="text-muted text-center py-3">Aucun produit avec stock bas</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-warning">
                                <thead>
                                    <tr>
                                        <th>Code-barres</th>
                                        <th>Produit</th>
                                        <th>Catégorie</th>
                                        <th>Stock actuel</th>
                                        <th>Stock min</th>
                                        <th>Emplacement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStock as $p): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($p['code_barre']); ?></code></td>
                                        <td><?php echo htmlspecialchars($p['nom_produit']); ?></td>
                                        <td><?php echo htmlspecialchars($p['nom_categorie'] ?? '-'); ?></td>
                                        <td class="fw-bold text-warning"><?php echo $p['stock_actuel']; ?></td>
                                        <td><?php echo $p['stock_minimum']; ?></td>
                                        <td><?php echo htmlspecialchars($p['emplacement'] ?? '-'); ?></td>
                                        <td>
                                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_entry&barcode=<?php echo urlencode($p['code_barre']); ?>" class="btn btn-sm btn-success">
                                                <i class="fas fa-plus"></i> Réapprovisionner
                                            </a>
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
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>