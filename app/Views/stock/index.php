<?php
$title = 'Gestion des stocks';
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h2><i class="fas fa-warehouse"></i> Gestion des stocks</h2>
                <div class="btn-group flex-wrap gap-1">
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_entry" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nouvelle entrée
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_transfer" class="btn btn-info">
                        <i class="fas fa-exchange-alt"></i> Transfert
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_alerts" class="btn btn-warning">
                        <i class="fas fa-bell"></i> Alertes
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_movements" class="btn btn-secondary">
                        <i class="fas fa-history"></i> Historique
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
                    <h5 class="card-title"><i class="fas fa-boxes"></i> Total produits</h5>
                    <h2 class="card-text"><?php echo $stats['total_products'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Valeur du stock</h5>
                    <h2 class="card-text"><?php echo number_format($stats['total_value'] ?? 0, 0, ',', ' '); ?> G</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-triangle"></i> Stock bas</h5>
                    <h2 class="card-text"><?php echo $stats['low_stock'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-times-circle"></i> Rupture</h5>
                    <h2 class="card-text"><?php echo $stats['out_of_stock'] ?? 0; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alertes rapides -->
    <?php if (!empty($lowStockProducts) || !empty($outOfStockProducts)): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-bell"></i> Alertes stock</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($outOfStockProducts)): ?>
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-times-circle"></i> Rupture de stock :</strong>
                        <?php foreach ($outOfStockProducts as $p): ?>
                        <span class="badge bg-danger me-2"><?php echo htmlspecialchars($p['nom_produit']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($lowStockProducts)): ?>
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-exclamation-triangle"></i> Stock bas :</strong>
                        <?php foreach ($lowStockProducts as $p): ?>
                        <span class="badge bg-warning text-dark me-2">
                            <?php echo htmlspecialchars($p['nom_produit']); ?> (<?php echo $p['stock_actuel']; ?>/<?php echo $p['stock_minimum']; ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Liste des produits -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Liste des produits</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Rechercher un produit par nom ou code-barres...">
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                发展
                                    <th>Code-barres</th>
                                    <th>Produit</th>
                                    <th>Catégorie</th>
                                    <th>Prix achat</th>
                                    <th>Prix vente</th>
                                    <th>Stock</th>
                                    <th>Min.</th>
                                    <th>Emplacement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="fas fa-box-open fa-3x mb-3"></i><br>
                                        Aucun produit en stock
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                <tr class="<?php echo $product['stock_actuel'] <= $product['stock_minimum'] ? ($product['stock_actuel'] == 0 ? 'table-danger' : 'table-warning') : ''; ?>">
                                    <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['nom_categorie'] ?? '-'); ?></td>
                                    <td><?php echo number_format($product['prix_achat_ht'], 0, ',', ' '); ?> G</td>
                                    <td><?php echo number_format($product['prix_vente_ht'], 0, ',', ' '); ?> G</td>
                                    <td class="fw-bold <?php echo $product['stock_actuel'] == 0 ? 'text-danger' : ($product['stock_actuel'] <= $product['stock_minimum'] ? 'text-warning' : ''); ?>">
                                        <?php echo $product['stock_actuel']; ?>
                                    </td>
                                    <td><?php echo $product['stock_minimum']; ?></td>
                                    <td><?php echo htmlspecialchars($product['emplacement'] ?? '-'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="adjustStock(<?php echo $product['id_produit']; ?>, '<?php echo addslashes($product['nom_produit']); ?>')">
                                            <i class="fas fa-edit"></i> Ajuster
                                        </button>
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
    
    <!-- Derniers mouvements -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Derniers mouvements</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th>Type</th>
                                    <th>Quantité</th>
                                    <th>Stock avant</th>
                                    <th>Stock après</th>
                                    <th>Utilisateur</th>
                                    <th>Raison</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movements)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">Aucun mouvement récent</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d/m/Y H:i', strtotime($m['date_mouvement'])); ?></td>
                                    <td><?php echo htmlspecialchars($m['nom_produit']); ?></td>
                                    <td>
                                        <?php
                                        $typeClass = [
                                            'entree' => 'success',
                                            'sortie_vente' => 'danger',
                                            'retour_client' => 'warning',
                                            'retour_fournisseur' => 'info',
                                            'inventaire' => 'primary',
                                            'ajustement' => 'secondary'
                                        ];
                                        $typeLabel = [
                                            'entree' => '➕ Entrée',
                                            'sortie_vente' => '➖ Sortie vente',
                                            'retour_client' => '🔄 Retour client',
                                            'retour_fournisseur' => '📦 Retour fournisseur',
                                            'inventaire' => '📊 Inventaire',
                                            'ajustement' => '⚙️ Ajustement'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $typeClass[$m['type_mouvement']] ?? 'secondary'; ?>">
                                            <?php echo $typeLabel[$m['type_mouvement']] ?? $m['type_mouvement']; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?php echo $m['quantite']; ?></td>
                                    <td><?php echo $m['stock_avant']; ?></td>
                                    <td><?php echo $m['stock_apres']; ?></td>
                                    <td><?php echo htmlspecialchars($m['user_nom'] . ' ' . ($m['user_prenom'] ?? '')); ?></td>
                                    <td><small><?php echo htmlspecialchars($m['raison'] ?? '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-2">
                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=stock_movements" class="btn btn-link">
                            Voir tous les mouvements <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajustement stock -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=stock_adjust">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Ajuster le stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="adjust_product_id">
                    <div class="mb-3">
                        <label>Produit</label>
                        <input type="text" id="adjust_product_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Type d'ajustement</label>
                        <select name="type" class="form-select" required>
                            <option value="entree">➕ Entrée (ajouter au stock)</option>
                            <option value="sortie">➖ Sortie (retirer du stock)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Quantité</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label>Raison</label>
                        <textarea name="raison" class="form-control" rows="2" placeholder="Ex: Inventaire, retour, perte, réapprovisionnement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Valider l'ajustement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adjustStock(id, name) {
    document.getElementById('adjust_product_id').value = id;
    document.getElementById('adjust_product_name').value = name;
    new bootstrap.Modal(document.getElementById('adjustModal')).show();
}

// Recherche en temps réel
const searchInput = document.getElementById('searchProduct');
if (searchInput) {
    searchInput.addEventListener('keyup', function() {
        let search = this.value.toLowerCase();
        let rows = document.querySelectorAll('#productsTable tbody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
}
</script>

<?php include dirname(__DIR__) . '/layouts/footer.php'; ?>