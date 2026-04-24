<?php
$title = 'Détails client - ' . $client['nom'] . ' ' . ($client['prenom'] ?? '');
include dirname(__DIR__) . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-circle"></i> Détails client</h2>
                <div>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=client_edit&id=<?php echo $client['id_client']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=pos&client_id=<?php echo $client['id_client']; ?>" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Nouvelle vente
                    </a>
                    <a href="<?php echo \BASE_PATH; ?>/index.php?action=clients" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations client -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-id-card"></i> Informations</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                        <h4 class="mt-2"><?php echo htmlspecialchars($client['nom'] . ' ' . ($client['prenom'] ?? '')); ?></h4>
                        <span class="badge bg-<?php 
                            echo $client['categorie_client'] == 'platine' ? 'danger' : 
                                ($client['categorie_client'] == 'or' ? 'warning' : 
                                ($client['categorie_client'] == 'argent' ? 'info' : 'secondary')); 
                        ?>">
                            <?php echo ucfirst($client['categorie_client']); ?>
                            <?php if ($client['remise_automatique'] > 0): ?>
                                (<?php echo $client['remise_automatique']; ?>% remise)
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <table class="table table-sm">
                        <tr>
                            <th>Code client</th>
                            <td><code><?php echo htmlspecialchars($client['code_client']); ?></code></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($client['telephone'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($client['email'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Adresse</th>
                            <td><?php echo nl2br(htmlspecialchars($client['adresse'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Plaque</th>
                            <td><strong><?php echo htmlspecialchars($client['plaque_immatriculation'] ?? '-'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td><?php echo ucfirst($client['type_client'] ?? 'particulier'); ?></td>
                        </tr>
                        <tr>
                            <th>Date inscription</th>
                            <td><?php echo date('d/m/Y', strtotime($client['date_inscription'])); ?></td>
                        </tr>
                        <tr>
                            <th>Dernier achat</th>
                            <td><?php echo $client['date_dernier_achat'] ? date('d/m/Y', strtotime($client['date_dernier_achat'])) : '-'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Statistiques fidélité -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Programme de fidélité</h5>
                </div>
                <div class="card-body text-center">
                    <div class="display-1 fw-bold text-warning"><?php echo number_format($client['points_fidelite'], 0, ',', ' '); ?></div>
                    <p>Points de fidélité</p>
                    
                    <div class="progress mb-3" style="height: 10px;">
                        <?php 
                        $nextLevel = 0;
                        $currentPoints = $client['points_fidelite'];
                        if ($currentPoints < 1000) $nextLevel = 1000;
                        elseif ($currentPoints < 5000) $nextLevel = 5000;
                        elseif ($currentPoints < 10000) $nextLevel = 10000;
                        $progress = $nextLevel > 0 ? ($currentPoints / $nextLevel) * 100 : 100;
                        ?>
                        <div class="progress-bar bg-warning" style="width: <?php echo min(100, $progress); ?>%"></div>
                    </div>
                    
                    <?php if ($nextLevel > 0): ?>
                        <p class="text-muted">Encore <?php echo number_format($nextLevel - $currentPoints, 0, ',', ' '); ?> points pour passer au niveau supérieur</p>
                    <?php else: ?>
                        <p class="text-success">🏆 Niveau maximum atteint !</p>
                    <?php endif; ?>
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <div class="small text-muted">Total achats</div>
                            <div class="fw-bold"><?php echo number_format($client['total_achats'] ?? 0, 0, ',', ' '); ?> G</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Nombre d'achats</div>
                            <div class="fw-bold"><?php echo $client['nb_achats'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" action="<?php echo \BASE_PATH; ?>/index.php?action=client_add_points" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?php echo $client['id_client']; ?>">
                        <input type="number" name="points" class="form-control" placeholder="Points à ajouter" min="1">
                        <button type="submit" class="btn btn-sm btn-warning">Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Véhicule -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-car"></i> Véhicule</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-car-side fa-3x text-info mb-3"></i>
                    <?php if (!empty($client['plaque_immatriculation'])): ?>
                        <h4><?php echo htmlspecialchars($client['plaque_immatriculation']); ?></h4>
                        <?php if ($client['id_modele_vehicule']): ?>
                            <p class="text-muted">Modèle enregistré</p>
                        <?php else: ?>
                            <p class="text-muted">Véhicule non renseigné</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">Aucun véhicule renseigné</p>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-info mt-2" onclick="alert('Fonctionnalité à venir')">
                        <i class="fas fa-edit"></i> Modifier véhicule
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Historique des achats -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Historique des achats</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facture</th>
                                    <th>Articles</th>
                                    <th>Total TTC</th>
                                    <th>Remise</th>
                                    <th>Points gagnés</th>
                                    <th>Caissier</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($historique)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-shopping-cart fa-3x mb-3"></i><br>
                                        Aucun historique d'achat
                                    </div>
                                </div>
                                <?php else: ?>
                                <?php foreach ($historique as $achat): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($achat['date_vente'])); ?></div>
                                    <td><strong><?php echo htmlspecialchars($achat['numero_facture']); ?></strong></div>
                                    <td><?php echo $achat['nb_articles']; ?> article(s)</div>
                                    <td><strong><?php echo number_format($achat['montant_total_ttc'], 0, ',', ' '); ?> G</strong></div>
                                    <td><?php echo $achat['montant_remise'] > 0 ? '-' . number_format($achat['montant_remise'], 0, ',', ' ') . ' G' : '-'; ?></div>
                                    <td><?php echo $achat['points_gagnes'] ?? floor($achat['montant_total_ttc'] / 100); ?> points</div>
                                    <td><?php echo htmlspecialchars($achat['caissier_nom'] . ' ' . ($achat['caissier_prenom'] ?? '')); ?></div>
                                    <td>
                                        <a href="<?php echo \BASE_PATH; ?>/index.php?action=sales_invoice&id=<?php echo $achat['id_vente']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                     </div>
                                </tr>
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