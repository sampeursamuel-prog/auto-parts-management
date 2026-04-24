<?php
// Vue création d'inventaire
?>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Nouvel inventaire</h5>
                </div>
                <div class="card-body">
                    <?php if (!$currentMagasin): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Aucun magasin sélectionné.
                            <a href="<?php echo \BASE_PATH; ?>/index.php?action=dashboard">Sélectionner un magasin</a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Magasin</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentMagasin['nom_magasin']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type d'inventaire</label>
                                <select name="type" class="form-select" required>
                                    <option value="complet">📦 Inventaire complet (tous les produits)</option>
                                    <option value="partiel">🎯 Inventaire partiel (produits sélectionnés)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Informations supplémentaires..."></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory" class="btn btn-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">Créer l'inventaire</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>