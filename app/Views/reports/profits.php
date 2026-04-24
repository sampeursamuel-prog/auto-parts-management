<?php
// Rapport des profits
?>
<style>
    .profit-positive {
        color: #10b981;
        font-weight: bold;
    }
    .profit-negative {
        color: #ef4444;
        font-weight: bold;
    }
    .summary-box {
        background: linear-gradient(135deg, #2c3e50, #1a252f);
        color: white;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-pie"></i> Rapport des profits</h2>
        <div>
            <button class="btn btn-success" onclick="exportCSV()">
                <i class="fas fa-file-excel"></i> Exporter CSV
            </button>
            <button class="btn btn-danger" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filter-bar">
        <form method="GET" action="<?php echo \BASE_PATH; ?>/index.php?action=reports_profits" class="row">
            <div class="col-md-4">
                <label class="form-label">Date début</label>
                <input type="date" name="date_start" class="form-control" value="<?php echo $dateStart; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_end" class="form-control" value="<?php echo $dateEnd; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Top produits rentables -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-crown"></i> Top 10 produits les plus rentables</h5>
        </div>
        <div class="card-body">
            <?php if (empty($topProducts)): ?>
                <div class="text-center py-4">Aucune donnée</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Produit</th>
                                <th>Quantité</th>
                                <th>Marge totale</th>
                                <th>Marge %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $index => $product): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($product['nom_produit']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($product['code_barre']); ?></small>
                                </td>
                                <td><?php echo $product['quantite_vendue']; ?></td>
                                <td class="profit-positive"><?php echo number_format($product['marge_totale'], 0, ',', ' '); ?> G</td>
                                <td class="profit-positive"><?php echo round($product['marge_pct'], 1); ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Détail des profits par produit -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Détail des profits par produit</h5>
        </div>
        <div class="card-body">
            <?php if (empty($profits)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                    <p>Aucune donnée trouvée</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th>Code-barres</th>
                                <th>Quantité</th>
                                <th>CA HT</th>
                                <th>Coût achat</th>
                                <th>Marge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalCA = 0;
                            $totalCout = 0;
                            $totalMarge = 0;
                            foreach ($profits as $profit): 
                                $totalCA += $profit['chiffre_affaires_ht'];
                                $totalCout += $profit['cout_achat'];
                                $totalMarge += $profit['marge'];
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($profit['nom_produit']); ?></strong></td>
                                <td><?php echo htmlspecialchars($profit['code_barre']); ?></td>
                                <td><?php echo $profit['quantite_vendue']; ?></td>
                                <td><?php echo number_format($profit['chiffre_affaires_ht'], 0, ',', ' '); ?> G</td>
                                <td><?php echo number_format($profit['cout_achat'], 0, ',', ' '); ?> G</td>
                                <td class="profit-positive"><?php echo number_format($profit['marge'], 0, ',', ' '); ?> G</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th><?php echo number_format($totalCA, 0, ',', ' '); ?> G</th>
                                <th><?php echo number_format($totalCout, 0, ',', ' '); ?> G</th>
                                <th class="profit-positive"><?php echo number_format($totalMarge, 0, ',', ' '); ?> G</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportCSV() {
    alert('Export CSV en développement');
}
</script>