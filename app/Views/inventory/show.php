<?php
// Vue détails d'un inventaire
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> 
                        Inventaire : <?php echo htmlspecialchars($inventory['numero_inventaire']); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Informations générales -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>Magasin</label>
                                <p><?php echo htmlspecialchars($inventory['nom_magasin']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <label>Type</label>
                                <p>
                                    <?php 
                                    $typeLabels = [
                                        'complet' => 'Complet',
                                        'cyclique' => 'Cyclique',
                                        'cible' => 'Ciblé'
                                    ];
                                    echo $typeLabels[$inventory['type_inventaire']] ?? $inventory['type_inventaire'];
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <label>Statut</label>
                                <p>
                                    <?php
                                    $statusClass = [
                                        'planifie' => 'secondary',
                                        'en_cours' => 'warning',
                                        'valide' => 'success',
                                        'annule' => 'danger'
                                    ];
                                    $statusText = [
                                        'planifie' => 'Planifié',
                                        'en_cours' => 'En cours',
                                        'valide' => 'Validé',
                                        'annule' => 'Annulé'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass[$inventory['statut']]; ?>">
                                        <?php echo $statusText[$inventory['statut']]; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <label>Date début</label>
                                <p><?php echo date('d/m/Y H:i', strtotime($inventory['date_debut'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>Date fin</label>
                                <p><?php echo $inventory['date_fin'] ? date('d/m/Y H:i', strtotime($inventory['date_fin'])) : 'En cours'; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progression -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Progression du comptage</h6>
                                    <div class="progress mb-2" style="height: 30px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                             style="width: <?php echo $inventory['stats']['progress']; ?>%">
                                            <?php echo $inventory['stats']['progress']; ?>%
                                        </div>
                                    </div>
                                    <p class="mb-0">
                                        Produits comptés : <?php echo $inventory['stats']['counted_products']; ?> / 
                                        <?php echo $inventory['stats']['total_products']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Onglets -->
                    <ul class="nav nav-tabs" id="inventoryTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                Tous les produits (<?php echo count($inventory['details']); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="discrepancies-tab" data-bs-toggle="tab" data-bs-target="#discrepancies" type="button" role="tab">
                                Écarts (<?php echo count($discrepancies); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="uncounted-tab" data-bs-toggle="tab" data-bs-target="#uncounted" type="button" role="tab">
                                Non comptés (<?php echo count($uncounted); ?>)
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-3">
                        <!-- Tous les produits -->
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Code barre</th>
                                            <th>Emplacement</th>
                                            <th>Stock théorique</th>
                                            <th>Quantité comptée</th>
                                            <th>Écart</th>
                                            <th>Date comptage</th>
                                            <th>Compté par</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($inventory['details'] as $detail): ?>
                                            <tr class="<?php echo $detail['quantite_comptee'] != $detail['quantite_theorique'] ? 'table-warning' : ''; ?>">
                                                <td><?php echo htmlspecialchars($detail['nom_produit']); ?></td>
                                                <td><?php echo htmlspecialchars($detail['code_barre']); ?></td>
                                                <td><?php echo htmlspecialchars($detail['emplacement']); ?></td>
                                                <td class="text-end"><?php echo number_format($detail['quantite_theorique']); ?></td>
                                                <td class="text-end">
                                                    <?php echo $detail['quantite_comptee'] > 0 ? number_format($detail['quantite_comptee']) : '<span class="text-muted">Non compté</span>'; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php
                                                    $ecart = $detail['quantite_comptee'] - $detail['quantite_theorique'];
                                                    if ($ecart > 0) {
                                                        echo '<span class="text-success">+' . number_format($ecart) . '</span>';
                                                    } elseif ($ecart < 0) {
                                                        echo '<span class="text-danger">' . number_format($ecart) . '</span>';
                                                    } else {
                                                        echo '<span class="text-muted">0</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $detail['date_comptage'] ? date('d/m/Y H:i', strtotime($detail['date_comptage'])) : '-'; ?></td>
                                                <td><?php echo $detail['id_user_comptage'] ?? '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Écarts -->
                        <div class="tab-pane fade" id="discrepancies" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Code barre</th>
                                            <th>Stock théorique</th>
                                            <th>Quantité comptée</th>
                                            <th>Écart</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($discrepancies as $disc): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($disc['nom_produit']); ?></td>
                                                <td><?php echo htmlspecialchars($disc['code_barre']); ?></td>
                                                <td class="text-end"><?php echo number_format($disc['quantite_theorique']); ?></td>
                                                <td class="text-end"><?php echo number_format($disc['quantite_comptee']); ?></td>
                                                <td class="text-end">
                                                    <?php
                                                    if ($disc['ecart'] > 0) {
                                                        echo '<span class="text-success">+' . number_format($disc['ecart']) . '</span>';
                                                    } else {
                                                        echo '<span class="text-danger">' . number_format($disc['ecart']) . '</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $disc['type_ecart'] == 'Surplus' ? 'success' : 'danger'; ?>">
                                                        <?php echo $disc['type_ecart']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($discrepancies)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Aucun écart détecté</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Non comptés -->
                        <div class="tab-pane fade" id="uncounted" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Code barre</th>
                                            <th>Emplacement</th>
                                            <th>Stock théorique</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($uncounted as $uc): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($uc['nom_produit']); ?></td>
                                                <td><?php echo htmlspecialchars($uc['code_barre']); ?></td>
                                                <td><?php echo htmlspecialchars($uc['emplacement']); ?></td>
                                                <td class="text-end"><?php echo number_format($uc['quantite_theorique']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($uncounted)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Tous les produits ont été comptés</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=inventory" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-box label {
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
    display: block;
}
.info-box p {
    font-size: 1.1em;
    margin-bottom: 0;
}
</style>