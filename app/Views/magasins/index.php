<?php
$title = 'Gestion des magasins';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-store"></i> Gestion des magasins</h2>
                <?php if (\App\Helpers\Auth::hasPermission('magasin_create')): ?>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=magasin_create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau magasin
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <?php foreach ($magasins as $magasin): ?>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><?php echo htmlspecialchars($magasin['nom_magasin']); ?></h5>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($magasin['ville'] ?? 'Non renseignée'); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($magasin['telephone'] ?? '-'); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($magasin['email'] ?? '-'); ?></p>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="small text-muted">Employés</div>
                            <div class="h4"><?php echo $magasin['nb_employes'] ?? 0; ?></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Produits</div>
                            <div class="h4"><?php echo $magasin['nb_produits'] ?? 0; ?></div>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <div class="small text-muted">Ventes du mois</div>
                        <div class="h5 text-success"><?php echo number_format($magasin['total_ventes_mois'] ?? 0, 0, ',', ' '); ?> G</div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=magasin_show&id=<?php echo $magasin['id_magasin']; ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Détails
                    </a>
                    <?php if (\App\Helpers\Auth::hasPermission('magasin_update')): ?>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=magasin_edit&id=<?php echo $magasin['id_magasin']; ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>