<?php
// Rapport des stocks
?>
<style>
    .stock-value {
        font-size: 24px;
        font-weight: bold;
        color: #667eea;
    }
    .low-stock {
        background-color: #fff3cd;
    }
    .category-card {
        transition: transform 0.3s;
    }
    .category-card:hover {
        transform: translateY(-3px);
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> Rapport des stocks</h2>
        <div>
            <button class="btn btn-success" onclick="exportCSV()">
                <i class="fas fa-file-excel"></i> Exporter CSV
            </button>
            <button class="btn btn-danger" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- Résumé du stock -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-boxes fa-3x text-primary mb-2"></i>
                    <h6>Valeur du stock (achat)</h6>
                    <div class="stock-value"><?php echo number_format($stockValue['cout_achat'], 0, ',', ' '); ?> G</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tag fa-3x text-success mb-2"></i>
                    <h6>Valeur du stock (vente)</h6>
                    <div class="stock-value"><?php echo number_format($stockValue['valeur_vente'], 0, ',', ' '); ?> G</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-cubes fa-3x text-info mb-2"></i>
                    <h6>Quantité totale</h6>
                    <div class="stock-value"><?php echo number_format($stockValue['total_quantite'], 0, ',', ' '); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock par catégorie -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Stock par catégorie</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($stockByCategory as $cat): ?>
                <div class="col-md-4 mb-3">
                    <div class="card category-card">
                        <div class="card-body">
                            <h6><i class="fas fa-folder"></i> <?php echo htmlspecialchars($cat['nom_categorie']); ?></h6>
                            <p class="mb-1">📦 Produits: <?php echo $cat['nb_produits']; ?></p>
                            <p class="mb-1">📊 Quantité: <?php echo number_format($cat['total_stock'], 0, ',', ' '); ?></p>
                            <p class="mb-0">💰 Valeur: <?php echo number_format($cat['valeur_stock'], 0, ',', ' '); ?> G</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Alertes stock bas -->
    <?php if (!empty($lowStockProducts)): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Alertes stock bas (<?php echo count($lowStockProducts); ?> produits)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code-barres</th>
                            <th>Produit</th>
                            <th>Stock actuel</th>
                            <th>Stock min</th>
                            <th>Emplacement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lowStockProducts as $product): ?>
                        <tr class="low-stock">
                            <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong></td>
                            <td class="text-danger fw-bold"><?php echo $product['stock_actuel']; ?></td>
                            <td><?php echo $product['stock_minimum']; ?></td>
                            <td><?php echo htmlspecialchars($product['emplacement'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste complète des produits -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Liste complète des produits</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="stockTable">
                    <thead class="table-light">
                        <tr>
                            <th>Code-barres</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Stock</th>
                            <th>Prix vente</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allProducts as $product): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($product['code_barre']); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['nom_categorie'] ?? '-'); ?></td>
                            <td class="<?php echo $product['stock_actuel'] <= $product['stock_minimum'] ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo $product['stock_actuel']; ?>
                            </td>
                            <td><?php echo number_format($product['prix_vente_ttc'], 0, ',', ' '); ?> G</td>
                            <td><?php echo number_format($product['stock_actuel'] * $product['prix_vente_ttc'], 0, ',', ' '); ?> G</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportCSV() {
    window.location.href = '<?php echo \BASE_PATH; ?>/index.php?action=reports_export&type=stock';
}
</script>