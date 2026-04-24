<?php
$title = 'Transfert de stock';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-exchange-alt"></i> Transfert de stock</h2>
                <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-truck"></i> Transférer des produits</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=stock_transfer">
                        <div class="mb-3">
                            <label>Produit</label>
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Sélectionner un produit --</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id_produit']; ?>">
                                    <?php echo htmlspecialchars($p['nom_produit']); ?> (Stock: <?php echo $p['stock_actuel']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label>Quantité à transférer</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label>Magasin destination</label>
                            <select name="to_magasin" class="form-select" required>
                                <option value="">-- Sélectionner un magasin --</option>
                                <?php foreach ($otherMagasins as $m): ?>
                                <option value="<?php echo $m['id_magasin']; ?>">
                                    🏪 <?php echo htmlspecialchars($m['nom_magasin']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label>Raison du transfert</label>
                            <textarea name="raison" class="form-control" rows="2" placeholder="Ex: Réapprovisionnement, commande spéciale..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-exchange-alt"></i> Effectuer le transfert
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>